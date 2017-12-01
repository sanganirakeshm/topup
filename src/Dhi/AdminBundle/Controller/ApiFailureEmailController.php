<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\ApiFailureEmailFormType;
use Dhi\AdminBundle\Entity\ApiFailureEmail;

class ApiFailureEmailController extends Controller {

    /**
     * List Api Failure Email list in Admin panel
     */
    public function indexAction(Request $request) {

        return $this->render('DhiAdminBundle:ApiFailureEmail:index.html.twig');
    }
    
    public function apiFailureEmailJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $apiFailureColumns = array('Email','Status','Id');
        $admin = $this->get('security.context')->getToken()->getUser();  
        
        //Common search data 
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($apiFailureColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'ae.id';
            $sortOrder = 'DESC';
        } else {
            
            if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'ae.id';
            }
            if ($gridData['order_by'] == 'Email') {
                
                $orderBy = 'ae.email';
            }
            if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'ae.status';
            }                       
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiAdminBundle:ApiFailureEmail')->getApiFailureEmailGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {
            
            if (isset($data['result']) && !empty($data['result'])) {
                
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                                
                foreach ($data['result'] AS $resultRow) {
                                      
                    $row = array();
                    $row[] = $resultRow->getEmail();
                    $row[] = $resultRow->getStatus() == 'true' ? '<span class="btn btn-success btn-sm">Active<span>' : '<span class="btn btn-success btn-sm">Inactive</span>' ;
                    $row[] = $resultRow->getId();
                    
                    $output['aaData'][] = $row;
                }               
            }
        }

        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createForm(new ApiFailureEmailFormType(), new ApiFailureEmail());

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objApiFailureEmail = $form->getData();
                $em->persist($objApiFailureEmail);
                $em->flush();
                
                // set audit log add serach api failure email
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add API log email list';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new email in api failure successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Email added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_api_failure_email_list'));
            }
        }
        return $this->render('DhiAdminBundle:ApiFailureEmail:new.html.twig', array('form' => $form->createView()));        
    }

    public function editAction(Request $request, $id) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $apiFailureEmail = $em->getRepository('DhiAdminBundle:ApiFailureEmail')->find($id);

        if (!$apiFailureEmail) {
        	
            $this->get('session')->getFlashBag()->add('failure', "API failure email list does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_api_failure_email_list'));
        }
        
        $form = $this->createForm(new ApiFailureEmailFormType(), $apiFailureEmail);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objApiFailureEmail = $form->getData();
                $em->persist($objApiFailureEmail);
                $em->flush();
                
                // set audit log edit api failure email
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit api failure email';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated api failure email ". $objApiFailureEmail->getEmail();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Api failure email updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_api_failure_email_list'));
            }
        }

        return $this->render('DhiAdminBundle:ApiFailureEmail:edit.html.twig', array('form' => $form->createView(), 'apiFailureEmail' => $apiFailureEmail));        
    }

    public function deleteAction(Request $request) {
    	
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        // set audit log delete api failure email
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete api failure email';
        
        $objApiFailureEmail = $em->getRepository('DhiAdminBundle:ApiFailureEmail')->find($id);

        if ($objApiFailureEmail) {
        	
            $email = $objApiFailureEmail->getEmail();
            $em->remove($objApiFailureEmail);
            $em->flush();
          

            /* START: add user audit log for Delete api-failure-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted api failure email ". $email;
            $result = array('type' => 'success', 'message' => 'Email deleted successfully!');
            
        } else {

            /* START: add user audit log for Delete api-failure-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete api failure email ";
           
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete api failure email list!');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $response = new Response(json_encode($result));        
		$response->headers->set('Content-Type', 'application/json');     
        
		return $response;        
    }
    
}
