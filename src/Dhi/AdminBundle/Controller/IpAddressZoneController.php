<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\Service;
use Dhi\AdminBundle\Form\Type\IpAddressZoneFormType;
use Dhi\AdminBundle\Form\Type\ServiceLocationFormType;
use Dhi\AdminBundle\Entity\IpAddressZone;
use Dhi\AdminBundle\Entity\ServiceLocation;
use \DateTime;

class IpAddressZoneController extends Controller {

    public function indexAction(Request $request) {
        
        if (!($this->get('admin_permission')->checkPermission('service_location_list') || $this->get('admin_permission')->checkPermission('service_location_create') || $this->get('admin_permission')->checkPermission('service_location_discount_create') || $this->get('admin_permission')->checkPermission('service_location_update') || $this->get('admin_permission')->checkPermission('service_location_discount_update') || $this->get('admin_permission')->checkPermission('service_location_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objCountries = $em->getRepository('DhiUserBundle:Country')->getAllCountry();
        $countries = array_map(function($country){ return $country['name']; } , $objCountries);
        return $this->render('DhiAdminBundle:IpAddressZone:index.html.twig', array('countries' => $countries));
    }
    
     //Country wise service list
    public function serviceLocationListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0,$fromip,$toip) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $serviceLocationColumns = array('Id','Country','Name','IPAddressRange');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($serviceLocationColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'sl.id';
            $sortOrder = 'DESC';
            
        } else {

            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'sl.id';
            }

            if ($gridData['order_by'] == 'Country') {

                $orderBy = 'c.name';
            }
            if ($gridData['order_by'] == 'Name') {

                $orderBy = 'sl.name';
            }

        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $serviceLocation = '';
        if ($admin->getGroup() != 'Super Admin') {
            $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $serviceLocation = empty($serviceLocation) ? '0' : $serviceLocation;
        }

        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getServiceLocationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$fromip,$toip, $serviceLocation);
    
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
                    $flagEditDiscount = 0;
                    
                    if($resultRow->getIpAddressZones()){
                        $ipAddressTable = '<table id="serviceLocationTable" class="datatable table table-striped table-bordered table-hover" style="margin-bottom: 0 !important;">';
                        $ipAddressTable .= '<tr><th>From</th><th>To</th><th>Service</th></tr>';

                        foreach($resultRow->getIpAddressZones() as $record) {
                            $ipAddressTable .= '<tr>';
                            $ipAddressTable .= '<td>'.$record->getFromIpAddress().'</td>';
                            $ipAddressTable .= '<td>'.$record->getToIpAddress().'</td>';
                            $ipAddressTable .= '<td>';
                            $count = 1;
                            $servicesCount = count($record->getServices());

                            $serviceName = '';
                            if($record->getServices()){
                               foreach ($record->getServices() as $service) {
                                   if ($count == $servicesCount) {
                                       $ipAddressTable .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                                   } else {
                                       $ipAddressTable .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                                   }
                                   $count++;
                               }
                            }
                            $ipAddressTable .= '</td>';
                            $ipAddressTable .= '</tr>';
                        }
                        $ipAddressTable .='</table>';
                    }

                    $activeUsers = $em->getRepository('DhiUserBundle:User')->getActiveUsers($resultRow);

                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getCountry()->getName();
                    $row[] = $resultRow->getName();
                    $row[] = $ipAddressTable ;
                    $row[] = $activeUsers;
                    $row[] = $resultRow->getId().'^'.$flagEditDiscount;
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function newAction(Request $request) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $objServiceLocation = new ServiceLocation();
        $objIpAddressZone   = new IpAddressZone();
        
        $objServiceLocation->addIpAddressZone($objIpAddressZone);
        
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new ServiceLocationFormType(), $objServiceLocation);
            
            if ($request->getMethod() == "POST") {
                
                $form->handleRequest($request);
                
                if ($form->isValid()) {
                    
                    $objServiceLocation = $form->getData();
                    
//                    $ipAddresses = $em->getRepository("DhiAdminBundle:IpAddressZone")->checkIpAddressRange($objServiceLocation->getIpAddressZones(), true);
//                    
//                    if(!empty($ipAddresses)) {
//                        
//                        $this->get('session')->getFlashBag()->add('danger', "Ip address range already exist.");
//                        return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
//                        
//                    }
                    
                    $em->persist($objServiceLocation);
                    
                    if($objServiceLocation->getIpAddressZones()){
                        
                        foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone){
                            
                            $ipAddressZone->setServiceLocation($objServiceLocation);
                            $em->persist($ipAddressZone);
                        }
                    }
                    $em->flush();
                    
                    // set audit log add ip address range
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add IP address range';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added Service location successfully";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Service location added successfully.");
                    return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
                }
            }
            
            return $this->render('DhiAdminBundle:IpAddressZone:new.html.twig', array('form' => $form->createView()));
        
    }
    
    public function editAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('service_location_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($id);

        if (!$objServiceLocation) {
            
            $this->get('session')->getFlashBag()->add('failure', "Service location does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
        }
        
//        $ipZones = $objServiceLocation->getIpAddressZones();
//        
//        $currentIpAddress = array();
//        
//        foreach($ipZones as $key => $ip) {
//            
//            $currentIpAddress[$key]['fromIp'] = $ip->getFromIpAddress();
//            $currentIpAddress[$key]['toIp'] = $ip->getToIpAddress();
//            
//        }
        
        $form = $this->createForm(new ServiceLocationFormType($objIpAddressZone), $objServiceLocation);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objServiceLocation = $form->getData();
                
//                $ipaddress = array();
//                
//                foreach($objServiceLocation->getIpAddressZones() as $key => $ip) {
//            
//                    $ipaddress[$key]['fromIp'] = $ip->getFromIpAddress();
//                    $ipaddress[$key]['toIp'] = $ip->getToIpAddress();
//            
//                 }
//                
//                $flag = false;
//                
//                if($currentIpAddress === $ipaddress) {
//                    
//                   $flag = false;
//                   
//                } else {
//                    
//                    $flag = true;
//                } 
//                
//                $arrIpAddresses = $em->getRepository("DhiAdminBundle:IpAddressZone")->checkIpAddressRange($objServiceLocation->getIpAddressZones(),$flag);
//
//                if(!empty($arrIpAddresses)) {
//
//                    $this->get('session')->getFlashBag()->add('danger', "Ip address range already exist.");
//                    return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
//
//                }
                
                $em->persist($objServiceLocation);

                if($objServiceLocation->getIpAddressZones()){

                    $ipZoneUpdatedId = array();
                    foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone){

                        $ipAddressZone->setServiceLocation($objServiceLocation);
                        $em->persist($ipAddressZone);

                        if($ipAddressZone->getId()) {

                            $ipZoneUpdatedId[] = $ipAddressZone->getId();
                        }
                        
                    }
                    
                    $deleteIpZoneList = $em->getRepository('DhiAdminBundle:IpAddressZone')->getRemoveIpZoneList($id,$ipZoneUpdatedId);
                    
                    if($deleteIpZoneList){
                    
                        foreach ($deleteIpZoneList as $deleteIpZone){
                            
                            $em->remove($deleteIpZone);
                        }                        
                    }
                }
                $em->flush();
                
                // set audit log edit ip address range
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit IP address range';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated Service location";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Service location updated successfully.");
                return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
            }
        }
        return $this->render('DhiAdminBundle:IpAddressZone:edit.html.twig', array('form' => $form->createView(), 'id' => $id));        
    }
    
    public function deleteAction(Request $request) {
        $id = $request->get('id');
        
        if(! $this->get('admin_permission')->checkPermission('service_location_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        // set audit log delete ip address range
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Service location';
        
        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($id);
        $locationUsers = $objServiceLocation->getUsers();

        if ($objServiceLocation && count($locationUsers) == 0) {
            $em->remove($objServiceLocation);
            $em->flush();

            /* START: add user audit log for Delete IP address range-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted Service location ".$objServiceLocation->getName();
            $result = array('type' => 'success', 'message' => 'Service location deleted successfully.');

            /* END: add user audit log for delete service-list */

        } else {
                
            /* START: add user audit log for Delete IP address range-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Service location";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for delete IP address range-list */
            if(count($locationUsers) > 0){
                $result = array('type' => 'error', 'message' => 'One or more users already exists in service location.');
            }else{
                $result = array('type' => 'error', 'message' => 'You are not allowed to delete Service location.');
            }
          
            
                
        }
        $response = new Response(json_encode($result));
        
	    $response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
    
    public function validateIpRangeAction(Request $request) {
        
        $em = $this->getDoctrine()->getManager();
        
        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            
            $serviceLocationData = $request->get('dhi_service_location');
            $id = $request->get('id');
            
            if($id) {
                
                $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($id);
                $form = $this->createForm(new ServiceLocationFormType($objIpAddressZone), $objServiceLocation);                            
            }else {
                
                $objServiceLocation = new ServiceLocation();
                $objIpAddressZone   = new IpAddressZone();
                
                $objServiceLocation->addIpAddressZone($objIpAddressZone);
                $form = $this->createForm(new ServiceLocationFormType(), $objServiceLocation);
            }
            
            $form->handleRequest($request);
            $objServiceLocation = $form->getData();
            
            
            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error']  = array();
            if($objServiceLocation && $serviceLocationData['ipAddressZones']) {
                
                $serviceLocationId = $objServiceLocation->getId();
                $serviceLocationData['ipAddressZones'] = array_values($serviceLocationData['ipAddressZones']);
                
                if($objServiceLocation->getIpAddressZones()) {
                    
                        $longIpArr = array();
                        $ipZoneUpdatedIds = array();
                        $key = 0;
                        
                        $objbundle = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'BUNDLE'));
                        $objIPTV = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'IPTV'));
                        $objISP = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'ISP'));
                        
                        foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone) {
                            
                            $fromIpAddressLong = '';
                            $toIpAddressLong = '';
                            $isValid = 1;
                            
                            $collectionIndex = $serviceLocationData['ipAddressZones'][$key]['collectionIndex'];
                            
                            /*-------Add Validation for Bundle Selection----------*/
                            $servicearray = $serviceLocationData['ipAddressZones'][$collectionIndex]['services'];
                            $bundleID = $objbundle->getId();
                            $iptvID = $objIPTV->getId();
                            $ispID = $objISP->getId();
                            if(in_array($bundleID, $servicearray)){
                                if(!in_array($iptvID, $servicearray) || !in_array($ispID, $servicearray)){
                                    $jsonResponse['error'][$collectionIndex]['validbundle'] = 'Please select IPTV & ISP for Bundle';
                                }
                            }
							if(in_array($iptvID, $servicearray) && in_array($ispID, $servicearray)){
							    if(!in_array($bundleID, $servicearray)){
                                    $jsonResponse['error'][$collectionIndex]['validbundle'] = 'Please select Bundle.';
                                }
                            }
                            /*------------end of code-----------*/
                            
                            if($ipAddressZone->getFromIpAddress()) {
                                
                                if (!filter_var($ipAddressZone->getFromIpAddress(), FILTER_VALIDATE_IP) === false) {
                                
                                    $fromIpAddressLong = ip2long($ipAddressZone->getFromIpAddress());
                                    $longIpArr[] = $fromIpAddressLong; 
                                }else{
                                    
                                    $jsonResponse['error'][$collectionIndex]['fromIp'] = 'Invalid IP Address';
                                    $isValid = 0;
                                }
                            }
                            
                            if($ipAddressZone->getToIpAddress()) {
                                                            
                                if (!filter_var($ipAddressZone->getToIpAddress(), FILTER_VALIDATE_IP) === false) {
                                    
                                    $toIpAddressLong = ip2long($ipAddressZone->getToIpAddress());                                
                                }else{
                                                                    
                                    $jsonResponse['error'][$collectionIndex]['toIp'] = 'Invalid IP Address';
                                    $isValid = 0;
                                }
                            }
                            
                            if($id && $ipAddressZone->getId()) {
                                
                                $ipZoneUpdatedIds[$ipAddressZone->getId()] = $ipAddressZone->getId();
                            }
                            
                            $longIpArr[$collectionIndex] = array('fromIP' => $fromIpAddressLong, 'toIP' => $toIpAddressLong, 'isValid' => $isValid);
                            $key++;
                        }
                        
                        $deletedIds = $em->getRepository('DhiAdminBundle:IpAddressZone')->getDeletedId($serviceLocationId,$ipZoneUpdatedIds);
                        
                        if($longIpArr) {
                            
                            $key = 0;
                            foreach ($objServiceLocation->getIpAddressZones() as $ipAddressZone) {
                                
                                $compFromIp = ip2long($ipAddressZone->getFromIpAddress());
                                $compToIp = ip2long($ipAddressZone->getToIpAddress());
                                
                                $collectionIndex = $serviceLocationData['ipAddressZones'][$key]['collectionIndex'];
                                
                                $isError = 0;
                                
                                foreach ($longIpArr as $key1 => $ipVal) {
                                    
                                    if($collectionIndex != $key1 && $ipVal['isValid'] == 1 && !$isError) {
                            
                                        if($ipAddressZone->getFromIpAddress()) {
                                            
                                            if($compFromIp >= $ipVal['fromIP'] && $compFromIp <= $ipVal['toIP']) {
                                                
                                                $jsonResponse['error'][$collectionIndex]['fromIp'] = 'IP Address already exists in IP range.';
                                                $isError = 1;
                                            }
                                        }
                                        
                                        if($ipAddressZone->getToIpAddress()) {
                                            
                                            if($compToIp >= $ipVal['fromIP'] && $compToIp <= $ipVal['toIP']) {
                                                
                                                $jsonResponse['error'][$collectionIndex]['toIp'] = 'IP Address already exists in IP range.';
                                                $isError = 1;
                                            }                      
                                        }                                                          
                                    }                                    
                                }
                                
                                if(!$isError) {
                                    
                                    //Compare FROM IP address in IpAddressZone table
                                    if($ipAddressZone->getFromIpAddress()) {
                                        
                                        $resultFromIp = $em->getRepository('DhiAdminBundle:IpAddressZone')->checkIpRangeExists($ipAddressZone->getFromIpAddress(),$ipAddressZone->getId(),$deletedIds);
                                        
                                        if($resultFromIp) {
                                           
                                            $jsonResponse['error'][$collectionIndex]['fromIp'] = 'IP Address already exists in IP range.';                                       
                                        }
                                    }
                                    
                                    //Compare TO IP address in IpAddressZone table
                                    if($ipAddressZone->getToIpAddress()) {
                                        
                                        $resultFromIp = $em->getRepository('DhiAdminBundle:IpAddressZone')->checkIpRangeExists($ipAddressZone->getToIpAddress(),$ipAddressZone->getId(),$deletedIds);
                                        
                                        if($resultFromIp) {
                                            $jsonResponse['error'][$collectionIndex]['toIp'] = 'IP Address already exists in IP range.';                 
                                        }
                                    }

                                    if($ipAddressZone->getFromIpAddress() && $ipAddressZone->getToIpAddress()) {
                                        
                                        $resultFromIp = $em->getRepository('DhiAdminBundle:IpAddressZone')->checkIpRangeExistsInRange($ipAddressZone->getFromIpAddress(), $ipAddressZone->getToIpAddress(), $ipAddressZone->getId(), $deletedIds);
                                        
                                        if($resultFromIp) {
                                            $jsonResponse['error'][$collectionIndex]['fromIp'] = 'IP Address already exists in IP range.';
                                            $jsonResponse['error'][$collectionIndex]['toIp'] = 'IP Address already exists in IP range.';
                                        }
                                    }
                                }
                                
                                $key++;
                            }                            
                        }
                        
                    
                    
                }
            }
            
            if(count($jsonResponse['error']) > 0) {

                $jsonResponse['status'] = 'error';
                
            }
            //echo "<pre>";print_r($jsonResponse);exit;
            echo json_encode($jsonResponse);
            exit;
            
            //$objServiceLocation->get
            
            
            
        }else{
    		
    		throw $this->createNotFoundException('Invalid Page Request');
    	}
    }
    
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder(array('id' => $id))
            ->add('id', 'hidden')
            ->getForm();
    }
    
    public function check_diff_multi($array1, $array2){
        $result = array();
        foreach($array1 as $key => $val) {
            if(isset($array2[$key])){
               if(is_array($val) && $array2[$key]){
                   $result[$key] = check_diff_multi($val, $array2[$key]);
               }
           } else {
               $result[$key] = $val;
           }
        }
        return $result;
    }
}
