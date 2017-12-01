<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\ServicePartnerFormType;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Entity\ServicePartner;
use \DateTime;

class ServicePartnerController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('service_partner_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view partner list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();      
        $arrPartners  = $em->getRepository('DhiAdminBundle:ServicePartner')->getAllPartnerNames();
        return $this->render('DhiAdminBundle:ServicePartner:index.html.twig', array('admin' => $admin, 'partners' => $arrPartners));
    }
    
    public function adminListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $adminColumns = array("Id","name", "pocName", "pocEmail", "pocPhone", "service", "status");
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sp.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'sp.id';
            }
            if ($gridData['order_by'] == 'name') {
                $orderBy = 'sp.name';
            }
            if ($gridData['order_by'] == 'pocName') {
                $orderBy = 'sp.pocName';
            }
              if ($gridData['order_by'] == 'pocEmail') {
                $orderBy = 'sp.pocEmail';
            }
            if ($gridData['order_by'] == 'pocPhone') {
                $orderBy = 'sp.pocPhone';
            } 
            if ($gridData['order_by'] == 'status') {
                $orderBy = 'sp.status';
            }
            if ($gridData['order_by'] == 'serviceType') {
                $orderBy = 'sp.serviceType';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        $data  = $em->getRepository('DhiAdminBundle:ServicePartner')->getpartnerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin, '', '','all');
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
                    $flagDelete = 0;
                    $delete = '';
                    $flagDelete = 1; 
                    $delete = $resultRow->getId().'^'.$flagDelete;
                    $serviceName = '<span class="btn btn-success btn-sm service">' .$resultRow->getService()->getName(). '</span>';
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
        if(! $this->get('admin_permission')->checkPermission('service_partner_new')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new service partner.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objServicePartner = new ServicePartner();
        $form = $this->createForm(new ServicePartnerFormType($admin, null), $objServicePartner);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $iptvService = $em->getRepository("DhiUserBundle:Service")->findOneBy(array('name' => "IPTV"));
                if($iptvService){
                    $objServicePartner->setService($iptvService);
                }
                $objServicePartner->setCreatedBy($admin);
                $objServicePartner->setCreatedAt(new DateTime());
                
                $showCred = $request->get('chdShowCredentials');
                if($showCred == 'on'){
                    $reqPassword = $request->get('dhi_admin_service_partner')['password'];
                    $password = password_hash($reqPassword, PASSWORD_BCRYPT);
                    $objServicePartner->setPassword($password);
                }
                $em->persist($objServicePartner);
                $em->flush();

                // Set activity log
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Service Partner';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new service partner: " . $objServicePartner->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                $this->get('session')->getFlashBag()->add('success', "ISP Service Partner added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_partner_list'));
            }
        }
        return $this->render('DhiAdminBundle:ServicePartner:new.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Request $request, $id) {
        if(! $this->get('admin_permission')->checkPermission('service_partner_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service partner.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
                
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();        
        $objServicePartner = $em->getRepository('DhiAdminBundle:ServicePartner')->find($id);

        if (!$objServicePartner) {
            $this->get('session')->getFlashBag()->add('failure', "Service partner user does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_service_partner_list'));
        }
        $currPassword = $objServicePartner->getPassword();
        
        $form = $this->createForm(new ServicePartnerFormType(), $objServicePartner);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $reason = $request->get('reason');
                $isError = false;
                if(empty($reason) || $reason == ''){
                    $errorMsg = "Please enter reason.";
                    $isError = true;
                }else if(strlen($reason) > 255){
                    $errorMsg = "Reason can have maximum 255 characters.";
                    $isError = true;
                }
                
                if($isError){
                    return $this->render('DhiAdminBundle:ServicePartner:edit.html.twig', array(
                        'form' => $form->createView(),
                        'servicePartner' => $objServicePartner,
                        'id' => $id,
                        'reasonValue' => $reason,
                        'errorMsg' => $errorMsg
                    ));
                }
                
                $showCred = $request->get('chdShowCredentials');
                if($showCred == 'on'){
                    $reqPassword = $request->get('dhi_admin_service_partner')['password'];
                    if($reqPassword != null){
                        $password = password_hash($reqPassword, PASSWORD_BCRYPT);
                        $objServicePartner->setPassword($password);
                    }else{
                        $objServicePartner->setPassword($currPassword);
                    }
                }else{
                    $objServicePartner->setUsername(null);
                    $objServicePartner->setpassword(null);
                }
                $em->persist($objServicePartner);
                $em->flush();

                // Set activity log update admin user
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Service Partner';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated ISP Service Partner: " . $objServicePartner->getName(). " Reason:".$reason;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "ISP Service Partner updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_partner_list'));
            }
        }
        return $this->render('DhiAdminBundle:ServicePartner:edit.html.twig', array(
            'form' => $form->createView(),
            'servicePartner' => $objServicePartner,
            'id' => $id
        ));       
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        $reason = $request->get('reason');
        if(empty($reason)){
            $this->get('session')->getFlashBag()->add('failure', "Please enter reason to delete service partner.");
            $response = new Response(json_encode(array()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }elseif(strlen($reason) > 255){
            $this->get('session')->getFlashBag()->add('failure', "Reason can have maximum 255 characters.");
            $response = new Response(json_encode(array()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        if(! $this->get('admin_permission')->checkPermission('service_partner_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service partner!");
            $response = new Response(json_encode(array()));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $objServicePartner = $em->getRepository('DhiAdminBundle:ServicePartner')->find($id);
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['ip'] = $request->getClientIp();
        $activityLog['sessionId'] = $request->getSession()->getId();
        $activityLog['url'] = $request->getUri();
        if ($objServicePartner) {
            $objBatches = $objServicePartner->getBatches();
            if(count($objBatches) == 0){
                // Set activity log change password
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Delete service partner';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted service partner: " . $objServicePartner->getName()." Reason:".$reason;
                
                $objServicePartner->setIsDeleted(true);
                $em->persist($objServicePartner);
                $em->flush();
                $result = array('type' => 'success', 'message' => 'ISP Service Partner deleted successfully!');
            }else{
                $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service";
                $result = array('type' => 'failure', 'message' => 'Service partner has already added promo codes!');
            }
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service";
            $result = array('type' => 'failure', 'message' => 'You are not allowed to delete ISP service partner!');
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

    public function checkPartnerNameAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        $name = $request->get('dhi_admin_service_partner')['name'];
        $action = $request->get('action');
        $id = $request->get('id');
        $condition = array('name'=>$name);
        
        if(!empty($name)){
            if(!empty($id) && !empty($action) && $action == 'edit'){
                $condition['id'] = $id;
            }else{
                $id = 0;
            }
            $checkFlag = $em->getRepository("DhiAdminBundle:ServicePartner")->checkPartnerName($name, $id);
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
    
    public function checkUsernameAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $username = $request->get('dhi_admin_service_partner')['username'];
        $action = $request->get('action');
        if($action != 'edit'){
            if(!empty($username)){
                $isUsernameExist = $em->getRepository('DhiAdminBundle:ServicePartner')->checkUsernameExists($username);
                if(count($isUsernameExist) > 0){
                    $status = "false";
                }else{
                    $status = "true";
                }
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
