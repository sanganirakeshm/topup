<?php

namespace Dhi\UserBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface {

    protected $router;
    protected $security;
    protected $session;
    protected $container;
    private $em;
    

    public function __construct(Router $router, SecurityContext $security, Session $session, Doctrine $doctrine, $container) {
        $this->router = $router;
        $this->security = $security;
        $this->session = $session;
        $this->em = $doctrine->getEntityManager();
        $this->container = $container;
        
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
        
        
        if ($this->security->isGranted('ROLE_ADMIN')) {
        
            $this->session->getFlashBag()->add('danger', 'Invalid credentials.');
            $response = new RedirectResponse($this->router->generate('fos_user_security_logout'));
            
            
        } else {
            
            $user = $this->security->getToken()->getUser();
            $userMacAddress = $this->em->getRepository('DhiUserBundle:UserSetting')->findOneBy(array('user' => $user));
            
            if($userMacAddress && $userMacAddress->getMacAddress() != "") {
                
                $this->session->set('maxMacAddress', $userMacAddress->getMacAddress());
            }
            else {
                
                $userMacAddress = $this->em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'mac_address'));
                
                $this->session->set('maxMacAddress', $userMacAddress->getValue());
            }

            // check for user suspended
            if($user->getIsSuspended() == TRUE ){
                $this->session->getFlashBag()->add('danger', 'Your account is Suspended');
                $response = new RedirectResponse($this->router->generate('fos_user_security_logout'));
                
            }else{
            		$this->session->set('userSessionId', $request->getSession()->getId());
                $response = new RedirectResponse($this->router->generate('dhi_user_account'));
            }
        }

        return $response;
    }

    /**
    * onAuthenticationFailure
    *
    * @author  Joe Sexton <joe@webtipblog.com>
    * @param  Request $request
    * @param  AuthenticationException $exception
    * @return  Response
    */
    public function onAuthenticationFailure( Request $request, AuthenticationException $exception )
   {
    
    if ( $request->isXmlHttpRequest() ) {

     $array = array( 'success' => false, 'message' => $exception->getMessage() ); // data to return via JSON
     $response = new Response( json_encode( $array ) );
     $response->headers->set( 'Content-Type', 'application/json' );

     return $response;

    // if form login 
    } else {

     // set authentication exception to session
     $request->getSession()->set(SecurityContextInterface::AUTHENTICATION_ERROR, $exception);
    
     return new RedirectResponse( $this->router->generate( 'fos_user_security_login' ) );
    }
   }
}
