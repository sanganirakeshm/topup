<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\BusinessFormType;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Entity\Business;
use \DateTime;

class BusinessController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('business_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view business list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();

        $objService = $em->getRepository("DhiUserBundle:Service")->findAll();
        $services = array();
        foreach ($objService as $service) {
            if(in_array($service->getName(), array('IPTV', 'ISP', 'BUNDLE'))){
                $services[] = $service->getName();
            }
        }
        $arrBusiness  = $em->getRepository('DhiAdminBundle:Business')->getAllBusinessNames();
        $admin = $this->get('security.context')->getToken()->getUser();              
        return $this->render('DhiAdminBundle:Business:index.html.twig', array('admin' => $admin, 'services' => $services, 'businesses' => $arrBusiness));
    }
    
    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $adminColumns = array("Id","name", "pocName", "pocEmail", "pocPhone", "service", "status");
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'b.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'b.id';
            }
            if ($gridData['order_by'] == 'name') {
                $orderBy = 'b.name';
            }
            if ($gridData['order_by'] == 'pocName') {
                $orderBy = 'b.pocName';
            }
              if ($gridData['order_by'] == 'pocEmail') {
                $orderBy = 'b.pocEmail';
            }
            if ($gridData['order_by'] == 'pocPhone') {
                $orderBy = 'b.pocPhone';
            } 
            if ($gridData['order_by'] == 'status') {
                $orderBy = 'b.status';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        $data  = $em->getRepository('DhiAdminBundle:Business')->getBusinessGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin);
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
                    $flagDelete  = 0;
                    $delete      = '';
                    $flagDelete  = 1; 
                    $delete      = $resultRow->getId().'^'.$flagDelete;
                    $serviceName = "";
                    $services    = $resultRow->getServices();
                    if ($services) {
                        foreach ($services as $service) {
                            $serviceName .= '<span class="btn btn-success btn-sm service">' .$service->getName(). '</span>';
                        }
                    }
                    $status = '<span class="btn btn-success btn-sm service">' .($resultRow->getStatus() ? 'Active':'Inactive'). '</span>';

                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getName();
                    $row[] = ($resultRow->getPocName()) ? $resultRow->getPocName() : 'N/A';
                    $row[] = ($resultRow->getPocEmail()) ? $resultRow->getPocEmail() : 'N/A';
                    $row[] = ($resultRow->getPocPhone()) ? $resultRow->getPocPhone() : 'N/A';
                    $row[] = $serviceName;
                    $row[] = $status;
                    $row[] = $delete; 
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return type
     */
    public function newAction(Request $request) {
        if(! $this->get('admin_permission')->checkPermission('business_new')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new business.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objBusiness = new Business();
        $form = $this->createForm(new BusinessFormType('add'), $objBusiness);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $objcheckBusinessName = $em->getRepository('DhiAdminBundle:Business')->findOneBy(array("name"=>$objBusiness->getName(),  "isDeleted" => false ));
                if(!$objcheckBusinessName){
                    $objBusiness->setCreatedBy($admin);
                    $objBusiness->setCreatedAt(new DateTime());
                    $em->persist($objBusiness);
                    $em->flush();

                    // Set activity log
                    // $reason = $request->get('reason');
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add Business';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new Business: " . $objBusiness->getName(). " for Reason:".$objBusiness->getReason();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    $this->get('session')->getFlashBag()->add('success', "Business added successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_business_list'));
                }else{
                    $this->get('session')->getFlashBag()->add('danger', "Business name already exists!");
                }
            }
        }
        return $this->render('DhiAdminBundle:Business:new.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Request $request, $id) {
        if(! $this->get('admin_permission')->checkPermission('business_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update business.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
                
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();        
        $objBusiness = $em->getRepository('DhiAdminBundle:Business')->find($id);

        if (!$objBusiness) {
            $this->get('session')->getFlashBag()->add('failure', "Business user does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_business_list'));
        }
        
        $form = $this->createForm(new BusinessFormType('edit'), $objBusiness);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
	                $em->persist($objBusiness);
        	        $em->flush();

                	// Set activity log update admin user
	                $activityLog = array();
	                $activityLog['admin'] = $admin;
        	        $activityLog['activity'] = 'Edit Business';
                	$activityLog['description'] = "Admin " . $admin->getUsername() . " has updated Business: " . $objBusiness->getName(). " Reason:".$objBusiness->getReason();
	                $this->get('ActivityLog')->saveActivityLog($activityLog);
	                $this->get('session')->getFlashBag()->add('success', "Business updated successfully!");
        	        return $this->redirect($this->generateUrl('dhi_admin_business_list'));
            }
        }
        return $this->render('DhiAdminBundle:Business:edit.html.twig', array(
            'form' => $form->createView(),
            'business' => $objBusiness,
            'id' => $id
        ));       
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        $reason = $request->get('reason');
        if(empty($reason)){
            $this->get('session')->getFlashBag()->add('failure', "Please enter reason to delete business.");
            $response = new Response(json_encode(array()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        if(! $this->get('admin_permission')->checkPermission('business_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete business!");
            $response = new Response(json_encode(array()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $objBusiness = $em->getRepository('DhiAdminBundle:Business')->find($id);
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['ip'] = $request->getClientIp();
        $activityLog['sessionId'] = $request->getSession()->getId();
        $activityLog['url'] = $request->getUri();
        if ($objBusiness) {
            $isRedeemed = $em->getRepository('DhiAdminBundle:Business')->getRedeemedPromocodes($id);

            if(count($isRedeemed) == 0){
                $objBatches = $objBusiness->getBatches();
                foreach ($objBatches as $objBatche) {
                    $promoCodes = $objBatche->getPromoCodes();
                    foreach ($promoCodes as $promoCode) {
                        $em->remove($promoCode);    
                    }
                    $em->remove($objBatche);
                }
                // Set activity log change password
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Delete Business';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted Business: " . $objBusiness->getName()." Reason:".$reason;
                
                $objBusiness->setIsDeleted(true);
                $em->persist($objBusiness);
                $em->flush();
                $result = array('type' => 'success', 'message' => 'Business deleted successfully!');
            }else{
                $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Business";
                $result = array('type' => 'failure', 'message' => 'Business has already added promo codes!');
            }
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Business";
            $result = array('type' => 'failure', 'message' => 'You are not allowed to delete Business!');
        }

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $this->get('session')->getFlashBag()->add($result['type'], $result['message']);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getLocationAction(Request $request){
        $locations = $request->get('sLocation');
        $em = $this->getDoctrine()->getManager();
        $uniqueService = $serviceLocations = array();
        $services = array();
        foreach ($locations as $key => $location) {
            $services = $em->getRepository('DhiAdminBundle:IpAddressZone')->getServicesByLocations($location, $services);
        }
        $response = new Response(json_encode($services));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function checkBusinessNameAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $name = $request->get('dhi_admin_business')['name'];
        $action = $request->get('action');
        $id = $request->get('id');
        $condition = array('name'=>$name);
        
        if(!empty($name)){
            if(!empty($id) && !empty($action) && $action == 'edit'){
                $condition['id'] = $id;
            }else{
                $id = 0;
            }
            $checkFlag = $em->getRepository("DhiAdminBundle:Business")->checkBusinessName($name, $id);
            if(count($checkFlag) > 0){
                $status = "false";
            }else{
                $status = "true";
            }
        }else{
            $status = "true";
        }
        $response = new Response($status);
        return $response;
    }
}
