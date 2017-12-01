<?php

namespace Dhi\AdminBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\Session\Session;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface {

    protected $router;
    protected $security;
    protected $session;
    protected $em;

    public function __construct(Router $router, SecurityContext $security, Session $session, Doctrine $doctrine) {
        $this->router = $router;
        $this->security = $security;
        $this->session = $session;
        $this->em = $doctrine->getEntityManager();
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token) {
        
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $admin = $this->security->getToken()->getUser();
            $group = $admin->getGroupObject();
           
            $this->session->set('permissions', $group->getGroupPermissionArr());
            
            $objSettings = $this->em->getRepository('DhiAdminBundle:Setting')->findAll();
            
            foreach ($objSettings as $setting) {
                $this->session->set($setting->getName(), $setting->getValue());
            }    
            $this->session->set('adminSessionId', $request->getSession()->getId());
            $response = new RedirectResponse($this->router->generate('dhi_admin_dashboard'));
        } else {
            
            $this->session->getFlashBag()->add('danger', 'Invalid credentials.');
            $response = new RedirectResponse($this->router->generate('admin_login'));
        }

        return $response;
    }

}
