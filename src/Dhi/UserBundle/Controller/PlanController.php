<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use \DateTime;

class PlanController extends Controller {

    public function indexAction(Request $request) {
        //
    }
    
    public function addAction(Request $request) {
        
        $em = $this->getDoctrine()->getManager();
        $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneByName('maintenance_mode');
               
        if('false' === $objSetting->getValue()) {
            return $this->render('DhiUserBundle:Plan:add.html.twig');
        } else {
            return $this->redirect($this->generateUrl('dhi_user_plan_maintenance'));
        }
    }
    
    public function maintenanceAction() {
        return $this->render('DhiUserBundle:Plan:maintenance.html.twig');
    }
}