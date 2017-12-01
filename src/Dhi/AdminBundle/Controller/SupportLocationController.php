<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\SupportLocation;
use Dhi\AdminBundle\Form\Type\SupportLocationFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class SupportLocationController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('support_location_list') || $this->get('admin_permission')->checkPermission('support_location_create') || $this->get('admin_permission')->checkPermission('support_location_update') || $this->get('admin_permission')->checkPermission('support_location_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view support location list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objLocation = $em->getRepository('DhiUserBundle:SupportLocation')->getAllSupportLocation();
        $locations = array();
        if($objLocation){
            foreach ($objLocation as $location) {
                $locations[] = $location['name'];
            }
        }
        $allWhiteLabelSites = $em->getRepository('DhiAdminBundle:WhiteLabel')->getWhiteLabelSites();
        return $this->render('DhiAdminBundle:SupportLocation:index.html.twig', array('supportLocations' => $locations,'allWhiteLabelSites' => $allWhiteLabelSites));
    }
    
    // support location grid list
    public function supportLocationListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $supportLocationColumns = array('Location', 'supportsite', 'SequenceNumber', 'createdBy');
        $admin = $this->get('security.context')->getToken()->getUser();
        
        // get common function for search data
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($supportLocationColumns);
        
        if ($request->get('supportsite') != null) {
            $gridData['search_data']['supportsite'] = $request->get('supportsite');
            $gridData['SearchType'] = 'ANDLIKE';
        }
        
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'c.sequenceNumber';
            $sortOrder = 'ASC';
            
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'c.id';
            }

            if ($gridData['order_by'] == 'Location') {
                
                $orderBy = 'c.name';
            }

            if ($gridData['order_by'] == 'supportsite') {
                $orderBy = 'wl.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiUserBundle:SupportLocation')->getSupportLocationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                    $row[] = $resultRow->getName();
                    $row[] = ($resultRow->getSupportsite())? $resultRow->getSupportsite()->getCompanyName().' ('.$resultRow->getSupportsite()->getDomain().')' : '';
                    $row[] = ($resultRow->getSequenceNumber() ? $resultRow->getSequenceNumber() : 'N/A');
                    $row[] = (($resultRow->getCreatedBy()) ? $resultRow->getCreatedBy()->getUsername() : 'N/A');
                    $row[] = ($resultRow->getId().'^'.$flagDelete);
                    
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
        if(! $this->get('admin_permission')->checkPermission('support_location_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add support location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objLocation = new SupportLocation();
        $form = $this->createForm(new SupportLocationFormType(), $objLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            
            $isFormValid = true;
            $siteId = $request->get('dhi_user_support_location')['supportsite'];
            $name   = $request->get('dhi_user_support_location')['name'];
            
            $isDublicateLocation = $em->getRepository('DhiUserBundle:SupportLocation')->findOneBy(array('supportsite' => $siteId, 'name' => $name, 'isDeleted' => 0));
            
            if($isDublicateLocation){
                $this->get('session')->getFlashBag()->add('danger', 'Support location is already exists in this site.');
                $isFormValid = false;
            }
            
            if ($form->isValid() && $isFormValid) {

                $objLocation = $form->getData();
                $siteId = $objLocation->getSupportsite()->getId();
                $objMaxSequenceNumber = $em->getRepository('DhiUserBundle:SupportLocation')->findOneBy(array('supportsite' => $siteId,'isDeleted'=>0), array('sequenceNumber' => 'DESC'));
                $sequenceNumber = 1;
                if($objMaxSequenceNumber){
                    $sequenceNumber = $objMaxSequenceNumber->getSequenceNumber();
                    $sequenceNumber = $sequenceNumber + 1;
                }
                $objLocation->setCreatedBy($admin);
                $objLocation->setSequenceNumber($sequenceNumber);
                $em->persist($objLocation);
                $em->flush();
                
                // set audit log add support location
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add support location';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added support location " . $objLocation->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support location added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_location_list'));
            }
        }
        return $this->render('DhiAdminBundle:SupportLocation:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update support location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objLocation = $em->getRepository('DhiUserBundle:SupportLocation')->find($id);

        if (!$objLocation) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support location.");
            return $this->redirect($this->generateUrl('dhi_admin_support_location_list'));
        }

        $form = $this->createForm(new SupportLocationFormType(), $objLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            
            $isFormValid = true;
            $siteId = $request->get('dhi_user_support_location')['supportsite'];
            $name   = $request->get('dhi_user_support_location')['name'];
            
            $isDublicateLocation = $em->getRepository('DhiUserBundle:SupportLocation')->findOneBy(array('supportsite' => $siteId, 'name' => $name, 'isDeleted' => 0));
            
            if($isDublicateLocation && $isDublicateLocation->getId() != $id){
                $this->get('session')->getFlashBag()->add('danger', 'Support location is already exists in this site.');
                $isFormValid = false;
            }

            if ($form->isValid() && $isFormValid) {

                $objLocation = $form->getData();
                $em->persist($objLocation);
                $em->flush();
                
                // set audit log add support location
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit support location';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated support location " . $objLocation->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support location updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_location_list'));
            }
        }

        return $this->render('DhiAdminBundle:SupportLocation:edit.html.twig', array(
                    'form' => $form->createView(),
                    'location' => $objLocation
        ));
    }
    
    public function deleteAction(Request $request) {
        
        $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_location_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete support location.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        $objLocation = $em->getRepository('DhiUserBundle:SupportLocation')->find($id);

        if ($objLocation) {
            $sequenceNum = $objLocation->getSequenceNumber();
            $siteId = $objLocation->getSupportsite()->getId();

            $objSolarWindsSupportLocation = $objLocation->getSolarwindsSupportLocation();
            if($objSolarWindsSupportLocation){
                $em->remove($objSolarWindsSupportLocation);
                $em->flush();
            }
            
            // set audit log delete support location
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete support location';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted support location " . $objLocation->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $objLocation->setIsDeleted(true);
            $em->persist($objLocation);
            $em->flush();
            
            $arrParams = array(
                'siteId' => $siteId,
                'sequenceNumber' => $sequenceNum
            );
            
            $objSupportLocation = $em->getRepository('DhiUserBundle:SupportLocation')->getSupportLocationForUpdateSequenceNum($arrParams);
            
            if($objSupportLocation){
                foreach ($objSupportLocation as $suppotLocation){
                    $seqNum = $suppotLocation->getSequenceNumber();
                    $seqNum = $seqNum - 1;
                    $suppotLocation->setSequenceNumber($seqNum);
                    $em->persist($suppotLocation);
                    $em->flush();
                }
            }
            $result = array('type' => 'success', 'message' => 'Support location deleted successfully.');
            
        } else {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete support location.');
          
        }
        
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
    
    public function updateSequenceNumberAction(Request $request){
        
        if(!$this->get('admin_permission')->checkPermission('support_location_update')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to update display order.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $siteId = $request->get('siteId');
        $startlimit = $request->get('startlimitnum');
                 
        $oldSequenceNum = $startlimit+$request->get('oldSequenceNum') + 1;
        $newSequenceNum = $startlimit+$request->get('newSequenceNum') + 1;
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Change Support Location Display Order';
        
        $objSupportLocation = $em->getRepository('DhiUserBundle:SupportLocation')->findOneBy(array('supportsite' => $siteId, 'isDeleted'=>0,'sequenceNumber' => $oldSequenceNum));
        if($objSupportLocation){
            
            $arrParams = array(
                'supportsiteId' => $siteId,
                'oldSequenceNum' => $oldSequenceNum,
                'newSequenceNum' => $newSequenceNum
            );
            
            $objAllOttherSupportLocation = $em->getRepository('DhiUserBundle:SupportLocation')->getSiteWiseSupportLocaion($arrParams);
            
            if($objAllOttherSupportLocation){
                foreach ($objAllOttherSupportLocation as $supportLocation){
                    $seqNum = $supportLocation->getSequenceNumber();
                    if($oldSequenceNum < $newSequenceNum){
                        $seqNum = $seqNum - 1 ;
                    }else{
                        $seqNum = $seqNum + 1 ;
                    }
                    
                    $supportLocation->setSequenceNumber($seqNum);
                    $em->persist($supportLocation);
                    $em->flush();
                }
            }
            $objSupportLocation->setSequenceNumber($newSequenceNum);
            $em->persist($objSupportLocation);
            $em->flush();
            
            $result = array('type' => 'success', 'message' => 'Display order updated successfully!');
            $activityLog['description'] = "Admin ".$admin->getUsername()." has updated display order for site: " . $objSupportLocation->getSupportsite()->getCompanyName();
            
            
        }else{
            $result = array('type' => 'danger', 'message' => 'Unable to find support location.');
            $activityLog['description'] = "Admin ".$admin->getUsername()." has try to update display order in support location";
        }
        
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function checkDuplicateAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        
        $siteId = $request->get('supportSite');
        $name = $request->get('name');
        
        $action = $request->get('action');
        $id = $request->get('id');
        $condition = array('name'=>$name);
        
        if(!empty($name) && !empty($siteId)){
            if(!empty($id) && !empty($action) && $action == 'edit'){
                $condition['id'] = $id;
            }else{
                $id = 0;
            }
            
            $checkFlag = $em->getRepository("DhiUserBundle:SupportLocation")->checkSupportLocationName($siteId, $name, $id);
            
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
