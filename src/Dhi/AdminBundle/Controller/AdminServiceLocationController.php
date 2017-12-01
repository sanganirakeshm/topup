<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\AdminServiceLocationFormType;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\UserActivityLog;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminServiceLocationController extends Controller {

    public function indexAction(Request $request, $id) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $countServiceLocation = 0;

        $user = $em->getRepository('DhiUserBundle:User')->find($id);

        if (!$user) {
            
            $this->get('session')->getFlashBag()->add('failure', "Admin user not found.");
            return $this->redirect($this->generateUrl('dhi_admin_list'));
        }
        //$objUser = $user ? $user : new User();

        if ($user) {

            $objUser = $user;
            $countServiceLocation = count($user->getServiceLocations());
            
        } else {
            
            $objUser = new User();
        }



        //Check permission
        if ($this->get('admin_permission')->checkPermission('admin_service_location_create')) {

            if ($countServiceLocation > 0) {
                
                $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add admin service location.");
            
            } else {
                
                $this->get('session')->getFlashBag()->add('failure', "You are not allowed to edit admin service location.");
            }
            
            return $this->redirect($this->generateUrl('dhi_admin_list'));
        }

        $objServiceLocation = new ServiceLocation();
        $objUser->addAdminServiceLocation($objServiceLocation);

        $form = $this->createForm(new AdminServiceLocationFormType(), $objUser);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objAdminServiceLocation = $form->getData();
                $em->persist($objAdminServiceLocation);
                $em->flush();
                
                // add user audit log for Add service-list
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = $countServiceLocation > 0 ? 'Edit Service location' : 'Add Service Location';
                $activityLog['description'] = $countServiceLocation > 0 ? 'Admin ' . $admin->getUsername() . ' has edited service location successfully' : 'Admin ' . $admin->getUsername() . ' has added service location successfully';

                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                if ($countServiceLocation > 0) {

                    $this->get('session')->getFlashBag()->add('success', "Service location edited successfully!");
                } else {

                    $this->get('session')->getFlashBag()->add('success', "Service location added successfully!");
                }

                return $this->redirect($this->generateUrl('dhi_admin_list'));
            }
        }
       
       
        return $this->render('DhiAdminBundle:AdminServiceLocation:index.html.twig', array('form' => $form->createView(), 'id' => $id, 'user' => $user));
    }

}
