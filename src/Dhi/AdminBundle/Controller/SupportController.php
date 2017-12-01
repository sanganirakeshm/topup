<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SupportController extends Controller {

    public function indexAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $query = $em->getRepository('DhiUserBundle:Support')->getAllSupports();

        $searchParams = $request->query->all();

        if (isset($searchParams)) {
            
            // set audit log search support
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Search support';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " searched " . json_encode($searchParams);
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $query = $em->getRepository('DhiUserBundle:Support')->getAllSupportsSearch($query, $searchParams);
        }

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        $supportCategories = $em->getRepository('DhiUserBundle:SupportCategory')->findAll();

        return $this->render('DhiAdminBundle:Support:index.html.twig', array(
                    'pagination' => $pagination,
                    'supportCategories' => $supportCategories
        ));
    }

    public function changeStatusAction($id, $status) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objSupport = $em->getRepository('DhiUserBundle:Support')->find($id);

        if (!$objSupport) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support ticket.");
        } else {
            
            if ($objSupport->getStatus() == 'New') {
            
                $objSupport->setStatus('InProgress');
            } elseif ($objSupport->getStatus() == 'InProgress') {
                
                $objSupport->setStatus('Resolved');
            }
            
            // set audit log admin user change password
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Change support status';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " change support status ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->persist($objSupport);
            $em->flush();
            
            $this->get('session')->getFlashBag()->add('success', 'You have successfully changed status.');
        }

        return $this->redirect($this->generateUrl('dhi_admin_support_list'));
    }

    public function viewAction($id) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objSupport = $em->getRepository('DhiUserBundle:Support')->find($id);

        if (!$objSupport) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support ticket.");
            return $this->redirect($this->generateUrl('dhi_admin_support_list'));
        }

        return $this->render('DhiAdminBundle:Support:view.html.twig', array(
                    'support' => $objSupport
        ));
    }

}
