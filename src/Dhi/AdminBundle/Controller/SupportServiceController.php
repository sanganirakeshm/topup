<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\SupportService;
use Dhi\AdminBundle\Form\Type\SupportServiceFormType;

class SupportServiceController extends Controller
{
    public function indexAction(Request $request) {
        
        if (!($this->get('admin_permission')->checkPermission('support_service_list') || $this->get('admin_permission')->checkPermission('support_service_new') || $this->get('admin_permission')->checkPermission('support_service_edit') || $this->get('admin_permission')->checkPermission('support_service_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view support service list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objAdmins  = $em->getRepository('DhiUserBundle:User')->getAllAdmin();
        $arrAllAdmins = array();
        if($objAdmins){
            foreach ($objAdmins as $admin){
                $arrAllAdmins[] = $admin['username'];
            }
        }
        return $this->render('DhiAdminBundle:SupportService:index.html.twig', array('allAdmins' => $arrAllAdmins));
    }
    
    public function listJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $supportCategoryColumns = array('ServiceName','Status', 'CreatedBy');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($supportCategoryColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'ss.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'ServiceName') {
                
                $orderBy = 'ss.serviceName';
            }elseif ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'ss.isActive';
            }elseif ($gridData['order_by'] == 'CreatedBy') {
                
                $orderBy = 'u.username';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiUserBundle:SupportService')->getSupportServiceGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                    $row[] = $resultRow['serviceName'];                   
                    $row[] = $resultRow['isActive'] ? 'Active' : 'InActive';
                    $row[] = $resultRow['username'];
                    $row[] = $resultRow['id'];
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
        if(! $this->get('admin_permission')->checkPermission('support_service_new')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add support service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objSupportService = new SupportService();
        $form = $this->createForm(new SupportServiceFormType(), $objSupportService);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $ipAddress = $this->get('session')->get('ipAddress');
                $objSupportService->setCreatedBy($admin->getId());
                $objSupportService->setUpdatedBy($admin->getId());
                $objSupportService->setIpAddress($ipAddress);
                $em->persist($objSupportService);
                $em->flush();
                
                // set audit log search group
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Support Service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added support service : " . $objSupportService->getServiceName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                 
                $this->get('session')->getFlashBag()->add('success', 'Support service added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_service_list'));
            }
        }
        return $this->render('DhiAdminBundle:SupportService:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('support_service_edit')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update support service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objSupportService = $em->getRepository('DhiUserBundle:SupportService')->findOneBy(array('id' => $id, 'isDeleted' => 0));

        if (!$objSupportService) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support service.");
            return $this->redirect($this->generateUrl('dhi_admin_support_service_list'));
        }

        $form = $this->createForm(new SupportServiceFormType(), $objSupportService);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $ipAddress = $this->get('session')->get('ipAddress');
                $objSupportService->setUpdatedBy($admin->getId());
                $objSupportService->setIpAddress($ipAddress);
                $em->persist($objSupportService);
                $em->flush();
                
                // set audit log edit support categoey
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Support Service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated support service " . $objSupportService->getServiceName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support service updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_service_list'));
            }
        }

        return $this->render('DhiAdminBundle:SupportService:edit.html.twig', array(
                    'form' => $form->createView(),
                    'id' => $id
        ));
    }
    
    public function deleteAction(Request $request) {
        
        $id = $request->get('id'); 
        if(! $this->get('admin_permission')->checkPermission('support_service_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete support service.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Support Service';

        $objSupportService = $em->getRepository('DhiUserBundle:SupportService')->findOneBy(array('id' => $id, 'isDeleted' => 0));

        if ($objSupportService) {
            
            $objSupportService->setIsDeleted(true);
            $em->persist($objSupportService);
            $em->flush();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted support service " . $objSupportService->getServiceName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $result = array('type' => 'success', 'message' => 'Support service deleted successfully!');
           
        } else {
             $result = array('type' => 'danger', 'message' => 'Unable to find support service.');
        }
       
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
}
