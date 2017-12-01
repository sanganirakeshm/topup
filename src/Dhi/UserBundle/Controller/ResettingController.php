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
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Form\Type\RequestPasswordFormType;

/**
 * Controller managing the resetting of the password
 *
 * @author Thibault Duplessis <thibault.duplessis@gmail.com>
 * @author Christophe Coevoet <stof@notk.org>
 */
class ResettingController extends Controller {

    /**
     * Request reset user password: show form
     */
    public function requestAction() {
        
        $requestPasswordForm = $this->createForm(new RequestPasswordFormType());
        
        return $this->render('DhiUserBundle:Resetting:request.html.twig',array('requestPasswordForm' => $requestPasswordForm->createView()));
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction(Request $request) {
        
        $requestPasswordForm = $this->createForm(new RequestPasswordFormType());
        
        if ($request->getMethod() == "POST") {

            $requestPasswordForm->handleRequest($request);

            if ($requestPasswordForm->isValid()) {
                //$username = $request->request->get('username');
                //resettype = [password, username]
                /** @var $user UserInterface */
                
                $username = $this->sanitizeString($requestPasswordForm->get('usernameoremail')->getData());
                
                if ($request->request->get('resettype') == 'username') {
                    $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
                } else {

                    $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);
                }

                if (null === $user) {

                    return $this->render('DhiUserBundle:Resetting:request.html.twig', array(
                                'invalid_email' => $username, 'requestPasswordForm' => $requestPasswordForm->createView()
                    ));
                }

                /*
                  if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                  return $this->render('DhiUserBundle:Resetting:passwordAlreadyRequested.html.twig');
                  }
                 * 
                 */

                if (null === $user->getConfirmationToken()) {
                    /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                    $tokenGenerator = $this->get('fos_user.util.token_generator');
                    $user->setConfirmationToken($tokenGenerator->generateToken());
                }

                // check whether the link has been expired or not (72 hrs)
                $password_requested_date = $user->getPasswordRequestedAt();
                if ($password_requested_date) {
                    $today = new DateTime();

                    $diff = $today->diff($password_requested_date);
                    $hours = $diff->h;
                    $hours = $hours + ($diff->days * 24);

                    /* check whether the email verificatin link has expired or not. link is 
                     * valid for 72 hours only
                     */
                    if ($hours > 72) {
                        /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                        $tokenGenerator = $this->get('fos_user.util.token_generator');
                        $user->setConfirmationToken($tokenGenerator->generateToken());
                    }
                }


                //Add Activity Log
                $activityLog = array();
                $activityLog['user'] = $user;

                if ($request->request->get('resettype') == 'username') {
                    
                    $session = $this->container->get('session');
                    $whitelabel = $session->get('brand');
                    if($whitelabel){
                        $subject   = 'Welcome '.$user->getUsername().' to '. $whitelabel['name'];
                        $fromEmail = $whitelabel['fromEmail'];
                        $compnayname = $whitelabel['name'];
                        $compnaydomain = $whitelabel['domain'];
                    } else {
                        $subject         = "Welcome " . $user->getUsername() . " to ExchangeVUE!";
                        $fromEmail       = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                        $compnayname     = 'ExchangeVUE';
                        $compnaydomain   = 'exchangevue.com';
                    }

                    $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:forgot_username_email.html.twig', array('user' => $user,'companyname'=>$compnayname,'companydomain'=>$compnaydomain));

                    $forgotusername_email = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom($fromEmail)
                            ->setTo($user->getEmail())
                            ->setBody($body->getContent())
                            ->setContentType('text/html');

                    $this->container->get('mailer')->send($forgotusername_email);

                    $activityLog['activity'] = 'Forgot Username Request';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has made request for forgot username';
                } else { // password
                    $session = $this->container->get('session');
                    $whitelabel = $session->get('brand');
                    if($whitelabel){
                        $subject   = $whitelabel['name']. ' Password Reset' ;
                        $fromEmail = $whitelabel['fromEmail'];
                        $compnayname = $whitelabel['name'];
                        $compnaydomain = $whitelabel['domain'];
                    } else {
                        $subject         = "ExchangeVUE Password Reset";
                        $fromEmail       = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                        $compnayname     = 'ExchangeVUE';
                        $compnaydomain   = 'exchangevue.com';
                    }

                    $url = $this->generateUrl('dhi_user_reset_password', array('token' => $user->getConfirmationToken()), true);
                    $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Resetting:email_resetting.html.twig', array('username' => $user, 'confirmationUrl' => $url,'companyname'=>$compnayname));

                    $resend_email_verification = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom($fromEmail)
                            ->setTo($user->getEmail())
                            ->setBody($body->getContent())
                            ->setContentType('text/html');
                    $this->container->get('mailer')->send($resend_email_verification);
                     
                    $activityLog['activity'] = 'Forgot Password Request';
                    $activityLog['description'] = 'User ' . $user->getUsername() . ' has made request for forgot password';
                }
                //
                $user->setPasswordRequestedAt(new \DateTime());
                $this->get('fos_user.user_manager')->updateUser($user);

                $this->get('ActivityLog')->saveActivityLog($activityLog);
                return new RedirectResponse($this->generateUrl('fos_user_resetting_check_email', array('email' => $user->getEmail() ? $user->getEmail() : '')
                ));
        
            }
        }
        
        return $this->render('DhiUserBundle:Resetting:request.html.twig', array('requestPasswordForm' => $requestPasswordForm->createView()));
        
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction(Request $request) {
        $email = $request->query->get('email');

        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('fos_user_resetting_request'));
        }
        
        //$message = 'An email has been sent to ' . $email . '. It contains a link you must click to reset your password.';
        $message = 'Email was sent.';
        $this->get('session')->getFlashBag()->clear();
        $this->get('session')->getFlashBag()->add('success', $message);
        return $this->redirect($this->generateUrl('fos_user_resetting_request'));
        
        /*
          return $this->render('DhiUserBundle:Resetting:checkEmail.html.twig', array(
          'email' => $email,
          )); */
    }

    /**
     * Reset user password
     */
    public function resetAction(Request $request, $token) {
        /** @var $formFactory \FOS\UserBundle\Form\Factory\FactoryInterface */
        $formFactory = $this->get('fos_user.resetting.form.factory');
        /** @var $userManager \FOS\UserBundle\Model\UserManagerInterface */
        $userManager = $this->get('fos_user.user_manager');
        /** @var $dispatcher \Symfony\Component\EventDispatcher\EventDispatcherInterface */
        $dispatcher = $this->get('event_dispatcher');

        $user = $userManager->findUserByConfirmationToken($token);

        if (null === $user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        // check whether the link has been expired or not (72 hrs)
        $password_requested_date = $user->getPasswordRequestedAt();
        $today = new DateTime();

        $diff = $today->diff($password_requested_date);
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);

        /* check whether the email verificatin link has expired or not. link is 
         * valid for 72 hours only
         */
        if ($hours > 72) {

            /* START: add user audit log for reset password */
            $em = $this->getDoctrine()->getManager();
            
            $activityLog = array();
            $activityLog['user']  = $user;
            $activityLog['activity']     = 'Reset password';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has tried to reset password after 72 hours';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $this->get('session')->getFlashBag()->add('failure', "Your password reset link has been expired. please resend new link");
            return $this->render('DhiUserBundle:Resetting:request.html.twig');
        }

        $event = new GetResponseUserEvent($user, $request);
        $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_INITIALIZE, $event);

        if (null !== $event->getResponse()) {
            return $event->getResponse();
        }

        $form = $formFactory->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            
            $changePasswordArr = $request->request->get('fos_user_resetting_form');
            $newPassword = $changePasswordArr['plainPassword']['first'];
            
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

            if($changePasswordArr['plainPassword']['first'] != "" &&  $changePasswordArr['plainPassword']['second'] != "") {
                
                $user->setEncryPwd(base64_encode($changePasswordArr['plainPassword']['first']));
                
            }
            
            $userManager->updateUser($user);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('dhi_password_reset_success');
                $response = new RedirectResponse($url);
            }

            /* START: add user audit log for reset password */
            
            $activityLog = array();
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Reset password';
            $activityLog['description'] = 'User ' . $user->getUsername() . ' has reset password successfully';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for Search admin */
            
            ############################### START Selevision API #################################
            //Check user exits in selevision
            $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);
            
            if ($selevisionUserExist['status'] == 1  && $selevisionUserExist['serviceAvailable'] == 1) {
                
                $currentPassword = $selevisionUserExist['password'];// get plain password for selevisions

                if ($currentPassword != $newPassword) {
                
                    // call selevisions service to update password
                    $wsParam = array();
                    $wsParam['cuLogin']   = $user->getUsername();
                    $wsParam['cuPwd']     = $currentPassword;
                    $wsParam['cuNewPwd1'] = $newPassword;
                    $wsParam['cuNewPwd2'] = $newPassword;
                
                    $wsResponse = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);
                }
            } else {
                        
                if($selevisionUserExist['serviceAvailable'] == 0) {
                        
                    //Store API fail log
                    $this->get('paymentProcess')->serviceAPIErrorLog($user,'ChangePassword','Selevision');
                }
            }
            ############################### END Selevisoin API #########################################
            
            
            ################################ START Aradial API #########################################
            //Check user exist in aradial
            $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());
                    
            if($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1 && $newPassword) {
            
                $wsParam = array();
                $wsParam['Page']      =  "UserEdit";
                $wsParam['Modify']    = 1;
                $wsParam['UserID']    = $user->getUsername();
                $wsParam['Password']  = $newPassword;
            
                $aradialResponse = $this->get('aradial')->callWSAction('updateUser',$wsParam);
            } else {
                    
                if($aradialUserExist['serviceAvailable'] == 0) {
                    
                    //Store API fail log
                    $this->get('paymentProcess')->serviceAPIErrorLog($user,'ChangePassword','Aradial');
                }
            }
            ################################## END Aradial API ########################################
            

            //$dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
            
        }

        return $this->render('DhiUserBundle:Resetting:reset.html.twig', array(
                    'token' => $token,
                    'form' => $form->createView(),
        ));
    }

    /**
     * Get the truncated email displayed when requesting the resetting.
     *
     * The default implementation only keeps the part following @ in the address.
     *
     * @param \FOS\UserBundle\Model\UserInterface $user
     *
     * @return string
     */
    protected function getObfuscatedEmail(UserInterface $user) {
        $email = $user->getEmail();
        if (false !== $pos = strpos($email, '@')) {
            $email = '...' . substr($email, $pos);
        }

        return $email;
    }

    public function resetSuccessAction() {
        // clear the session
        $this->get('session')->invalidate();
        return $this->render('DhiUserBundle:Resetting:reset_success.html.twig');
    }
    
    /**
    * @param string $input The input string
    * @param string $encoding Which character encoding are we using?
    * @return string
    */
    protected function sanitizeString($input) {
        
        return filter_var($input,FILTER_SANITIZE_SPECIAL_CHARS);
        
    }

}
