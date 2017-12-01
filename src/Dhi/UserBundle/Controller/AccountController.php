<?php

namespace Dhi\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Dhi\UserBundle\Form\Type\AccountFormType;
use Dhi\UserBundle\Form\Type\ChangePasswordFormType;
use Dhi\UserBundle\Form\Type\AccountSettingFormType;
use Dhi\UserBundle\Form\Type\AccountTypeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\Service;
use Dhi\UserBundle\Form\Type\UserMacAddressFormType;
use Symfony\Component\HttpFoundation\Cookie;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\UserBundle\Entity\UserAradialPurchaseHistory;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\UserBundle\Entity\UserService;
use Dhi\AdminBundle\Entity\Package;

class AccountController extends Controller {

    public function indexAction(Request $request) {
        // remove affiliate variable after successfull login
        if ($this->get('session')->get('affiliate')) {
            $this->get('session')->remove('affiliate');
        }
               
        $araiableUserStatus = $this->get('session')->get('aradialUserStatus');
        $araiableUserOffer = $this->get('session')->get('aradialUserOffer');
        $araiableUserOfferActive = $this->get('session')->get('aradialUserOfferActive');
        $aradialUserOfferPrice = $this->get('session')->get('aradialUserOfferPrice');
        $araiableUserOfferDescription = $this->get('session')->get('aradialUserOfferDescription');
        $araiableUserOfferExpirationTime = $this->get('session')->get('aradialUserOfferExpirationTime');
        $araiableUserOfferSaleEffectiveDate = $this->get('session')->get('aradialUserOfferSaleEffectiveDate');

        if ($araiableUserStatus == 1 && $araiableUserOfferActive == 1) {
            $singleUsrParam = array();
            $singleUsrParam['Page'] = 'OfferHit';
            $singleUsrParam['qdb_OfferId'] = $araiableUserOffer;
            $offerDetailsXml = $this->get('aradial')->getCurlResponse($singleUsrParam);
            $response = $this->get('aradial')->parseXMLResponse($offerDetailsXml, 'OfferHit');
            $this->get('session')->remove('aradialUserStatus');
            $this->get('session')->remove('aradialUserOffer');
            $user = $this->get('security.context')->getToken()->getUser();
            $username = $user->getUsername();

            // user session history
            $singleUsrParam = array();
            $singleUsrParam['Page'] = 'UserSessions';
            $singleUsrParam['SessionsMode'] = 'UsrAllSessions';
            $singleUsrParam['qdb_Users.UserID'] = $username;
            $singleUsrParam['op_$D$AcctSessionTime'] = "<>";
            $singleUsrParam['qdb_$D$AcctSessionTime'] = " ";

            $offerDetailsXml = $this->get('aradial')->getCurlResponse($singleUsrParam);
            $wsResponse = $this->get('aradial')->parseXMLResponse($offerDetailsXml, 'UserSessions');

            $userSessionData = array();
            if (!empty($wsResponse['userSession'])) {
                foreach ($wsResponse['userSession'] as $userdata) {
                    $userName1 = $userdata['UserID'];
                    if ($userName1 == $username) {
                        $userName = $userdata['UserID'];
                        $nasName = $userdata['NASName'];
                        $startTime = $userdata['InTime'];
                        $stopTime = $userdata['TimeOnline'];
                        $framedAddress = $userdata['FramedAddress'];
                        $callerId = $userdata['CallerId'];
                        $calledId = $userdata['CalledId'];
                        $isRefunded = 0;
                        $em = $this->get('doctrine')->getManager();
                        if (!empty($userName)) {
                            $userData = $em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
                        }
                        if (!empty($userData)) {
                            $email = $userData[0]['email'];
                        } else {
                            $email = '';
                        }

                        if (empty($email)) {
                            $Param                     = array();
                            $Param['Page']             = 'UserHit';
                            $Param['qdb_Users.UserID'] = $userName;
                            $aradial                   = $this->get('aradial');
                            $wsResponse1               = $aradial->callWSAction('getUserList', $Param);
                            if (!empty($wsResponse1['userList'])) {
                                $email = empty($wsResponse1['userList'][0]['UserDetails.Email']) ? '' : $wsResponse1['userList'][0]['UserDetails.Email'];
                            }
                        }

                        // fetching User session history from aradial
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
                            $sTime       = new \DateTime($startTime);
                            $objStopTime = clone $sTime;
                            $seconds     = ($hours * 3600) + ($minutes * 60) + $seconds;
                            $objStopTime->modify('+'.$seconds.' seconds');
                            $objUserSession->setStopDateTime($objStopTime);
                        }
                        $em->persist($objUserSession);
                        $em->flush();
                    }
                }
            }

            // fetching user aradial purchase history from aradial
            $em = $this->get('doctrine')->getManager();
            $objUserAradialPurchaseHistory = new UserAradialPurchaseHistory();
            $objUserAradialPurchaseHistory->setUser($user);
            $objUserAradialPurchaseHistory->setOfferId($araiableUserOffer);
            $objUserAradialPurchaseHistory->setPrice($aradialUserOfferPrice);
            $objUserAradialPurchaseHistory->setDescription($araiableUserOfferDescription);
            $objUserAradialPurchaseHistory->setExpirationTime((integer) $araiableUserOfferExpirationTime);
            $saleExpirationDate = null;
            if ($araiableUserOfferSaleEffectiveDate != ' ') {
                $saleExpirationDate = new \DateTime($araiableUserOfferSaleEffectiveDate);
            }
            $objUserAradialPurchaseHistory->setSaleExpirationDate($saleExpirationDate);
            $em->persist($objUserAradialPurchaseHistory);
            $em->flush();

            // update current user plan
            $this->addPlanForAradialUser($user, $response);

            return $this->render('DhiUserBundle:Registration:new_user_current_plan.html.twig', array(
                        'plandetail' => $response['package'][0])
            );
        } else {
            $em = $this->getDoctrine()->getManager();
            $user = $this->get('security.context')->getToken()->getUser();
            $sessionId = $this->get('session')->get('sessionId');
            $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
            $countPurchasedService = $em->getRepository('DhiUserBundle:UserService')->countUserPurchaseService($user);
            $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackage($user);

            $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
            $emailVerifiedForNextPurchase = $this->get('paymentProcess')->emailVerifiedForNextPurchase();

            // For premium plan
            $packageArr = $this->get('SelevisionPackage')->userLocationPackages();
            $isPlanFound = true;
            if (!empty($packageArr) && !empty($packageArr['PREMIUM'])) {
                $premiumPackage = $packageArr['PREMIUM'];
                foreach ($premiumPackage as $addOnsPackage) {
                    if ($summaryData['IsIPTVAvailabledInCart'] == 0) {
                        $premiumPackagePriceBasedOnIPTV = $addOnsPackage['packagePrice'];
                        if ($summaryData['IsIPTVAvailabledInPurchased'] == 1) {
                            $perDayPrice = $addOnsPackage['packagePrice'] / $addOnsPackage['validity'];
                            $premiumPackagePriceBasedOnIPTV = $perDayPrice * $summaryData['Purchased']['IPTVRemainDays'];
                        }
                    } else {
                        $premiumPackagePriceBasedOnIPTV = 0;
                    }
                    if ($premiumPackagePriceBasedOnIPTV <= 5 && $summaryData['IsIPTVAvailabledInCart'] == 0) {
                        if ($isPlanFound == true) {
                            $isPlanFound = false;
                        }
                    }
                }
            }
            if (($this->get("session")->has('isExtendISP') && $this->get("session")->get('isExtendISP') == 1) ||
            	$this->get("session")->has('isExtendIPTV') && $this->get("session")->get('isExtendIPTV') == 1
            ) {
                $isPlanExtended = true;
            }

            $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'selevision_shutdown_message'));
            if($objSetting){
                if($objSetting->getValue()  !=  "~"){
                       $this->get('session')->getFlashBag()->add('notice', $objSetting->getValue());
                 }
            }

            return $this->render('DhiUserBundle:Account:index.html.twig', array(
                'user'                         => $user,
                'sessionId'                    => $sessionId,
                'summaryData'                  => $summaryData,
                'emailVerifiedForNextPurchase' => $emailVerifiedForNextPurchase,
                'isDeersAuthenticated'         => $isDeersAuthenticated,
                'countPurchasedService'        => $countPurchasedService,
                'expiredPackage'               => $expiredPackage,
                'isPlanFound'                  => $isPlanFound,
                'isPlanExtended'               => (!empty($isPlanExtended) ? $isPlanExtended : 0)
            ));
        }
    }

    public function accountUpdateAction(Request $request, $tab) {
        $user = $this->get('security.context')->getToken()->getUser();

        $oldEmail = $email = $user->getEmail();
        $username = $user->getUsername();
        $wsParam = array();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new AccountFormType(), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);
        $accountSettingForm = $this->createForm(new AccountSettingFormType(), $user);

        if ($request->getMethod() == "POST") {

            $isFormValid = true;
            $form->handleRequest($request);
            $changePasswordForm->handleRequest($request);
            $accountSettingForm->handleRequest($request);

            /* START: add user audit log for update profile */
            $activityLog = array();
            $activityLog['user'] = $user;
            /* END: add user audit log for update profile */

            if ($request->request->has('dhi_user_account_update')) {

                $email = $request->get('dhi_user_account_update')['email']['first'];
                $firstName = $request->get('dhi_user_account_update')['firstname'];
                $lastName = $request->get('dhi_user_account_update')['lastname'];

                if (!preg_match('/^[^\'#"]*$/', $email)) {
                    $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                    $isFormValid = false;
                } else if (!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)) {
                    $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                    $isFormValid = false;
                } else if (!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)) {
                    $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                    $isFormValid = false;
                }

                $tab = 1;
                if ($form->isValid() && $isFormValid) {

                    $activityLog['activity'] = 'Update User Account';

                    if ($oldEmail != $email) {

                        $userManager = $this->get('fos_user.user_manager');
                        $tokenGenerator = $this->get('fos_user.util.token_generator');
                        $token = $tokenGenerator->generateToken();
                        $user->setConfirmationToken($token);
                        $user->setIsEmailVerified(0);
                        $user->setEmailVerificationDate(new DateTime());

                        $userManager->updateUser($user);
                        $session = $this->container->get('session');
                        $whitelabel = $session->get('brand');
                        if($whitelabel){
                            $subject   = "Welcome ".$user->getUsername()." to ".$whitelabel['name']."!";
                            $fromEmail = $whitelabel['fromEmail'];
                            $compnayname = $whitelabel['name'];
                            $compnaydomain = $whitelabel['domain'];
                        } else {
                            $subject         = "Welcome ".$user->getUsername()." to ExchangeVUE!";
                            $fromEmail       = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                            $compnayname     = 'ExchangeVUE';
                            $compnaydomain   = 'exchangevue.com';
                        }

                        $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:resend_email_verification.html.twig', array('user' => $user, 'token' => $token,'companyname'=>$compnayname));

                        $resend_email_verification = \Swift_Message::newInstance()
                                ->setSubject($subject)
                                ->setFrom($fromEmail)
                                ->setTo($user->getEmail())
                                ->setBody($body->getContent())
                                ->setContentType('text/html');

                        $this->container->get('mailer')->send($resend_email_verification);

                        // add description for audit log if email change
                        $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account details. Changed email to ' . $user->getEmail();
                    } else {
                        // add description for audit log update profile
                        $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account details.';
                    }

                    /* START: save record into user audit log for update profile */
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    /* END: save record into user audit log for update profile */

                    $user->setUsername($username);
                    $em->persist($user);
                    $em->flush();
                    $this->get('session')->getFlashBag()->add('success', "Account information updated successfully!");

                    #################### START Selevision API ###########################
                    // check selevision api to check whether customer exist in system
                    // if customer exists, update the details

                    $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

                    // get account details for selevisions account
                    $accountArr = $request->request->get('dhi_user_account_update');

                    if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

                        // call selevisions service to update account
                        $wsParam['cuLogin'] = $user->getUsername();
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
                    ########################## END Selevision API #########################
                    ########################## START Aradial API ###########################
                    //Update profile detail in aradial
                    $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());

                    if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1) {

                        $wsParam = array();
                        $wsParam['Page'] = "UserEdit";
                        $wsParam['Modify'] = 1;
                        $wsParam['UserID'] = $user->getUsername();
                        $wsParam['db_UserDetails.FirstName'] = $accountArr['firstname'];
                        $wsParam['db_UserDetails.LastName'] = $accountArr['lastname'];
                        $wsParam['db_UserDetails.Email'] = $user->getEmail();
                        $wsParam['db_UserDetails.Address1'] = $accountArr['address'];
                        $wsParam['db_UserDetails.City'] = $accountArr['city'];
                        $wsParam['db_$GS$UserDetails.State'] = $accountArr['state'];
                        $wsParam['db_$GS$UserDetails.Country'] = ($user->getCountry()) ? $user->getCountry()->getName() : "";
                        $wsParam['db_UserDetails.Zip'] = $accountArr['zip'];


                        $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
                    } else {

                        if ($aradialUserExist['serviceAvailable'] == 0) {

                            //Store API fail log
                            $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ProfileUpdate', 'Aradial');
                        }
                    }
                    ########################## END Aradial API ###############################
                }
            }

            if ($request->request->has('dhi_user_changepassword')) {

                $tab = 2;

                if ($changePasswordForm->isValid()) {

                    $changePasswordArr = $request->request->get('dhi_user_changepassword');
                    $currentPassword = $changePasswordArr['current_password'];
                    $newPassword = $changePasswordArr['plainPassword']['first'];

                    if ($changePasswordArr['current_password'] != $changePasswordArr['plainPassword']['first']) {

                        $user->setEncryPwd(base64_encode($changePasswordArr['plainPassword']['first']));
                    }

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");

                    /* START: add user audit log for update profile */
                    $activityLog['activity'] = 'Change Password';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated password.';

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    /* END: add user audit log for update profile */

                    ############################### START Selevision API #################################
                    //Check user exits in selevision
                    $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

                    if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

                        $seleVisionCurrrentPwd = $selevisionUserExist['password']; // get plain password for selevisions
                        //Update new password in selevision
                        if (($user->getNewSelevisionUser() || $currentPassword != $newPassword)) {

                            // call selevisions service to update password
                            $wsParam = array();
                            $wsParam['cuLogin'] = $user->getUsername();
                            $wsParam['cuPwd'] = ($user->getNewSelevisionUser()) ? $seleVisionCurrrentPwd : $currentPassword;
                            $wsParam['cuNewPwd1'] = $newPassword;
                            $wsParam['cuNewPwd2'] = $newPassword;

                            $wsResPwd = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);

                            if ($wsResPwd['status'] == 1) {

                                $user->setNewSelevisionUser(0);
                                $em->persist($user);
                                $em->flush();
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
                    //Check user exist in aradial
                    $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());

                    if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1 && $newPassword) {

                        //Update password in aradial
                        $wsParam = array();
                        $wsParam['Page'] = "UserEdit";
                        $wsParam['Modify'] = 1;
                        $wsParam['UserID'] = $user->getUsername();
                        $wsParam['Password'] = $newPassword;

                        $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
                    } else {

                        if ($aradialUserExist['serviceAvailable'] == 0) {

                            //Store API fail log
                            $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ChangePassword', 'Aradial');
                        }
                    }
                    ################################## END Aradial API ########################################
                }
            }
            if ($request->request->has('dhi_user_account_setting')) {

                $tab = 3;
                if ($accountSettingForm->isValid()) {

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    $this->get('session')->getFlashBag()->add('success', "Account settings updated successfully!");

                    /* START: add user audit log for update profile */
                    $activityLog['activity'] = 'Update Account Setting';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated account setting.';

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    /* END: add user audit log for update profile */
                }
            }
        }

        // get user mac address
        $objMacAddress = $em->getRepository("DhiUserBundle:UserMacAddress")->findBy(array('user' => $user));

        if (!$this->get('session')->has('maxMacAddress', 0)) {

            $userMacAddress = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'mac_address'));
            $this->get('session')->set('maxMacAddress', $userMacAddress->getValue());
        }


        return $this->render('DhiUserBundle:Account:accountUpdate.html.twig', array(
                    'form' => $form->createView(),
                    'changePasswordForm' => $changePasswordForm->createView(),
                    'accountSettingForm' => $accountSettingForm->createView(),
                    'tab' => $tab,
                    'userMacAddress' => $objMacAddress
        ));
    }

    public function typeAction(Request $request) {
        $user = $this->get('security.context')->getToken()->getUser();

        $form = $this->createForm(new AccountTypeFormType(), $user);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($request->request->has('dhi_user_account_type_update')) {

                if ($form->isValid()) {

                    $userManager = $this->get('fos_user.user_manager');

                    if ($form->getData()->getUserType() != 'US Military') {

                        $userManager->updateUser($user);
                    } else {

                        $userManager->updateUser($user);
                    }

                    // Activity log user type
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'User type change';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has change type.';
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Account type has been updated successfully!");
                }
            }
        }

        return $this->render('DhiUserBundle:Account:type.html.twig', array('form' => $form->createView()));
    }

    public function ajaxAccountSummaryAction(Request $request, $tab) {
        $view = array();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $emailVerifiedForNextPurchase = $this->get('paymentProcess')->emailVerifiedForNextPurchase();

        if ($this->container->hasParameter('selevision_simulator')) {
            $view['selevisionSimulator'] = $this->container->getParameter('selevision_simulator');
        }else{
            $view['selevisionSimulator'] = false;
        }

        $view['summaryData'] = $summaryData;
        $view['isDeersAuthenticated'] = $isDeersAuthenticated;
        $view['emailVerifiedForNextPurchase'] = $emailVerifiedForNextPurchase;
        if (in_array($tab, array('1', '2'))) {

            if ($request->isXmlHttpRequest()) {

                if ($tab == 1) {

                    return $this->render('DhiUserBundle:Account:ajaxAccountSummaryTabOne.html.twig', $view);
                }

                if ($tab == 2) {

                    return $this->render('DhiUserBundle:Account:ajaxAccountSummaryTabTwo.html.twig', $view);
                }
            }
        } else {
            return 'Something went wrong. Invalid argument passed';
        }
    }

    public function purchaseUserCreditAction(Request $request) {

        $sessionId = $this->get('paymentProcess')->generateCartSessionId();
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        $credits = $em->getRepository('DhiAdminBundle:Credit')->findBy(array('isDeleted' => '0'), array('amount' => 'ASC'));

        if ($request->getMethod() == "POST") {

            $userPurchaseCredit = $request->get('userPurchaseCredit');
            $termsUse = $request->get('termsUse');
            $creditId = $request->get('creditId');

            if ($userPurchaseCredit != "" && $userPurchaseCredit == 1) {

                if ($termsUse != 1) {

                    $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');
                    return $this->redirect($this->generateUrl('dhi_user_credit'));
                } else {

                    if ($creditId != "") {

                        $flagCredit = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);

                        if ($flagCredit) {

                            if ($user->getUserCredit() && $user->getUserCredit()->getTotalCredits() > 0) {
                                $this->get('session')->getFlashBag()->add('success', 'Credit has been updated successfully.');
                            } else {

                                $this->get('session')->getFlashBag()->add('success', 'Credit has been added successfully.');
                            }
                        } else {

                            $this->get('session')->getFlashBag()->add('failure', 'Something went to wrong.');
                        }
                    } else {

                        $this->get('session')->getFlashBag()->add('failure', 'Please select credit.');
                        return $this->redirect($this->generateUrl('dhi_user_credit'));
                    }
                }

                return $this->redirect($this->generateUrl('dhi_user_account'));
            }
        }

        return $this->render('DhiUserBundle:Account:purchaseUserCredit.html.twig', array('summaryData' => $summaryData, 'credits' => $credits));
    }

    public function checkEmailAction(Request $request) {

        $user = $this->get('security.context')->getToken()->getUser();
        $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $request->get('email')));
        $objUseremailusername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $request->get('email')));

        if ($objUser && $objUser->getId() != $user->getId() || $objUseremailusername) {

            $result = 'error';
        } else {

            $result = 'success';
        }

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function checkValidUsernameAction(Request $request) {
        $result = $userName = $objUseremailusername = '';
        if ($request->get('email')) {

            $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $request->get('email')));
            $objemail = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $request->get('email')));
            $objUseremailusername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $request->get('email')));
        } else if ($request->get('username')) {
            $userName = $request->get('username');
            if (!empty($userName)) {
                $objUseremailusername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $request->get('username')));
                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('username' => $userName));
            }
        }else{
            $result = 'error';
        }


        // status for database exists
        if ($result != 'error') {

            if ($objUser || $objUseremailusername) {

            $result = 'error';
        } else {
            if (isset($objemail) && !$objemail) {
                $result = 'sucess';
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');

                return $response;
            }
            $result = 'success';
            // check for aradial api
            // $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($request->get('username'));

               /* if (!empty($userName)) {

            $Param = array();
            $Param['Page'] = 'UserHit';
            $Param['qdb_Users.UserID'] = $request->get('username');

            try {
                $aradial = $this->get('aradial');
                $wsResponse1 = $aradial->callWSAction('getUserList', $Param);
                if (isset($wsResponse1['error'])) {
                    $result = 'error1';
                } else {
                    if (isset($wsResponse1['userList'])) {
                        if (!empty($wsResponse1['userList'])) {
                            if (isset($wsResponse1['userList'][0]['Users.UserExpiryDate'])) {
                                if ($wsResponse1['userList'][0]['Users.UserExpiryDate'] == null) {
                                    $result = 'aradialExist';
                                } else {
                                    $userExpirationDate = (string) $wsResponse1['userList'][0]['Users.UserExpiryDate'];
                                    $date = strtotime($userExpirationDate);
                                    $userExpirationDate = date('U', $date);

                                    $currentdate = date('U');
                                    if ($currentdate < $userExpirationDate) {
                                        $result = 'aradialExist';
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Symfony\Component\Config\Definition\Exception\Exception $ex) {
                $result = $ex;
            }

                    // if (isset($aradialUserExist['entity'])) {
                    //     $result = 'aradialExist';
                    // }
                }*/
            }
        }


        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function ajaxGetCheckoutAction(Request $request){
        $user                         = $this->get('security.context')->getToken()->getUser();
        $em                           = $this->getDoctrine()->getManager();
        $summaryData                  = $this->get('DashboardSummary')->getUserServiceSummary();
        $emailVerifiedForNextPurchase = $this->get('paymentProcess')->emailVerifiedForNextPurchase();
        $isDeersAuthenticated         = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $screen                       = $request->get('screen');

        $checkoutLabel = "Continue";
        if (($this->get("session")->has('isExtendISP') && $this->get("session")->get('isExtendISP') == 1) ||
            $this->get("session")->has('isExtendIPTV') && $this->get("session")->get('isExtendIPTV') == 1
        ) {
            $checkoutLabel = 'Extend';
        }

        $bundlePlanCount = 0;
        if (!empty($summaryData['IsISPAvailabledInCart']) && $summaryData['IsISPAvailabledInCart'] == 1) {
            $ispPackageId    = $summaryData['Cart']['ISP']['RegularPack'][0]['packageId'];
            $condition       = array('ispPackageId' => $ispPackageId, 'serviceLocationId' => $this->get('session')->get('serviceLocationId'));
            $bundlePlanCount = $em->getRepository('DhiAdminBundle:Package')->getBundleFromPlan($condition, 'count');
        }

        if (!empty($summaryData['IsBundleAvailabledInCart']) && $summaryData['IsBundleAvailabledInCart'] == 1) {
            $cartISPPackageId = '';
        }else{
            $cartISPPackageId = '';
        }

        $errorMsg = '';
        if($summaryData['IsIPTVAvailabledInCart'] == 1 && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 1){
            $errorMsg = 'Please select ExchangeVUE Plan To Upgrade The Plan.';
        }

        $view = array(
            'emailVerifiedForNextPurchase'  => $emailVerifiedForNextPurchase,
            'isDeersAuthenticated'          => $isDeersAuthenticated,
            'IsIPTVAvailabledInCart'        => $summaryData['IsIPTVAvailabledInCart'],
            'IsISPAvailabledInCart'         => $summaryData['IsISPAvailabledInCart'],
            'IsBundleAvailabledInCart'      => $summaryData['IsBundleAvailabledInCart'],
            'IsDeersRequiredPlanAdded'      => $summaryData['IsDeersRequiredPlanAdded'],
            'IsCreditAvailabledInCart'      => $summaryData['IsCreditAvailabledInCart'],
            'IsAddOnAvailabledInCart'       => $summaryData['IsAddOnAvailabledInCart'],
            'AvailableServicesOnLocation'   => $summaryData['AvailableServicesOnLocation'],
            'IsIPTVAvailabledInPurchased'   => $summaryData['IsIPTVAvailabledInPurchased'],
            'IsBundleAvailabledInPurchased' => $summaryData['IsBundleAvailabledInPurchased'],
            'checkoutLabel'                 => $checkoutLabel,
            'CartISPPackageId'              => $summaryData['CartISPPackageId'],
            'bundlePlanCount'               => $bundlePlanCount,
            'errorMsg'                      => $errorMsg,
            'screen'                        => !empty($screen) ? $screen : 0
        );
        return $this->render('DhiUserBundle:Account:ajaxCheckout.html.twig', $view);
    }

    public function ajaxGetServicePlanAction(Request $request) {
        $service = strtoupper($request->get('type'));
        $user    = $this->get('security.context')->getToken()->getUser();
        $em      = $this->getDoctrine()->getManager();

        $view         = array();
        $responseJson = array();
        $isError      = false;
        $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary();

        if (empty($service) && in_array('ISP', $summaryData['AvailableServicesOnLocation']) && !in_array('IPTV', $summaryData['AvailableServicesOnLocation'])) {
            $service = 'ISP';
        }else if (empty($service) && in_array('IPTV', $summaryData['AvailableServicesOnLocation']) && !in_array('ISP', $summaryData['AvailableServicesOnLocation'])) {
            $service = 'IPTV';
        }else if (empty($service) && in_array('BUNDLE', $summaryData['AvailableServicesOnLocation']) && in_array('ISP', $summaryData['AvailableServicesOnLocation']) && in_array('IPTV', $summaryData['AvailableServicesOnLocation']) ) {
            $service = 'BUNDLE';
        }

        if ($service == 'ADDONS') {
            $service = 'IPTV';
            $isAddon = true;
        }else{
            $isAddon = false;
        }

        if (in_array($service, array('IPTV', 'ISP', 'BUNDLE'))) {

            if ($request->isXmlHttpRequest()) {

                $discount = $this->get('BundleDiscount')->getBundleDiscount();
                $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

                if (!$this->get('paymentProcess')->emailVerifiedForNextPurchase()) {
                    $responseJson['msg'] = 'You need to verify your email address.';
                    $isError = true;
                }

                if ($service == 'ISP' && $summaryData['IsBundleAvailabledInCart'] == 1) {
                    $responseJson['msg'] = 'Bundle Plan is already exists in cart! Please remove Bundle Plan from cart.';
                    $responseJson['redirect'] = 'screen2';
                    $isError = true;
                }

                if ($service == 'ISP' && $summaryData['IsBundleAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 1 && $summaryData['IsIPTVAvailabledInPurchased'] == 0) {
                    $responseJson['msg'] = 'Invalid package request';
                    $responseJson['redirect'] = 'ISP';
                    $isError = true;
                }

                if ($service == 'IPTV' && $summaryData['IsBundleAvailabledInPurchased'] == 0 && $summaryData['IsISPAvailabledInCart'] == 1) {
                    $responseJson['msg'] = 'Invalid package request';
                    $responseJson['redirect'] = 'ISP';
                    $isError = true;
                }

                if ($service == 'IPTV' && $isAddon == false && (!empty($summaryData['Cart']['ISP']) || (!empty($summaryData['Purchased']['ISP'])))) {
                    $responseJson['msg'] = 'Invalid package request';
                    $isError = true;
                }

                /*if ($service == 'ISP' &&
                    (!empty($summaryData['Purchased']['IPTV']) || (!empty($summaryData['Cart']['IPTV']) && !in_array('BUNDLE', $summaryData['AvailableServicesOnLocation'])))) {
                    $responseJson['msg'] = 'Invalid package request';
                    $isError = true;
                }*/

                //Check IPTV or ISP service available on location
                if (!empty($summaryData['AvailableServicesOnLocation'])) {
                    if (!in_array($service, $summaryData['AvailableServicesOnLocation'])) {
			            $responseJson['msg'] = 'Invalid service plan request.';
                        $isError = true;
                    }
                } else {

                    $responseJson['msg'] = $service . ' not available on your location.';
                    $isError = true;
                }

                if (!$isError) {

                    $myPackages = array();
                    $allPackages = array();
                    $premiumPackage = array();
                    $promotionalPackages = array();
                    $ispValidity = 0;

                    //Get ISP current pack validity
                    if (in_array('ISP', $summaryData['AvailableServicesOnLocation'])) {

                        if ($summaryData['IsISPAvailabledInCart'] == 1) {

                            $ispValidity = $summaryData['Cart']['ISP']['CurrentISPPackvalidity'];
                        } else if ($summaryData['IsISPAvailabledInPurchased'] == 1) {

                            $ispValidity = $summaryData['Purchased']['ISPRemainDays'];
                        }
                    }

                    if (isset($summaryData['isEmployee']) && isset($summaryData['isServiceLocationChanged']) && isset($summaryData['isSiteChanged']) && $summaryData['isEmployee'] == 0 && $summaryData['isServiceLocationChanged'] == 0 && $summaryData['isSiteChanged'] == 0) {
                        $packageArr = $this->get('SelevisionPackage')->userLocationPackages($service);
                    }else{
                        $packageArr = array();
                    }

                    $credits = $em->getRepository('DhiAdminBundle:Credit')->findBy(array('isDeleted' => '0'), array('amount' => 'ASC'));

                    $view['packageColumnArray']   = array('Package', 'Price', 'Number Of Channels');
                    $view['discount']             = ($discount) ? $discount['Precentage'] : '';
                    $view['summaryData']          = $summaryData;
                    $view['credits']              = $credits;
                    $view['service']              = $service;
                    $view['ispValidity']          = $ispValidity;
                    $view['isDeersAuthenticated'] = $isDeersAuthenticated;

                    $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'max_allow_to_show_channels'));
                    $maxShowChannels = 0;
                    if($objSetting){
                        $maxShowChannels = $objSetting->getValue();
                    }

                    if (in_array($service, array('ISP', 'BUNDLE'))) {
                        if (!empty($packageArr['ISP'])) {
                            $allPackages = array();
                            foreach ($packageArr['ISP'] as $package) {
                                $allPackages[(empty($package['isHourlyPlan']) ? $package['validity'] : 'Hourly') ]['packages'][] = $package;
                            }
                            krsort($allPackages);
                        }

                        if (!empty($packageArr) && !empty($packageArr['PROMOTIONAL']['ISP'])) {
                            $promotionalPackages = $packageArr['PROMOTIONAL']['ISP'];
                        }

                        if (!empty($summaryData['IsISPAvailabledInCart']) && !empty($summaryData['Cart']['ISP']['RegularPack'][0]['validity'])) {
                            $plan = $summaryData['Cart']['ISP']['RegularPack'][0];
                            $view['cartPlanValidity'] = strtoupper($plan['validityType']).'-'. (($plan['validityType'] != 'HOURLY') ? $plan['validity'] : '');
                        }else{
                            $view['cartPlanValidity'] = '';
                        }

                        $ispAutoBundleIds = array();
                        if (!empty($packageArr['ISPAUTOBUNDLE'])) {
                            foreach ($packageArr['ISPAUTOBUNDLE'] as $autoBundle)
                            {
                                $ispAutoBundleIds[] = $autoBundle['regularIspPackageId'];
                            }
                            $ispAutoBundleIds = array_unique($ispAutoBundleIds);
                        }

                        $condition       = array(
                            'activeService' => $user->getActivePackages(),
                            'locationId'    => $this->get('session')->get('serviceLocationId')
                        );
                        $premiumPlans    = $this->get('SelevisionPackage')->userLocationPackages("PREMIUM", false);
                        $iptvBundlePlans = $em->getRepository('DhiAdminBundle:Bundle')->getIPTVIds($condition);
                        $arrChannelImage = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannelImage();

                        $tmpValidity         = array_keys($allPackages);
                        $remainingValidities = array_diff(array(1, 7, 30), $tmpValidity);
                        $allValidity         = array_merge($remainingValidities, $tmpValidity);
                        rsort($allValidity);

                        if (empty($view['cartPlanValidity']) && !empty($tmpValidity)) {
                            $view['cartPlanValidity'] = ((strtolower(max($tmpValidity))) == 'hourly' ? 'HOURLY-' : 'DAYS-'.max($tmpValidity));
                        }

                        $view['autoBundleRegularIspIds'] = $ispAutoBundleIds;
                        $view['ChannelImages']           = $arrChannelImage;
                        $view['iptvBundlePlans']         = $iptvBundlePlans;
                        $view['ispAutoBundlePlans']      = (!empty($packageArr['ISPAUTOBUNDLE']) ? $packageArr['ISPAUTOBUNDLE'] : array());
                        $view['premiumPlans']            = (!empty($premiumPlans['PREMIUM']) ? $premiumPlans['PREMIUM'] : array());
                        $view['promotionalPackages']     = $promotionalPackages;
                        $view['allPackages']             = $allPackages;
                        $view['planValidity']            = $tmpValidity;
                        $view['packageValidity']         = $allValidity;
                        $view['maxShowChannels']         = $maxShowChannels;
                        return $this->render('DhiUserBundle:Account:ajaxISPPlan.html.twig', $view);

                    } elseif ($service == 'IPTV') {
                        if (!empty($packageArr['IPTV'])) {
                            $allPackages = $packageArr['IPTV'];
                        }

                        if (!empty($packageArr) && !empty($packageArr['PROMOTIONAL']['IPTV'])) {

                            $promotionalPackages = $packageArr['PROMOTIONAL']['IPTV'];
                        }

                        $isIPTVAvailable = true;
                        if ($summaryData['IsBundleAvailabledInCart'] == 1 or $summaryData['IsBundleAvailabledInPurchased'] == 1) {
                            $isIPTVAvailable = false;
                        }
                        $activeService = $user->getActivePackages();
                        $arrChannelImage = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannelImage();
                        $view['allPackages']         = $allPackages;
                        $view['promotionalPackages'] = $promotionalPackages;
                        $view['totalPackages']       = count($allPackages);
                        $view['isIPTVAvailable']     = $isIPTVAvailable;
                        $view['maxShowChannels']     = $maxShowChannels;
                        $view['activeService']       = $activeService;
                        $view['arrChannelImage']     = $arrChannelImage;
                        return $this->render('DhiUserBundle:Account:ajaxIPTVPlan.html.twig', $view);
                    }
                }
            }
        } else {
            $responseJson['msg'] = 'Error No: #1003, Something went wrong. please try again.';
            $isError = true;
        }

        if ($isError) {
            $response = new Response(json_encode($responseJson));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    public function ajaxGetPromoCodeAction(Request $request) {

        if ($request->isXmlHttpRequest()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->get('security.context')->getToken()->getUser();
            $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackage($user);
            $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
            return $this->render('DhiUserBundle:Account:ajaxDisplayPromoCode.html.twig', array(
                'summaryData'    => $summaryData,
                'expiredPackage' => $expiredPackage
            ));
        } else {
            echo 'Error No: #1003, Something went wrong in ajax request.';
            exit;
        }
    }

    public function checkAradialAuthAction(Request $request) {
        $dataRes = array();
        if ($request->isXmlHttpRequest()) {
            $username = $this->get('request')->request->get('username');
            $password = $this->get('request')->request->get('password');

            $dataRes['result'] = '';

            $aradialUserExist = $this->get('aradial')->checkUserAuthAradial($username, $password);

            if ($aradialUserExist['result']) {
                $dataRes['result'] = 'success';
                $dataRes['status'] = $aradialUserExist['status'];
                $dataRes['status'] = 1;
                $dataRes['offer'] = $aradialUserExist['offer'];
                $dataRes['PricePlanAssignDate'] = $aradialUserExist['PricePlanAssignDate'];

                $singleUsrParam = array();
                $singleUsrParam['Page'] = 'OfferHit';
                $singleUsrParam['qdb_OfferId'] = $dataRes['offer'];
                $offerDetailsXml = $this->get('aradial')->getCurlResponse($singleUsrParam);
                $response = $this->get('aradial')->parseXMLResponse($offerDetailsXml, 'OfferHit');

                // set offer detail in session
                $this->get('session')->set('aradialUserOfferDescription', (string) $response['package']['0']['Description']);
                $this->get('session')->set('aradialUserOfferPrice', (string) $response['package']['0']['Price']);
                $this->get('session')->set('aradialUserOfferExpirationTime', (string) $response['package']['0']['ExpirationTime']);
                $this->get('session')->set('aradialUserOfferSaleEffectiveDate', (string) $response['package']['0']['SaleEffectiveDate']);


                // if current plan expiration time less than 30 days
                $ExpirationDays = $response['package']['0']['ExpirationTime'];
                if ($ExpirationDays < 30) {
                    // checkout userLogIn or not
                    $singleUsrParam = array();
                    $singleUsrParam['Page'] = 'UserSessions';
                    $singleUsrParam['SessionsMode'] = 'UsrAllSessions';
                    $singleUsrParam['qdb_Users.UserID'] = $username;

                    $UserSeesionsDetailsXml = $this->get('aradial')->getCurlResponse($singleUsrParam);
                    $userSeesionsResponse = $this->get('aradial')->parseXMLResponse($offerDetailsXml, 'UserSessions');

                    if (isset($userSeesionsResponse['package'][0]['AcctSessionTime'])) {
                        $this->get('session')->set('aradialUserloggedInStatus', 1);
                    } else {
                        $this->get('session')->set('aradialUserloggedInStatus', 0);
                    }
                } else {
                    $this->get('session')->set('aradialUserloggedInStatus', 1);
                }

                // get expiration  Date
                // $planpurchaseDate = (string) $aradialUserExist['PricePlanAssignDate'];
                // $date = strtotime($planpurchaseDate);
                // $date = strtotime("+" . $response['package']['0']['ExpirationTime'] . " day", $date);
                // $expireddate = date('U', $date);
                // get Current Date
                // $currentdate = date('U');
                // if ($currentdate < $expireddate) {
                //  $this->get('session')->set('aradialUserOffer', (string) $aradialUserExist['offer']);
                //  $this->get('session')->set('aradialUserOfferActive', 1);
                //  $dataRes['ActiveOffer'] = 1;
                // } else {
                //  	$this->get('session')->set('aradialUserOfferActive', 0);
                //   $dataRes['ActiveOffer'] = 0;
                //   $dataRes['FirstName'] = $aradialUserExist['FirstName'];
                //   $dataRes['LastName'] = $aradialUserExist['LastName'];
                // }

                $this->get('session')->set('aradialUserOffer', (string) $aradialUserExist['offer']);
                $this->get('session')->set('aradialUserOfferActive', 1);
                $dataRes['ActiveOffer'] = 1;

                $this->get('session')->set('aradialUserStatus', (string) $dataRes['status']);
            } else {
                $dataRes['result'] = 'error';
            }
        }

        $response = new Response(json_encode($dataRes));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    // ajax request for checking email availablility
    public function checkAradialMailAvailableAction(Request $request) {
        $dataRes = array();
        $dataRes['result'] = 'error';
        // check for when vpn  available


        if ($request->isXmlHttpRequest()) {

            $username = $this->get('request')->request->get('username');
            $emailAradial = $this->get('request')->request->get('emailAradial');

            $aradialUserExist = $this->get('aradial')->checkEmailAvailableAradial($emailAradial);
            $dataRes['resp'] = $aradialUserExist;


            if ($aradialUserExist['result']) {
                $userName = $aradialUserExist['responseArray']->TR->TD[1];
                $userPassword = $aradialUserExist['responseArray']->TR->TD[3];

                $whitelabel = $session->get('brand');
                if($whitelabel){
                    $subject = "Your ".$whitelabel['name']." user details";
                    $fromEmail = $whitelabel['fromEmail'];
                    $fromName  = $whitelabel['name'];
                    $compnayname = $whitelabel['name'];

                } else {
                $subject = "Your DHI user details";
                    $fromEmail       = $this->container->getParameter('purchase_summary_from_email');
                    $compnayname     = 'ExchangeVUE';
                }


                $fromEmail = $this->container->getParameter('support_email_recipient');
                $toEmail = $emailAradial;
                $dataRes['result'] = 'success';

                $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:aradial_lookup_password.html.twig', array('username' => $userName, 'userPassword' => $userPassword,'companyname'=>$compnayname));
                $apiServiceDownMail = \Swift_Message::newInstance()
                        ->setSubject($subject)
                        ->setFrom($fromEmail)
                        ->setTo($toEmail)
                        ->setBody($body->getContent())
                        ->setContentType('text/html');

                $dataRes['reemail'] = $this->container->get('mailer')->send($apiServiceDownMail);

                if ($this->container->get('mailer')->send($apiServiceDownMail)) {
                    $dataRes['result'] = 'success';
                } else {
                    $dataRes['result'] = 'error';
                }
            } else {
                $dataRes['result'] = 'error';
            }
        }


        $response = new Response(json_encode($dataRes));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ajaxGetPackageNameAction(Request $request) {
        $packageId = strtoupper($request->get('id'));
        $em = $this->getDoctrine()->getManager();
        $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('id' => $packageId));
        if ($objPackage) {
            $packageName = $objPackage->getPackageName();
        } else {
            $objBundle = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id' => $packageId));
            if ($objBundle) {
                $packageName = $objBundle->getDisplayBundleName();
            }
        }
        if (!empty($packageName)) {
            $responseJson['msg'] = 'You are adding Plan <b>' . $packageName . '</b> to the shopping cart, please accept the terms and click checkout to complete the transaction';
        } else {
            $responseJson['msg'] = 'Invalid Request';
        }
        $response = new Response(json_encode($responseJson));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function ajaxGetBundleIptvModalAction(Request $request) {
        $service = strtoupper($request->get('type'));
        $Ispid = strtoupper($request->get('ispid'));
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $view = array();
        $responseJson = array();
        $isError = false;

        if ($this->get("session")->has('isExtendBundle') && $this->get("session")->get('isExtendBundle') == 1) {
            $isError = true;
        }

        if ($isError == false && in_array($service, array('IPTV', 'ISP', 'BUNDLE'))) {

            $availableService = $this->get('UserLocationWiseService')->getUserLocationService();
            $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
            $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

            if (!empty($summaryData['AvailableServicesOnLocation'])) {
                if (!in_array($service, $summaryData['AvailableServicesOnLocation'])) {
                    $responseJson['msg'] = 'Invalid service plan request.';
                    $isError = true;
                }
            } else {
                $responseJson['msg'] = $service . ' not available on your location.';
                $isError = true;
            }
            if (!$isError) {
                $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'max_allow_to_show_channels'));
                $maxShowChannels = 0;
                if($objSetting){
                    $maxShowChannels = $objSetting->getValue();
                }
                $arrChannelImage = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannelImage();
                $bundledata      = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $Ispid));
                $activeService   = $user->getActivePackages();
                $condition       = array(
                    'activeService' => $activeService,
                    'locationId'    => $bundledata->getServiceLocation()->getId(),
                    'ISPid'         => $bundledata->getId()
                );

                $BundleIPTV                            = $em->getRepository('DhiAdminBundle:Bundle')->getIPTVIds($condition);
                $view['IPTVbundleplan']                = $BundleIPTV;
                $view['arrChannelImage']               = $arrChannelImage;
                $view['maxShowChannels']               = $maxShowChannels;
                $view['CartBundleIds']                 = $summaryData['CartBundlePackageId'];
                $view['IsBundleAvailabledInPurchased'] = $summaryData['IsBundleAvailabledInPurchased'];
                $view['IsBundleAvailabledInCart']      = $summaryData['IsBundleAvailabledInCart'];
                $view['PurchasedBUNDLEPackageId']      = $summaryData['PurchasedBUNDLEPackageId'];
                $view['IsIPTVAvailabledInPurchased']   = $summaryData['IsIPTVAvailabledInPurchased'];
                return $this->render('DhiUserBundle:Account:ajaxIPTVbundlePlan.html.twig', $view);
            }
        } else {
            $responseJson['msg'] = 'Something went wrong. please try again.';
            $isError = true;
        }

        if ($isError) {
            $response = new Response(json_encode($responseJson));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
     }

    public function ajaxGetAddonModalAction(Request $request) {
        $service = strtoupper($request->get('type'));
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $view = array();
        $responseJson = array();
        $isError = false;

        if ($service == 'ADDONS') {
            $service = 'IPTV';
        }

        if (in_array($service, array('IPTV'))) {
            if ($request->isXmlHttpRequest()) {
                $availableService = $this->get('UserLocationWiseService')->getUserLocationService();
                $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
                $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

                if (!empty($summaryData['AvailableServicesOnLocation'])) {

                    if (!in_array($service, $summaryData['AvailableServicesOnLocation'])) {

                        $responseJson['msg'] = 'Invalid service plan request.';
                        $isError = true;
                    }
                } else {

                    $responseJson['msg'] = $service . ' not available on your location.';
                    $isError = true;
                }

                if (!$isError) {
                    //$allPackages    = array();
                    $premiumPackage = array();
                    $arrAddonsImage = array();

                    if (isset($summaryData['isEmployee']) && isset($summaryData['isServiceLocationChanged']) && $summaryData['isEmployee'] == 0 && $summaryData['isServiceLocationChanged'] == 0) {
                        $packageArr = $this->get('SelevisionPackage')->userLocationPackages();
                    }else{
                        $packageArr = array();
                    }
                    if (!empty($packageArr) && !empty($packageArr['PREMIUM'])) {

                        $premiumPackage = $packageArr['PREMIUM'];
                        $arrAddonsImage = $em->getRepository('DhiAdminBundle:AddonsMaster')->getAddonsImage();
                    }

                    $view['premiumPackage'] = $premiumPackage;
                    $view['totalPremiumPackage'] = count($premiumPackage);
                    $view['summaryData'] = $summaryData;
                    $view['arrAddonsImage'] = $arrAddonsImage;
                    return $this->render('DhiUserBundle:Account:ajaxAddon.html.twig', $view);
                } else {

                }
            }
        } else {
            $responseJson['msg'] = 'Something went wrong. please try again.';
            $isError = true;
        }

        if ($isError) {
            echo json_encode($responseJson);
            exit;
        }
    }

    public function ajaxRedeemPromoCodeAction(Request $request) {
        $promocode = $request->get('promocode');
        $jsonResponse = array('result' => '', 'errMsg' => '');

        if (!empty($promocode) && $this->get('DashboardSummary')->checksiteWiseLocation()) {
            $em = $this->getDoctrine()->getManager();
            $user = $this->get('security.context')->getToken()->getUser();
            $sessionId = $this->get('paymentProcess')->generateCartSessionId();
            $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackageService($user);
            $objPackagePromoCode = $em->getRepository('DhiUserBundle:PromoCode')->getPackagePromoData($promocode);
            $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

            if ($objPackagePromoCode) {
                $objPackage = $objPackagePromoCode;
                $objPromoCode = $objPackage[0];

                if ($expiredPackage) {
                    foreach ($expiredPackage as $package) {
                        $userService = $package[0];

                        if ($package['bundleApplied']) {
                            if (!empty($package['purchase_type']) && $package['purchase_type'] != $objPromoCode->getService()->getName()) {
                                $jsonResponse['result'] = 'error';
                                $jsonResponse['errMsg'] = 'Promo code is invalid';
                            }
                        } else {
                            if ($package['name'] != $objPromoCode->getService()->getName()) {
                                $jsonResponse['result'] = 'error';
                                $jsonResponse['errMsg'] = 'Promo code is invalid';
                            }
                        }
                    }
                }

                if ($jsonResponse['result'] != 'error') {
                    $isError = false;
                    if (((!empty($objPackage['isDeers']) && $objPackage['isDeers'] == 1) || (!empty($objPackage['ispDeers']) && $objPackage['ispDeers'] == 1) || (!empty($objPackage['iptvDeers']) && $objPackage['iptvDeers'] == 1)) && $isDeersAuthenticated == 2) {
                        $jsonResponse['result'] = 'deersMsg';
                        $jsonResponse['errMsg'] = '';
                        $isError = true;
                    }

                    // check promo code exist in service location
                    if ($isError == false && $objPromoCode->getServiceLocations()) {
                        if ($objPromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> is not available in service location';
                            $isError = true;
                        }
                    }

                    if ($isError == false && $objPromoCode->getIsPlanExpired() == 'Yes') {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Please enter valid promo code';
                        $isError = true;
                    }

                    $date = new \DateTime();
                    if ($isError == false && $date >= $objPromoCode->getExpiredAt()) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already expired for the account.';
                        $isError = true;
                    }

                    $noOfRedemption = $objPromoCode->getNoOfRedemption();
                    if ($isError == false && $noOfRedemption >= 1) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed.';
                        $isError = true;
                    }

                    if ($objPromoCode->getIsBundle()) {
                        if (empty($objPackage['bundle_id'])) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Please enter valid promo code';
                            $isError = true;
                        }
                    } else {
                        if ((isset($objPackage['isExpired']) && $objPackage['isExpired'] == 1) || (empty($objPackage['packageId']))) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Please enter valid promo code';
                            $isError = true;
                        }
                    }

                    if ($isError == false) {
                        if ($objPromoCode->getIsBundle()) {
                            $jsonResponse['packageName'] = $objPackage['displayBundleName'];
                            $jsonResponse['description'] = $objPackage['bundleDesc'];
                        } else {
                            if($objPackage['bandwidth'] >= 1024){
                                $mbbandwidth        = $objPackage['bandwidth']/1024;
                                $packagedescription = str_replace($objPackage['bandwidth'].'k', $mbbandwidth.'MB', $objPackage['description']);
                            }else {
                              $packagedescription = $objPackage['description'];
                            }
                            
                            $jsonResponse['packageName'] = $objPackage['packageName'];
                            $jsonResponse['description'] = $packagedescription;
                        }

                        $jsonResponse['promoName'] = $promocode;
                        $jsonResponse['validity'] = $objPromoCode->getDuration();

                        $activityLog = array();
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = 'Promocode Reedemed';
                        $activityLog['description'] = "User " . $user->getUsername() . " has redeemed promo code " . $promocode;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $jsonResponse['result'] = 'success';
                        $jsonResponse['succMsg'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully.';
                    }
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Please enter valid promo code';
            }

            if ($jsonResponse['result'] == 'error') {
                $jsonResponse = $this->checkBusinessPromocode($promocode);
            }

            if ($jsonResponse['result'] == 'error') {
                $jsonResponse = $this->checkPartnerPromocode($promocode);
            }
        } else {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter promo code';
        }

        $response = new Response(json_encode($jsonResponse));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function ajaxApplyPromoCodeAction(Request $request) {
        $totalValidaity = '';
        $jsonResponse = array();
        $jsonResponse['result'] = '';
        $jsonResponse['succMsg'] = '';
        $jsonResponse['errMsg'] = '';
        $jsonResponse['response'] = '';

        $promocode = $request->get('promocode');

        if (!empty($promocode) && $this->get('DashboardSummary')->checksiteWiseLocation()) {
            $em = $this->getDoctrine()->getManager();
            $objPackagePromoCode = $em->getRepository('DhiUserBundle:PromoCode')->getPackagePromoData($promocode);

            if ($objPackagePromoCode) {
                $user = $this->get('security.context')->getToken()->getUser();
                $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
                $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
                $ipAddress = $this->get('session')->get('ipAddress');
                $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackage($user);
                $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'promocode'));

                $objPackage = $objPackagePromoCode;
                $objPromoCode = $objPackage[0];
                $service = '';
                if ($objPromoCode->getIsPlanExpired() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                }

                $noOfRedemption = $objPromoCode->getNoOfRedemption();
                if ($noOfRedemption >= 1) {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed.';
                }

                if ($jsonResponse['result'] != 'error') {
                    if ($objPromoCode->getService()) {
                        $service = $objPromoCode->getService();
                    }

                    if ($service->getName() == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_user_account'));
                        }
                    } else if ($service->getName() == 'ISP') {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_user_account'));
                        }
                    } else if ($service->getName() == 'BUNDLE') {

                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {

                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                        }

                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());

                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {

                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_user_account'));
                        }
                    }

                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setPaymentMethod($paymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentStatus('InProcess');
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $objPurchaseOrder->setTotalAmount('0');
                    $em->persist($objPurchaseOrder);
                    $promoValidity = $objPromoCode->getDuration();

                    if (!empty($objPackage['isHourlyPlan']) && $objPackage['isHourlyPlan'] == 1) {
                        $PromoDays = $objPromoCode->getDuration();
                        $validityType = "HOURS";
                    }else{
                    	$PromoDays = floor($objPromoCode->getDuration() / 24);
                    	if ($PromoDays == 0) {
                        	$PromoDays = 1;
                        }
                        $validityType = "DAYS";
                    }

                    if ($service->getName() == 'ISP' || $service->getName() == 'IPTV') {
                        $objServicePurchase = new ServicePurchase();
                        $payableAmount = 0;
                        // $payableAmount = ($PromoDays * $objPackage['amount']) / ($objPackage['validity']);

                        if($this->get('session')->has('brand'))
                        {
                            $whiteLabelBrand = $this->get('session')->get('brand');
                            if($whiteLabelBrand)
                            {
                                $whiteLabelBrandId = $whiteLabelBrand['id'];
                                $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                if($whiteLabelBrandObj)
                                {
                                    $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                }
                            }
                        }

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
                        $objServicePurchase->setFinalCost($payableAmount);
                        $objServicePurchase->setPayableAmount($payableAmount);
                        $objServicePurchase->setPromoCodeApplied(1);
                        $objServicePurchase->setValidityType($validityType);
                        $objUserServiceLocation = ($objPromoCode->getServiceLocations() ? $objPromoCode->getServiceLocations() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                        $objServicePurchase->setServiceLocationId($objUserServiceLocation);
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
                                $objServicePurchase->setPurchaseType('BUNDLE');
                                $objServicePurchase->setBundleId($objPromoCode->getPackageId());
                                $objServicePurchase->setBundleDiscount($objPackage['bundleDiscount']);
                                $objServicePurchase->setBundleName($objPackage['bundleName']);
                                $objServicePurchase->setBundleApplied(1);
                                $objServicePurchase->setPromoCodeApplied(1);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objUserServiceLocation = $user->getUserServiceLocation() ? $user->getUserServiceLocation() : ($objBundlePromoCode->getIsp()->getServiceLocation() ? $objBundlePromoCode->getIsp()->getServiceLocation() : null);
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);

                                if($this->get('session')->has('brand'))
                                {
                                    $whiteLabelBrand = $this->get('session')->get('brand');
                                    if($whiteLabelBrand)
                                    {
                                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                                        $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                        if($whiteLabelBrandObj)
                                        {
                                            $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                        }
                                    }
                                }

                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIspAmount() - $payableAmount);

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

                                if($this->get('session')->has('brand'))
                                {
                                    $whiteLabelBrand = $this->get('session')->get('brand');
                                    if($whiteLabelBrand)
                                    {
                                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                                        $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                        if($whiteLabelBrandObj)
                                        {
                                            $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                        }
                                    }
                                }

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
                                $objServicePurchase->setPromoCodeApplied(1);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objUserServiceLocation = ($objBundlePromoCode->getIptv()->getServiceLocation() ? $objBundlePromoCode->getIptv()->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIptvAmount() - $payableAmount);
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
                    $this->get('session')->set('expiredPackage', $expiredPackage);

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
                        echo json_encode($jsonResponse);
                        exit;
                    }

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

                    $jsonResponse['result'] = 'success';
                    $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Please enter valid promo code';
            }

            if ($jsonResponse['result'] == 'error') {
                $jsonResponse = $this->applyBusinessPromoCode($promocode);
            }

            if ($jsonResponse['result'] == 'error') {
                $jsonResponse = $this->applyPartnerPromoCode($promocode);
            }
        } else {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter valid promo code';
        }

        $response = new Response(json_encode($jsonResponse));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    private function applyPartnerPromoCode($promocode) {
        $jsonResponse = array('result' => '', 'succMsg' => '', 'errMsg' => '', 'response' => '');
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
        $ipAddress = $this->get('session')->get('ipAddress');
        $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'partner_promocode'));
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $promocode));
        $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackage($user);
        $date = new \DateTime();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        if (!$paymentMethod) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Error No: #1003, Something went wrong on server';
        } else {
            if ($objPackagePromoCode) {
                $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $objPackagePromoCode->getPackageId()));
                $isError = false;
                if ($objPackage) {
                    $isDeers = $objPackage->getIsDeers();
                } else {
                    $isError = true;
                }

                if ($isError == false && $objPackagePromoCode->getIsPlanExpired() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                    $isError = true;
                }

                if ($isError == false && (!empty($isDeers) && $isDeers == 1) && $isDeersAuthenticated == 2) {
                    $jsonResponse['result'] = 'deersMsg';
                    $jsonResponse['errMsg'] = '';
                    $isError = true;
                }

                if ($isError == false && $objPackagePromoCode->getIsRedeemed() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed';
                    $isError = true;
                }

                if ($isError == false) {
                    if ($objPackage->getPackageType()) {
                        $service = $objPackage->getPackageType();
                    }

                    $objService = $em->getRepository("DhiUserBundle:Service")->findOneBy(array('name' => $service));
                    if (!$objService) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Service not found for <b>' . $promocode . '</b> promo code';
                        $isError = true;
                    }

                    if ($objPackagePromoCode->getServiceLocations()) {
                        if ($objPackagePromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                            $isError = true;
                        }
                    }

                    if ($isError == false && !in_array($service, $summaryData['AvailableServicesOnLocation'])) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Service is not available in your service location';
                        $isError = true;
                    }

                    if ($objPackagePromoCode->getExpirydate() != null) {
                        $expiryDate = $objPackagePromoCode->getExpirydate() ? $objPackagePromoCode->getExpirydate()->format('Y-m-d 23:59:59') : null;
                        if (!is_null($expiryDate) && $date->format('Y-m-d H:i:s') > $expiryDate) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                            $isError = true;
                        }
                    }
                    if ($objPackagePromoCode->getStatus() == 'Inactive') {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Please enter valid promo code';
                        $isError = true;
                    }
                }

                if ($isError == false) {

                    if ($service == 'IPTV') {
                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if ($isSelevisionUser == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_user_account'));
                        }
                    } else if ($service == 'ISP') {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_user_account'));
                        }
                    }

                    $payableAmount = $objPackage->getAmount();
                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setPaymentMethod($paymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentStatus('InProcess');
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $objPurchaseOrder->setTotalAmount($payableAmount);
                    $em->persist($objPurchaseOrder);

                    $packageValidity = $objPackage->getValidity();
                    $promoValidity = $objPackagePromoCode->getDuration();
                    $isHourlyPlan = $objPackage->getIsHourlyPlan();
                    $ispromoValidity = false;
                    if (!empty($promoValidity)) {
                        $ispromoValidity = true;
                    } else {
                        if ($isHourlyPlan != 1) {
                            $promoValidity = ($packageValidity * 24);
                        }
                    }

                    if ($isHourlyPlan == 1) {
                        $PromoDays = $promoValidity;
                        $validityType = "HOURS";
                    }else{
                        $PromoDays = floor($promoValidity / 24);
                        if ($PromoDays == 0) {
                            $PromoDays = 1;
                        }
                        $validityType = "DAYS";
                    }

                    $objServicePurchase = new ServicePurchase();

                    if($this->get('session')->has('brand'))
                    {
                        $whiteLabelBrand = $this->get('session')->get('brand');
                        if($whiteLabelBrand)
                        {
                            $whiteLabelBrandId = $whiteLabelBrand['id'];
                            $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                            if($whiteLabelBrandObj)
                            {
                                $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                            }
                        }
                    }

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
                    $objServicePurchase->setValidity($PromoDays);
                    $objServicePurchase->setFinalCost($payableAmount);
                    $objServicePurchase->setPayableAmount($payableAmount);
                    $objServicePurchase->setPromoCodeApplied(2);
                    $objServicePurchase->setValidityType($validityType);
                    $objServicePurchase->setDiscountedPartnerPromocode($objPackagePromoCode);
                    $objUserServiceLocation = ($objPackage->getServiceLocation() ? $objPackage->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                    $objServicePurchase->setServiceLocationId($objUserServiceLocation);
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
                    $this->get('session')->set('expiredPackage', $expiredPackage);
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
                        $jsonResponse['errMsg'] = 'valid package not found';
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
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = 'partner Promocode Applied';
                        $activityLog['description'] = "User " . $user->getUsername() . " has applied promo code " . $promocode;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $jsonResponse['result'] = 'success';
                        $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';
                    }
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Please enter valid promo code';
            }
        }

        return $jsonResponse;
    }

    private function checkPartnerPromocode($promocode) {
        $jsonResponse = array('result' => '', 'errMsg' => '');
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('paymentProcess')->generateCartSessionId();
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $promocode));
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $date = new \DateTime();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        if ($objPackagePromoCode) {
            $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $objPackagePromoCode->getPackageId()));
            $isError = false;
            if ($objPackage) {
                if ($isError == false && $objPackagePromoCode->getServiceLocations()) {
                    if ($objPackagePromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                        $isError = true;
                    }
                }

                if ($isError == false && !in_array($objPackage->getPackageType(), $summaryData['AvailableServicesOnLocation'])) {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Service is not available in your service location';
                    $isError = true;
                }

                if ($objPackagePromoCode->getExpirydate() != null) {
                    $expiryDate = $objPackagePromoCode->getExpirydate() ? $objPackagePromoCode->getExpirydate()->format('Y-m-d 23:59:59') : null;
                    if (!is_null($expiryDate) && $isError == false && $date->format('Y-m-d H:i:s') > $expiryDate) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                        $isError = true;
                    }
                }
                if ($isError == false && $objPackagePromoCode->getIsRedeemed() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed';
                    $isError = true;
                }

                if ($isError == false && $objPackagePromoCode->getStatus() == 'Inactive') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                    $isError = true;
                }

                if ($isError == false && $objPackagePromoCode->getIsPlanExpired() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                    $isError = true;
                }

                $isDeers = $objPackage->getIsDeers();
                if ($isError == false && (!empty($isDeers) && $isDeers == 1) && $isDeersAuthenticated == 2) {
                    $jsonResponse['result'] = 'deersMsg';
                    $jsonResponse['errMsg'] = '';
                    $isError = true;
                }

                if ($isError == false) {
                    $validity = 0;
                    $packageValidity = $objPackage->getValidity();
                    $promoValidity = $objPackagePromoCode->getDuration();
                    if (!empty($promoValidity)) {
                        $validity = $promoValidity;
                    } else {
                        $validity = ($packageValidity * 24);
                    }

                    $jsonResponse['packageName'] = $objPackage->getPackageName();
                    $jsonResponse['description'] = $objPackage->getDescription();
                    $jsonResponse['promoName'] = $promocode;
                    $jsonResponse['validity'] = $validity;

                    $activityLog = array();
                    $activityLog['user'] = $user->getUsername();
                    $activityLog['activity'] = 'Partner Promocode Reedemed';
                    $activityLog['description'] = "User " . $user->getUsername() . " has redeemed partner promo code: " . $promocode;
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $jsonResponse['result'] = 'success';
                    $jsonResponse['succMsg'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully';
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Package does not exists for this promocode';
            }
        } else {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter valid promo code';
        }
        return $jsonResponse;
    }

    public function addPlanForAradialUser($user, $response) {

        $em = $this->getDoctrine()->getEntityManager();

        $flagAradialUser = false;

        if ($user) {

            $planActivationDate = '';
            $planExpirationDate = '';
            $orderNumber = 'ARD-' . $this->get('PaymentProcess')->generateOrderNumber();
            $ipAddress = $this->get('session')->get('ipAddress');
            $ipAddressToLong = ip2long($ipAddress);

            $packageId = '';
            $packageName = '';
            $amount = '';
            $validity = '';
            $bandwidth = '';
            $isplanActive = 1;

            $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'DHIMigrate'));
            $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => 'ISP'));

            $userLoggedInStatus = $this->get('session')->get('aradialUserloggedInStatus');

            if (isset($response) && !empty($response) && $objPaymentMethod) {

                $aradialUserDetail = $this->get('aradial')->getUserInformation($user->getUsername());

                if (!empty($aradialUserDetail) && $aradialUserDetail['result'] == 1) {
                    $planActivationDate = new \DateTime(date('Y-m-d', strtotime($aradialUserDetail['PricePlanAssignDate'] ? $aradialUserDetail['PricePlanAssignDate'] : $aradialUserDetail['PlanActivationDate'])));
                    if (!empty($aradialUserDetail['UserExpirationDate'])) {
                        $UserExpirationDate = trim($aradialUserDetail['UserExpirationDate']);
                        if (!empty($UserExpirationDate)) {
                            $planExpirationDate = new \DateTime(date('Y-m-d', strtotime($UserExpirationDate)));
                        } else {
                            $planExpirationDate = new \DateTime(date('Y-m-d'));
                            $planExpirationDate = $planExpirationDate->modify('+7 day');
                            $isplanActive = 2;
                        }
                    } else {
                        $planExpirationDate = new \DateTime(date('Y-m-d'));
                        $planExpirationDate = $planExpirationDate->modify('+7 day');
                        $isplanActive = 2;
                    }
                }

                $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $response['package']['0']['OfferId']));

                if (!$objPackage) {

                    $objPackage = new Package();
                    $packageKeyArr = explode('-', $response['package'][0]['Name']);

                    if (!empty($packageKeyArr) && $packageKeyArr && count($packageKeyArr) > 4 || count($packageKeyArr) == 4) {

                        $isDeers = 0;
                        $packageType = 'ISP';

                        $objLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getServiceLocationAradial($packageKeyArr);

                        if ($objLocation) {

                            $bandwidth = str_replace("k)", "", substr($response['package'][0]['Description'], -7));

                            if (!is_numeric($bandwidth)) {

                                $bandwidth = 10;
                            }

                            // save package
                            $objPackage = new Package();
                            $objPackage->setPackageId($response['package'][0]['OfferId']);
                            $objPackage->setPackageName(str_replace("-", " ", $packageKeyArr[2]));
                            $objPackage->setAmount($response['package'][0]['Price']);
                            $objPackage->setPackageType($packageType);
                            $objPackage->setDescription($response['package'][0]['Description']);
                            $objPackage->setStatus(1);
                            $objPackage->setBandwidth($bandwidth);
                            $objPackage->setValidity($response['package'][0]['ExpirationTime'] ? $response['package'][0]['ExpirationTime'] : 30);
                            $objPackage->setTotalChannels(0);
                            $objPackage->setServiceLocation($objLocation);
                            $objPackage->setIsDeers($isDeers);

                            $em->persist($objPackage);
                            $em->flush();
                        }
                    }
                }

                if ($objPackage->getId()) {
                    $packageId = $objPackage->getPackageId();
                    $packageName = $objPackage->getPackageName();
                    $amount = $objPackage->getAmount();
                    $validity = $objPackage->getValidity();
                    $bandwidth = $objPackage->getBandwidth();

                    $daysInterval = $planActivationDate->diff($planExpirationDate);
                    if (!empty($daysInterval) && $daysInterval->days != $validity) {
                        $validity = $daysInterval->days;
                    }

                    $objPurchaseOrder = new PurchaseOrder();
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setSessionId('');
                    $objPurchaseOrder->setTotalAmount($amount);
                    $objPurchaseOrder->setPaymentStatus('Completed');
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setCreatedAt(new \DateTime());
                    $objPurchaseOrder->setUpdatedAt(new \DateTime());
                    $objPurchaseOrder->setRecurringStatus(0);

                    $em->persist($objPurchaseOrder);
                    $em->flush();

                    if ($objPurchaseOrder->getId()) {
                        $location = ($objPackage->getServiceLocation() ? $objPackage->getServiceLocation() : (($user->getUserServiceLocation()) ? $user->getUserServiceLocation() : null));

                        $objServicePurchase = new ServicePurchase();

                        if($this->get('session')->has('brand'))
                        {
                            $whiteLabelBrand = $this->get('session')->get('brand');
                            if($whiteLabelBrand)
                            {
                                $whiteLabelBrandId = $whiteLabelBrand['id'];
                                $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                if($whiteLabelBrandObj)
                                {
                                    $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                }
                            }
                        }

                        $objServicePurchase->setService($objService);
                        $objServicePurchase->setUser($user);
                        $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                        $objServicePurchase->setPackageId($packageId);
                        $objServicePurchase->setPackageName($packageName);
                        $objServicePurchase->setPaymentStatus('Completed');
                        $objServicePurchase->setActualAmount($amount);
                        $objServicePurchase->setPayableAmount($amount);
                        $objServicePurchase->setSessionId('');
                        $objServicePurchase->setRechargeStatus(1);
                        $objServicePurchase->setIsUpgrade(0);
                        $objServicePurchase->setTermsUse(1);
                        $objServicePurchase->setValidity($validity);
                        $objServicePurchase->setBandwidth($bandwidth);
                        $objServicePurchase->setCreatedAt(new \DateTime());
                        $objServicePurchase->setUpdatedAt(new \DateTime());
                        $objServicePurchase->setIsAddon(0);
                        $objServicePurchase->setIsCredit(0);
                        $objServicePurchase->setIsCompensation(0);
                        $objServicePurchase->setFinalCost($amount);
                        $objServicePurchase->setServiceLocationId($location);

                        $em->persist($objServicePurchase);
                        $em->flush();

                        if ($objServicePurchase->getId()) {

                            $objUserService = new UserService();
                            $objUserService->setUser($user);
                            $objUserService->setService($objService);
                            $objUserService->setServicePurchase($objServicePurchase);
                            $objUserService->setPurchaseOrder($objPurchaseOrder);
                            $objUserService->setPackageId($packageId);
                            $objUserService->setPackageName($packageName);
                            $objUserService->setActualAmount($amount);
                            $objUserService->setPayableAmount($amount);
                            $objUserService->setActivationDate($planActivationDate);
                            $objUserService->setExpiryDate($planExpirationDate);
                            $objUserService->setSentExpiredNotification(0);
                            $objUserService->setStatus(1);
                            $objUserService->setIsAddon(0);
                            $objUserService->setServiceLocationIp($ipAddressToLong);
                            $objUserService->setBandwidth($bandwidth);
                            $objUserService->setValidity($validity);
                            $objUserService->setCreatedAt(new \DateTime());
                            $objUserService->setUpdatedAt(new \DateTime());
                            $objUserService->setIsExtend(0);
                            $objUserService->setFinalCost($amount);
                            if ($isplanActive == 2) {
                                $objUserService->setIsPlanActive(0);
                            }

                            $em->persist($objUserService);
                            $em->flush();

                            if ($objUserService->getId()) {

                                $flagAradialUser = true;

                                $objUser = $em->getRepository('DhiUserBundle:User')->find($user);

                                if ($objUser) {

                                    $objUser->setIsAradialExists(1);
                                    $objUser->setIsAradialMigrated(1);
                                    $em->persist($objUser);
                                    $em->flush();
                                }
                            }
                        }
                    }
                }
            }
        }

        return $flagAradialUser;
    }

    public function getStateAction(request $request) {

        $em = $this->getDoctrine()->getEntityManager();

        $countryId = $request->get('countryId');
        $source = $request->get('source');

        if ($countryId == 1 || $countryId == 'US') {
            $state = $this->get('GeoLocation')->getStates();
        } else {
            if (!empty($source) && $source == 'registration') {
                $state = array('Others' => 'Other');
            }else{
                $state = array('NA' => 'Other');
            }
        }

        $response = new Response(json_encode($state));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    private function checkBusinessPromocode($promocode) {
        $jsonResponse = array('result' => '', 'errMsg' => '');
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('paymentProcess')->generateCartSessionId();
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getPackagePromoData($promocode);
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $date = new \DateTime();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        if ($objPackagePromoCode) {
            $objPackage = $objPackagePromoCode;
            $objPromoCode = $objPackage[0];

            $isError = false;
            if (!empty($objPackage) && (!empty($objPackage['packageId']) || !empty($objPackage['bundle_id']))) {
                if (!empty($objPackage['isDeers']) || !empty($objPackage['ispDeers']) || !empty($objPackage['iptvDeers']) && $isDeersAuthenticated == 2) {
                    $jsonResponse['result'] = 'deersMsg';
                    $jsonResponse['errMsg'] = '';
                    $isError = true;
                }

                if ($isError == false && $objPromoCode->getServiceLocations()) {
                    if ($objPromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                        $isError = true;
                    }
                }

                if ($objPromoCode->getService()) {
                    $serviceName = $objPromoCode->getService()->getName();
                    if ($isError == false && !in_array($objPromoCode->getService()->getName(), $summaryData['AvailableServicesOnLocation'])) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Service is not available in your service location';
                        $isError = true;
                    }
                }

                if ($objPromoCode->getExpirydate() != null) {
                    $expiryDate = $objPromoCode->getExpirydate() ? $objPromoCode->getExpirydate()->format('Y-m-d 23:59:59') : null;
                    if (!is_null($expiryDate) && $isError == false && $date->format('Y-m-d H:i:s') > $expiryDate) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                        $isError = true;
                    }
                }
                if ($isError == false && $objPromoCode->getIsRedeemed() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed the max number of times for the account';
                    $isError = true;
                }

                if ($isError == false && $objPromoCode->getStatus() == 'Inactive') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                    $isError = true;
                }

                if (!empty($serviceName) && strtoupper($serviceName) != 'BUNDLE') {
                    if ((isset($objPackage['isExpired']) && $objPackage['isExpired'] == 1) || (empty($objPackage['packageId']))) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Please enter valid promo code';
                        $isError = true;
                    }
                } else if (!empty($serviceName) && strtoupper($serviceName) == 'BUNDLE') {
                    if (empty($objPackage['bundle_id'])) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Please enter valid promo code';
                        $isError = true;
                    }
                }

                if ($isError == false) {
                    $validity = 0;

                    if (!empty($serviceName) && strtoupper($serviceName) == 'BUNDLE') {
                        $jsonResponse['packageName'] = $objPackage['displayBundleName'];
                        $jsonResponse['description'] = $objPackage['bundleDesc'];
                    } else {
                        $jsonResponse['packageName'] = $objPackage['packageName'];
                        $jsonResponse['description'] = $objPackage['description'];
                    }

                    $jsonResponse['promoName'] = $promocode;
                    $jsonResponse['validity'] = $objPromoCode->getDuration();
                    $activityLog = array();
                    $activityLog['user'] = $user->getUsername();
                    $activityLog['activity'] = 'Business Promocode Reedemed';
                    $activityLog['description'] = "User " . $user->getUsername() . " Has Redeemed Business Promo Code: " . $promocode;
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $jsonResponse['result'] = 'success';
                    $jsonResponse['succMsg'] = 'Promo code <b>' . $promocode . '</b> has been redeemed successfully';
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Package does not exists for this promocode';
            }
        } else {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please enter valid promo code';
        }
        return $jsonResponse;
    }

    private function applyBusinessPromoCode($promocode) {
        $jsonResponse = array('result' => '', 'succMsg' => '', 'errMsg' => '', 'response' => '');
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $sessionId = $this->get('PaymentProcess')->generateCartSessionId();
        $ipAddress = $this->get('session')->get('ipAddress');
        $paymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneBy(array('code' => 'business_promocode'));
        $objPackagePromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getPackagePromoData($promocode);
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $expiredPackage = $em->getRepository('DhiUserBundle:UserService')->getUserExpiredPackage($user);
        $date = new \DateTime();
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        if (!$paymentMethod) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Error No: #1003, Something went wrong on server';
        } else {
            if ($objPackagePromoCode) {
                $objPackage = $objPackagePromoCode;
                $objPromoCode = $objPackage[0];

                $isError = false;
                if (!$objPackage) {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Package does not exists for this promocode';
                    $isError = true;
                }

                if ($isError == false && $objPromoCode->getIsPlanExpired() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Please enter valid promo code';
                    $isError = true;
                }

                if ($isError == false && (!empty($objPackage['isDeers']) || !empty($objPackage['ispDeers']) || !empty($objPackage['iptvDeers'])) && $isDeersAuthenticated == 2) {
                    $jsonResponse['result'] = 'deersMsg';
                    $jsonResponse['errMsg'] = '';
                    $isError = true;
                }


                if ($isError == false && $objPromoCode->getIsRedeemed() == 'Yes') {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already been redeemed the max number of times for the account';
                    $isError = true;
                }

                if ($isError == false && $objPromoCode->getService()) {
                    $objService = $objPromoCode->getService();
                    $service = $objService->getName();
                } else {
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Service not found for <b>' . $promocode . '</b> promo code';
                    $isError = true;
                }

                if ($isError == false) {

                    if ($objPromoCode->getServiceLocations()) {
                        if ($objPromoCode->getServiceLocations()->getName() != $user->getUserServiceLocation()->getName()) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> is not available in your service location';
                            $isError = true;
                        }
                    }

                    if ($isError == false && !in_array($service, $summaryData['AvailableServicesOnLocation'])) {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Service is not available in your service location';
                        $isError = true;
                    }

                    if ($objPromoCode->getExpirydate() != null) {
                        $expiryDate = $objPromoCode->getExpirydate() ? $objPromoCode->getExpirydate()->format('Y-m-d 23:59:59') : null;
                        if (!is_null($expiryDate) && $date->format('Y-m-d H:i:s') > $expiryDate) {
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Promo code <b>' . $promocode . '</b> has already expired.';
                            $isError = true;
                        }
                    }
                    if ($objPromoCode->getStatus() == 'Inactive') {
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Please enter valid promo code';
                        $isError = true;
                    }

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
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setIpAddress($ipAddress);
                    $promoValidity = $objPromoCode->getDuration();
                    $em->persist($objPurchaseOrder);

                    $ispromoValidity = false;
                    if (!empty($promoValidity)) {
                        $ispromoValidity = true;
                    }

                    if (!empty($objPackage['isHourlyPlan']) && $objPackage['isHourlyPlan'] == 1) {
                        $PromoDays = $promoValidity;
                        $validityType = "HOURS";
                    }else{
                        $PromoDays = floor($promoValidity / 24);
                        if ($PromoDays == 0) {
                            $PromoDays = 1;
                        }
                        $validityType = "DAYS";
                    }

                    $objUserServiceLocation = ($objPromoCode->getServiceLocations() ? $objPromoCode->getServiceLocations() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));

                    if (in_array(strtoupper($service), array('IPTV', 'ISP'))) {
                        $totalPayableAmount = $payableAmount = $objPackage['amount'];

                        $objServicePurchase = new ServicePurchase();

                        if($this->get('session')->has('brand'))
                        {
                            $whiteLabelBrand = $this->get('session')->get('brand');
                            if($whiteLabelBrand)
                            {
                                $whiteLabelBrandId = $whiteLabelBrand['id'];
                                $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                if($whiteLabelBrandObj)
                                {
                                    $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                }
                            }
                        }

                        $objServicePurchase->setUser($user);
                        $objServicePurchase->setPurchaseOrder($objPurchaseOrder);
                        $objServicePurchase->setService($objService);
                        $objServicePurchase->setPackageId($objPackage['packageId']);
                        $objServicePurchase->setPackageName($objPackage['packageName']);
                        $objServicePurchase->setPaymentStatus('Completed');
                        $objServicePurchase->setActualAmount($objPackage['amount']);
                        $objServicePurchase->setSessionId($sessionId);
                        $objServicePurchase->setRechargeStatus('0');
                        $objServicePurchase->setBandwidth($objPackage['bandwidth']);
                        $objServicePurchase->setValidity($PromoDays);
                        $objServicePurchase->setFinalCost($payableAmount);
                        $objServicePurchase->setPayableAmount($payableAmount);
                        $objServicePurchase->setPromoCodeApplied(3);
                        $objServicePurchase->setDiscountedBusinessPromocode($objPromoCode);
                        $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                        $objServicePurchase->setValidityType($validityType);
                        $em->persist($objServicePurchase);
                    } else if ($service == "BUNDLE") {
                        $objBundlePromoCode = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id' => $objPromoCode->getPackageId()));
                        if ($objBundlePromoCode) {

                            if ($objBundlePromoCode->getIsp()) {
                                $objServicePurchase = new ServicePurchase();

                                if($this->get('session')->has('brand'))
                                {
                                    $whiteLabelBrand = $this->get('session')->get('brand');
                                    if($whiteLabelBrand)
                                    {
                                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                                        $whiteLabelBrandObj = $this->em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                        if($whiteLabelBrandObj)
                                        {
                                            $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                        }
                                    }
                                }

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

                                if($this->get('session')->has('brand'))
                                {
                                    $whiteLabelBrand = $this->get('session')->get('brand');
                                    if($whiteLabelBrand)
                                    {
                                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                                        $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                                        if($whiteLabelBrandObj)
                                        {
                                            $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                                        }
                                    }
                                }

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
                                $objServicePurchase->setDiscountedBusinessPromocode($objPromoCode);
                                $objServicePurchase->setDisplayBundleName($objPackage['displayBundleName']);
                                $objServicePurchase->setServiceLocationId($objUserServiceLocation);
                                $objServicePurchase->setDisplayBundleDiscount($objBundlePromoCode->getIptvAmount() - $payableAmount);
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
                    $this->get('session')->set('expiredPackage', $expiredPackage);
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
                    } else {
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
                        $activityLog['user'] = $user->getUsername();
                        $activityLog['activity'] = 'Business Promocode Applied';
                        $activityLog['description'] = "User " . $user->getUsername() . " has applied promo code " . $promocode;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);

                        $jsonResponse['result'] = 'success';
                        $jsonResponse['succMsg'] = 'Promo code ' . $promocode . ' has been applied successfully.';
                    }
                }
            } else {
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Please enter valid promo code';
            }
        }
        return $jsonResponse;
    }

    public function checkUserLoginAction() {
        $user = $this->get('security.context')->getToken();
        $roles = $user->getRoles();
        if (!empty($roles[0]) && $roles[0] != 'ROLE_USER') {
            $status = true;
        } else {
            $status = false;
        }
        $response = new Response(json_encode(array('status' => $status)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
