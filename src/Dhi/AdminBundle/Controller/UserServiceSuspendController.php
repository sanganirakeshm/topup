<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\User;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\AdminBundle\Entity\UserSuspendHistory;
use Doctrine\ORM\EntityRepository;

class UserServiceSuspendController extends Controller {

    public function doServiceSuspendAction(Request $request, $id) {
        $userId = $id;

        $em = $this->getDoctrine()->getManager();
        $activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($userId, 'all');
        $admin = $this->get('security.context')->getToken()->getUser();
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        $isServiceDisable = 0;
        $isSelevisionDisable = 0;
        $isAradialDisable = 0;
        if (count($activeServices) > 0) {

            foreach ($activeServices as $activeService) {

                //data to be saved
                $data = array();
                $data['activationDate'] = $activeService->getActivationDate();
                $data['expirationDate'] = $activeService->getExpiryDate();
                $data['packageId'] = $activeService->getPackageId();
                $data['service'] = $activeService->getService();
                $data['user'] = $activeService->getUser()->getId();
                $data['userServiceId'] = $activeService->getId();
                $data['admin'] = $admin->getUsername();

                // check selevision de-active
                if ($activeService->getService()->getName() == 'IPTV') {
                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUserName();
                    $deactiveCustomerResponse = $this->get('selevisionService')->callWSAction('deactivateCustomer', $wsParam);
                    if ($deactiveCustomerResponse['status'] == 1) {
                        $isServiceDisable = 1;
                        $isSelevisionDisable = 1;
                    } else {
                        $isServiceDisable = 0;
                        // unsusnpend from aradial
                        if ($isAradialDisable == 1) {
                            $extraParam = array();
                            $extraParam['db_$N$Users.Offer'] = $activeService->getPackageId();
                            $extraParam['db_$D$Users.StartDate'] = $activeService->getActivationDate()->format('m/d/Y H:i:s');
                            $extraParam['db_$D$Users.UserExpiryDate'] = $activeService->getExpiryDate()->format('m/d/Y H:i:s');
                            $aradialResponse = $this->container->get('aradial')->createUser($activeService->getUser(), $extraParam);

                            if ($aradialResponse != 1) {
                                $this->get('session')->getFlashBag()->add('failure', " UserId: " . $user->getId() . " fail to re-create user in aradial , Please contact admin.");
                            }else{
                                $isAradialDisable = 0;
                            }
                        }
                    }
                    //suspend use in  the aradial
                } else if ($activeService->getService()->getName() == 'ISP') {

                    $cancelingUsrParam = array();
                    $cancelingUsrParam['Page'] = "UserEdit";
                    $cancelingUsrParam['UserId'] = $activeService->getUser()->getUserName();
                    $cancelingUsrParam['ConfirmDelete'] = 1;

                    $cancelUsrResponse = $this->get('aradial')->callWSAction('cancelUser', $cancelingUsrParam);
                    if ($cancelUsrResponse['status'] != '0') {
                        $isServiceDisable = 1;
                        $isAradialDisable = 1;
                    } else {
                        $isServiceDisable = 0;

                        if ($isSelevisionDisable == 1) {
                            //reactivate the user in selevision
                            $wsParam = array();
                            $wsParam['cuLogin'] = $user->getUserName();
                            $deactiveCustomerResponse = $this->get('selevisionService')->callWSAction('deactivateCustomer', $wsParam);
                            if ($deactiveCustomerResponse['status'] == 1) {
                                $isSelevisionDisable = 0;
                            } else {
                                $this->get('session')->getFlashBag()->add('failure', " UserId: " . $user->getId() . " fail to re-deactivateCustomer user in selevision , Please contact admin.");
                            }
                        }
                    }
                }

                if ($isServiceDisable == 1) {
                    // insert into userSuspendedHistory table
                    $userSuspendHistory = new UserSuspendHistory();
                    $userSuspendHistory->setUser($user)
                            ->setService($data['service'])
                            ->setUserService($activeService)
                            ->setPackageId($data['packageId'])
                            ->setApiStatus($isServiceDisable)
                            ->setStatus(0)
                            ->setActivationDate($data['activationDate'])
                            ->setExpiryDate($data['expirationDate'])
                            ->setAdmin($data['admin']);
                    $em->persist($userSuspendHistory);
                    $em->flush();
                    $userSuspendHistoryId = $userSuspendHistory->getId();
                    if (!$userSuspendHistoryId) {
                        $isServiceDisable = 0;

                    } else {
                        // update user service table
                        $objUserService = $em->getRepository('DhiUserBundle:UserService')->find($activeService->getId());
                        $objUserService->setStatus(0);
                        $objUserService->setSuspendedStatus(1);
                        $em->persist($objUserService);
                        $em->flush();
                    }
                }
            }
            if($isServiceDisable == 1){
                // update user table
                $user->setIsSuspended(1);
                $em->persist($user);
                $em->flush();
            }
        } else {
            // update user table
            $user->setIsSuspended(1);
            $em->persist($user);
            $em->flush();
            $isServiceDisable = 1;
        }

        $logDescription = '';
        if ($isServiceDisable == 1) {
            $this->get('session')->getFlashBag()->add('success', "User: " . $user->getUsername() . " has been suspended successfully");
            $logDescription = "Admin " . $admin->getUsername() . " has suspended user " . $user->getUsername();
        } else {
            $this->get('session')->getFlashBag()->add('failure', "Could not able to suspend the User: " . $user->getUsername() . ", Please try again later.");
            $logDescription = "Admin " . $admin->getUsername() . " has fail to suspend user " . $user->getUsername();
        }
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['user'] = $user;
        $activityLog['activity'] = 'Suspend user';
        $activityLog['description'] = $logDescription;
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
    }

    public function doServiceUnsuspendAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);

        $suspendedServices = $em->getRepository('DhiAdminBundle:UserSuspendHistory')->getSuspendedServices($id);
        $isServiceActive = 0;
        $isAradialActive = 0;
        $isSelevisionActive = 0;
        $currentDate = new \DateTime();
        if (count($suspendedServices) > 0) {
            foreach ($suspendedServices as $suspendedService) {

                $objUserService = $suspendedService->getUserService();
                $data = array();
                $data['validity'] = $objUserService->getValidity();
                $data['createdAt'] = $suspendedService->getCreatedAt();
                $data['expirationDate'] = $objUserService->getExpiryDate();
                $validityType = $objUserService->getServicePurchase()->getValidityType();
                
                // modify the changes
                $interval = $data['createdAt']->diff($currentDate);
                // $seconds = date_create('@0')->add($interval)->getTimestamp();
                $newdatext = clone $data['expirationDate'];
                
                if ($validityType == "HOURS") {
                    $extraTime = ($interval->days * 24) + $interval->h;

                } else {
                    $validityType = 'DAYS';
                    $extraTime    = $interval->days;
                }

                $newdatext->modify('+' . $extraTime . ' ' . $validityType);
                $data['newValidity'] = $data['validity'] + $extraTime;

                // check selevision active
                if ($objUserService->getService()->getName() == 'IPTV') {
                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUserName();
                    $deactiveCustomerResponse = $this->get('selevisionService')->callWSAction('reactivateCustomer', $wsParam);
                    if ($deactiveCustomerResponse['status'] == 1) {
                        $isServiceActive = 1;
                        $isSelevisionActive = 1;
                    } else {
                        // if selesion  user fail to reactivate  and aradial already create user  then cancel from aradial
                        $isServiceActive = 0;
                        if ($isAradialActive == 1) {
                            $cancelingUsrParam = array();
                            $cancelingUsrParam['Page'] = "UserEdit";
                            $cancelingUsrParam['UserId'] = $suspendedService->getUser()->getUserName();
                            $cancelingUsrParam['ConfirmDelete'] = 1;
                            $cancelUsrResponse = $this->get('aradial')->callWSAction('cancelUser', $cancelingUsrParam);
                            if ($cancelUsrResponse['status'] != '0') {
                                $isAradialActive = 0;
                            } else {
                                $this->get('session')->getFlashBag()->add('failure', " UserId: " . $user->getId() . " fail to re-cancelUser  in aradial , Please contact admin.");
                            }
                        }
                    }
                    // check selevision active
                } else if ($objUserService->getService()->getName() == 'ISP') {
                    $extraParam                               = array();
                    $extraParam['db_$N$Users.Offer']          = $suspendedService->getPackageId();
                    $extraParam['db_$D$Users.StartDate']      = $suspendedService->getActivationDate()->format('m/d/Y H:i:s');
                    $extraParam['db_$D$Users.UserExpiryDate'] = $newdatext->format('m/d/Y H:i:s');
                    $aradialResponse = $this->container->get('aradial')->createUser($suspendedService->getUser(), $extraParam);

                    if ($aradialResponse == 1) {

                        if ($objUserService->getIsPlanActive() == 1) {
                            $activationDate = $suspendedService->getActivationDate();
                            $expiryDate     = $newdatext;
                            $isUserUpdated  = $this->container->get('aradial')->updateUserIsp($user, $activationDate, $expiryDate);
                            if ($isUserUpdated) {
                                $objUserService->setSuspendedStatus(3);
                                $objUserService->setIsPlanActive(1);
                            } else {
                                $objUserService->setSuspendedStatus(2);
                                $objUserService->setIsPlanActive(0);
                            }
                        }else{
                            $extraTime = 0;
                            $newdatext = $objUserService->getExpiryDate();
                            $data['newValidity'] = $objUserService->getValidity();
                            $objUserService->setSuspendedStatus(3);
                        }

                        $isServiceActive = 1;
                        $isAradialActive = 1;

                    } else {
                        // if aradial create user fail and selevison already activate then deactivate from selevision
                        $isServiceActive = 0;
                        if ($isSelevisionActive == 1) {
                            $wsParam = array();
                            $wsParam['cuLogin'] = $user->getUserName();
                            $deactiveCustomerResponse = $this->get('selevisionService')->callWSAction('deactivateCustomer', $wsParam);
                            if ($deactiveCustomerResponse['status'] == 1) {
                                $isSelevisionActive = 0;
                            } else {
                                $this->get('session')->getFlashBag()->add('failure', " UserId: " . $user->getId() . " fail to re-deactivacustomer in selevision, Please contact admin.");
                            }
                        }
                    }
                }

                if ($isServiceActive == 1) {
                    // update user service table with new expire date and validity
                    $datetemp = new DateTime();
                    $objUserService->setExpiryDate($newdatext);
                    $objUserService->setValidity($data['newValidity']);
                    $objUserService->setStatus(1);
                    $em->persist($objUserService);
                    $em->flush();
                    // update user suspended history
                    $suspendedService->setStatus(1);
                    $suspendedService->setSuspendValidity($extraTime);
                    $em->persist($suspendedService);
                    $em->flush();
                }
            }
            if ($isServiceActive == 1) {
                // update user table
                $user->setIsSuspended(0);
                $em->persist($user);
                $em->flush();
            }
        } else {
            // update user table
            $user->setIsSuspended(0);
            $em->persist($user);
            $em->flush();
            $isServiceActive = 1;
        }
        $logDescription = '';
        if ($isServiceActive == 1) {
            $this->get('session')->getFlashBag()->add('success', " User: " . $user->getUsername() . " unsuspended successfully");
            $logDescription = "Admin " . $admin->getUsername() . " has unsuspended user " . $user->getUsername();
        } else {
            $this->get('session')->getFlashBag()->add('failure', "Could not able to unsuspend the User: " . $user->getUsername() . ", Please try again later.");
            $logDescription = "Admin " . $admin->getUsername() . " has fail to unsuspend user " . $user->getUsername();
        }
        // unsuspend log
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['user'] = $user;
        $activityLog['activity'] = 'Unsuspend user';
        $activityLog['description'] = $logDescription;
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
    }
}