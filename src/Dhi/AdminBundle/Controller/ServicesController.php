<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\ServicesFormType;
use Dhi\UserBundle\Entity\Service;
use Dhi\UserBundle\Entity\UserActivityLog;

class ServicesController extends Controller {

    /**
     * List Services in Admin panel
     */
    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('service_list') || $this->get('admin_permission')->checkPermission('service_create') || $this->get('admin_permission')->checkPermission('service_update') || $this->get('admin_permission')->checkPermission('service_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objServices = $em->getRepository('DhiUserBundle:Service')->getAllService();
        $services = array_map(function($service){ return $service['name']; } , $objServices);

        return $this->render('DhiAdminBundle:Services:index.html.twig', array('services'  => $services));
    }
    
    public function serviceListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $serviceColumns = array('Id','Name','Status');
        $admin = $this->get('security.context')->getToken()->getUser();  
        
        //Common search data 
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($serviceColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 's.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 's.id';
            }

            if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 's.name';
            }
             if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 's.status';
            }
            
           
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiUserBundle:Service')->getServiceGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                   
                   
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getName();
                    $row[] = $resultRow->getStatus() == 'true' ? '<span class="btn btn-success btn-sm">Active<span>' : '<span class="btn btn-success btn-sm">Inactive</span>' ;
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    
                    $output['aaData'][] = $row;
                }
                
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createForm(new ServicesFormType(), new Service());

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objServices = $form->getData();
                $em->persist($objServices);
                $em->flush();
                
                // set audit log add serach service
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new service successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_list'));
            }
        }
        return $this->render('DhiAdminBundle:Services:new.html.twig', array('form' => $form->createView()));        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $services = $em->getRepository('DhiUserBundle:Service')->find($id);

        if (!$services) {
            $this->get('session')->getFlashBag()->add('failure', "Service does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_service_list'));
        }
        
        $form = $this->createForm(new ServicesFormType(), $services);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objServices = $form->getData();
                $em->persist($objServices);
                $em->flush();
                
                // set audit log edit serach service
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated service ". $objServices->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_list'));
            }
        }

        return $this->render('DhiAdminBundle:Services:edit.html.twig', array('form' => $form->createView(), 'services' => $services));        
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete service.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        // set audit log delete service
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete service';
        
        $objServices = $em->getRepository('DhiUserBundle:Service')->find($id);

        if ($objServices) {
            $serviceName = $objServices->getName();
            $em->remove($objServices);
            $em->flush();
          

            /* START: add user audit log for Delete service-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted service ". $serviceName;
            $result = array('type' => 'success', 'message' => 'Service deleted successfully!');
            
        } else {

            /* START: add user audit log for Delete service-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service ";
           
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete service!');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));        
	$response->headers->set('Content-Type', 'application/json');     
        return $response;
        
    }
    
}
