<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Form\Type\UserFormType;
use Dhi\AdminBundle\Form\Type\UserSettingFormType;
use Dhi\AdminBundle\Form\Type\ChangePasswordFormType;
use Dhi\AdminBundle\Form\Type\LoginLogSearchFormType;
use Dhi\AdminBundle\Form\Type\UserCompensationFormType;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\UserBundle\Entity\UserSetting;
use Dhi\UserBundle\Entity\Compensation;
use Dhi\UserBundle\Entity\ServiceLocation;
use Dhi\UserBundle\Entity\CustomerCompensationLog;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\UserBundle\Entity\UserCredit;
use Dhi\AdminBundle\Entity\InformationLog;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Dhi\UserBundle\Entity\CompensationUserService;

class UserController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('user_list') || $this->get('admin_permission')->checkPermission('user_create') || $this->get('admin_permission')->checkPermission('user_delete') || $this->get('admin_permission')->checkPermission('user_update') || $this->get('admin_permission')->checkPermission('view_user') || $this->get('admin_permission')->checkPermission('user_credit_add') )) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $admin = $this->get('security.context')->getToken()->getUser();
        $this->get('session')->remove('serviceLocationSelection');
        $objCredits = $em->getRepository('DhiAdminBundle:Credit')->findBy(array('isDeleted' => '0'), array('amount' => 'ASC'));

        // Service locaiton
        $serviceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        $arrServiceLocations = array();

        // if ($admin->getGroup() != 'Super Admin') {
        //     $lo = $admin->getServiceLocations();
        //     foreach ($lo as $key => $value) {
        //         $arrServiceLocations[] = $value->getName();
        //     }
        //     sort($arrServiceLocations);
        // } else {
            foreach ($serviceLocations as $key => $serviceLocation) {
                $arrServiceLocations[] = $serviceLocation['name'];
            }
        // }

        return $this->render('DhiAdminBundle:User:index.html.twig', array(
                    'admin' => $admin,
                    'credits' => $objCredits,
                    "serviceLocations" => $arrServiceLocations
        ));
    }

    public function userListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $userColumns = array('Id', 'Name', 'Username', 'Email', 'ActiveServices', 'ServiceLocation', 'ActivationDate', 'ExpiryDate', 'ServiceSettings');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($userColumns);

        $firstname = trim($request->get('firstname'));
        $lastname = trim($request->get('lastname'));

        $isSuspended = $request->get("isSuspended");
        if($isSuspended == 'true'){
            $gridData['search_data']['isSuspended'] = true;
            $gridData['SearchType'] = 'ANDLIKE';
            $this->get('session')->set('isSuspended', true);
        }else{
            $this->get('session')->remove('isSuspended');
        }
        
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'u.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'u.id';
            }

            if ($gridData['order_by'] == 'Name') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'Username') {

                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'Email') {

                $orderBy = 'u.email';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $country = '';
        // if ($admin->getGroup() != 'Super Admin') {
        //     $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
        //     $country = empty($country) ? '0' : $country;
        // }
        $data = $em->getRepository('DhiUserBundle:User')->getUserGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $country, $firstname, $lastname);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );

                foreach ($data['result'] AS $resultRow) {
                    $flagDelete = 1;
                    $flagSetting = 0;

                    $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow->getId())) . '">' . $resultRow->getUsername() . '</a>';

                    $count = 1;
                    $activeServices = $resultRow->getActiveServices();
										$servicesCount = count($activeServices);

                    $serviceName = '';
										if ($activeServices) {
												foreach ($activeServices as $service) {
														if ($service['name'] != "TVOD") {
                                if ($count == $servicesCount) {

                                    $serviceName .= '<span class="btn btn-success btn-sm service">' . $service['name'] . '</span>';
                                } else {

                                    $serviceName .= '<span class="btn btn-success btn-sm service">' . $service['name'] . '</span>';
                                }

                                $count++;
                            }
                        }
                    }

                    $activationDate = '';
                    $expiryDate = '';
                    $purchasedServiceLocation = 'N/A';
                    $userServices = $resultRow->getUserServices() ? $resultRow->getUserServices() : false;

					if($userServices) {
						$counttext = 0;
                        $pretext = array();
                        $activationArray = array();
                        $expiryArray = array();

						foreach ($userServices as $user_service) {
                            $purchasedServiceLocation = $user_service->getServicePurchase()->getServiceLocationId() ? $user_service->getServicePurchase()->getServiceLocationId()->getName() : 'N/A';
                            if ($user_service->getService()->getName() == 'TVOD') {
                                continue;
                            }

                            if ($user_service->getIsAddon() == 0) {
                                if ($user_service->getStatus() == 1) {
                                    $counttext++;
                                    $pretext[$counttext] = $user_service->getService()->getName();
									$activationArray[$counttext] =  $user_service->getActivationDate()->format('m/d/Y H:i:s') ;
                                	if ($user_service->getIsPlanActive() == 0 && $user_service->getServicePurchase()->getPurchaseType() == ''){
                                        $expiryArray[$counttext] = 'Not Logged in';
                                    } else {
                                        $expiryArray[$counttext] = $user_service->getExpiryDate()->format('m/d/Y H:i:s');
                                    }
                                }
                            }
                        }

                        foreach ($pretext as $key => $value) {
                            if ($counttext > 1) {
                                $activationDate .= $activationArray[$key] . ' (' . $pretext[$key] . ')<br>';
                                $expiryDate .= $expiryArray[$key] . ' (' . $pretext[$key] . ')<br>';
                            } else {
                                $activationDate .= $activationArray[$key] . '<br>';
                                $expiryDate .= $expiryArray[$key] . '<br>';
                            }
                        }

                        $scount = 1;
                        $serviceSettings = $resultRow->getServiceSettings();
												$settingCount = count($serviceSettings);

                        $settingName = '';

												if ($serviceSettings) {
                            $flagSetting = 1;

												foreach ($serviceSettings as $setting) {
                                if ($setting->getServiceStatus() == 'Disabled') {

                                    $settingName .= '<a href="' . $this->generateUrl('dhi_admin_user_service_status', array('userId' => $resultRow->getId(), 'serviceSettingId' => $setting->getId())) . '" title = "Reactivate' . $setting->getService()->getName() . ' for user ' . $resultRow->getUsername() . '"> Reactivate ' . $setting->getService()->getName() . '</a><br/>';
                                } else {
                                    $settingName .= '<a href="' . $this->generateUrl('dhi_admin_user_service_status', array('userId' => $resultRow->getId(), 'serviceSettingId' => $setting->getId())) . '" title = "Deactivate' . $setting->getService()->getName() . ' for user ' . $resultRow->getUsername() . '"> Deactivate ' . $setting->getService()->getName() . '</a><br/>';
                                }

                                $scount++;
                            }
                        }

                        $row = array();
                        $row[] = $resultRow->getId();
                        $row[] = $resultRow->getFirstname() . ' ' . $resultRow->getLastname();
                        $row[] = $username;
                        $row[] = $resultRow->getEmail();
						$row[] = $activeServices ? $serviceName : 'N/A';
                        $row[] = $purchasedServiceLocation;
                        //$row[] = $locationaname;
						$row[] = $activationDate ? $activationDate : 'N/A' ;
						$row[] = $expiryDate ? $expiryDate : 'N/A' ;
                        $row[] = $settingName;
                        $row[] = $resultRow->getId() . '^' . $flagDelete . '^' . $flagSetting . '^' . $resultRow->getUsername();
                        $row[] = $resultRow->getIsSuspended()  == true ? 'Suspended' : 'Un-Suspended' ;
                        $output['aaData'][] = $row;
                    }
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function newAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new user.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $ipAddress = $this->get('session')->get('ipAddress');
        $countryObj = '';

        $form = $this->createForm(new UserFormType($admin, null), new User());

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            $formValues = $request->request->get('dhi_admin_registration');

            $isFormValid = true;
            $email = $formValues['email'];
            $firstName = $formValues['firstname'];
            $lastName = $formValues['lastname'];


            if (!preg_match('/^[^\'"]*$/', $email)) {
                $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                $isFormValid = false;
            }

            if (!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)) {
                $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                $isFormValid = false;
            }

            if (!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)) {
                $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                $isFormValid = false;
            }

            if ($form->isValid() && $isFormValid) {

                $registrationArr = $request->request->get('dhi_admin_registration');
                
                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($registrationArr['email']);
                $objUsername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkUsernameEmail($registrationArr['username']);

                if ($objUser || $objUsername) {
                    $this->get('session')->getFlashBag()->add('danger', "Email Or Username Already in used!");
                    //return $this->redirect($this->generateUrl('fos_user_registration_register'),array('form'=>$formFactory->createForm()));
                    return $this->render('DhiAdminBundle:User:new.html.twig', array(
                    'form' => $form->createView()
                     ));
                }

                $objUser = $form->getData();

                if ($objUser->getUserServiceLocation()) {

                    $countryObj = $objUser->getUserServiceLocation()->getCountry();
                }
              
                $tokenGenerator = $this->get('fos_user.util.token_generator');
                $objUser->setConfirmationToken($tokenGenerator->generateToken());
                $objUser->setEncryPwd(base64_encode($registrationArr['plainPassword']['first']));
                $objUser->setGeoLocationCountry($countryObj);
                $objUser->setIpAddress($ipAddress);
                $objUser->setIpAddressLong(ip2long($ipAddress));
                $em->persist($objUser);
                $em->flush();

 
                // set audit log add user
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $objUser;
                $activityLog['activity'] = 'Add user';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new user Email: " . $objUser->getEmail() . " and Username: " . $objUser->getUsername();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                
                $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$registrationArr['userServiceLocation']));
               
                if($getdomain){
                    $subject = "Welcome " . $objUser->getName() . " to ".$getdomain->getWhiteLabel()->getCompanyName()."!";;
                    $fromemail = $getdomain->getWhiteLabel()->getFromEmail();
                    $compnayname = $getdomain->getWhiteLabel()->getCompanyName();
                    $compnaydomain = $getdomain->getWhiteLabel()->getDomain();
                    $supportpage   = $getdomain->getWhiteLabel()->getSupportpage();
                } else {
                    $subject = "Welcome " . $objUser->getName() . " to ExchangeVUE!";
                    $fromemail = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                    $compnayname     = 'ExchangeVUE';
                    $compnaydomain   = 'exchangevue.com';
                    $supportpage     = 'https://www.facebook.com/dhitelecom';
                }
               
                
                
                
                $confimationurl = $compnaydomain.'/confirm/'.$objUser->getConfirmationToken();
                if($request->isSecure()){
                    $confimationurl = 'https://'.$confimationurl;
                } else {
                   $confimationurl  = 'http://'.$confimationurl;
                }
                $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:register_email.html.twig', array(
                    'user' => $objUser,
                    'token' => $objUser->getConfirmationToken(),
                    'companyname'=>$compnayname,
                    'confirmationurl'=>$confimationurl,
                    'companydomain'=>$compnaydomain,
                    'supportpage' =>$supportpage,
                    'httpProtocol' => ($request->isSecure() ? 'https://' : 'http://')
                ));

                $resend_email_verification = \Swift_Message::newInstance()->
                                // ->setSubject("Welcome " . $objUser->getUsername() . " to ExchangeVUE!")
                                setSubject($subject)
                            ->setFrom($fromemail)
                            ->setTo($objUser->getEmail())->setBody($body->getContent())->setContentType('text/html');

                $this->container->get('mailer')->send($resend_email_verification);

                $this->get('session')->getFlashBag()->add('success', "Customer added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_user_list'));
            }
        }

        return $this->render('DhiAdminBundle:User:new.html.twig', array(
                    'form' => $form->createView()
        ));
    }

    /* END */

    /* START - Customer Delete Action */

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete user.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('DhiUserBundle:User')->find($id);

        if ($user) {

            $user->setIsDeleted(1);
            $user->setExpired(1);
            $user->setExpiresAt(new DateTime());
            $em->persist($user);
            $em->flush();

            // set audit log delete user
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Delete user';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted user " . $user->getUsername();


            $result = array('type' => 'success', 'message' => 'Customer deleted successfully!');
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Customer";

            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete customer!');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /* END */

    /* START - Customer Edit Action */

    public function editAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update user.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);

        if (!$user) {
            $this->get('session')->getFlashBag()->add('failure', "Customer does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        // SET parameters for user audit log for edit user
        $activityLog = array(
            'admin' => $admin,
            'ip' => $request->getClientIp(),
            'sessionId' => $request->getSession()->getId(),
            'url' => $request->getUri()
        );

        $email = $user->getEmail();
        $usermail = $user->getEmail();
        $roles = $user->getRoles();

        $form = $this->createForm(new UserFormType($admin, $user), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);

        $userSetting = $em->getRepository('DhiUserBundle:UserSetting')->getUserMaxSetting($id);

        if ($userSetting == null) {
            $userSetting = new UserSetting();
            $userSetting->setUser($user);
            $userSetting->setAdminId($admin->getId());
        }
        $userSettingForm = $this->createForm(new UserSettingFormType(), $userSetting);

        // set default user settings from Global settings
        if (!$userSetting->getMacAddress()) {
            $userSettingForm->get('mac_address')->setData($this->get('session')->get('mac_address'));
        }
        if (!$userSetting->getMaxDailyTransaction()) {
            $userSettingForm->get('max_daily_transaction')->setData($this->get('session')->get('max_daily_transaction'));
        }

        if ($request->getMethod() == "POST") {

            if ($request->request->has($form->getName())) {
                
                
              $getuserdata = $request->get('dhi_admin_registration');
              if($email!=$getuserdata['email']){
                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($getuserdata['email']);

                if ($objUser) {
                    $this->get('session')->getFlashBag()->add('danger', "Email Already in used!");
                    
                    return $this->render('DhiAdminBundle:User:edit.html.twig', array(
                                'form' => $form->createView(),
                                'user' => $user,
                                'admin' => $admin,
                                'role' => $roles[0],
                                'changePasswordForm' => $changePasswordForm->createView(),
                                'userSettingform' => $userSettingForm->createView(),
                                'id' => $id
                    ));
                }
              }
                $username = $user->getUsername();
                $form->handleRequest($request);
                $formValues = $request->request->get('dhi_admin_registration');

                $isFormValid = true;
                $email = $formValues['email'];
                $firstName = $formValues['firstname'];
                $lastName = $formValues['lastname'];


                if (!preg_match('/^[^\'"]*$/', $email)) {
                    $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                    $isFormValid = false;
                }

                if (!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)) {
                    $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                    $isFormValid = false;
                }

                if (!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)) {
                    $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                    $isFormValid = false;
                }

                if ($form->isValid() && $isFormValid) {

                    $note = $request->request->get('profile-note');

                    if ($note) {

                        // set audit log edit user
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'Customer update information';
                        $activityLog['description'] = $note;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        //////////////////////////

                        if ($getuserdata['email'] != $usermail) {
                            $userManager = $this->get('fos_user.user_manager');
                            $tokenGenerator = $this->get('fos_user.util.token_generator');
                            $token = $tokenGenerator->generateToken();
                            $user->setConfirmationToken($token);
                            $user->setIsEmailVerified(0);
                            $user->setEmailVerificationDate(new DateTime());

                            $userManager->updateUser($user);
                            $servicelocationId =  $user->getUserServiceLocation()->getId();
                            $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$servicelocationId));

                            if($getdomain){
                                $subject = "Welcome " . $user->getUsername() . " to ".$getdomain->getWhiteLabel()->getCompanyName()."!";;
                                $fromemail = $getdomain->getWhiteLabel()->getFromEmail();
                                $compnayname = $getdomain->getWhiteLabel()->getCompanyName();
                                $compnaydomain = $getdomain->getWhiteLabel()->getDomain();
                                $supportpage   = $getdomain->getWhiteLabel()->getSupportpage();
                            } else {
                                $subject = "Welcome " . $user->getUsername() . " to ExchangeVUE!";
                                $fromemail = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                                $compnayname     = 'ExchangeVUE';
                                $compnaydomain   = 'exchangevue.com';
                                $supportpage     = 'https://www.facebook.com/dhitelecom';
                            }

                            $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:resend_email_verification.html.twig', array(
                                'user' => $user,
                                'token' => $token,
                                'companyname'=>$compnayname
                            ));

                            $resend_email_verification = \Swift_Message::newInstance()
                                                         ->setSubject($subject)
                                                         ->setFrom($fromemail)
                                                         ->setTo($user->getEmail())
                                                         ->setBody($body->getContent())
                                                         ->setContentType('text/html');

                            $this->container->get('mailer')->send($resend_email_verification);
                        }
                        $objUser = $form->getData();
                        $em->persist($objUser);
                        $em->flush();

                        // get account details for selevisions account
                        $accountArr = $request->request->get('dhi_admin_registration');

                        ############################ START Selevision API ######################################
                        // check selevision api to check whether customer exist in system
                        $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

                        // if customer exists, update the details
                        if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

                            // call selevisions service to update account
                            $wsParam = array();
                            $wsParam['cuLogin'] = $objUser->getUsername();
                            $wsParam['cuNewFirstName'] = $accountArr['firstname'];
                            $wsParam['cuNewLastName'] = $accountArr['lastname'];
                            $wsParam['cuNewEmail'] = $user->getEmail();

                            $wsResponse = $this->get('selevisionService')->callWSAction('updateCustomer', $wsParam);
                        } else {

                            if ($selevisionUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ProfileUpdate', 'Selevision');
                            }
                        }
                        ########################## END Selevision API #################################
                        ############################# START Aradial API #################################
                        //Update profile detail in aradial
                        $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());

                        if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1) {

                            $wsParam = array();
                            $wsParam['Page'] = "UserEdit";
                            $wsParam['Modify'] = 1;
                            $wsParam['UserID'] = $user->getUsername();
                            $wsParam['db_UserDetails.FirstName'] = $accountArr['firstname'];
                            $wsParam['db_UserDetails.LastName'] = $accountArr['lastname'];
                            $wsParam['db_UserDetails.Address1'] = $accountArr['address'];
                            $wsParam['db_UserDetails.City'] = $accountArr['city'];
                            $wsParam['db_$GS$UserDetails.State'] = $accountArr['state'];
                            $wsParam['db_$GS$UserDetails.Country'] = ($objUser->getCountry()) ? $objUser->getCountry()->getName() : "";
                            $wsParam['db_UserDetails.Zip'] = $accountArr['zip'];
                            $wsParam['db_UserDetails.Email'] = $user->getEmail();

                            $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
                        } else {

                            if ($aradialUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ProfileUpdate', 'Aradial');
                            }
                        }
                        ########################### END Aradial API ###################################
                        // set audit log edit user
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'Edit user';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated user " . $user->getUsername();
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $this->get('session')->getFlashBag()->add('success', "Customer updated successfully!");
                        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
                    } else {

                        $this->get('session')->getFlashBag()->add('failure', "Please enter customer information note!");
                        return $this->redirect($request->headers->get('referer'));
                    }
                }
            }

            if ($request->request->has($changePasswordForm->getName())) {

                $changePasswordForm->handleRequest($request);

                if ($changePasswordForm->isValid()) {

                    $note = $request->request->get('change-password-note');

                    if ($note) {

                        // set audit log edit user
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'Customer update information';
                        $activityLog['description'] = $note;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        //////////////////////////
                        // ====================== //

                        $changePasswordArr = $request->request->get('dhi_admin_changepassword');
                        $newPassword       = $changePasswordArr['plainPassword']['first'];
                        $userPassword      = $user->getEncryPwd();
                        $isToUpdate = true;
                        if ($changePasswordArr['plainPassword']['first'] == "" || $changePasswordArr['plainPassword']['second'] == "") {
                            $isToUpdate = false;
                        }

                        ############################### START Selevision API #################################
                        $isIPTVPwdUdated = false;
                        // Check user exits in selevision
                        $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);
                        if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1 && $isToUpdate == true) {
                            $currentPassword = $selevisionUserExist['password']; // get plain password for selevisions
                            $seleVisionCurrrentPwd = $selevisionUserExist['password']; // get plain password for selevisions

                            if (($user->getNewSelevisionUser() || $currentPassword != $newPassword)) {

                                // call selevisions service to update password
                                $wsParam = array();
                                $wsParam['cuLogin'] = $user->getUsername();
                                $wsParam['cuPwd'] = ($user->getNewSelevisionUser()) ? $seleVisionCurrrentPwd : $currentPassword;
                                $wsParam['cuNewPwd1'] = $newPassword;
                                $wsParam['cuNewPwd2'] = $newPassword;

                                $wsResPwd = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);

                                if ($wsResPwd['status'] == 1) {
                                    $isIPTVPwdUdated = true;
                                    $user->setNewSelevisionUser(0);
                                    $em->persist($user);
                                    $em->flush();
                                }else{
                                    $isToUpdate = false;
                                }
                            }else{
                                if ($currentPassword == $newPassword) {
                                    $isIPTVPwdUdated = true;
                                }
                            }
                        } else {
                            if ($selevisionUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ChangePassword', 'Selevision');
                            }
                        }
                        ############################### END Selevisoin API #########################################
                        ################################ START Aradial API #########################################
                        //Update password in aradial
                        $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());

                        if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1 && $newPassword && $isToUpdate) {

                            $wsParam = array();
                            $wsParam['Page'] = "UserEdit";
                            $wsParam['Modify'] = 1;
                            $wsParam['UserID'] = $user->getUsername();
                            $wsParam['Password'] = $newPassword;

                            $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
                            if (!empty($aradialResponse['status']) && $aradialResponse['status'] == 1) {
                                $isToUpdate = true;
                            }else{
                                $isToUpdate = false;

                                if ($isIPTVPwdUdated == true) {
                                    $wsParam = array();
                                    $wsParam['cuLogin']   = $user->getUsername();
                                    $wsParam['cuPwd']     = $newPassword;
                                    $wsParam['cuNewPwd2'] = $wsParam['cuNewPwd1'] = base64_decode($userPassword);
                                    $wsResetPassword = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);
                                    if (empty($wsResetPassword['status']) || (!empty($wsResetPassword['status']) && $wsResetPassword['status'] != 1)) {
                                        
                                        // Send Notification to admin
                                        $setting = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('service_failed_notification_email');
                                        if ($setting) {
                                            $emails = $setting->getValue();
                                            if(!empty($emails)){
                                                $arrEmails = explode(',', $emails);
                                                $servicetype = "IPTV";
                                                $type        = 'ChangePassword';
                                                if(count($arrEmails) > 0){
                                                    foreach ($arrEmails as $email){
                                                        $this->get("PackageActivation")->sendNotification($servicetype,$type,$user,$email);
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($aradialUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ChangePassword', 'Aradial');
                            }
                        }
                        ################################## END Aradial API ########################################

                        if ($isToUpdate) {
                            $userManager = $this->get('fos_user.user_manager');

                            // password strore in base64
                            $user->setEncryPwd(base64_encode($changePasswordArr['plainPassword']['first']));
                            $userManager->updateUser($user);
                            $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");

                            // set audit log edit user
                            $activityLog = array();
                            $activityLog['admin'] = $admin;
                            $activityLog['user'] = $user;
                            $activityLog['activity'] = 'Change user password';
                            $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed password for user " . $user->getUsername();
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                        }else{
                            $this->get('session')->getFlashBag()->add('failure', "Sorry! Can not change the password.");
                        }

                        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
                    } else {

                        $this->get('session')->getFlashBag()->add('failure', "Please enter customer information note!");
                        return $this->redirect($request->headers->get('referer'));
                    }
                }
            }


            if ($request->request->has($userSettingForm->getName())) {
                $userSettingForm->handleRequest($request);
                if ($userSettingForm->isValid()) {

                    $note = $request->request->get('user-setting-note');

                    if ($note) {

                        // add cutomer update note.
                        // set audit log edit user
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'Customer update information';
                        $activityLog['description'] = $note;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        // ====================== //


                        $objSetting = $userSettingForm->getData();
                        $em->persist($objSetting);
                        $em->flush();

                        // set audit log edit user
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'User settings';
                        $activityLog['description'] = "Admin '" . $admin->getUsername() . "' has updated user settings for user '" . $user->getUsername() . "'";
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $this->get('session')->getFlashBag()->add('success', "Settings saved successfully!");
                        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
                    } else {

                        $this->get('session')->getFlashBag()->add('failure', "Please enter customer information note!");
                        return $this->redirect($request->headers->get('referer'));
                    }
                }
            }
        }

        //$roles = $user->getRoles();

        return $this->render('DhiAdminBundle:User:edit.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user,
                    'admin' => $admin,
                    'role' => $roles[0],
                    'changePasswordForm' => $changePasswordForm->createView(),
                    'userSettingform' => $userSettingForm->createView(),
                    'id' => $id
        ));
    }

    /**
     * user login log list
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function loginLogAction(Request $request, $id) {

        //Check permission

        if (!($this->get('admin_permission')->checkPermission('user_login_log') || $this->get('admin_permission')->checkPermission('user_login_log_export') || $this->get('admin_permission')->checkPermission('user_login_log_print') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user log detail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = null;
        if ($id) {
            $user = $em->getRepository('DhiUserBundle:User')->find($id);
        }
        /*

          $searchParams = $request->query->all();

          $form = $this->createForm(new LoginLogSearchFormType(), array(
          'searchParams' => isset($searchParams['search']) ? $searchParams['search'] : ''
          ));

          if (isset($searchParams['search']) && !empty($searchParams['search'])) {
          $search = $searchParams['search'];

          // set audit log search user log
          $activityLog = array();
          $activityLog['admin'] = $admin;
          $activityLog['activity'] = 'Search user login log';
          $activityLog['description'] = "Admin " . $admin->getUsername() . " searched criteria name:'{$searchParams['search']['search']}', startDate:'{$searchParams['search']['startDate']}' and endDate:'{$searchParams['search']['endDate']}'";

          if ($user) {

          $activityLog['user'] = $user;
          }

          $this->get('ActivityLog')->saveActivityLog($activityLog);
          $query = $em->getRepository('DhiUserBundle:UserLoginLog')->getUserLoginLogSearch($query, $searchParams['search']);
          }
         */
        $objServices = $em->getRepository('DhiUserBundle:Service')->getSpecificServices(array('tvod', 'bundle'));
        $services = array_map(function($service) {
            return $service['name'];
        }, $objServices);
        $objCountries = $em->getRepository('DhiUserBundle:Country')->getAllCountry();
        $countries = array_map(function($country) {
            return $country['name'];
        }, $objCountries);

        return $this->render('DhiAdminBundle:User:loginLog.html.twig', array(
                    'services' => $services,
                    'admin' => $admin,
                    'countries' => $countries,
                    // 'form'   => $form->createView(),
                    'id' => $id
        ));
    }

    public function loginLogListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $id) {

        $loginLogColumns = array('Name', 'IpAddress', 'ServiceLocation', 'ActiveServices', 'AvailableServices', 'Country', 'Logintime');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($loginLogColumns);

        if (!empty($gridData['search_data'])) {

            $this->get('session')->set('loginLogSearchData', $gridData['search_data']);
        } else {

            $this->get('session')->remove('loginLogSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'l.id';
            $sortOrder = 'DESC';
        } else {

//			if ($gridData['order_by'] == 'Id') {
//
//				$orderBy = 'l.id';
//			}
            if ($gridData['order_by'] == 'Name') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'IpAddress') {

                $orderBy = 'l.ipAddress';
            }
            if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }

            if ($gridData['order_by'] == 'ActiveServices') {

                $orderBy = 'u.activeServices';
            }
            if ($gridData['order_by'] == 'Country') {

                $orderBy = 'c.name';
            }
            if ($gridData['order_by'] == 'Logintime') {

                $orderBy = 'l.createdAt';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
        	$adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
          $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
 
				$data = $em->getRepository('DhiUserBundle:UserLoginLog')->getUserLoginLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $id, $adminServiceLocationPermission);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {

                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );

                foreach ($data['result'] AS $resultRow) {

                    $em = $this->getDoctrine()->getManager();
										$user = $em->getRepository('DhiUserBundle:User')->find($resultRow['userId']);
                    $userService = $this->get('UserWiseService');
										$data = $userService->getUserService($resultRow['ipAddress'], $user);
										$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow['userId']);

                    $count = 1;
                    $servicesCount = count($activeServices);

                    $activeService = '';
                    if ($activeServices) {
                        foreach ($activeServices as $service) {

                            if ($count == $servicesCount) {

                                $activeService .= '<span class="btn btn-success btn-sm service">' . $service . '</span>';
                            } else {

                                $activeService .= '<span class="btn btn-success btn-sm service">' . $service . '</span>';
                            }
                            $count++;
                        }
                    }

                    $acount = 1;
                    $aservicesCount = count($data['services']);

                    $availableServices = '';
                    if ($data['services']) {
                        foreach ($data['services'] as $availableservice) {

                            if ($acount == $aservicesCount) {

                                $availableServices .= '<span class="btn btn-success btn-sm service">' . $availableservice . '</span>';
                            } else {

                                $availableServices .= '<span class="btn btn-success btn-sm service">' . $availableservice . '</span>';
                            }
                            $acount++;
                        }
                    }


                    $userFullName = $resultRow['firstname'].' '.$resultRow['lastname'];
                    $row = array();
										$row[] = $userFullName;
										$row[] = $resultRow['ipAddress'] ? $resultRow['ipAddress'] : 'N/A';
										$row[] = $resultRow['userServiceLocation'] ? $resultRow['userServiceLocation'] : 'N/A';
                    $row[] = $activeService;
                    $row[] = $availableServices;
										$row[] = $resultRow['countryName'] ? $resultRow['countryName'] : 'N/A';
										$row[] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y H:i:s') : 'N/A';

                    $output['aaData'][] = $row;
                }
            }
        }

        $limit = $this->container->getParameter("dhi_admin_export_limit");
        $exportArr = array();
        if (!empty($limit)) {
            if ($output['iTotalRecords'] > 0) {
                $i = 0;
                while ($i < $output['iTotalRecords']) {
                    $exportArr[$i] = number_format($i+1)." - ".number_format($i+$limit).' Records';
                    $i = $i + $limit;
                }
            }
        }else{
            $exportArr[] = "0 - ".$output['iTotalRecords'];
        }

        $output['exportSlots'] = $exportArr;

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function loginLogExportAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log_export')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $offset = $request->get("offset");
        $slot   = $this->container->getParameter("dhi_admin_export_limit");
        if (!isset($slot) || !isset($offset)) {
            $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin   = $this->get('security.context')->getToken()->getUser();
        $em      = $this->getDoctrine()->getManager();
        $slotArr = array('limit'  => $slot, 'offset' => $offset);

        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

        $file_name = 'user_login_log_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download

        $searchData = array();

        if ($this->get('session')->has('loginLogSearchData') && $this->get('session')->get('loginLogSearchData') != '') {

            $searchData = $this->get('session')->get('loginLogSearchData');
        }

        // set audit log search user log
        $activityLog = array();
        $activityLog['admin'] = $admin;

        $activityLog['activity'] = 'Export user login log pdf';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has exported Login Log pdf";
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $resultUserLoginLog = $em->getRepository('DhiUserBundle:UserLoginLog')->getAllUserLoginLogQuery($searchData, $slotArr, $adminServiceLocationPermission);


        $userLoginLog = array();

        if ($resultUserLoginLog) {

            foreach ($resultUserLoginLog as $key => $resultRow) {

                $em = $this->getDoctrine()->getManager();
								$user = $em->getRepository('DhiUserBundle:User')->find($resultRow['userId']);
                $userService = $this->get('UserWiseService');
								$data = $userService->getUserService($resultRow['ipAddress'], $user);
								$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow['userId']);

                $count = 1;
                $servicesCount = count($activeServices);

                $activeService = '';
                if ($activeServices) {
                    foreach ($activeServices as $service) {

                        if ($count == $servicesCount) {

                            $activeService .= $service . '<br>';
                        } else {

                            $activeService .= $service . '<br>';
                        }
                        $count++;
                    }
                }

                $acount = 1;
                $aservicesCount = count($data['services']);

                $availableServices = '';
                if ($data['services']) {
                    foreach ($data['services'] as $availableservice) {

                        if ($acount == $aservicesCount) {

							$availableServices .= $availableservice . '<br>';
						} else {

							$availableServices .= $availableservice . '<br>';
						}
						$acount++;
					}
				}

        $userFullName = $resultRow['firstname'].' '.$resultRow['lastname'];
				$userLoginLog[$key]['id'] = $resultRow['loginLogId'];
				$userLoginLog[$key]['name'] = $userFullName;
				$userLoginLog[$key]['ip'] = $resultRow['ipAddress'];
				$userLoginLog[$key]['location'] = $resultRow['userServiceLocation'] ? $resultRow['userServiceLocation'] : 'N/A';
				$userLoginLog[$key]['activeService'] = $activeService;
				$userLoginLog[$key]['availableServices'] = $availableServices;
				$userLoginLog[$key]['country'] = $resultRow['countryName'] ? $resultRow['countryName'] : 'N/A';
				$userLoginLog[$key]['loginDate'] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y H:i:s') : 'N/A';
			}
		}
                
                $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
                $html = '<style>' . $stylesheet . '</style>';
                $html .= $this->renderView('DhiAdminBundle:User:loginLogExport.html.twig', array('userLoginLog' => $userLoginLog));
                
                unset($userLoginLog);
                return new Response(
                    $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
                    200,
                    array(
                        'Content-Type'          => 'application/pdf',
                        'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
                    )
                );

		// create html to pdf
		/*$pdf = $this->get("white_october.tcpdf")->create();

        // set document information
        $pdf->SetCreator('ExchangeVUE');
        $pdf->SetAuthor('ExchangeVUE');
        $pdf->SetTitle('ExchangeVUE');
        $pdf->SetSubject('Purchase History');

        // set default header data
        $pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="' . $dhiLogoImg . '" />');

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 9);

        // add a page
        $pdf->AddPage();

        // Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');

        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:User:loginLogExport.html.twig', array(
            'userLoginLog' => $userLoginLog
        ));

        // output the HTML content
        $pdf->writeHTML($html);

        // reset pointer to the last page
        $pdf->lastPage();

		// Close and output PDF document
		$pdf->Output($file_name, 'D');
		exit();*/
	}

    public function loginLogExportPrintAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log_print')) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $offset           = $request->get("offset");
        $slot    = $this->container->getParameter("dhi_admin_export_limit");
        if (!isset($slot) || !isset($offset)) {
            $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $slotArr = array('limit'  => $slot, 'offset' => $offset);
        $admin   = $this->get('security.context')->getToken()->getUser();
        $em      = $this->getDoctrine()->getManager();

        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

//        $file_name = 'user_login_log_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download

        $searchData = array();

        if ($this->get('session')->has('loginLogSearchData') && $this->get('session')->get('loginLogSearchData') != '') {

            $searchData = $this->get('session')->get('loginLogSearchData');
        }

        // set audit log search user log
        $activityLog = array();
        $activityLog['admin'] = $admin;

        $activityLog['activity'] = 'Export user login log print';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has exported Login Log print";
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $resultUserLoginLog = $em->getRepository('DhiUserBundle:UserLoginLog')->getAllUserLoginLogQuery($searchData, $slotArr, $adminServiceLocationPermission);

        $userLoginLog = array();

        if ($resultUserLoginLog) {

            foreach ($resultUserLoginLog as $key => $resultRow) {

                $em = $this->getDoctrine()->getManager();
								$user = $em->getRepository('DhiUserBundle:User')->find($resultRow['userId']);
                $userService = $this->get('UserWiseService');
								$data = $userService->getUserService($resultRow['ipAddress'], $user);
								$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow['userId']);

                $count = 1;
                $servicesCount = count($activeServices);

                $activeService = '';
                if ($activeServices) {
                    foreach ($activeServices as $service) {

                        if ($count == $servicesCount) {

                            $activeService .= $service . '<br>';
                        } else {

                            $activeService .= $service . '<br>';
                        }
                        $count++;
                    }
                }

                $acount = 1;
                $aservicesCount = count($data['services']);

                $availableServices = '';
                if ($data['services']) {
                    foreach ($data['services'] as $availableservice) {

                        if ($acount == $aservicesCount) {

                            $availableServices .= $availableservice . '<br>';
                        } else {

                            $availableServices .= $availableservice . '<br>';
                        }
                        $acount++;
                    }
                }

                $userFullName = $resultRow['firstname'].' '.$resultRow['lastname'];
								$userLoginLog[$key]['id'] = $resultRow['loginLogId'];
								$userLoginLog[$key]['name'] = $userFullName;
								$userLoginLog[$key]['ip'] = $resultRow['ipAddress'];
								$userLoginLog[$key]['location'] = $resultRow['userServiceLocation'] ? $resultRow['userServiceLocation'] : 'N/A';
                $userLoginLog[$key]['activeService'] = $activeService;
                $userLoginLog[$key]['availableServices'] = $availableServices;
								$userLoginLog[$key]['country'] = $resultRow['countryName'] ? $resultRow['countryName'] : '';
								$userLoginLog[$key]['loginDate'] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y H:i:s') : 'N/A';
            }
        }
        return $this->render('DhiAdminBundle:User:loginLogPrint.html.twig', array(
                    'userLoginLog' => $userLoginLog,
                    'img' => $dhiLogoImg
        ));
    }

    public function loginLogPrintAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_login_log_print')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $user = null;
        if ($id) {
            $user = $em->getRepository('DhiUserBundle:User')->find($id);
        }

        $activityLog = array();
        $activityLog['admin'] = $admin;

        $desc = "";
        if ($user) {
            $activityLog['user'] = $user;
            $desc = " of user " . $user->getUsername();
        }

        $activityLog['activity'] = 'Print user login log';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has print Login Log " . $desc;
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        /* END: add user audit log for user-login-log export */
    }

    public function changeServiceStatusAction($userId, $serviceSettingId) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to change user services.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        $setting = $em->getRepository('DhiUserBundle:UserServiceSetting')->find($serviceSettingId);
        if ($setting) {
            if ($setting->getService()->getName() == 'IPTV') {

                $setting->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Enabled' : 'Disabled');
                $em->persist($setting);

                // Add User's service log
                $objUserServiceSettingLog = new UserServiceSettingLog();
                $objUserServiceSettingLog->setService($setting->getService());
                $objUserServiceSettingLog->setUser($user);
                $objUserServiceSettingLog->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated');
                $em->persist($objUserServiceSettingLog);

                $em->flush();

                // Set log user service status change
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $user;
                $activityLog['activity'] = 'User service status change';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has change status of " . $user;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                ############################### START Selevision API #################################
                $wsService = ($setting->getServiceStatus() == 'Disabled') ? 'deactivateCustomer' : 'reactivateCustomer';

                // check selevision api to check whether customer exist in system
                $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

                // if customer exists, Disable/Reactivate customer
                if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUsername();
                    $wsResponse = $this->get('selevisionService')->callWSAction($wsService, $wsParam);
                } else {

                    if ($selevisionUserExist['serviceAvailable'] == 0) {

                        //Store API fail log
                        $this->get('paymentProcess')->serviceAPIErrorLog($user, ucfirst($wsService), 'Aradial');
                    }
                }
                ######################### END Selevision API #################################

                $serviceStatus = ($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated';
                $this->get('session')->getFlashBag()->add('success', "Service " . $setting->getService()->getName() . " " . $serviceStatus . " for user " . $user->getUsername());
            }
        }
        return $this->redirect($this->generateUrl('dhi_admin_user_list'));
    }

    public function serviceDetailAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_purchase')) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to purchase for user.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        $admin = $this->get('security.context')->getToken()->getUser();
        $selectedServiceLocation = $this->get('session')->get('selectedServiceLocation');
        $adminIp = $this->get('session')->get('ipAddress');

        $isOnlySingleLocation = 0;

        if (!$user) {

            throw $this->createNotFoundException('Invalid Page Request');
        }

				/*if (!$selectedServiceLocation && count($admin->getServiceLocations()) > 1) {
					$this->get('session')->getFlashBag()->add('failure', "Please choose service location for service purchase.");
					return $this->redirect($this->generateUrl('dhi_admin_user_service_location_list', array('id' => $id)));
				}*/

		if (count($admin->getServiceLocations()) == 1) {

			$isOnlySingleLocation = 1;

			$serviceLocation = $admin->getServiceLocations();
			if ($serviceLocation) {

				$country = ($serviceLocation[0]->getCountry()) ? $serviceLocation[0]->getCountry() : NULL;

				$user->setGeoLocationCountry($country);
				$user->setUserServiceLocation($serviceLocation[0]);
				$user->setIpAddress($adminIp);
				$user->setIpAddressLong(ip2long($adminIp));

				$em->persist($user);
				$em->flush();
			}
		}

        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary('admin', $user);

        $locationId = '';
        if ($user->getUserServiceLocation()) {
            $locationId = $user->getUserServiceLocation()->getId();
        }
        $serviceLocations = array();
        if ($admin->getGroup() != 'Super Admin') {
            $adminLocation = $admin->getServiceLocations();
            if (!empty($adminLocation)) {
                foreach ($adminLocation as $key => $value) {
                    $serviceLocations[] = array('id' => $value->getId(), 'name' => $value->getName());
                }
            }
        } else {
            $serviceLocations= $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        }

        return $this->render('DhiAdminBundle:User:userPurchaseDetail.html.twig', array(
                    'user' => $user,
                    'summaryData' => $summaryData,
                    'isOnlySingleLocation' => $isOnlySingleLocation,
                    'serviceLocations' => $serviceLocations,
                    'userLocationId' => $locationId
        ));
    }

    public function addIptvPackageAction($userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_service_setting')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add package.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);

        $wsParam = array();
        $wsRespose = $this->get('selevisionService')->callWSAction('getAllOffers', $wsParam);

        $packages = array();
        if ($wsRespose['status'] == 1) {

            $packages = $wsRespose;
        }

        $view = array();
        $view['user'] = $user;
        $view['packages'] = $packages;

        return $this->render('DhiAdminBundle:User:addIptvPackage.html.twig', $view);
    }

    public function viewAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);

        if (!$user) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user detail.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        // check user mac address how many add maximum
        $userSetting = $em->getRepository('DhiUserBundle:UserSetting')->findOneBy(array('user' => $user));

        if (isset($userSetting) && $userSetting && $userSetting->getMacAddress()) {

            $request->getSession()->set('mac_address', $userSetting->getMacAddress());
        }

        if (isset($userSetting) && $userSetting && $userSetting->getMacAddress()) {

            $request->getSession()->set('max_daily_transaction', $userSetting->getMacAddress());
        }


        // get user mac address
        $objMacAddress = $em->getRepository("DhiUserBundle:UserMacAddress")->findBy(array('user' => $user));

        $activeServiceList = $em->getRepository("DhiUserBundle:UserService")->activeServiceList($user->getUserServices());

             
        return $this->render('DhiAdminBundle:User:view.html.twig', array('user' => $user, 'userMacAddress' => $objMacAddress, 'activeServiceList' => $activeServiceList));
    }

    public function refundAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $defaultRefundType = array('ISP', 'IPTV', 'IPTVPremium', 'BUNDLE');

        $userId = $request->get('userId');
        $userServiceId = $request->get('userServiceId');
        $packageType = $request->get('packageType');
        $confirmPage = $request->get('confirmPage');
        $submitRefundPayment = $request->get('submitRefundPayment');
        $refundAmount = $request->get('processAmount');
        $finalRefundAmount = $request->get('finalRefundAmount');

        if (!$userId && !in_array($packageType, $defaultRefundType)) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to refund amount.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        $isAddOn = 0;
        $serviceTypeArr = array();
        if ($packageType == 'ISP' || $packageType == 'BUNDLE') {

            $serviceTypeArr = array('ISP', 'IPTV');
        }

        if ($packageType == 'IPTV') {

            $serviceTypeArr = array('IPTV');
        }

        if ($packageType == 'IPTVPremium') {

            $serviceTypeArr = array('IPTV');
            $isAddOn = 1;
        }

        $refundSummary = $em->getRepository("DhiUserBundle:UserService")->getRefundUserServiceData($userId, $serviceTypeArr, $isAddOn, $userServiceId);

        $user = $em->getRepository("DhiUserBundle:User")->find($userId);
        //Get user's active package from selevision
        $selevisionService = $this->get('selevisionService');

        if ($request->isXmlHttpRequest() && ($submitRefundPayment == 1 || $confirmPage == 1)) {

            $displayJsonResponse = false;

            $jsonResponse = array();
            $jsonResponse['status'] = 'failed';
            $jsonResponse['msg'] = 'Error No: #1003, Something went wrong in ajax request. please again';

            if ($refundAmount >= 0) {

                if ($refundAmount > $refundSummary['TotalActualAmt']) {

                    $jsonResponse['msg'] = 'Refund amount $' . $refundAmount . ' is not valid.';

                    $displayJsonResponse = true;
                }
                $doProcessRefund = true;
            } else {

                $jsonResponse['msg'] = 'Please check refund amount.';
                $displayJsonResponse = true;
            }

            //Check refund package available in user's selevision account
            $IPTVPackageId   = $request->get('IPTVPackageId');
            $IPTVPackageName = $request->get('IPTVPackageName');
            $ISPPackageId    = $request->get('ISPPackageId');
            $ISPPackageName  = $request->get('ISPPackageName');
            $refundServiceId = $request->get('refundServiceId');

			if ($IPTVPackageId && in_array('IPTV', $serviceTypeArr)) {
				$i = 0;
        $selevisionIPTVActivePackageIds = $selevisionService->getActivePackageIds($user->getUsername());
				foreach ($IPTVPackageId as $packageId) {

					if (!in_array($packageId, $selevisionIPTVActivePackageIds)) {

						if ($i == 0) {

							$jsonResponse['msg'] = '';
						}

						$jsonResponse['msg'] .= $IPTVPackageName[$packageId] . " package not exist in user's selevision account.<br/>";
						$displayJsonResponse = true;

						$i++;
					}
				}
			}
			//End here


            if ($submitRefundPayment == 1) {

                $displayJsonResponse = true;
                $isPaymentRefundedSuccess = false;

                if ($refundAmount == 0 && $refundSummary['TotalRefundAmt'] == 0) {

                    $isPaymentRefundedSuccess = true;
                } else {

                    $isPaymentRefundedSuccess = true;
                }
                //After success refund payment update status into database and remove package from selevision account
                if ($isPaymentRefundedSuccess) {

                    $packRefundAmount = 0;
                    $totalRefundAmount = 0;
                    $totalRefundedAmount = 0;
                    $isRefundedAnyOnePack = false;
                    $purchaseOrderDetail = array();
                    $i = 0;

                    $userServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServiceFromIds($refundServiceId);

                    if ($userServices) {

                        foreach ($userServices as $userService) {


                            if ($userService->getPurchaseOrder() && $i == 0) {

                                $purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] = 0;
                            }
                            $doProcessRefund = false;
                            $servicePurchase = $userService->getServicePurchase();

                            //Calculate package validity remain days
                            $todayDateTime = new \DateTime();
                            $expiredDate = $userService->getExpiryDate();

                            $intervalRemainDays = $todayDateTime->diff($expiredDate);
                            
                            /*-----------------Add condtion for hously plan---------------*/
                             $validitytype = $userService->getServicePurchase()->getValidityType();
                             if($validitytype=='HOURS') {
                               $remainingDays = $intervalRemainDays->format('%h');
                            } else {
                               $remainingDays = $intervalRemainDays->format('%a');
                            }
                            
                            /*-----------------------End of code-------------------------------------*/
                            
                            
                            //End here

                            $finalCost = $userService->getFinalCost();
                            // $prevCredit = $userService->getUnusedCredit();
                            // if($prevCredit > 0){
                            // 	$finalCost += $prevCredit;
                            // }

                            $perDayAmount = $finalCost / $userService->getValidity(); //Per day package price
                            $packRefundAmount = $perDayAmount * $remainingDays; //Remaining days package amount
                            $totalRefundAmount += $packRefundAmount;

                            if ($userService->getService() && $userService->getIsAddOn() == $isAddOn) {

                                $serviceName = strtoupper($userService->getService()->getName());

                                if (in_array($serviceName, $serviceTypeArr)) {

                                    if ($isAddOn == 1) {

                                        if ($userService->getIsAddOn() == 1 && $userService->getId() == $userServiceId) {

                                            $doProcessRefund = true;
                                        }
                                    } else {

                                        $doProcessRefund = true;
                                    }

                                    if ($doProcessRefund) {

                                        $isPackageRefunded = true;
                                        if ($serviceName == 'IPTV') {

                                            //Call Selevision webservice for unset package
                                            $wsOfferParam = array();
                                            $wsOfferParam['cuLogin'] = $userService->getUser()->getUserName();
                                            $wsOfferParam['offer'] = $userService->getPackageId();

                                            $selevisionService = $this->get('selevisionService');
                                            $wsRes = $selevisionService->callWSAction('unsetCustomerOffer', $wsOfferParam);

                                            if ($wsRes['status'] == 1) {

                                                $isPackageRefunded = true;
                                            }
                                        }

                                        if ($serviceName == 'ISP') {

                                            ######## Update Password in Aradial ####################
                                            if ($this->get('aradial')->checkUserExistsInAradial($userService->getUser()->getUserName())) {
                                                $wsParam                           = array();
                                                $wsParam['Page']                   = 'UserSessions';
                                                $wsParam['SessionsMode']           = 'UsrAllSessions';
                                                $wsParam['qdb_Users.UserID']       = $userService->getUser()->getUserName();
                                                $wsParam['op_$D$AcctSessionTime']  = "<>";
                                                $wsParam['qdb_$D$AcctSessionTime'] = " ";

                                                $aradial = $this->get('aradial');
                                                $wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);

                                                if (!empty($wsResponse['userSession'])) {

                                                    foreach ($wsResponse['userSession'] as $user) {

                                                        $userName = $user['UserID'];
                                                        $nasName = $user['NASName'];
                                                        $startTime = $user['InTime'];
                                                        $stopTime = $user['TimeOnline'];
                                                        $framedAddress = $user['FramedAddress'];
                                                        $callerId = $user['CallerId'];
                                                        $calledId = $user['CalledId'];
                                                        $acctSessionTime = $user['AcctSessionTime'];
                                                        $isRefunded = 1;

                                                        if (!empty($userName)) {
                                                            $userData = $em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
                                                        }

                                                        if (!empty($userData)) {
                                                            $email = $userData[0]['email'];
                                                        } else {
                                                            $email = '';
                                                        }

                                                        $Param = array();
                                                        $Param['Page'] = 'UserHit';
                                                        $Param['qdb_Users.UserID'] = $userName;

                                                        if (empty($email)) {
                                                            $wsResponse1 = $aradial->callWSAction('getUserList', $Param);
                                                            if (!empty($wsResponse1['userList'])) {
                                                                $email = empty($wsResponse1['userList'][0]['UserDetails.Email']) ? '' : $wsResponse1['userList'][0]['UserDetails.Email'];
                                                            }
                                                        }

                                                        if (!empty($acctSessionTime)) {
                                                            $objUserSession = new UserSessionHistory();
                                                            $objUserSession->setUserName($userName);
                                                            $objUserSession->setEmail($email);
                                                            $objUserSession->setNasName($nasName);
                                                            $objUserSession->setStartTime($startTime);
                                                            $objUserSession->setStopTime($stopTime);
                                                            $objUserSession->setCallerId($callerId);
                                                            $objUserSession->setCalledId($calledId);
                                                            $objUserSession->setFramedAddress($framedAddress);
                                                            $objUserSession->setIsRefunded($isRefunded);
                                                            if (!empty($startTime)) {
                                                                $objUserSession->setStartDateTime(new \DateTime($startTime));
                                                            }
                                                            if (!empty($stopTime) && !empty($startTime)) {
                                                                list($hours, $minutes, $seconds) = sscanf($stopTime, '%d:%d:%d');
                                                                $sTime = new \DateTime($startTime);
                                                                $objStopTime = clone $sTime;
                                                                $seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                                                                $objStopTime->modify('+'.$seconds.' seconds');
                                                                $objUserSession->setStopDateTime($objStopTime);
                                                            }
                                                            $em->persist($objUserSession);
                                                            $em->flush();
                                                        }
                                                    }
                                                }
                                                // Delete existing user from aradial
                                                $cancelingUsrParam = array();
                                                $cancelingUsrParam['Page'] = "UserEdit";
                                                $cancelingUsrParam['UserId'] = $userService->getUser()->getUserName();
                                                $cancelingUsrParam['ConfirmDelete'] = 1;

                                                $cancelUsrResponse = $this->get('aradial')->callWSAction('cancelUser', $cancelingUsrParam);
                                            }
                                            ############ END Here ##################################

                                            $isPackageRefunded = true;
                                        }

                                        if ($isPackageRefunded) {

                                            //Update user service data
                                            $userService->setRefund(1);
                                            $userService->setRefundAmount($refundAmount);
                                            $userService->setStatus(0);
                                            $userService->setRefundedBy($admin);
                                            $userService->setRefundedAt(new \DateTime());
                                            $em->persist($userService);

                                            //Update service purchase data
                                            if ($servicePurchase) {

                                                $servicePurchase->setPaymentStatus('Refunded');
                                                $servicePurchase->setRechargeStatus(2);
                                                $em->persist($servicePurchase);

                                                // update the employee code refunded code
                                                $objEmployeePromoCode = $servicePurchase->getDiscountedEmployeePromocode();
                                                if ($objEmployeePromoCode) {
                                                    $objUserEmployeePromoCode = $servicePurchase->getUser();
                                                    $objEmployeePromoCodeCustomer = $em->getRepository('DhiAdminBundle:EmployeePromoCodeCustomer')->findOneby(array('EmployeePromoCodeId' => $objEmployeePromoCode, 'user' => $objUserEmployeePromoCode));
                                                    $objEmployeePromoCodeCustomer->setStatus(1);
                                                    $em->persist($objEmployeePromoCodeCustomer);
                                                }
                                            }

                                            if ($userService->getPurchaseOrder()) {

                                                $purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] += $packRefundAmount;
                                            }

                                            $totalRefundedAmount += $packRefundAmount;
                                            $isRefundedAnyOnePack = true;
                                        }
                                    }
                                }
                            }
                            $i++;
                        }


                        if ($isRefundedAnyOnePack) {

                            $paymentStatus = 'Refunded';
                            if ($purchaseOrderDetail) {

                                foreach ($purchaseOrderDetail as $key => $val) {

                                    $totalRefundAmount = $val['totalRefundAmount'];
                                    $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($key);

                                    if ($purchaseOrder) {

                                        if ($purchaseOrder->getUserService()) {

                                            foreach ($purchaseOrder->getUserService() as $service) {

																								if ($service->getStatus() == 1 && $service->getExpiryDate() > new \DateTime()) {

                                                    $paymentStatus = 'Completed';
                                                }
                                            }
                                        }

                                        $purchaseOrder->setPaymentStatus($paymentStatus);
//										$purchaseOrder->setRefundAmount($purchaseOrder->getRefundAmount() + $totalRefundAmount);
                                        $purchaseOrder->setRefundAmount($refundAmount);

                                        $em->persist($purchaseOrder);
                                    }
                                }

                                $jsonResponse['status'] = 'success';
                                $jsonResponse['msg'] = "$" . $refundAmount . " has been refunded successfully.";
                            }
                        }
                        $em->flush();
                    } else {

                        $jsonResponse['msg'] = 'Data not exist for refund payment.';
                    }
                }
            }

            if ($displayJsonResponse) {

                echo json_encode($jsonResponse);
                exit;
            }
        }

        $view = array();
        $view['refundSummary'] = $refundSummary;
        $view['packageType'] = $packageType;
        $view['userId'] = $userId;
        $view['userServiceId'] = $userServiceId;
        $view['refundAmount'] = $refundAmount;
        $view['finalRefundAmount'] = $finalRefundAmount;

        if ($confirmPage) {

            return $this->render('DhiAdminBundle:User:confirmRefundPayment.html.twig', $view);
        } else {

            return $this->render('DhiAdminBundle:User:refundPayment.html.twig', $view);
        }
    }

    public function getUserServiceDetailAction($userId, $ipAddress) {
        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('DhiUserBundle:User')->find($userId);

        $userService = $this->get('UserWiseService');
        $data = $userService->getUserService($ipAddress, $user);

        $activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($userId);

        return $this->render('DhiAdminBundle:User:userServiceLocation.html.twig', array('services' => $data['services'], 'location' => $data['location'], 'activeServices' => $activeServices));
    }

    // add user credit
    public function creditAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $userCreditArr = $request->request->get('userCredit');

        $response = array('status' => 'failure', 'message' => '');


        if ($request->isXmlHttpRequest()) {

            if (!empty($userCreditArr)) {

                $user = $em->getRepository('DhiUserBundle:User')->find($userCreditArr['userId']);
                $userCredit = $em->getRepository('DhiAdminBundle:Credit')->find($userCreditArr['creditId']);

                if ($userCreditArr['amount'] == '') {

                    $response = array('status' => 'failure', 'message' => 'Please select amount.');
                } else if ($userCreditArr['creditType'] == '') {

                    $response = array('status' => 'failure', 'message' => 'Please select credit type.');
                } else {

                    // create customer
                    $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);

                    // add user credit
                    $wsParam = array();
                    $wsParam['cuLogin'] = $user->getUsername();
                    $wsParam['credit'] = $userCredit->getCredit();

                    $selevisionService = $this->get('selevisionService');
                    $wsRespose = $selevisionService->callWSAction('giveCustomerCredit', $wsParam);

                    if (!empty($wsRespose) && $wsRespose['status'] == 1) {

                        // set purchase order
                        $objPurchaseOrder = new PurchaseOrder();
                        $objPurchaseOrder->setUser($user);
                        $objPurchaseOrder->setOrderNumber($this->get('PaymentProcess')->generateOrderNumber());
                        $objPurchaseOrder->setTotalAmount($userCreditArr['amount']);
                        $objPurchaseOrder->setPaymentStatus('Completed');
                        $objPurchaseOrder->setSessionId('');
                        $objPurchaseOrder->setPaymentBy('Admin');
                        $objPurchaseOrder->setPaymentByUser($admin);

                        $em->persist($objPurchaseOrder);
                        $em->flush();

                        $purchaseOrderId = $objPurchaseOrder->getId();

                        // set serice purchase
                        if ($purchaseOrderId) {

                            $objServicePurchase = new ServicePurchase();
                            $objServicePurchase->setUser($user);
                            $objServicePurchase->setCredit($userCredit);
                            $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                            $objServicePurchase->setActualAmount($userCreditArr['amount']);
                            $objServicePurchase->setPayableAmount($userCreditArr['amount']);
                            $objServicePurchase->setPaymentStatus('Completed');
                            $objServicePurchase->setIsCredit(1);
                            $objServicePurchase->setPackageId('');
                            $objServicePurchase->setPackageName('');
                            $objServicePurchase->setSessionId('');

                            $em->persist($objServicePurchase);
                            $em->flush();

                            if ($objServicePurchase->getIsCredit() == 1) {

                                $credit = $objServicePurchase->getCredit()->getCredit();
                                $userCreditPurchase = $credit;
                                $amount = $objServicePurchase->getPayableAmount();

                                //insert error to failure table
                                $objUserCreditLog = new UserCreditLog();

                                $objUserCreditLog->setUser($user);
                                $objUserCreditLog->setCredit($credit);
                                $objUserCreditLog->setAmount($amount);
                                $objUserCreditLog->setTransactionType('Credit');
                                $objUserCreditLog->setType($userCreditArr['creditType']);
                                $objUserCreditLog->setEagleCashNo(NULL);
                                $objUserCreditLog->setPurchaseOrder($objPurchaseOrder);

                                $em->persist($objUserCreditLog);
                                $em->flush();

                                if ($objUserCreditLog->getId()) {

                                    $objUserCredit = $user->getUserCredit();

                                    if (!$objUserCredit) {

                                        $objUserCredit = new UserCredit();
                                        $message = 'Credit added successfully.';
                                    } else {

                                        $credit = $credit + $objUserCredit->getTotalCredits();
                                        $message = 'Credit updated successfully.';
                                    }

                                    $objUserCredit->setUser($user);
                                    $objUserCredit->setTotalCredits($credit);
                                    $em->persist($objUserCredit);
                                    $em->flush();

                                    if ($objUserCredit->getId()) {

                                        $response = array('status' => 'success', 'message' => $message);
                                    }
                                }
                            } else {
                                $response = array('status' => 'failure', 'message' => 'Something went to wrong');
                            }
                        } else {

                            $response = array('status' => 'failure', 'message' => 'Something went to wrong');
                        }
                    } else {

                        $response = array('status' => 'failure', 'message' => 'Something went wrong with your purchase. Please contact support if the issue persists');
                    }
                }
            } else {

                $response = array('status' => 'failure', 'message' => 'Something went to wrong');
            }
        }

        echo json_encode($response);
        exit;
    }

    //get service location list of admin
    public function serviceLocationAdminListAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_purchase_detail')) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user's purchased detail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        $admin = $this->get('security.context')->getToken()->getUser();
        $adminIp = $this->get('session')->get('ipAddress');
        $sessionId = $this->get('session')->get('adminSessionId'); //Get Session Id
        $selectedServiceLocation = $this->get('session')->get('selectedServiceLocation');
        //echo $selectedServiceLocation;exit;
        if (!$user) {

            throw $this->createNotFoundException('Invalid Page Request');
        }

        $serviceLocationArr = array();
        $adminServiceLocations = $admin->getServiceLocations();
        foreach ($adminServiceLocations as $serviceLocation) {

            $serviceLocationArr[$serviceLocation->getId()] = $serviceLocation->getName();
        }

        if ($request->getMethod() == "POST") {

            $serviceLocationId = $request->get('serviceLocationSelection');

            if (!$serviceLocationId) {

                $this->get('session')->getFlashBag()->add('failure', "Please choose service location for service purchase.");
                return $this->redirect($this->generateUrl('dhi_admin_user_service_location_list', array('id' => $id)));
            } else {

                if ($selectedServiceLocation != $serviceLocationId) {

                    $deleteServicePurchase = $em->createQueryBuilder()->delete('DhiServiceBundle:ServicePurchase', 'sp')
                                    ->where('sp.sessionId =:sessionId')
                                    ->setParameter('sessionId', $sessionId)
                                    ->andWhere('sp.paymentStatus =:paymentStatus')
                                    ->setParameter('paymentStatus', 'New')
                                    ->getQuery()->execute();
                }

                $result = $em->getRepository('DhiAdminBundle:IpAddressZone')->getAdminPurchaseLocation($adminIp, $serviceLocationId);

                if ($result['data']) {

                    if ($result['data']->getServiceLocation()) {

                        $serviceLocation = $result['data']->getServiceLocation();
                        $country = ($serviceLocation->getCountry()) ? $serviceLocation->getCountry() : NULL;

                        $user->setGeoLocationCountry($country);
                        $user->setUserServiceLocation($serviceLocation);
                        $user->setIpAddress($result['ipAddress']);
                        $user->setIpAddressLong(ip2long($result['ipAddress']));

                        $em->persist($user);
                        $em->flush();

                        $this->get('session')->set('selectedServiceLocation', $serviceLocationId);
                        return $this->redirect($this->generateUrl('dhi_admin_user_service_details', array('id' => $id)));
                    }
                }
            }
        }

        return $this->render('DhiAdminBundle:User:serviceLocationSelection.html.twig', array(
                    'user' => $user,
                    'serviceLocations' => $serviceLocationArr
        ));
    }

    public function activeUserReportAction() {
        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('active_user_report_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view active user report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $allPackageType = $em->getRepository('DhiAdminBundle:Package')->getPackagesForActiveUsers();
        $packageGroup = array();
        foreach ($allPackageType as $key => $packageName) {
            $packageGroup[$key] = $allPackageType[$key]['packageName'];
        }

        //service location
        $allPackageType = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
        $packageType = array();
        if ($admin->getGroup() != 'Super Admin') {
            $accessLocation = $admin->getServiceLocations();
            if($accessLocation){
                foreach ($accessLocation as $key => $value) {
                    $packageType[] = $value->getName();
                }
            }
        } else {
            foreach ($allPackageType as $key => $packageName) {
                $packageType[$key] = $allPackageType[$key]['name'];
            }
        }

        $services = $em->getRepository("DhiUserBundle:Service")->findBy(array("status" => 1));

        return $this->render('DhiAdminBundle:User:activeUserReport.html.twig', array(
                    'admin' => $admin,
                    'packagename' => $packageGroup,
                    'serlocation' => $packageType,
                    "services" => $services
        ));
    }

    public function activeUserListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $fromip, $toip) {
        $request = $this->getRequest();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');
        $aColumns = array('name', 'total');
        $gridData = $helper->getSearchData($aColumns);
        if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {
            $this->get('session')->set('salesReportSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('salesReportSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sl.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'name') {
                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'paymentMethod') {
                $orderBy = 'p.packageName';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $serviceType = array();
        $adminServiceLocationPermission = '';

        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $name = $request->get('name');
        if ($name != null) {
            $gridData['search_data']['name'] = str_replace(",", "','", $request->get('name'));
        }

        $serviceName = $request->get('service');
        if ($serviceName != null) {
            $serviceType = array($serviceName);
            if (in_array("IPTV", $serviceType)) {
                $serviceType[] = "PREMIUM";
            }
            $gridData['search_data']['serviceType'] = $serviceType;
        }

        $gridData['SearchType'] = 'ANDLIKE';


        if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {

            $this->get('session')->set('activeUserReportSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('activeUserReportSearchData');
        }

        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getActiveUserReportGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $fromip, $toip, $adminServiceLocationPermission);
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );

        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {

                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => count($data['result']),
                    "iTotalDisplayRecords" => 0,
                    "aaData" => array()
                );

                $finalArray = array();

                $locationKey = '';
                $method = '';
                $grandTotalLocation = '';
                $i = 1;
                $totalMembers = 0;
                foreach ($data['result'] as $locationKey => $recordLocation) {
                    $innerContent = $this->activeUserLocationPackages($recordLocation['name'], $serviceType);
                    if ($innerContent['totalUser'] > 0) {
                        $row = array();
                        $row[] = $recordLocation['name'];
                        $row[] = $innerContent['innerhtml'];
                        $totalMembers += $innerContent['totalUser'];
                        $output['aaData'][] = $row;
                    }
                }
                $row = array();
                $row[] = 'Grand Total';
                $row[] = $totalMembers;
                $output['aaData'][] = $row;
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function activeUserExportCsvAction(Request $request) {
        $response = new StreamedResponse();
        $searchData = null;

        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('active_user_report_export_csv'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export active user report csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if ($this->get('session')->get('activeUserReportSearchData')) {
            $searchData = $this->get('session')->get('activeUserReportSearchData');
        }

        $em = $this->getDoctrine()->getManager();
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }

        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getActiveUserReportData($searchData, $adminServiceLocationPermission);
        $locationData = array();

        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                foreach ($data['result'] as $locationKey => $recordLocation) {
                    $locationData[$locationKey]['location'] = $recordLocation['name'];
                    $temp = $this->getPackageServiceList($recordLocation['name'], (!empty($searchData['serviceType']) ? $searchData['serviceType'] : array()));

                    $locationData[$locationKey]['details'] = $temp['result'];
                }
            }
        }
        $response->setCallback(function() use($locationData) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Location", "Total"), ',');
            $grandTotal = 0;
            foreach ($locationData as $key => $value) {
                $totalUser = 0;
                fputcsv($handle, array($value['location'], "Service Name", "Package Name", "Description", "Total Users"), ',');
                if ($value['details']) {
                    foreach ($value['details'] as $key => $locatonDetails) {
                        fputcsv($handle, array("", $locatonDetails['name'], $locatonDetails['packageName'], $locatonDetails['description'], $locatonDetails['totalUsers']), ',');
                        $totalUser +=$locatonDetails['totalUsers'];
                        $grandTotal +=$locatonDetails['totalUsers'];
                    }
                }
                fputcsv($handle, array("", "Total Active Users", "", "", $totalUser), ',');
            }
            fputcsv($handle, array("Grand total", "", "", "", $grandTotal), ',');
            fclose($handle);
        });

        // create filename
        $file_name = 'activeUser_' . 'admin' . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

    public function activeUserExportExcelAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('active_user_report_export_excel'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export active user report excel.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $file_name = 'active_user_report_' . 'admin' . '_' . date('m-d-Y', time()) . '.xls'; // Create pdf file name for download
        $response = new StreamedResponse();
        $searchData = null;
        if ($this->get('session')->get('activeUserReportSearchData')) {
            $searchData = $this->get('session')->get('activeUserReportSearchData');
        }
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getActiveUserReportData($searchData, $adminServiceLocationPermission);
        $locationData = array();
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                foreach ($data['result'] as $locationKey => $recordLocation) {
                    $locationData[$locationKey]['location'] = $recordLocation['name'];
                    $temp = $this->getPackageServiceList($recordLocation['name'], (!empty($searchData['serviceType']) ? $searchData['serviceType'] : array()));
                    $locationData[$locationKey]['details'] = $temp['result'];
                }
            }
        }

        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
                ->setLastModifiedBy("Admin")
                ->setTitle("DhiPortal Active User Report")
                ->setSubject("Active User Report")
                ->setDescription("Active User Report")
                ->setKeywords("Active User Report")
                ->setCategory("Active User Report");

        $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Location')
                ->setCellValue('B1', 'Total');
        $row = 2;
        $GrandTotal = 0;
        foreach ($locationData as $key => $value) {
            $totalUser = 0;

            $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue('A' . $row, $value['location'])
                    ->setCellValue('B' . $row, 'Service Name')
                    ->setCellValue('C' . $row, 'Package Name')
                    ->setCellValue('D' . $row, 'Description')
                    ->setCellValue('E' . $row, 'Total Users');

            if ($value['details']) {
                foreach ($value['details'] as $key => $locatonDetails) {
                    $temp = preg_replace('/\s+/', ' ', $locatonDetails['description']);
                    $row++;
                    $phpExcelObject->setActiveSheetIndex(0)
                            ->setCellValue('B' . $row, $locatonDetails['name'])
                            ->setCellValue('C' . $row, $locatonDetails['packageName'])
                            ->setCellValue('D' . $row, $temp)
                            ->setCellValue('E' . $row, $locatonDetails['totalUsers']);

                    //echo "\t" . $locatonDetails[0]['packageType'] . "\t" . $locatonDetails[0]['packageName'] . "\t" . $temp . "\t" . $locatonDetails[1] . "\n";
                    $totalUser +=$locatonDetails['totalUsers'];
                    $GrandTotal +=$locatonDetails['totalUsers'];
                }
                $row++;
            } else {
                $row++;
            }

            $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue('B' . $row, 'Total Action Users')
                    ->setCellValue('E' . $row, $totalUser);
            $row++;
        }
        $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A' . $row, 'Grand Total')
                ->setCellValue('E' . $row, $GrandTotal);




        // set header
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file_name);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
    }

    private function getPackageServiceList($serviceLoction, $packageType) {
        $dataResult = array('result' => array(), 'totalUsers' => 0);
        $conn = $this->get('database_connection');
        $pWhere = $bWhere = '';
        // $sWhere     = array();
        $curDate = new \DateTime();

        $bWhere = " AND s.name IN('ISP') ";
        if (!empty($packageType)) {
            if (!in_array('ISP', $packageType) && !in_array('IPTV', $packageType)) {
                $bWhere = " AND s.name IN('BUNDLE') ";
            }
            $pWhere = " AND s.name IN('" . implode("','", $packageType) . "') ";
        }

        $sql = "(
				SELECT s.name, us.package_name as packageName, 'N/A' as description, us.package_id, count(u.id) as totalUsers
				FROM dhi_user u
					INNER JOIN user_services us ON u.id = us.user_id
					INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id
					INNER JOIN service_location sl ON sp.service_location_id = sl.id
					INNER JOIN service s ON sp.service_id = s.id
				WHERE u.locked = 0
					AND u.is_deleted = 0
					AND us.status = 1
					AND us.is_addon = 0
					AND us.expiry_date > now()
					AND sp.purchase_type IS NULL
					AND u.roles LIKE '%ROLE_USER%'
		" .
                (!empty($serviceLoction) ? " AND sl.name = '$serviceLoction'" : "") . $pWhere
                . "
				GROUP BY us.package_id
			) UNION (
				SELECT sp.purchase_type as name, b.display_bundle_name as packageName, b.description, us.package_id, count(u.id) as totalUsers
				FROM dhi_user u
					INNER JOIN user_services us ON u.id = us.user_id
					INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id
                                        INNER JOIN service_location sl ON sp.service_location_id = sl.id
					INNER JOIN service s ON sp.service_id = s.id
					LEFT JOIN bundle b on sp.bundle_id = b.bundle_id
				WHERE u.locked = 0
					AND u.is_deleted = 0
					AND us.status = 1
					AND us.is_addon = 0
					AND us.expiry_date > now()
					AND sp.purchase_type = 'BUNDLE'
					AND u.roles LIKE '%ROLE_USER%'
		" .
                (!empty($serviceLoction) ? " AND sl.name = '$serviceLoction'" : "") . $bWhere
                . "
				GROUP BY sp.bundle_id
			)";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $data = $stmt->fetchAll();

        if (!empty($data)) {
            $dataResult['result'] = $data;
            $dataResult["totalUsers"] = count($data);
        }
        return $dataResult;
    }

    function activeUserLocationPackages($serviceLoction, $serviceType = array()) {
        $em = $this->getDoctrine()->getManager();
        $data = $this->getPackageServiceList($serviceLoction, $serviceType);
        $html = "<table style='margin-bottom: 0 !important;' class='table table-bordered table-hover'>"
                . "<tbody><tr><th>Service Name</th><th>Package Name</th><th>Description</th><th>Total Users</th></tr>";
        $totaluser = 0;
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => count($data['result']),
                    "iTotalDisplayRecords" => 0,
                    "aaData" => array()
                );

                foreach ($data['result'] as $locationKey => $recordLocation) {
                    $totaluser += $recordLocation['totalUsers'];
                    $html .="<tr><td>" . (!empty($recordLocation['name']) ? $recordLocation['name'] : 0) . "</td><td>" . $recordLocation['packageName'] . "</td><td>" . (!empty($recordLocation['description']) ? $recordLocation['description'] : 'N/A') . "</td><td>" . $recordLocation['totalUsers'] . "</td></tr>";
                }
            }
        }
        $html .="<tr><td colspan='3'><b>Total Active User</b></td><td><b>" . $totaluser . "</b></td></tr><tr></tr></tbody></table>";
        return array('innerhtml' => $html, 'totalUser' => $totaluser);
    }

    public function logAction(Request $request, $id) {
        //Check permission
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        if (!($this->get('admin_permission')->checkPermission('user_login_log') || $this->get('admin_permission')->checkPermission('user_login_log_export') || $this->get('admin_permission')->checkPermission('user_login_log_print') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user log detail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $user = null;
        if ($id) {
            $user = $em->getRepository('DhiUserBundle:User')->find($id);
            if ($user) {
                $userName = $user->getUserName();
                $query = $em->getRepository('DhiUserBundle:UserActivityLog')->getAllActivityLogs();
                //$getQuery  = $em->getRepository('DhiUserBundle:UserActivityLog')->getActivityLogSearch($query, array('user' => $userName));
                //$paginator = $this->get('knp_paginator');
                //$logs      = $paginator->paginate($query, $request->query->get('page', 1), 10);
            }
        }

        return $this->render('DhiAdminBundle:User:log.html.twig', array(
                    'admin' => $admin,
                    'id' => $id,
        ));
    }

    public function activityLogListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $id) {

        $em = $this->getDoctrine()->getManager();
        $aColumns = array('Admin', 'User', 'Activity', 'Description', 'IP', 'Date Time');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($aColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );

        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        if ($user) {
            $userName = $user->getUserName();
        } else {
            $response = new Response(json_encode($output));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'al.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Admin') {

                $orderBy = 'al.admin';
            }

            if ($gridData['order_by'] == 'User') {

                $orderBy = 'al.user';
            }

            if ($gridData['order_by'] == 'Activity') {

                $orderBy = 'al.activity';
            }

            if ($gridData['order_by'] == 'IP') {

                $orderBy = 'al.ip';
            }
            if ($gridData['order_by'] == 'Date Time') {

                $orderBy = 'al.timestamp';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $data = $em->getRepository('DhiUserBundle:UserActivityLog')->getActivityLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $userName);


        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {

                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );

                foreach ($data['result'] AS $resultRow) {

                    $row = array();
										$row[] = $resultRow['admin'] ? $resultRow['admin'] : 'N/A';
										$row[] = (($resultRow['user'] && $resultRow['admin'] == "") ? $resultRow['user'] : 'N/A');
										$row[] = $resultRow['activity'];
										$row[] = $resultRow['description'];
										$row[] = $resultRow['ip'];
										$row[] = $resultRow['timestamp']->format('m/d/Y H:i:s');
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function addCompensationAction(Request $request, $id) {

        //Check permission
        $admin = $this->get('security.context')->getToken()->getUser();
        if (!$this->get('admin_permission')->checkPermission('compensation_create') || ($admin->getGroup() != 'Super Admin' && $admin->getGroup() != 'Manager')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new compensation.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        $em = $this->getDoctrine()->getManager();
        $ipAddress = $this->get('session')->get('ipAddress');

        $user = $em->getRepository("DhiUserBundle:User")->find($id);
        if (!$user) {
            $this->get('session')->getFlashBag()->add('failure', "User does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        $userActiveService = $em->getRepository("DhiUserBundle:UserService")->getUserActiveServiceForCompensation(array("ISP","IPTV"), $user->getId(), true);

        if ($userActiveService && !empty($userActiveService[$user->getId()]['data'])) {
            $compensation = new Compensation();
            $compensation->addUser($user);
            $form = $this->createForm(new UserCompensationFormType($admin, $id, $em), $compensation);

            if ($request->getMethod() == "POST") {
                $form->handleRequest($request);
                if ($form->isValid()) {

                    $compensation->setStatus('Completed');
                    $compensation->setTitle("Compensation for " . $user->getUserName());
                    $compensation->setAdminId($admin->getId());
                    $compensation->setNote($compensation->getReason());
                    $compensation->setIsEmailActive(false);
                    $compensation->getIsStarted(1);
                    $em->persist($compensation);

                    $compId = $compensation->getId();

                    $activeUserService = $userActiveService;
                    $isISPExtend = false;
                    if ($activeUserService && !empty($activeUserService[$user->getId()]['data'])) {
                        foreach ($activeUserService[$user->getId()]['data'] as $key => $objUserActiveService) {

                            if ($key) {
                                if (strtoupper($key) == 'IPTV') {
                                    $isIPTVAutoExtend = true;
                                    if ($this->processCompensationOnIPTV($compensation, $user, $objUserActiveService, $isIPTVAutoExtend)) {
                                        $isIPTVExtend = true;
                                    }
                                }

                                if (strtoupper($key) == 'ISP') {
                                    $isISPAutoExtend = false;
                                    if ($activeUserService[$user->getId()]['autoExtendService'] == 'ISP') {
                                        $isISPAutoExtend = true;
                                    }

                                    if ($this->processCompensationOnISP($compensation, $user, $objUserActiveService, $isISPAutoExtend)) {
                                        $isISPExtend = true;
                                    }
                                }
                            }
                        }
                    }

                    if (isset($isIPTVExtend) && $isIPTVExtend == true) {
                        $compensation->setIptvDays($compensation->getIspHours());
                    }
                    $compHours = $compensation->getIspHours();

                    if (isset($isISPExtend) && $isISPExtend == false) {
                        $compensation->setIspHours(0);
                    }
                    $em->persist($compensation);
                    $em->flush();

                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Add Compensation for user';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new " . $compHours . " Hour(s) compensation for user: " . $user->getUsername();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    if ((isset($isIPTVExtend) && $isIPTVExtend == true) || (isset($isISPExtend) && $isISPExtend == true)) {
                        $this->get('session')->getFlashBag()->add('success', "Compensation added successfully!");
                    }else{
                        $this->get('session')->getFlashBag()->add('failure', "Compensation could not be added! Please try again");
                    }

                    return $this->redirect($this->generateUrl('dhi_admin_user_list'));
                }
            }

            return $this->render('DhiAdminBundle:User:newCompensation.html.twig', array(
                'form'   => $form->createView(),
                'result' => 'success',
                'id'     => $id
            ));
        } else {
            return $this->render('DhiAdminBundle:User:newCompensation.html.twig', array(
                'form'   => null,
                'result' => 'error',
                'msg'    => 'This user has not logged in yet.',
                'id'     => $id
            ));
        }
    }

    public function processCompensationOnIPTV($objCompensation, $objCustomer, $objUserActiveService, $autoExtend = true) {
        $em = $this->getDoctrine()->getManager();
        if ($objCompensation && $objCustomer && $objUserActiveService) {
            $totalIPTVExtendHours = 0;
            if ($objCompensation->getIptvDays()) {
                $totalIPTVExtendHours = $objCompensation->getIptvDays();
            } else {
                if ($autoExtend) {
                    $totalIPTVExtendHours = $objCompensation->getIspHours();
                }
            }

            if ($totalIPTVExtendHours) {
                $compensationStatus = 'Failure';
                $apiError = '';
                $compensationStatus = 'Success';

                //add compensation of expiry date code start
                $expiryDate = $objUserActiveService->getExpiryDate()->format('Y-m-d H:i:s');

                $currentExpiryDate = new \DateTime($expiryDate);
                $newExpiryDate = $currentExpiryDate->modify('+' . $totalIPTVExtendHours . ' HOURS');
                $objUserActiveService->setExpiryDate($newExpiryDate);
                $activationDate = $objUserActiveService->getActivationDate();
                $intervalRemainDays = $activationDate->diff($newExpiryDate);
                $validityDays = $intervalRemainDays->format('%a');
                $objUserActiveService->setValidity($validityDays);
                $em->persist($objUserActiveService);
                $em->flush();
                //end code of compensation

                $objCompensation->setIptvDays($totalIPTVExtendHours);
                $purchaseOrder = null;
                if ($compensationStatus == 'Success') {
                    $purchaseOrder = $this->addInPurchaseHistory($objCompensation, $objCustomer, $objUserActiveService);
                }

                //Add Customer Compensation Log
                $this->storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer, $objUserActiveService, $purchaseOrder);

                if ($compensationStatus == 'Success') {
                    return true;
                }
            }
        }
        return false;
    }

    public function processCompensationOnISP($objCompensation, $objCustomer, $objUserActiveService, $autoExtend = false) {
        $em = $this->getDoctrine()->getManager();
        if ($objCompensation && $objCustomer && $objUserActiveService) {
            $totalISPExtendHours = 0;
            if ($objCompensation->getIspHours()) {
                $totalISPExtendHours = $objCompensation->getIspHours();
            } else {
                if ($autoExtend) {
                    $totalISPExtendHours = $objCompensation->getIptvDays();
                }
            }

            //add hours into expiry date
            $expiryDate = $objUserActiveService->getExpiryDate()->format('Y-m-d H:i:s');
            $currentExpiryDate = new \DateTime($expiryDate);
            $newExpiryDate = $currentExpiryDate->modify('+' . $totalISPExtendHours . ' HOURS');
            $compensationStatus = 'Failure';
            $apiError = '';
            $aradialResponse['status'] = 0;

            $responseUserExits = $this->get('aradial')->checkUserExistsInAradial($objCustomer->getUsername());
            if ($responseUserExits['status'] == 1) {
                $wsParam = array();
                $wsParam['Page'] = "UserEdit";
                $wsParam['Modify'] = 1;
                $wsParam['UserID'] = $objCustomer->getUsername();
                $wsParam['db_$D$Users.UserExpiryDate'] = $newExpiryDate->format('m/d/Y H:i:s');
                $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
            }

            if ($aradialResponse['status'] == 1) {
                $servicePurchase = $objUserActiveService->getServicePurchase();
                $validityType = 'DAYS';
                if ($servicePurchase) {
                    $validityType = $servicePurchase->getValidityType();
                }
                $activationDate = $objUserActiveService->getActivationDate();
                $interval = $activationDate->diff($newExpiryDate);
                if ($validityType == "HOURS") {
                    $days = $interval->format('%h');
                }else{
                    $days = $interval->format('%a');
                }

                $objUserActiveService->setExpiryDate($newExpiryDate);
                $objUserActiveService->setValidity($days);
                $em->persist($objUserActiveService);
                $em->flush();
                //end code of compensation

                $objCompensation->setIspHours($totalISPExtendHours);
                $compensationStatus = 'Success';
            }

            $purchaseOrder = null;
            if ($compensationStatus == 'Success') {
                $purchaseOrder = $this->addInPurchaseHistory($objCompensation, $objCustomer, $objUserActiveService);
            }

            //Add Customer Compensation Log
            $this->storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer, $objUserActiveService, $purchaseOrder);
            if($compensationStatus == 'Success'){
                return true;
            }
        }
        return false;
    }

    public function storeCompensationLog($compensationStatus, $apiError, $objCompensation, $objCustomer, $objUserActiveService, $purchaseOrder = null) {
        $em = $this->getDoctrine()->getManager();
        if ($objCompensation && $objCustomer && $objUserActiveService->getService()) {
            $objService = $objUserActiveService->getService();
            $bonus = '';
            if (strtoupper($objService->getName()) == 'IPTV') {
                $bonus = $objCompensation->getIptvDays();
            }
            if (strtoupper($objService->getName()) == 'ISP') {
                $bonus = $objCompensation->getIspHours();
            }

            $objCustomerCompensationLog = new CustomerCompensationLog();
            $objCustomerCompensationLog->setUser($objCustomer);
            $objCustomerCompensationLog->setBonus($bonus);
            $objCustomerCompensationLog->setServices($objService);
            $objCustomerCompensationLog->setStatus($compensationStatus);
            $objCustomerCompensationLog->setCompensation($objCompensation);
            $objCustomerCompensationLog->setApiError($apiError);
            $objCustomerCompensationLog->setUserService($objUserActiveService);
            $em->persist($objCustomerCompensationLog);

            // Compensation user service log
            $compensationUserService = new CompensationUserService();
            $compensationUserService->setCompensation($objCompensation);
            $compensationUserService->setUserService($objUserActiveService);
            if (!empty($purchaseOrder) && is_object($purchaseOrder)) {
                $compensationUserService->setPurchaseOrder($purchaseOrder);
                $compensationUserService->setStatus(1);
            }else{
                $compensationUserService->setStatus(0);
            }
            $em->persist($compensationUserService);

            $em->flush();
            return true;
        }
        return false;
    }

    public function addInPurchaseHistory($objCompensation, $objCustomer, $objUserActiveService) {
        $em = $this->getDoctrine()->getManager();
        $orderNumber = $this->generateOrderNumber();

        if ($objCompensation && $objCustomer && $objUserActiveService) {
            $compensationValidity = '';
            if ($objUserActiveService->getService()) {
                if (strtoupper($objUserActiveService->getService()->getName()) == 'IPTV') {
                    $compensationValidity = $objCompensation->getIptvDays();
                }
                if (strtoupper($objUserActiveService->getService()->getName()) == 'ISP') {
                    $compensationValidity = $objCompensation->getIspHours();
                }
            }
            $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('Compensation');

            //Save paypal response in PaypalExpressCheckOutCustomer table
            $objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);
            if (!$objPurchaseOrder) {
                $objPurchaseOrder = new PurchaseOrder();
            }

            $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
            $objPurchaseOrder->setSessionId('');
            $objPurchaseOrder->setOrderNumber($orderNumber);
            $objPurchaseOrder->setUser($objCustomer);
            $objPurchaseOrder->setTotalAmount(0);
            $objPurchaseOrder->setPaymentStatus('Completed');
            $objPurchaseOrder->setCompensationValidity($compensationValidity);

            $em->persist($objPurchaseOrder);
            $em->flush();
            $insertIdPurchaseOrder = $objPurchaseOrder->getId();

            if ($insertIdPurchaseOrder) {
                $objServicePurchase = new ServicePurchase();
                $objServicePurchase->setService($objUserActiveService->getService());
                $objServicePurchase->setUser($objCustomer);
                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                $objServicePurchase->setPackageId($objUserActiveService->getPackageId());
                $objServicePurchase->setPackageName($objUserActiveService->getPackageName());
                $objServicePurchase->setActualAmount(0);
                $objServicePurchase->setPayableAmount(0);
                $objServicePurchase->setPaymentStatus('Completed');
                $objServicePurchase->setRechargeStatus(1);
                $objServicePurchase->setSessionId('');
                $objServicePurchase->setIsUpgrade(0);
                $objServicePurchase->setIsAddon(0);
                $objServicePurchase->setTermsUse(1);
                $objServicePurchase->setIsCompensation(1);

                $em->persist($objServicePurchase);
                $em->flush();

                return $objPurchaseOrder;
            }
        }
        return false;
    }

    public function generateOrderNumber() {

        $today = date("Ymd");
        $rand = strtoupper(substr(uniqid(sha1(time())), 0, 4));
        return $today . $rand;
    }

    public function convertDaysIntoHours($days) {

        $totalHours = 0;
        if ($days) {

            $totalHours = ceil($days * 24);
        }

        return $totalHours;
    }

    public function convertHoursIntoDays($hours) {

        $totalDays = 0;
        if ($hours) {

            $totalDays = ceil($hours / 24);
        }

        return $totalDays;
    }

    public function updateServiceLocationAction(Request $request) {
        $result     = array('status' => '', 'msg' => '');
        $em         = $this->getDoctrine()->getManager();
        $admin      = $this->get('security.context')->getToken()->getUser();
        $userId     = $request->get('userId');
        $locationId = $request->get('id');
        $adminIp    = $this->get('session')->get('ipAddress');
        if ((isset($locationId) and $locationId > 0) and ( isset($userId) and $userId > 0)) {
            $user = $em->getRepository('DhiUserBundle:User')->find($userId);
            if ($user) {
                $result = $em->getRepository('DhiAdminBundle:IpAddressZone')->getAdminPurchaseLocation($adminIp, $locationId);
                if (!empty($result['data'])) {

                    if ($result['data']->getServiceLocation()) {

                        $serviceLocation = $result['data']->getServiceLocation();
                        $country = ($serviceLocation->getCountry()) ? $serviceLocation->getCountry() : NULL;

                        $user->setGeoLocationCountry($country);
                        $user->setUserServiceLocation($serviceLocation);
                        $user->setIpAddress($result['ipAddress']);
                        $user->setIpAddressLong(ip2long($result['ipAddress']));
                        $em->persist($user);
                        $em->flush();

                        $activityLog = array();
                        $activityLog['admin'] = $admin->getUsername();
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = 'Admin Customer Service Location Changed';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed Service Location: '" . $serviceLocation->getName() . "' for User: " . $user->getUsername();
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $result['status'] = 'success';
                        $result['msg'] = 'Service location has been updated successfully';
                    }else{
                        $result['status'] = 'failure';
                        $result['msg'] = 'Serice location does not exists';
                    }
                }else{
                    $result['status'] = 'failure';
                    $result['msg'] = 'Serice location does not exists';
                }
            } else {
                $result['status'] = 'failure';
                $result['msg'] = 'User does not exists';
            }
        } else {
            $result['status'] = 'failure';
            $result['msg'] = 'Invalid Page Request';
        }

        $this->get('session')->getFlashBag()->add($result['status'], $result['msg']);
    
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function SupportPlanChangeAction(Request $request) {
        $userId = $request->get("id");
        $response = array("status" => "", "msg" => "");
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('user_plan_expire_by_customer_support')) {
            $response['status'] = "failure";
            $response['msg'] = "You are not allowed to change the active plan.";
        } else {
            if (!empty($userId)) {
                $user = $em->getRepository("DhiUserBundle:User")->find($userId);
                if ($user) {
                    $activeServices = $em->getRepository("DhiUserBundle:UserService")->findBy(array("user" => $user, "status" => 1));
                    $aradial = $this->get('aradial');
                    $selevision = $this->get('selevisionService');
                    if ($activeServices) {
                        $isPlanExpired = false;
                        $expiredPackages = array();
                        $failedBundleService = array();

                        foreach ($activeServices as $userService) {
                            $service = $userService->getService();
                            $serviceName = $service->getName();
                            $isPackageExpired = false;
                            $servicePurchase = $userService->getServicePurchase();

                            if ($serviceName == 'IPTV' && !in_array("ISP", $failedBundleService)) {

                                // Active plans for IPTV
                                $wsOfferParam = array();
                                $wsOfferParam['cuLogin'] = $user->getUserName();
                                $wsOfferParam['offer'] = $userService->getPackageId();
                                $wsRes = $selevision->callWSAction('unsetCustomerOffer', $wsOfferParam);

                                if ($wsRes['status'] == 1) {
                                    $isPackageExpired = true;
                                    if ($servicePurchase->getPurchaseType() == "BUNDLE") {
                                        $expiredPackages['IPTV'] = $wsOfferParam;
                                        $expiredPackages['IPTV']['userServiceId'] = $userService->getId();
                                    }
                                } else {

                                    if ($servicePurchase->getPurchaseType() == "BUNDLE") {
                                        $failedBundleService[] = 'IPTV';
                                    }

                                    if (!empty($expiredPackages['ISP'])) {

                                        $ispServices = $em->getRepository("DhiUserBundle:UserService")->find($expiredPackages['ISP']['userServiceId']);
                                        if ($ispServices) {

                                            // Recreate ISP plan
                                            $extraParam = array();
                                            $extraParam['db_$N$Users.Offer'] = $ispServices->getPackageId();
                                            $extraParam['db_$D$Users.StartDate'] = $ispServices->getActivationDate()->format('m/d/Y H:i:s');
                                            $extraParam['db_$D$Users.UserExpiryDate'] = $ispServices->getExpiryDate()->format('m/d/Y H:i:s');
                                            $extraParam['charge'] = $ispServices->getActualAmount();
                                            $aradialResponse = $aradial->createUser($user, $extraParam, 0);
                                            if ($aradialResponse == 1) {
                                                // Update user services
                                                $ispServices->setIsExpired(0);
                                                $ispServices->setStatus(1);
                                                $ispServices->setExpiredBy(null);
                                                $ispServices->setExpiredAt(null);
                                                $em->persist($ispServices);

                                                // Update purchase order
                                                $purchaseOrder = $ispServices->getPurchaseOrder();
                                                if ($purchaseOrder) {
                                                    $purchaseOrder->setPaymentStatus('Completed');
                                                    $em->persist($purchaseOrder);
                                                }

                                                // Update service purchase data
                                                if ($servicePurchase) {
                                                    $servicePurchase->setPaymentStatus('Completed');
                                                    $servicePurchase->setRechargeStatus(1);
                                                    $em->persist($servicePurchase);
                                                }
                                                $em->flush();
                                                unset($expiredPackages['ISP']);
                                            }
                                        }
                                    }
                                }
                            } else if ($serviceName == 'ISP' && !in_array("IPTV", $failedBundleService)) {
                                // Check active services for ISP
                                if ($aradial->checkUserExistsInAradial($user->getUserName())) {
                                    $wsParam = array();
                                    $wsParam['Page'] = 'UserSessions';
                                    $wsParam['SessionsMode'] = 'UsrAllSessions';
                                    $wsParam['qdb_Users.UserID'] = $user->getUserName();

                                    $wsParam['op_$D$AcctSessionTime'] = "<>";
                                    $wsParam['qdb_$D$AcctSessionTime'] = " ";

                                    $wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);
                                    if (!empty($wsResponse['userSession'])) {
                                        foreach ($wsResponse['userSession'] as $userSession) {
                                            $userName = $userSession['UserID'];
                                            $nasName = $userSession['NASName'];
                                            $startTime = $userSession['InTime'];
                                            $stopTime = $userSession['TimeOnline'];
                                            $framedAddress = $userSession['FramedAddress'];
                                            $callerId = $userSession['CallerId'];
                                            $calledId = $userSession['CalledId'];
                                            $acctSessionTime = $userSession['AcctSessionTime'];
                                            $isRefunded = 1;

                                            if (!empty($user)) {
                                                $email = $user->getEmail();
                                            } else {
                                                $email = '';
                                            }

                                            $Param = array();
                                            $Param['Page'] = 'UserHit';
                                            $Param['qdb_Users.UserID'] = $userName;
                                            if (empty($email)) {
                                                $wsResponse1 = $aradial->callWSAction('getUserList', $Param);
                                                if (!empty($wsResponse1['userList'])) {
                                                    $email = !empty($wsResponse1['userList'][0]['UserDetails.Email']) ? $wsResponse1['userList'][0]['UserDetails.Email'] : '';
                                                }
                                            }

                                            if (!empty($acctSessionTime)) {
                                                $objUserSession = new UserSessionHistory();
                                                $objUserSession->setUserName($userName);
                                                $objUserSession->setEmail($email);
                                                $objUserSession->setNasName($nasName);
                                                $objUserSession->setStartTime($startTime);
                                                $objUserSession->setStopTime($stopTime);
                                                $objUserSession->setCallerId($callerId);
                                                $objUserSession->setCalledId($calledId);
                                                $objUserSession->setFramedAddress($framedAddress);
                                                $objUserSession->setIsRefunded($isRefunded);
                                                if (!empty($startTime)) {
                                                    $objUserSession->setStartDateTime(new \DateTime($startTime));
                                                }
                                                if (!empty($stopTime) && !empty($startTime)) {
                                                    list($hours, $minutes, $seconds) = sscanf($stopTime, '%d:%d:%d');
                                                    $sTime = new \DateTime($startTime);
                                                    $objStopTime = clone $sTime;
                                                    $seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                                                    $objStopTime->modify('+'.$seconds.' seconds');
                                                    $objUserSession->setStopDateTime($objStopTime);
                                                }
                                                $em->persist($objUserSession);
                                                $em->flush();
                                            }
                                        }
                                    }

                                    // Delete existing user from aradial
                                    $cancelingUsrParam = array();
                                    $cancelingUsrParam['Page'] = "UserEdit";
                                    $cancelingUsrParam['UserId'] = $user->getUserName();
                                    $cancelingUsrParam['ConfirmDelete'] = 1;
                                    $cancelUsrResponse = $aradial->callWSAction('cancelUser', $cancelingUsrParam);

                                    if ($cancelUsrResponse['status'] == 1) {
                                        $isPackageExpired = true;
                                        if ($servicePurchase->getPurchaseType() == "BUNDLE") {
                                            $expiredPackages['ISP']['userServiceId'] = $userService->getId();
                                        }
                                    } else {

                                        if ($servicePurchase->getPurchaseType() == "BUNDLE") {
                                            $failedBundleService[] = 'ISP';
                                        }

                                        if (!empty($expiredPackages['IPTV'])) {
                                            $iptvServices = $em->getRepository("DhiUserBundle:UserService")->find($expiredPackages['IPTV']['userServiceId']);
                                            if ($iptvServices) {

                                                // Recreate IPTV plan
                                                $wsOfferParam = array();
                                                $wsOfferParam['cuLogin'] = $expiredPackages['IPTV']['cuLogin'];
                                                $wsOfferParam['offer'] = $expiredPackages['IPTV']['offer'];
                                                $wsRes = $selevisionService->callWSAction('setCustomerOffer', $wsOfferParam);
                                                if ($wsRes == 1) {

                                                    // Update user services
                                                    $iptvServices->setIsExpired(0);
                                                    $iptvServices->setStatus(1);
                                                    $iptvServices->setExpiredBy(null);
                                                    $iptvServices->setExpiredAt(null);
                                                    $em->persist($iptvServices);

                                                    // Update purchase order
                                                    $purchaseOrder = $iptvServices->getPurchaseOrder();
                                                    if ($purchaseOrder) {
                                                        $purchaseOrder->setPaymentStatus('Completed');
                                                        $em->persist($purchaseOrder);
                                                    }

                                                    // Update service purchase data
                                                    if ($servicePurchase) {
                                                        $servicePurchase->setPaymentStatus('Completed');
                                                        $servicePurchase->setRechargeStatus(1);
                                                        $em->persist($servicePurchase);
                                                    }
                                                    $em->flush();
                                                    unset($expiredPackages['IPTV']);
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            if ($isPackageExpired) {
                                $userService->setIsExpired(1);
                                $userService->setStatus(0);
                                $userService->setExpiredBy($admin);
                                $userService->setExpiredAt(new \DateTime());
                                $em->persist($userService);

                                $purchaseOrder = $userService->getPurchaseOrder();
                                if ($purchaseOrder) {
                                    $purchaseOrder->setPaymentStatus('Expired');
                                    $em->persist($purchaseOrder);
                                }

                                //Update service purchase data
                                if ($servicePurchase) {
                                    $servicePurchase->setPaymentStatus('Expired');
                                    $servicePurchase->setRechargeStatus(2);
                                    $em->persist($servicePurchase);
                                }
                                $em->flush();
                                $isPlanExpired = true;
                            }
                        }
                        if ($isPlanExpired && empty($failedBundleService)) {
                            $response['status'] = "success";
                            $response['msg'] = "All packages has been expired successfully.";
                        } else {
                            $response['status'] = "failure";
                            $response['msg'] = "Can not expire one or more active services.";
                        }
                    } else {
                        $response['status'] = "failure";
                        $response['msg'] = "Active services does not exists.";
                    }
                } else {
                    $response['status'] = "failure";
                    $response['msg'] = "User does not exists.";
                }
            } else {
                $response['status'] = "failure";
                $response['msg'] = "Invalid Request.";
            }
        }
        $this->get('session')->getFlashBag()->add($response['status'], $response['msg']);
        $finalResponse = new Response(json_encode($response));
        $finalResponse->headers->set('Content-Type', 'application/json');
        return $finalResponse;
    }

    public function redeemPromoCodeAction(Request $request) {
        $userId = $request->get("user");
        $promocode = $request->get("promoCode");
        $promoAction = $request->get("promoAction");
        $response = array("status" => "", "message" => "");
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $promoCodeFound = false;
        $date = new \DateTime();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        if (!empty($promocode) && !empty($userId)) {
            $user = $em->getRepository("DhiUserBundle:User")->find($userId);
            if ($user) {
                $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated('', $user->getId());

                // Check customer promocode
                $objPackagePromoCode = $em->getRepository('DhiUserBundle:PromoCode')->getPackagePromoData($promocode);
                if ($objPackagePromoCode) {
                    $objPackage = $objPackagePromoCode;
                    $objPromoCode = $objPackage[0];

                    $isError = false;
                    if (((!empty($objPackage['isDeers']) && $objPackage['isDeers'] == 1) || (!empty($objPackage['ispDeers']) && $objPackage['ispDeers'] == 1) || (!empty($objPackage['iptvDeers']) && $objPackage['iptvDeers'] == 1)) && $isDeersAuthenticated == 2) {
                        $response['status'] = 'error';
                        $response['message'] = 'DEERS authentication required.';
                        $isError = true;
                    }

                    if ($isError == false && $objPromoCode->getIsPlanExpired() == 'Yes') {
                        $response['status']  = 'error';
                        $response['message'] = 'Please enter valid promo code';
                        $isError = true;
                    }

                    // check promo code exist in service location
                    if ($isError == false && $objPromoCode->getServiceLocations()) {
                        if ($objPromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $response['status'] = 'error';
                            $response['message'] = 'Promo code <b>' . $promocode . '</b> is not available in service location';
                            $isError = true;
                        }
                    }

                    if ($isError == false && $date >= $objPromoCode->getExpiredAt()) {
                        $response['status'] = 'error';
                        $response['message'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                        $isError = true;
                    }

                    $noOfRedemption = $objPromoCode->getNoOfRedemption();
                    if ($isError == false && $noOfRedemption >= 1) {
                        $response['status'] = 'error';
                        $response['message'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed.';
                        $isError = true;
                    }

                    if ($objPromoCode->getIsBundle()) {
                        if (empty($objPackage['bundle_id'])) {
                            $response['status'] = 'error';
                            $response['message'] = 'Please enter valid promo code';
                            $isError = true;
                        }
                    } else {
                        if ((isset($objPackage['isExpired']) && $objPackage['isExpired'] == 1) || (empty($objPackage['packageId']))) {
                            $response['status'] = 'error';
                            $response['message'] = 'Please enter valid promo code';
                            $isError = true;
                        }
                    }

                    if ($isError == false) {
                        if($promoAction == 'redeem')
                        {
                            $activityLog = array();
                            $activityLog['admin'] = $admin->getUsername();
                            $activityLog['user'] = $user->getUsername();
                            $activityLog['activity'] = 'Admin Customer Promocode Reedemed';
                            $activityLog['description'] = "Admin " . $admin->getUsername() . " has redeemed customer promo code: '" . $promocode . "' for User: " . $user->getUsername();
                            $this->get('ActivityLog')->saveActivityLog($activityLog);

                            if ($objPromoCode->getIsBundle()) {
                                $response['packageName'] = $objPackage['displayBundleName'];
                                $response['description'] = $objPackage['bundleDesc'];
                            } else {
                                $response['packageName'] = $objPackage['packageName'];
                                $response['description'] = $objPackage['description'];
                            }
                            $response['promoName'] = $promocode;
                            $response['validity'] = $objPromoCode->getDuration();
                            $response['promoCode'] = 'customer';
                            $response['status'] = 'success';
                            $response['message'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully.';
                        }
                        else if($promoAction == 'apply')
                        {
                            $customerResponse = $this->applyCustomerPromoCode($userId, $promocode);
                            if($customerResponse['result'] == 'success')
                            {
                                $response['status'] = $customerResponse['result'];
                                $response['message'] = $customerResponse['succMsg'];
                            }
                            else
                            {
                                $response['status'] = $customerResponse['result'];
                                $response['message'] = $customerResponse['errMsg'];
                            }
                        }
                    }
                    $promoCodeFound = true;
                } else {
                    $response['status'] = "error";
                    $response['message'] = "Please enter valid promo code.";
                }

                // Check Business promocode
                if ($promoCodeFound == false) {
                    $objPackagePromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getPackagePromoData($promocode);
                    if ($objPackagePromoCode) {
                        $objPackage = $objPackagePromoCode;
                        $objPromoCode = $objPackage[0];

                        $isError = false;
                        if (!empty($objPackage) && (!empty($objPackage['packageId']) || !empty($objPackage['bundle_id']))) {
                            if ($isError == false && $objPromoCode->getIsPlanExpired() == 'Yes') {
                                $response['status']  = 'error';
                                $response['message'] = 'Please enter valid promo code';
                                $isError = true;
                            }

                            if ($isError == false && (!empty($objPackage['isDeers']) || !empty($objPackage['ispDeers']) || !empty($objPackage['iptvDeers']) && $isDeersAuthenticated == 2)) {
                                $response['status'] = 'error';
                                $response['message'] = 'DEERS authentication required.';
                                $isError = true;
                            } else if ($isError == false && $objPromoCode->getServiceLocations()) {
                                if ($objPromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                                    $isError = true;
                                }
                            }

                            if ($objPromoCode->getService()) {
                                $serviceName = $objPromoCode->getService()->getName();
                                if ($isError == false && !in_array($serviceName, $summaryData['AvailableServicesOnLocation'])) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Service is not available in your service location';
                                    $isError = true;
                                }
                            }

                            if ($objPromoCode->getExpirydate() != null) {
                                $expiryDate = $objPromoCode->getExpirydate() ? $objPromoCode->getExpirydate() : null;
                                if (!is_null($expiryDate) && $isError == false && $date >= $expiryDate) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                                    $isError = true;
                                }
                            } else if ($isError == false && $objPromoCode->getIsRedeemed() == 'Yes') {
                                $response['status'] = 'error';
                                $response['message'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed the max number of times for the account';
                                $isError = true;
                            } else if ($isError == false && $objPromoCode->getStatus() == 'Inactive') {
                                $response['status'] = 'error';
                                $response['message'] = 'Please enter valid promo code';
                                $isError = true;
                            }

                            if (!empty($serviceName) && strtoupper($serviceName) != 'BUNDLE') {
                                if ((isset($objPackage['isExpired']) && $objPackage['isExpired'] == 1) || (empty($objPackage['packageId']))) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Please enter valid promo code';
                                    $isError = true;
                                }
                            } else if (!empty($serviceName) && strtoupper($serviceName) == 'BUNDLE') {
                                if (empty($objPackage['bundle_id'])) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Please enter valid promo code';
                                    $isError = true;
                                }
                            }

                            if ($isError == false) {
                                if($promoAction == 'redeem')
                                {
                                    $validity = 0;
                                    if (!empty($serviceName) && strtoupper($serviceName) == 'BUNDLE') {
                                        $response['packageName'] = $objPackage['displayBundleName'];
                                        $response['description'] = $objPackage['bundleDesc'];
                                    } else {
                                        $response['packageName'] = $objPackage['packageName'];
                                        $response['description'] = $objPackage['description'];
                                    }

                                    $activityLog = array();
                                    $activityLog['admin'] = $admin->getUsername();
                                    $activityLog['user'] = $user->getUsername();
                                    $activityLog['activity'] = 'Admin Business Promocode Reedemed';
                                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has redeemed business promo code: '" . $promocode . "' for User: " . $user->getUsername();
                                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                                    $response['promoName'] = $promocode;
                                    $response['validity'] = $objPromoCode->getDuration();
                                    $response['status'] = 'success';
                                    $response['promoCode'] = 'business';
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully';
                                }
                                else if($promoAction == 'apply')
                                {
                                    $businessResponse = $this->applyBusinessPromoCode($userId, $promocode);
                                    if($businessResponse['result'] == 'success')
                                    {
                                        $response['status'] = $businessResponse['result'];
                                        $response['message'] = $businessResponse['succMsg'];
                                    }
                                    else
                                    {
                                        $response['status'] = $businessResponse['result'];
                                        $response['message'] = $businessResponse['errMsg'];
                                    }
                                }
                            }
                            $promoCodeFound = true;
                        } else {
                            $response['status'] = 'error';
                            $response['message'] = 'Package does not exists for this promocode';
                        }
                    }
                }

                // Check Partner promocode
                if ($promoCodeFound == false) {
                    $objPackagePromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $promocode));
                    if ($objPackagePromoCode) {
                        $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $objPackagePromoCode->getPackageId()));
                        $isError = false;
                        if ($objPackage) {

                            if ($isError == false && $objPackagePromoCode->getIsPlanExpired() == 'Yes') {
                                $jsonResponse['status']  = 'error';
                                $jsonResponse['message'] = 'Please enter valid promo code';
                                $isError = true;
                            }

                            $isDeers = $objPackage->getIsDeers();
                            if ($isError == false && (!empty($isDeers) && $isDeers == 1) && $isDeersAuthenticated == 2) {
                                $response['status'] = 'error';
                                $response['message'] = 'DEERS authentication required.';
                                $isError = true;
                            }

                            if ($isError == false && $objPackagePromoCode->getServiceLocations()) {
                                if ($objPackagePromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                                    $isError = true;
                                }
                            }

                            if ($isError == false && !in_array($objPackage->getPackageType(), $summaryData['AvailableServicesOnLocation'])) {
                                $response['status'] = 'error';
                                $response['message'] = 'Service is not available in your service location';
                                $isError = true;
                            }

                            if ($objPackagePromoCode->getExpirydate() != null) {
                                $expiryDate = $objPackagePromoCode->getExpirydate() ? $objPackagePromoCode->getExpirydate()->format('Y-m-d 23:59:59') : null;
                                if (!is_null($expiryDate) && $isError == false && $date->format('Y-m-d H:i:s') > $expiryDate) {
                                    $response['status'] = 'error';
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                                    $isError = true;
                                }
                            }

                            if ($isError == false && $objPackagePromoCode->getIsRedeemed() == 'Yes') {
                                $response['status'] = 'error';
                                $response['message'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed';
                                $isError = true;
                            }

                            if ($isError == false && $objPackagePromoCode->getStatus() == 'Inactive') {
                                $response['status'] = 'error';
                                $response['message'] = 'Please enter valid promo code';
                                $isError = true;
                            }

                            if ($isError == false) {
                                if($promoAction == 'redeem')
                                {
                                    $validity = 0;
                                    $packageValidity = $objPackage->getValidity();
                                    $promoValidity = $objPackagePromoCode->getDuration();

                                    if (!empty($promoValidity)) {
                                        $validity = $promoValidity;
                                    } else {
                                        $validity = ($packageValidity * 24);
                                    }

                                    $activityLog = array();
                                    $activityLog['admin'] = $admin->getUsername();
                                    $activityLog['user'] = $user->getUsername();
                                    $activityLog['activity'] = 'Admin Partner Promocode Reedemed';
                                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has redeemed partner promo code: '" . $promocode . "' for User: " . $user->getUsername();
                                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                                    $response['packageName'] = $objPackage->getPackageName();
                                    $response['description'] = $objPackage->getDescription();
                                    $response['promoName'] = $promocode;
                                    $response['validity'] = $validity;
                                    $response['status'] = "success";
                                    $response['message'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully';
                                }
                                else if($promoAction == 'apply')
                                {
                                    $partnerResponse = $this->applyPartnerPromoCode($userId, $promocode);
                                    if($partnerResponse['result'] == 'success')
                                    {
                                        $response['status'] = $partnerResponse['result'];
                                        $response['message'] = $partnerResponse['succMsg'];
                                    }
                                    else
                                    {
                                        $response['status'] = $partnerResponse['result'];
                                        $response['message'] = $partnerResponse['errMsg'];
                                    }
                                }
                            }
                        }
                    } else {
                        $response['status'] = "error";
                        $response['message'] = "Please enter valid promo code.";
                    }
                }
            } else {
                $response['status'] = "error";
                $response['msg'] = "User does not exists.";
            }
        } else {
            $response['status'] = "error";
            $response['msg'] = "Invalid Request.";
        }

        $finalResponse = new Response(json_encode($response));
        $finalResponse->headers->set('Content-Type', 'application/json');
        return $finalResponse;
    }

    public function applyCustomerPromoCode($userId, $promocode)
    {
        $totalValidaity = '';
        $jsonResponse = array('result' => '', 'succMsg' => '', 'errMsg' => '', 'response' => '');
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
        $ipAddress = $this->get('session')->get('ipAddress');
        $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'promocode'));
        $objPackagePromoCode = $em->getRepository('DhiUserBundle:PromoCode')->getPackagePromoData($promocode);
        $user = $em->getRepository("DhiUserBundle:User")->find($userId);

        if ($objPackagePromoCode) {
            $objPackage = $objPackagePromoCode;
            $objPromoCode = $objPackage[0];
            $service = '';

            $noOfRedemption = $objPromoCode->getNoOfRedemption();
            if ($noOfRedemption >= 1) {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed.';
            }

            if ($jsonResponse['result'] != 'error') {
                if ($objPromoCode->getService()) {
                    $service = $objPromoCode->getService();

                    if ($service->getName() == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    } else if ($service->getName() == 'ISP') {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    } else if ($service->getName() == 'BUNDLE') {

                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }

                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());

                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    }

                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setPaymentMethod($paymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentStatus('InProcess');
                    $objPurchaseOrder->setPaymentBy('Admin');
                    $objPurchaseOrder->setPaymentByUser($admin);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $objPurchaseOrder->setTotalAmount('0');
                    $em->persist($objPurchaseOrder);
                    $promoValidity = $objPromoCode->getDuration();
                    $PromoDays = floor($objPromoCode->getDuration() / 24);

                    if ($PromoDays == 0) {
                        $PromoDays = 1;
                    }

                    if ($service->getName() == 'ISP' || $service->getName() == 'IPTV') {
                        $objServicePurchase = new ServicePurchase();
                        $payableAmount = 0;
                        // $payableAmount = ($PromoDays * $objPackage['amount']) / ($objPackage['validity']);

                        $objServicePurchase->setUser($user);
                        $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                        $objServicePurchase->setService($service);
                        $objServicePurchase->setPackageId($objPromoCode->getPackageId());
                        $objServicePurchase->setPackageName($objPackage['packageName']);
                        $objServicePurchase->setPaymentStatus('Completed');
                        $objServicePurchase->setActualAmount($objPackage['amount']);
                        $objServicePurchase->setSessionId($sessionId);
                        $objServicePurchase->setRechargeStatus('0');
                        $objServicePurchase->setBandwidth($objPackage['bandwidth']);
                        $objServicePurchase->setValidity($PromoDays);
                        $objServicePurchase->setIsAppliedByAdmin(true);
                        $objServicePurchase->setFinalCost($payableAmount);
                        $objServicePurchase->setPayableAmount($payableAmount);
                        $objServicePurchase->setPromoCodeApplied(1);
                        $objUserServiceLocation = ($objPromoCode->getServiceLocations() ? $objPromoCode->getServiceLocations() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                        $objServicePurchase->setServiceLocationId($objUserServiceLocation);

                        $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objPromoCode->getPackageId());
                        if ($wwSite) {
                            $objServicePurchase->setWhiteLabel($wwSite);
                        }

                        $em->persist($objServicePurchase);
                        $em->flush();
                    } else if ($service->getName() == 'BUNDLE') {

                        $objBundlePromoCode = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id' => $objPromoCode->getPackageId()));

                        if ($objBundlePromoCode) {
                            $objServicePurchase = new ServicePurchase();
                            if ($objBundlePromoCode->getIsp()) {
                                $this->get('session')->set('IsISPAvailabledInCart', 1);
                                $ispService = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                                $payableAmount = 0;
                                // $payableAmount = ($PromoDays * $code->getIsp()->getAmount()) / ($code->getIsp()->getValidity());
                                $objServicePurchase->setUser($user);
                                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                                $objServicePurchase->setService($ispService);
                                $objServicePurchase->setPackageId($objBundlePromoCode->getIsp()->getPackageId());
                                $objServicePurchase->setPackageName($objBundlePromoCode->getIsp()->getPackageName());
                                $objServicePurchase->setPaymentStatus('Completed');
                                $objServicePurchase->setActualAmount($objBundlePromoCode->getIspAmount());
                                $objServicePurchase->setSessionId($sessionId);
                                $objServicePurchase->setRechargeStatus('0');
                                $objServicePurchase->setBandwidth($objBundlePromoCode->getIsp()->getBandwidth());
                                $objServicePurchase->setValidity($PromoDays);
                                $objServicePurchase->setFinalCost($payableAmount);
                                $objServicePurchase->setPayableAmount($payableAmount);
                                $objServicePurchase->setIsAppliedByAdmin(true);
                                $objServicePurchase->setPurchaseType('BUNDLE');
                                $objServicePurchase->setBundleId($objPromoCode->getPackageId());
                                $objServicePurchase->setBundleDiscount($objPackage['bundleDiscount']);
                                $objServicePurchase->setBundleName($objPackage['bundleName']);
                                $objServicePurchase->setBundleApplied(1);
                                $objServicePurchase->setPromoCodeApplied(1);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objUserServiceLocation = ($objBundlePromoCode->getIsp()->getServiceLocation() ? $objBundlePromoCode->getIsp()->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);

                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIspAmount() - $payableAmount);

                                $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objBundlePromoCode->getIsp()->getPackageId());
                                if ($wwSite) {
                                    $objServicePurchase->setWhiteLabel($wwSite);
                                }

                                $em->persist($objServicePurchase);
                                $em->flush();
                                $purchasedIsp = $objServicePurchase;
                                $isAradialUserUpdate = true;
                            }

                            if ($objBundlePromoCode->getIptv()) {
                                $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);

                                $this->get('session')->set('IsIPTVAvailabledInCart', 1);

                                $iptvService = $em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');
                                $objServicePurchase = new ServicePurchase();
                                $payableAmount = 0;
                                // $payableAmount = ($PromoDays * $code->getIptv()->getAmount()) / ($code->getIptv()->getValidity());

                                $objServicePurchase->setUser($user);
                                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                                $objServicePurchase->setService($iptvService);
                                $objServicePurchase->setPackageId($objBundlePromoCode->getIptv()->getPackageId());
                                $objServicePurchase->setPackageName($objBundlePromoCode->getIptv()->getPackageName());
                                $objServicePurchase->setPaymentStatus('Completed');
                                $objServicePurchase->setActualAmount($objBundlePromoCode->getIptvAmount());
                                $objServicePurchase->setSessionId($sessionId);
                                $objServicePurchase->setRechargeStatus('0');
                                $objServicePurchase->setBandwidth($objBundlePromoCode->getIptv()->getBandwidth());
                                $objServicePurchase->setValidity($PromoDays);
                                $objServicePurchase->setFinalCost($payableAmount);
                                $objServicePurchase->setPayableAmount($payableAmount);
                                $objServicePurchase->setPurchaseType('BUNDLE');
                                $objServicePurchase->setBundleId($objPromoCode->getPackageId());
                                $objServicePurchase->setBundleDiscount($objPackage['bundleDiscount']);
                                $objServicePurchase->setBundleName($objPackage['bundleName']);
                                $objServicePurchase->setIsAppliedByAdmin(true);
                                $objServicePurchase->setBundleApplied(1);
                                $objServicePurchase->setPromoCodeApplied(1);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objUserServiceLocation = ($objBundlePromoCode->getIptv()->getServiceLocation() ? $objBundlePromoCode->getIptv()->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIptvAmount() - $payableAmount);

                                $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objBundlePromoCode->getIptv()->getPackageId());
                                if ($wwSite) {
                                    $objServicePurchase->setWhiteLabel($wwSite);
                                }

                                $em->persist($objServicePurchase);
                                $em->flush();
                            }

                        }

                    }

                    $insertIdPurchaseOrder = $objPurchaseOrder->getId();

                    $this->get('session')->set('PurchaseOrderId', $insertIdPurchaseOrder);

                    if ($objPromoCode->getService()->getName() == 'ISP') {
                        $this->get('session')->set('isISPOnly', true);
                        $this->get('session')->set('IsISPAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsISPAvailabledInCart', 0);
                    }

                    if ($objPromoCode->getService()->getName() == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        $this->get('session')->set('IsIPTVAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsIPTVAvailabledInCart', 0);
                    }

                    $this->get('session')->set('promoDaysValidity', $promoValidity);

                    $paymentRefundStatus = $this->get('PackageActivation')->activateServicePack($user);

                    if ($this->get("session")->has('PurchaseOrderId')) {
                        $this->get('session')->remove('PurchaseOrderId');
                    }

                    if ($this->get("session")->has('IsISPAvailabledInCart')) {
                        $this->get('session')->remove('IsISPAvailabledInCart');
                    }

                    if ($this->get("session")->has('IsIPTVAvailabledInCart')) {
                        $this->get('session')->remove('IsIPTVAvailabledInCart');
                    }

                    if ($this->get("session")->has('promoDaysValidity')) {
                        $this->get('session')->remove('promoDaysValidity');
                    }

                    if ($this->get("session")->has('isISPOnly')) {
                        $this->get('session')->remove('isISPOnly');
                    }

                    if ($this->get("session")->has('expiredPackage')) {
                        $this->get('session')->remove('expiredPackage');
                    }

                    if ($paymentRefundStatus == 'true') {
                        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($insertIdPurchaseOrder);
                        $purchaseOrder->setPaymentStatus('Refunded');
                        $em->persist($purchaseOrder);
                        $em->flush();
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Valid package not found';
                    }
                    else
                    {
                        $objPromoCustomer = new \Dhi\UserBundle\Entity\PromoCustomer();
                        $objPromoCustomer->setPromoCodeId($objPromoCode);
                        $objPromoCustomer->setUser($user);
                        $objPromoCustomer->setPromoCount(1);
                        $em->persist($objPromoCustomer);

                        $objPromoCode->setNoOfRedemption($objPromoCode->getNoOfRedemption() + 1);
                        $objPromoCode->setStatus(0);
                        $em->persist($objPromoCode);

                        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($insertIdPurchaseOrder);
                        $purchaseOrder->setPaymentStatus('Completed');
                        $em->persist($purchaseOrder);
                        $em->flush();

                        if (isset($isAradialUserUpdate) && isset($purchasedIsp) && $isAradialUserUpdate == true) {
                            $aradial = $this->get("aradial");
                            $userExists = $aradial->getUserInfo($user->getId());
                            if ($userExists) {
                                $service = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                                $inactivePackage = $em->getRepository("DhiUserBundle:UserService")->findOneBy(array("servicePurchase" => $purchasedIsp, 'service' => $service));
                                if ($inactivePackage) {
                                    $planExpiryDate = trim((string) $userExists['PlanExpirationDate']);
                                    $planActivationDate = trim((string) $userExists['PlanActivationDate']);
                                    $activationDate = new \DateTime($planActivationDate);
                                    $expiryDate = new \DateTime($planExpiryDate);
                                    $actualExpiryDateObj = $inactivePackage->getExpiryDate();
                                    $daysInterval = $actualExpiryDateObj->diff($expiryDate);
                                    if (!empty($daysInterval) && $daysInterval->d > 0) {
                                        $isUserUpdated = $aradial->updateUserIsp($user, $activationDate, $actualExpiryDateObj);
                                    }
                                }
                            }
                        }

                        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);
                        ## Tikilive promocode ## 
                        $tikiLivePromoCodeResponse = $this->get('paymentProcess')->redeemTikilivePromoCode($purchasedSummaryData);

                        $objpromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('redeemedBy' => $purchasedSummaryData['UserId'],'purchaseId'=>$purchasedSummaryData['PurchaseOrderId']));
                        $view = array();
                        $view['istikilivepromocode'] = false;
                        if($objpromocode){
                            $view['tikilivepromocode']   = $objpromocode->getPromoCode();

                            $tikiliveMsg   = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'tikilive_promo_code_success_message'));
                            if ($tikiliveMsg) {
                                $view['tikiliveMsg'] = $tikiliveMsg->getValue();
                                $view['tikiliveMsg'] = str_replace("TIKILIVE-PROMO-CODE", $view['tikilivepromocode'], $view['tikiliveMsg']);
                                $view['istikilivepromocode'] = true;
                            }
                        }

                        ## End here ##

                        $this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, false, $view);

                        $activityLog = array();
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = 'Promocode Applied';
                        $activityLog['description'] = "User " . $user->getUsername() . " has applied promo code " . $promocode;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $jsonResponse['result']  = 'success';
                        $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';

                        $this->get('session')->getFlashBag()->add($jsonResponse['result'], $jsonResponse['succMsg']);
                    }
                }
                else
                {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Service not found.';
                }
            }
        }
        else
        {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Package not found.';
        }
        return $jsonResponse;
    }

    public function applyPartnerPromoCode($userId, $promocode)
    {
        $jsonResponse = array('result' => '', 'succMsg' => '', 'errMsg' => '', 'response' => '');
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
        $ipAddress = $this->get('session')->get('ipAddress');
        $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'partner_promocode'));
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $promocode));
        $date = new \DateTime();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $user = $em->getRepository("DhiUserBundle:User")->find($userId);

        if (!$paymentMethod) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Error No: #1003, Something went wrong on server';
        } else {

            if ($objPackagePromoCode) {

                $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $objPackagePromoCode->getPackageId()));
                $isError = false;
                if ($objPackage) {
                    $isDeers = $objPackage->getIsDeers();
                    if ($objPackage->getPackageType()) {
                        $service = $objPackage->getPackageType();
                    }
                    $objService = $em->getRepository("DhiUserBundle:Service")->findOneBy(array('name' => $service));

                    if (!$objService) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Service not found for <b>' . $promocode . '</b> promo code';
                        $isError = true;
                    }

                    if ($service == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    } else if ($service == 'ISP') {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    }

                    $payableAmount = $objPackage->getAmount();
                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setPaymentMethod($paymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentStatus('InProcess');
                    $objPurchaseOrder->setPaymentBy('Admin');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $objPurchaseOrder->setTotalAmount($payableAmount);
                    $em->persist($objPurchaseOrder);

                    $packageValidity = $objPackage->getValidity();
                    $promoValidity = $objPackagePromoCode->getDuration();
                    $ispromoValidity = false;
                    if (!empty($promoValidity)) {
                        $promoValidity = $promoValidity;
                        $ispromoValidity = true;
                    } else {
                        $promoValidity = ($packageValidity * 24);
                    }

                    $PromoDays = floor($promoValidity / 24);
                    if ($PromoDays == 0) {
                        $PromoDays = 1;
                    }

                    $objServicePurchase = new ServicePurchase();
                    $objServicePurchase->setUser($user);
                    $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                    $objServicePurchase->setService($objService);
                    $objServicePurchase->setPackageId($objPackage->getPackageId());
                    $objServicePurchase->setPackageName($objPackage->getPackageName());
                    $objServicePurchase->setPaymentStatus('Completed');
                    $objServicePurchase->setActualAmount($objPackage->getAmount());
                    $objServicePurchase->setSessionId($sessionId);
                    $objServicePurchase->setRechargeStatus('0');
                    $objServicePurchase->setBandwidth($objPackage->getBandwidth());
                    $objServicePurchase->setIsAppliedByAdmin(true);
                    $objServicePurchase->setValidity($PromoDays);
                    $objServicePurchase->setFinalCost($payableAmount);
                    $objServicePurchase->setPayableAmount($payableAmount);
                    $objServicePurchase->setPromoCodeApplied(2);
                    $objServicePurchase->setDiscountedPartnerPromocode($objPackagePromoCode);
                    $objUserServiceLocation = ($objPackage->getServiceLocation() ? $objPackage->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                    $objServicePurchase->setServiceLocationId($objUserServiceLocation);

                    $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objPackage->getPackageId());
                    if ($wwSite) {
                        $objServicePurchase->setWhiteLabel($wwSite);
                    }

                    $em->persist($objServicePurchase);
                    $em->flush();

                    $this->get('session')->set('PurchaseOrderId', $objPurchaseOrder->getId());
                    if ($objService->getName() == 'ISP') {
                        $this->get('session')->set('isISPOnly', true);
                        $this->get('session')->set('IsISPAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsISPAvailabledInCart', 0);
                    }

                    if ($objService->getName() == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        $this->get('session')->set('IsIPTVAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsIPTVAvailabledInCart', 0);
                    }

                    $this->get('session')->set('promoDaysValidity', $promoValidity);
                    $paymentRefundStatus = $this->get('PackageActivation')->activateServicePack($user);

                    if ($this->get("session")->has('PurchaseOrderId')) {
                        $this->get('session')->remove('PurchaseOrderId');
                    }

                    if ($this->get("session")->has('IsISPAvailabledInCart')) {
                        $this->get('session')->remove('IsISPAvailabledInCart');
                    }

                    if ($this->get("session")->has('IsIPTVAvailabledInCart')) {
                        $this->get('session')->remove('IsIPTVAvailabledInCart');
                    }

                    if ($this->get("session")->has('promoDaysValidity')) {
                        $this->get('session')->remove('promoDaysValidity');
                    }

                    if ($this->get("session")->has('isISPOnly')) {
                        $this->get('session')->remove('isISPOnly');
                    }

                    if ($this->get("session")->has('expiredPackage')) {
                        $this->get('session')->remove('expiredPackage');
                    }

                    if ($paymentRefundStatus == 'true') {
                        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($objPurchaseOrder->getId());
                        $purchaseOrder->setPaymentStatus('Refunded');
                        $em->persist($purchaseOrder);
                        $em->flush();
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Valid package not found';
                    } else {

                        $objPackagePromoCode->setIsRedeemed('Yes');
                        $objPackagePromoCode->setRedeemedBy($user->getId());
                        $objPackagePromoCode->setRedeemedDate(new \DateTime(date('Y-m-d H:i:s')));
                        $em->persist($objPackagePromoCode);

                        $objPurchaseOrder->setPaymentStatus('Completed');
                        $em->persist($objPurchaseOrder);
                        $em->flush();

                        if ($service == 'ISP' && $ispromoValidity == true) {
                            $aradial = $this->get("aradial");
                            $userExists = $aradial->getUserInfo($user->getId());
                            if ($userExists) {
                                $service = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                                $inactivePackage = $em->getRepository("DhiUserBundle:UserService")->findOneBy(array("servicePurchase" => $objServicePurchase, 'service' => $objService));
                                if ($inactivePackage) {
                                    $planExpiryDate = trim((string) $userExists['PlanExpirationDate']);
                                    $planActivationDate = trim((string) $userExists['PlanActivationDate']);
                                    $activationDate = new \DateTime($planActivationDate);
                                    $expiryDate = new \DateTime($planExpiryDate);
                                    $actualExpiryDateObj = $inactivePackage->getExpiryDate();
                                    $daysInterval = $actualExpiryDateObj->diff($expiryDate);
                                    if (!empty($daysInterval) && $daysInterval->d > 0) {
                                        $isUserUpdated = $aradial->updateUserIsp($user, $activationDate, $actualExpiryDateObj);
                                    }
                                }
                            }
                        }

                        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);

                        ## Tikilive promocode ## 
                        $tikiLivePromoCodeResponse = $this->get('paymentProcess')->redeemTikilivePromoCode($purchasedSummaryData);

                        $objpromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('redeemedBy' => $purchasedSummaryData['UserId'],'purchaseId'=>$purchasedSummaryData['PurchaseOrderId']));
                        $view = array();
                        $view['istikilivepromocode'] = false;
                        if($objpromocode){
                            $view['tikilivepromocode']   = $objpromocode->getPromoCode();

                            $tikiliveMsg   = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'tikilive_promo_code_success_message'));
                            if ($tikiliveMsg) {
                                $view['tikiliveMsg'] = $tikiliveMsg->getValue();
                                $view['tikiliveMsg'] = str_replace("TIKILIVE-PROMO-CODE", $view['tikilivepromocode'], $view['tikiliveMsg']);
                                $view['istikilivepromocode'] = true;
                            }
                        }

                        ## End here ##

                        $this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, false, $view);

                        $activityLog = array();
                        $activityLog['admin'] = $admin->getUsername();
                        $activityLog['activity'] = 'partner Promocode Applied';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has applied promo code " . $promocode . " to User " . $user->getUsername();
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $jsonResponse['result'] = 'success';
                        $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';
                        $this->get('session')->getFlashBag()->add($jsonResponse['result'], $jsonResponse['succMsg']);
                    }
                }
            }
            else
            {
                $jsonResponse['result'] = 'error';
                $jsonResponse['succMsg'] = 'Please enter valid promocode';
            }
        }
        return $jsonResponse;
    }

    public function applyBusinessPromoCode($userId, $promocode)
    {
        $jsonResponse = array('result' => '', 'succMsg' => '', 'errMsg' => '', 'response' => '');
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $ipAddress = $this->get('session')->get('ipAddress');
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
        $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'business_promocode'));
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getPackagePromoData($promocode);
        $date = new \DateTime();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $user = $em->getRepository("DhiUserBundle:User")->find($userId);
        if (!$paymentMethod) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Error No: #1003, Something went wrong on server';
        }
        else
        {
            if ($objPackagePromoCode) {
                $objPackage = $objPackagePromoCode;
                $objPromoCode = $objPackage[0];
                if($objPromoCode->getService())
                {
                    $objService = $objPromoCode->getService();
                    $service = $objService->getName();

                    if (in_array(strtoupper($service), array('IPTV', 'BUNDLE'))) {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    } else if (in_array(strtoupper($service), array('ISP', 'BUNDLE'))) {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.';
                            $isError = true;
                        }
                    }

                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setPaymentMethod($paymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentStatus('InProcess');
                    $objPurchaseOrder->setPaymentBy('Admin');
                    $objPurchaseOrder->setPaymentByUser($admin);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $promoValidity = $objPromoCode->getDuration();
                    $em->persist($objPurchaseOrder);

                    $ispromoValidity = false;
                    if (!empty($promoValidity)) {
                        $ispromoValidity = true;
                    }

                    $PromoDays = floor($promoValidity / 24);
                    if ($PromoDays == 0) {
                        $PromoDays = 1;
                    }

                    $objUserServiceLocation = ($objPromoCode->getServiceLocations() ? $objPromoCode->getServiceLocations() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));

                    if (in_array(strtoupper($service), array('IPTV', 'ISP'))) {
                        $totalPayableAmount = $payableAmount = $objPackage['amount'];

                        $objServicePurchase = new ServicePurchase();
                        $objServicePurchase->setUser($user);
                        $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                        $objServicePurchase->setService($objService);
                        $objServicePurchase->setPackageId($objPackage['packageId']);
                        $objServicePurchase->setPackageName($objPackage['packageName']);
                        $objServicePurchase->setPaymentStatus('Completed');
                        $objServicePurchase->setActualAmount($objPackage['amount']);
                        $objServicePurchase->setSessionId($sessionId);
                        $objServicePurchase->setRechargeStatus('0');
                        $objServicePurchase->setIsAppliedByAdmin(true);
                        $objServicePurchase->setBandwidth($objPackage['bandwidth']);
                        $objServicePurchase->setValidity($PromoDays);
                        $objServicePurchase->setFinalCost($payableAmount);
                        $objServicePurchase->setPayableAmount($payableAmount);
                        $objServicePurchase->setPromoCodeApplied(3);
                        $objServicePurchase->setDiscountedBusinessPromocode($objPromoCode);
                        $objServicePurchase->setServiceLocationId($objUserServiceLocation);

                        $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objPackage['packageId']);
                        if ($wwSite) {
                            $objServicePurchase->setWhiteLabel($wwSite);
                        }

                        $em->persist($objServicePurchase);
                    } else if ($service == "BUNDLE") {
                        $objBundlePromoCode = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id' => $objPromoCode->getPackageId()));
                        if ($objBundlePromoCode) {

                            if ($objBundlePromoCode->getIsp()) {
                                $objServicePurchase = new ServicePurchase();
                                $totalPayableAmount = $payableAmount = $objBundlePromoCode->getIsp()->getAmount();

                                $ispService = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                                $objServicePurchase->setUser($user);
                                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                                $objServicePurchase->setService($ispService);
                                $objServicePurchase->setPackageId($objBundlePromoCode->getIsp()->getPackageId());
                                $objServicePurchase->setPackageName($objBundlePromoCode->getIsp()->getPackageName());
                                $objServicePurchase->setPaymentStatus('Completed');
                                $objServicePurchase->setActualAmount($objBundlePromoCode->getIspAmount());
                                $objServicePurchase->setSessionId($sessionId);
                                $objServicePurchase->setRechargeStatus('0');
                                $objServicePurchase->setBandwidth($objBundlePromoCode->getIsp()->getBandwidth());
                                $objServicePurchase->setValidity($PromoDays);
                                $objServicePurchase->setFinalCost($payableAmount);
                                $objServicePurchase->setPayableAmount($payableAmount);
                                $objServicePurchase->setPurchaseType('BUNDLE');
                                $objServicePurchase->setBundleId($objPromoCode->getPackageId());
                                $objServicePurchase->setBundleDiscount($objPackage['bundleDiscount']);
                                $objServicePurchase->setBundleName($objPackage['bundleName']);
                                $objServicePurchase->setBundleApplied(1);
                                $objServicePurchase->setPromoCodeApplied(3);
                                $objServicePurchase->setIsAppliedByAdmin(true);
                                $objServicePurchase->setDiscountedBusinessPromocode($objPromoCode);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIspAmount() - $payableAmount);

                                $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objBundlePromoCode->getIsp()->getPackageId());
                                if ($wwSite) {
                                    $objServicePurchase->setWhiteLabel($wwSite);
                                }

                                $em->persist($objServicePurchase);

                                $purchasedIsp = $objServicePurchase;
                                $isAradialUserUpdate = true;
                            }

                            if ($objBundlePromoCode->getIptv()) {
                                $payableAmount = $objBundlePromoCode->getIptv()->getAmount();
                                $totalPayableAmount += $payableAmount;

                                $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                                $iptvService = $em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');
                                $objServicePurchase = new ServicePurchase();
                                $objServicePurchase->setUser($user);
                                $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                                $objServicePurchase->setService($iptvService);
                                $objServicePurchase->setPackageId($objBundlePromoCode->getIptv()->getPackageId());
                                $objServicePurchase->setPackageName($objBundlePromoCode->getIptv()->getPackageName());
                                $objServicePurchase->setPaymentStatus('Completed');
                                $objServicePurchase->setActualAmount($objBundlePromoCode->getIptvAmount());
                                $objServicePurchase->setSessionId($sessionId);
                                $objServicePurchase->setRechargeStatus('0');
                                $objServicePurchase->setBandwidth($objBundlePromoCode->getIptv()->getBandwidth());
                                $objServicePurchase->setValidity($PromoDays);
                                $objServicePurchase->setFinalCost($payableAmount);
                                $objServicePurchase->setPayableAmount($payableAmount);
                                $objServicePurchase->setPurchaseType('BUNDLE');
                                $objServicePurchase->setBundleId($objPromoCode->getPackageId());
                                $objServicePurchase->setBundleDiscount($objPackage['bundleDiscount']);
                                $objServicePurchase->setBundleName($objPackage['bundleName']);
                                $objServicePurchase->setBundleApplied(1);
                                $objServicePurchase->setPromoCodeApplied(3);
                                $objServicePurchase->setIsAppliedByAdmin(true);
                                $objServicePurchase->setDiscountedBusinessPromocode($objPromoCode);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIptvAmount() - $payableAmount);

                                $wwSite = $this->get('DashboardSummary')->getSiteFromPackage($objBundlePromoCode->getIptv()->getPackageId());
                                if ($wwSite) {
                                    $objServicePurchase->setWhiteLabel($wwSite);
                                }

                                $em->persist($objServicePurchase);
                            }
                        }
                    }

                    $objPurchaseOrder->setTotalAmount($totalPayableAmount);
                    $em->persist($objPurchaseOrder);
                    $em->flush();

                    $this->get('session')->set('PurchaseOrderId', $objPurchaseOrder->getId());
                    if ($objService->getName() == 'ISP') {
                        $this->get('session')->set('isISPOnly', true);
                        $this->get('session')->set('IsISPAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsISPAvailabledInCart', 0);
                    }

                    if ($objService->getName() == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        $this->get('session')->set('IsIPTVAvailabledInCart', 1);
                    } else {
                        $this->get('session')->set('IsIPTVAvailabledInCart', 0);
                    }

                    $this->get('session')->set('promoDaysValidity', $promoValidity);
                    $paymentRefundStatus = $this->get('PackageActivation')->activateServicePack($user);

                    if ($this->get("session")->has('PurchaseOrderId')) {
                        $this->get('session')->remove('PurchaseOrderId');
                    }

                    if ($this->get("session")->has('IsISPAvailabledInCart')) {
                        $this->get('session')->remove('IsISPAvailabledInCart');
                    }

                    if ($this->get("session")->has('IsIPTVAvailabledInCart')) {
                        $this->get('session')->remove('IsIPTVAvailabledInCart');
                    }

                    if ($this->get("session")->has('promoDaysValidity')) {
                        $this->get('session')->remove('promoDaysValidity');
                    }

                    if ($this->get("session")->has('isISPOnly')) {
                        $this->get('session')->remove('isISPOnly');
                    }

                    if ($this->get("session")->has('expiredPackage')) {
                        $this->get('session')->remove('expiredPackage');
                    }

                    if ($paymentRefundStatus == true) {
                        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($objPurchaseOrder->getId());
                        $purchaseOrder->setPaymentStatus('Refunded');
                        $em->persist($purchaseOrder);
                        $em->flush();
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Valid Package Not Found';
                    }
                    else
                    {
                        $objPromoCode->setIsRedeemed('Yes');
                        $objPromoCode->setRedeemedBy($user->getId());
                        $objPromoCode->setRedeemedDate(new \DateTime(date('Y-m-d H:i:s')));
                        $em->persist($objPromoCode);

                        $objPurchaseOrder->setPaymentStatus('Completed');
                        $em->persist($objPurchaseOrder);
                        $em->flush();

                        if (isset($isAradialUserUpdate) && $service == 'ISP' && $ispromoValidity == true && $isAradialUserUpdate == true) {
                            $aradial = $this->get("aradial");
                            $userExists = $aradial->getUserInfo($user->getId());
                            if ($userExists) {
                                $service = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                                $inactivePackage = $em->getRepository("DhiUserBundle:UserService")->findOneBy(array("servicePurchase" => $objServicePurchase, 'service' => $objService));
                                if ($inactivePackage) {
                                    $planExpiryDate = trim((string) $userExists['PlanExpirationDate']);
                                    $planActivationDate = trim((string) $userExists['PlanActivationDate']);
                                    $activationDate = new \DateTime($planActivationDate);
                                    $expiryDate = new \DateTime($planExpiryDate);
                                    $actualExpiryDateObj = $inactivePackage->getExpiryDate();
                                    $daysInterval = $actualExpiryDateObj->diff($expiryDate);
                                    if (!empty($daysInterval) && $daysInterval->d > 0) {
                                        $isUserUpdated = $aradial->updateUserIsp($user, $activationDate, $actualExpiryDateObj);
                                    }
                                }
                            }
                        }

                        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);
                        
                        ## Tikilive promocode ## 
                        $tikiLivePromoCodeResponse = $this->get('paymentProcess')->redeemTikilivePromoCode($purchasedSummaryData);

                        $objpromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('redeemedBy' => $purchasedSummaryData['UserId'],'purchaseId'=>$purchasedSummaryData['PurchaseOrderId']));
                        $view = array();
                        $view['istikilivepromocode'] = false;
                        if($objpromocode){
                            $view['tikilivepromocode']   = $objpromocode->getPromoCode();

                            $tikiliveMsg   = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'tikilive_promo_code_success_message'));
                            if ($tikiliveMsg) {
                                $view['tikiliveMsg'] = $tikiliveMsg->getValue();
                                $view['tikiliveMsg'] = str_replace("TIKILIVE-PROMO-CODE", $view['tikilivepromocode'], $view['tikiliveMsg']);
                                $view['istikilivepromocode'] = true;
                            }
                        }

                        ## End here ##

                        $this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, false, $view);

                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        $activityLog['activity'] = 'Business Promocode Applied';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " has applied promo code " . $promocode . " to user " . $user->getUsername();
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        $jsonResponse['result'] = 'success';
                        $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';
                        $this->get('session')->getFlashBag()->add($jsonResponse['result'], $jsonResponse['succMsg']);
                    }
                }
            }
            else
            {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Please enter valid promo code';
            }
        }
        return $jsonResponse;
    }

}
