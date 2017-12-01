<?php

namespace Dhi\UserBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine; 

use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Symfony\Component\Routing\Router;
use Dhi\UserBundle\Entity\UserActivityLog;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class LogoutListener implements LogoutSuccessHandlerInterface
{
	/** @var \Symfony\Component\Security\Core\SecurityContext */
	private $securityContext;
	
	/** @var \Doctrine\ORM\EntityManager */
	private $em;
	
        private $router;
        
        protected $session;
        
	/**
	 * Constructor
	 * 
	 * @param SecurityContext $securityContext
	 * @param Doctrine        $doctrine
	 */
	public function __construct(Router $router, SecurityContext $securityContext, Doctrine $doctrine, Session $session)
	{ 
		$this->securityContext = $securityContext;
		$this->em              = $doctrine->getEntityManager();
                $this->router = $router;
                $this->session = $session;
                
	}
	
	/**
	 * 
	 * @param request $request
	 */
	public function onLogoutSuccess(Request $request)
        { 
            if( ! $this->securityContext->getToken()) {
                if(strpos($request->getPathInfo(), '/admin/') !== false) { 
                    $response = new RedirectResponse($this->router->generate('admin_login'));
                } else {
                    $response = new RedirectResponse($this->router->generate('fos_user_security_login'));
                }
                return $response;
            }
            $admin = $this->securityContext->getToken()->getUser();
            $isValidAccess = true;
            // Create object of ActivityLog
            $objActivityLog = new UserActivityLog();
            
            if (
                    $admin->hasRole('ROLE_SUPER_ADMIN')
                    || $admin->hasRole('ROLE_HELPDESK')
                    || $admin->hasRole('ROLE_CASHIER')
                    || $admin->hasRole('ROLE_MANAGER')
                    || $admin->hasRole('ROLE_ADMIN')

                ) {
                
                if(strpos($request->getPathInfo(), '/admin/') !== false) { 
                    $route = $this->router->generate('admin_login');
                    $admin->setIsloggedin(false);
                    
                    $this->em->persist($admin);
                    //$this->em->flush();
                
                    /* START: add admin audit log for logout activity */
                    $objActivityLog->setAdmin($admin);
                    $adminSessionId = $this->session->get('adminSessionId');
                    if(!$adminSessionId){
                        $adminSessionId = $request->getSession()->getId();
                    }
                    $objActivityLog->setSessionId($adminSessionId);
                    $objActivityLog->setDescription('Admin ' . $admin->getUsername() . ' has logged out.');
                    /* END: add admin audit log for logout activity */
                
                    // set routing for adminpanel
                    $this->session->remove('adminSessionId');
                }else{
                    $isValidAccess = false;
                    $route = $this->router->generate('fos_user_security_login');
                }
                
            } else {

                /* START: add user audit log for logout activity */
                if($admin->getIsSuspended() == TRUE ){
                    $isValidAccess = false;
                }
                $objActivityLog->setUser($admin);
                $userSessionId = $this->session->get('userSessionId');
                if(!$userSessionId){
                    $userSessionId = $request->getSession()->getId();
                }
                $objActivityLog->setSessionId($userSessionId);
                $objActivityLog->setDescription('User ' . $admin->getUsername() . ' has logged out');
                /* END: add user audit log for logout activity */

                // set routing for frontend
                $route = $this->router->generate('fos_user_security_login');
                $this->session->remove('userSessionId');

                if ($this->session->has('UserLocationWiseService')) {
                    $this->session->remove('UserLocationWiseService');
                }

                if ($this->session->has('serviceLocationId')) {
                    $this->session->remove('serviceLocationId');
                }

                if ($this->session->has('serviceLocationName')) {
                    $this->session->remove('serviceLocationName');
                }
                
                if ($this->session->has('ChaseWPInfo')) {
                   $this->session->remove('ChaseWPInfo');
                }
                
                if ($this->session->has('chaseUserErrorMsg')) {
                    $this->session->remove('chaseUserErrorMsg');
                }

                if ($this->session->has('availableServiceLocationOnSite')) {
                    $this->session->remove('availableServiceLocationOnSite');
                }
            }
            
            
            $whiteLabelBrand = $this->session->get('brand');
            if($whiteLabelBrand){
                $objActivityLog->setWhiteLabelId($whiteLabelBrand['id']);
            }
            
            /* START: add user audit log for logout activity */
            $objActivityLog->setActivity('Logout');
            $objActivityLog->setIp($request->server->get('REMOTE_ADDR'));
            $objActivityLog->setVisitedUrl($request->getUri());
            
            if($isValidAccess){
                $this->em->persist($objActivityLog);
                $this->em->flush();
            }
            /* END: add user audit log for logout activity */
            
            $response = new RedirectResponse($route);

            return $response;
	}
       
}


