<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dhi\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;

/**
 * Controller managing the registration
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class RegistrationController extends Controller
{
    public function registerAction(Request $request)
    {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.registration.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->createUser();

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::REGISTRATION_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }
        
        # START-  check User IP in for Any serviece location
        $clientIpAdd = $_SERVER['REMOTE_ADDR'];
        $em = $this->getDoctrine()->getManager();
        $isServiceLocExistForUser = $em->getRepository('DhiAdminBundle:IpAddressZone')->checkIpRangeExists($clientIpAdd, null, null);
        # END-  check User IP in for Any serviece location

        
        $referEmail = base64_decode($request->get('email'));
        $referToken = base64_decode($request->get('token'));
        $objReferralInvitees = $em->getRepository('DhiUserBundle:ReferralInvitees')->findOneBy(array('emailId' => $referEmail, 'token' => $referToken));
        if($objReferralInvitees){
            
            if($objReferralInvitees->getIsRegister() == 1){
                
                $this->get('session')->getFlashBag()->add('failure', "Your link is expired");
                $redirectRoute = 'fos_user_registration_register';
                
                if($this->get('session')->has('affiliate')){
                    $affiliateValue =  $this->get('session')->get('affiliate');
                    if($affiliateValue == 'bv'){
                        $redirectRoute = 'dhi_signup';
                    }else if($data['affiliate'] == 'netgate'){
                        $redirectRoute = 'dhi_signup_netgate';
                    }
                }
                return $this->redirect($this->generateUrl($redirectRoute));
            }
        }
        
        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);
        
        $isFormValid = true;
        if ($request->getMethod() == "POST") {
        
            $email = $request->get('fos_user_registration_form')['email']['first'];
            $firstName = $request->get('fos_user_registration_form')['firstname'];
            $lastName = $request->get('fos_user_registration_form')['lastname'];
            
            if(!preg_match('/^[^\'#"]*$/', $email)){
                $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                $isFormValid = false;
            }else if(!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)){
                $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                $isFormValid = false;
            }else if(!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)){
                $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                $isFormValid = false;
            }
        }
        
        if ($form->isValid() && $isFormValid) {
            $registrationType = $request->get('affiliate');
            //$isFormValid = true;
            if (!empty($registrationType) && $registrationType == "netgate") {
                if (preg_match("/^tji\/|^bsm\//i", $user->getUsername())) {
                    $isFormValid = false;
                    $this->get('session')->getFlashBag()->add('danger', 'Invalid Username!');
                }
            }

            if ($isFormValid) {

                $getuserdata = $request->get('fos_user_registration_form');
                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($getuserdata['email']['first']);
                $objUsername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($getuserdata['username']);

                if ($objUser || $objUsername) {
                    $this->get('session')->getFlashBag()->add('danger', "Email Or Username Already in used!");

                }else{
                
                    $password = $event->getUser()->getPlainPassword();

                    $event = new FormEvent($form, $request);
                    $dispatcher->dispatch(FOSUserEvents::REGISTRATION_SUCCESS, $event);

                    // adding ip address to database
                    $ip_address = $this->get('GeoLocation')->getIPAddress('ip');

                    /*
                        $ip_address = $this->container->get('request')->getClientIp();
                        $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
                        echo "<pre>";
                        var_dump($this->get('request')->request->get('isVerifiedAradial'));
                        die("die to test");
                        if($this->get('request')->request->get('isVerifiedAradial')){
                            $user->setIsAradialExists($this->get('request')->request->get('isVerifiedAradial'));
                        }
                    */

                    $isAradialExists = false;
                    if($this->get('request')->request->get('isVerifiedAradial') == 'true'){
                        $isAradialExists = true;
                    }
                    $user->setIsAradialExists($isAradialExists);
                    $user->setIpAddress($ip_address);
                    $user->setIpAddressLong(ip2long($ip_address));
                    $user->setEnabled(true);
                    $user->setLocked(false);
                    $user->setEncryPwd(base64_encode($password));

                    // White Label Brand Name
                    $whiteLabelBrand = $this->get('session')->get('brand');
                    $whiteLabelBrandId = $whiteLabelBrandName = $whiteLabelBrandObj = '';
                    if($whiteLabelBrand)
                    {
                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                        $whiteLabelBrandName = $whiteLabelBrand['name'];
                        $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                        if($whiteLabelBrandObj)
                        {
                            $user->setWhiteLabel($whiteLabelBrandObj);
                        }
                    }
                    $userManager->updateUser($user);
                    if (null === $response = $event->getResponse()) {
                        $url = $this->generateUrl('fos_user_registration_confirmed');
                        $response = new RedirectResponse($url);
                    }
                    $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

              	## Start Here - Update Referral Invitees
	            	$referEmail = base64_decode($request->get('hdnEmail'));
	            	$referToken = base64_decode($request->get('hdnToken'));

	            	if(!empty($referEmail) && !empty($referToken)){
	                $objReferralInvitees = $em->getRepository('DhiUserBundle:ReferralInvitees')->findOneBy(array('emailId' => $referEmail, 'token' => $referToken));
	                
	                if($objReferralInvitees){
	                    $objReferralInvitees->setIsRegister(1);
	                    $em->persist($objReferralInvitees);
	                    $em->flush($objReferralInvitees);
	                }
                        /*else{ 
	                    $this->get('session')->getFlashBag()->add('failure', "Invalid email id or token please try again!");
	                    $url = $this->generateUrl('fos_user_registration_register');
	                    $response = new RedirectResponse($url);
	                    return $response;;
	                }*/

	            	}
	            	## End Here

                $isAradialExists = false;
                if($this->get('request')->request->get('isVerifiedAradial') == 'true'){
                    $isAradialExists = true;
                }
                $user->setIsAradialExists($isAradialExists);
                $user->setIpAddress($ip_address);
                $user->setIpAddressLong(ip2long($ip_address));
                $user->setEnabled(true);
                $user->setLocked(false);
                $user->setEncryPwd(base64_encode($password));

                $userManager->updateUser($user);
                if (null === $response = $event->getResponse()) {
                    $url = $this->generateUrl('fos_user_registration_confirmed');
                    $response = new RedirectResponse($url);
                }
                $dispatcher->dispatch(FOSUserEvents::REGISTRATION_COMPLETED, new FilterUserResponseEvent($user, $request, $response));


                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['user']         = $user;
                    $activityLog['activity']     = 'Registration';
                    $activityLog['description']  = 'User '.$user->getUsername().' is registered.';

                    $this->get('session')->set('userSessionId', $request->getSession()->getId());
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    //End here
                    return $response;
                }
            }
        }
        if(!$isServiceLocExistForUser){ // check User ip not in Service location
           
            return $this->render('DhiUserBundle:Registration:noServiceLocation.html.twig');
        }
        $email = base64_decode($request->get('email'));
        if($request->get('affiliate')){
            $data['affiliate'] = $request->get('affiliate');
            $this->get('session')->set('affiliate', $data['affiliate']);
            if($data['affiliate'] == 'bv'){
                return $this->render('DhiUserBundle:Registration:registerAffiliate.html.twig', array(
                'form' => $form->createView(),'affiliate' => $data['affiliate'], 'email' => $email
                ));
            }else if($data['affiliate'] == 'netgate'){
                return $this->render('DhiUserBundle:Registration:registerAffiliateNetgate.html.twig', array(
                'form' => $form->createView(),'affiliate' => $data['affiliate'], 'email' => $email
                ));
            }
        }else{
            if($this->get('session')->has('affiliate')){
                $affiliateValue =  $this->get('session')->get('affiliate');
                if($affiliateValue == 'bv'){
                    return $this->redirect($this->generateUrl('dhi_signup',array('affiliate'=>'bv', 'email' => $request->get('email'), 'token' => $request->get('token'))));
                }else if($affiliateValue == 'netgate'){
                    return $this->redirect($this->generateUrl('dhi_signup_netgate',array('affiliate'=>'netgate', 'email' => $request->get('email'), 'token' => $request->get('token'))));
                }
            }else{ 
                return $this->render('DhiUserBundle:Registration:register.html.twig', array(
                    'form' => $form->createView(),
                    'email' => $email
                ));
            }
        }
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction()
    {
        $email = $this->get('session')->get('fos_user_send_confirmation_email/email');
        $this->get('session')->remove('fos_user_send_confirmation_email/email');
        $user = $this->get('fos_user.user_manager')->findUserByEmail($email);

        $em = $this->getDoctrine()->getManager();
        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with email "%s" does not exist', $email));
        }

        if($user) {

                $userIpAddressZone = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($this->get('session')->get('ipAddress'));

                if($userIpAddressZone) {

                    $user->setUserServiceLocation($userIpAddressZone->getServiceLocation());
                    $em->persist($user);
                    $em->flush();
                }

            }

        $message = 'You have registered successfully. An email has been sent to the email address you entered. Open the email and click on the activation link now to activate your account!';

        $this->get('session')->getFlashBag()->clear();
        $this->get('session')->getFlashBag()->add('success', $message);

        return $this->redirect($this->generateUrl('dhi_user_account'));
    }

    /**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction(Request $request, $token)
    {
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            $session = $this->container->get('session')->isStarted();
            if($session){
                session_destroy();
            }
            return $this->redirect($this->generateUrl('dhi_user_invalid_token'));
        }

        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        // calculated the difference in hours to validate verification link
        $email_verification_date = $user->getEmailVerificationDate();
        $today = new DateTime();

        $diff = $today->diff($email_verification_date);
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);

         //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Email Confirmation';

        /* check whether the email verificatin link has expired or not. link is
         * valid for 72 hours only
        */
        if ($hours > 72) {
            /* START: add user audit log for email confirmation */
            $em = $this->getDoctrine()->getManager();

            $activityLog['description'] = 'User '.$user->getUsername().' has tried to confirm email after 72 hours';
            $this->get('ActivityLog')->saveActivityLog($activityLog);


            /* END: add user audit log for email confirmation */

            return $this->render('DhiUserBundle:Registration:confirm.html.twig', array('user' => $user));
        } else {

            $event = new GetResponseUserEvent($user, $request);
            $dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRM, $event);
            $user->setConfirmationToken(null);
            $user->setIsEmailVerified(true);
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('dhi_user_verification_success');
                $response = new RedirectResponse($url);
            }

            //$dispatcher->dispatch(FOSUserEvents::REGISTRATION_CONFIRMED, new FilterUserResponseEvent($user, $request, $response));

            /* START: add user audit log for email confirmation */
            $em = $this->getDoctrine()->getManager();

            $activityLog['description'] = 'User '.$user->getUsername().' has confirmed email';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for email confirmation */

            return $response;
        }

    }

    /**
     * Tell the user his account is now confirmed
     */
    public function confirmedAction()
    {
        $user = $this->getUser();
        if (!is_object($user) || !$user instanceof UserInterface) {
            throw new AccessDeniedException('This user does not have access to this section.');
        }

        return $this->render('DhiUserBundle:Registration:confirmed.html.twig', array(
            'user' => $user,
        ));
    }

    /**
     * Tell the user to resend the verification link
    */
    public function resendAction(Request $request, $token) {
        $userManager = $this->get('fos_user.user_manager');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with confirmation token "%s" does not exist', $token));
        }

        $tokenGenerator = $this->get('fos_user.util.token_generator');
        $token = $tokenGenerator->generateToken();
        $user->setConfirmationToken($token);
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
        $this->get('session')->getFlashBag()->add('success', sprintf('An email has been sent to "%s". It contains an activation link you must click to verify your email address.', $user->getEmail()));


        //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Resend Activation Email';
        $activityLog['description'] = 'User '.$user->getUsername().' has resend activation email';
        $this->get('ActivityLog')->saveActivityLog($activityLog);

//        return $this->render('DhiUserBundle:Registration:resend.html.twig', array(
//                    'user' => $user,
//        ));
        return $this->redirect($this->generateUrl('dhi_user_account'));
    }

    public function emailVerificationSuccessAction() {

        return $this->render('DhiUserBundle:Registration:verification_success.html.twig');
    }

    public function invalidVerificationTokenAction() {


        $message = 'This email verification link has expired.<br/>Please <a href="'.$this->generateUrl('fos_user_security_login').'">sign in</a> to re-send an email verification link.';
        return $this->render('DhiUserBundle:Registration:confirm_token.html.twig', array('message' => $message));
    }

    public function resendEmailAction(Request $request) {

        $userManager = $this->get('fos_user.user_manager');

        $user = $this->getUser();

        if($user) {

            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $token = $tokenGenerator->generateToken();
            $user->setEmailVerificationDate(new DateTime());
            $user->setConfirmationToken($token);
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

            $this->get('session')->getFlashBag()->set('success', sprintf('An email has been sent to "%s". It contains an activation link you must click to verify your email address.', $user->getEmail()));


            //Add Activity Log
            $activityLog = array();
            $activityLog['user']         = $user;
            $activityLog['activity']     = 'Resend Activation Email';
            $activityLog['description'] = 'User '.$user->getUsername().' has resend activation email';
            $this->get('ActivityLog')->saveActivityLog($activityLog);

        } else {

            $this->get('session')->getFlashBag()->add('danger', 'No user found!');

        }

        return $this->redirect($this->generateUrl('dhi_user_account'));

    }
}
