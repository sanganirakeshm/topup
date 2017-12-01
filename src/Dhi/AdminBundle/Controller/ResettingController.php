<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dhi\AdminBundle\Controller;

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
    public function requestAction(Request $request) {
        
        $requestPasswordForm = $this->createForm(new RequestPasswordFormType());
        
        return $this->render('DhiAdminBundle:Resetting:request.html.twig', array('requestPasswordForm' => $requestPasswordForm->createView()));
    }

    /**
     * Request reset user password: submit form and send email
     */
    public function sendEmailAction(Request $request) {
        
        $requestPasswordForm = $this->createForm(new RequestPasswordFormType());
        
        if ($request->getMethod() == "POST") {

            $requestPasswordForm->handleRequest($request);

            if ($requestPasswordForm->isValid()) {
                
                    
                    $username = $this->sanitizeString($requestPasswordForm->get('usernameoremail')->getData());
                    
                    /** @var $user UserInterface */
                    $user = $this->get('fos_user.user_manager')->findUserByUsernameOrEmail($username);

                    if (null === $user) {
                        $this->get('session')->getFlashBag()->add('danger', 'The username or email address "' . $username . '" does not exist.');
                        return $this->redirect($this->generateUrl('dhi_admin_password_resetting_request'));
                    }
                    $role = $user->getRoles();
                    if ($user && $role[0] == 'ROLE_USER') {
                        $this->get('session')->getFlashBag()->add('danger', 'You are not allow to reset password.');
                        return $this->redirect($this->generateUrl('dhi_admin_password_resetting_request'));
                    }

                    // not required to check whether request already sent or not.
                    /* if ($user->isPasswordRequestNonExpired($this->container->getParameter('fos_user.resetting.token_ttl'))) {
                      return $this->render('DhiUserBundle:Resetting:passwordAlreadyRequested.html.twig');
                      } */

                    if (!$user->getConfirmationToken()) {
                        /** @var $tokenGenerator \FOS\UserBundle\Util\TokenGeneratorInterface */
                        $tokenGenerator = $this->get('fos_user.util.token_generator');
                        $user->setConfirmationToken($tokenGenerator->generateToken());
                    }
                    
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

                    $url = $this->generateUrl('dhi_admin_reset_password', array('token' => $user->getConfirmationToken()), true);
                    $body = $this->container->get('templating')->renderResponse('DhiAdminBundle:Resetting:email_resetting.html.twig', array('username' => $user, 'confirmationUrl' => $url,'companyname'=>$compnayname));

                    $resend_email_verification = \Swift_Message::newInstance()
                            ->setSubject($subject)
                            ->setFrom($fromEmail)
                            ->setTo($user->getEmail())
                            ->setBody($body->getContent())
                            ->setContentType('text/html');

                    $this->container->get('mailer')->send($resend_email_verification);

                    $user->setPasswordRequestedAt(new \DateTime());
                    $this->get('fos_user.user_manager')->updateUser($user);

                    // set audit log forgot password request
                    $activityLog = array();
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Forgot password request';
                    $activityLog['description'] = "User '" . $user->getUsername() . "' has made request for forgot password.";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    return new RedirectResponse($this->generateUrl('dhi_admin_resetting_check_email', array('email' => $this->getObfuscatedEmail($user))
                  ));
            }
        }
        
        return $this->render('DhiAdminBundle:Resetting:request.html.twig', array('requestPasswordForm' => $requestPasswordForm->createView()));
    }

    /**
     * Tell the user to check his email provider
     */
    public function checkEmailAction(Request $request) {
        $email = $request->query->get('email');
        if (empty($email)) {
            // the user does not come from the sendEmail action
            return new RedirectResponse($this->generateUrl('dhi_admin_password_resetting_request'));
        }
		
		 $this->get('session')->getFlashBag()->add('success', "An email has been sent to ".$email.". It contains a link you must click to reset your password.");
		
		 $url = $this->generateUrl('admin_login');
                 $response = new RedirectResponse($url);
		 return $response;
		

        return $this->render('DhiAdminBundle:Resetting:checkEmail.html.twig', array(
                    'email' => $email,
        ));
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

        if (!$user) {
            throw new NotFoundHttpException(sprintf('The user with "confirmation token" does not exist for value "%s"', $token));
        }

        // check whether the link has been expired or not (72 hrs)
        $password_requested_date = $user->getPasswordRequestedAt();
        $today = new DateTime();

        $diff = $today->diff($password_requested_date);
        $hours = $diff->h;
        $hours = $hours + ($diff->days * 24);

        
        // set audit log forgot password request
        $activityLog = array();
        $activityLog['user'] = $user;
        
        /* check whether the email verificatin link has expired or not. link is 
         * valid for 72 hours only
         */
        if ($hours > 72) {

            $activityLog['activity'] = 'Reset password after 72 hours';
            $activityLog['description'] = "User '" . $user->getUsername() . "' has tried to reset password after 72 hours.";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $this->get('session')->getFlashBag()->add('failure', "Your password reset link has been expired. please resend new link");
            return $this->render('DhiAdminBundle:Resetting:request.html.twig');
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
            $event = new FormEvent($form, $request);
            $dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_SUCCESS, $event);

            $userManager->updateUser($user);

            $activityLog['activity'] = 'Reset password';
            $activityLog['description'] = "User '" . $user->getUsername() . "' has changed password.";

            $this->get('ActivityLog')->saveActivityLog($activityLog);

            if (null === $response = $event->getResponse()) {
                $url = $this->generateUrl('dhi_admin_password_reset_success');
                $response = new RedirectResponse($url);
            }

            //$dispatcher->dispatch(FOSUserEvents::RESETTING_RESET_COMPLETED, new FilterUserResponseEvent($user, $request, $response));

            return $response;
        }

        return $this->render('DhiAdminBundle:Resetting:reset.html.twig', array(
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
        return $user->getEmail();

        // not required to hide email
        /* $email = $user->getEmail();
          if (false !== $pos = strpos($email, '@')) {
          $email = '...' . substr($email, $pos);
          }

          return $email; */
    }

    public function resetSuccessAction() {
        // clear the session
        $this->get('session')->invalidate();
		
		 $this->get('session')->getFlashBag()->add('success', "Congratulations, Your password has been reset successfully.");
		
		 $url = $this->generateUrl('admin_login');
         $response = new RedirectResponse($url);
		 return $response;
		
		
        return $this->render('DhiAdminBundle:Resetting:reset_success.html.twig');
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
