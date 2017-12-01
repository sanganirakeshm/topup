<?php

namespace Dhi\AdminBundle\EventListener;


use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Router;

/**
 * Listener responsible to change the redirection at the end of the password resetting
 */
class AccessDeniedListener {

    protected $_session;
    protected $_router;
    protected $_request;

    public function __construct(Session $session, Router $router, Request $request) {
        $this->_session = $session;
        $this->_router = $router;
        $this->_request = $request;
    }

    public function onAccessDeniedException(GetResponseForExceptionEvent $event) {
        if ($event->getException()->getMessage() == 'Access Denied') {
            $this->_session->getFlashBag()->add('danger', "You are not authrize to access");
            $event->setResponse(new RedirectResponse($this->_router->generate('admin_login')));
        }
        
    }

}

?>