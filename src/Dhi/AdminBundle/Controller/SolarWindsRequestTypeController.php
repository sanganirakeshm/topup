<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Form\Type\SolarWindsRequestFormType;
use Dhi\UserBundle\Entity\SolarWindsSupportLocation;


class SolarWindsRequestTypeController extends Controller {
    
    public function indexAction(Request $request) {
        
       if (!($this->get('admin_permission')->checkPermission('solar_wind_location_list') || $this->get('admin_permission')->checkPermission('solar_wind_location_add') || $this->get('admin_permission')->checkPermission('solar_wind_location_edit') || $this->get('admin_permission')->checkPermission('solar_wind_location_delete'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view solar winds.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        } 
        
        $em                 = $this->getDoctrine()->getManager();
        $admin              = $this->get('security.context')->getToken()->getUser();
        $supportLocation    = array();
       
        $objSupportLocation = $em->getRepository('DhiUserBundle:SupportLocation')->getAllSupportLocation();
        $supportsites            = $em->getRepository('DhiAdminBundle:WhiteLabel')->getallsitewithdomain();

        $objRequestTypes = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->FindAll();

        $requestTypes = array();
        if ($objRequestTypes) {
            foreach ($objRequestTypes as $objRequestType) {
                $requestTypes[] = $objRequestType->getRequestTypeName();
            }
        }

        if ($objSupportLocation) {
            foreach ($objSupportLocation as $activity) {
                $supportLocation[] = $activity['name'];
            }
        }
       return $this->render('DhiAdminBundle:SolarWindsRequestTypes:index.html.twig',array('supportLocations' => $supportLocation,'supportsites'=>$supportsites, 'requestTypes' => $requestTypes));  
    }
    
    public function solarwindsLocationListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
       $SolarWindLocationColumns = array('supportsite','supportLocation', 'solarWindsRequestType', 'createdBy', 'CreatedAt', 'UpdatedAt');
       $helper = $this->get('grid_helper_function');
       $gridData = $helper->getSearchData($SolarWindLocationColumns);
       
       $sortOrder = $gridData['sort_order'];
       $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sw.id';
            $sortOrder = 'DESC';

        } else {
            if ($gridData['order_by'] == 'supportsite') {
                $orderBy = 'st.companyName';

            }else if ($gridData['order_by'] == 'supportLocation') {
                $orderBy = 'sl.name';

            }else if ($gridData['order_by'] == 'solarWindsRequestType') {
                $orderBy = 'so.requestTypeName';

            }else if ($gridData['order_by'] == 'CreatedAt') {
                $orderBy = 'sw.createdAt';

            }else if ($gridData['order_by'] == 'UpdatedAt') {
                $orderBy = 'sw.updatedAt';

            }else if ($gridData['order_by'] == 'createdBy') {
                $orderBy = 'cu.username';
            }            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->getSolarWindsLocationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
              
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
                    $row[] = $resultRow['companyName'].'('.$resultRow['domain'].')';
                    $row[] = $resultRow['supportLocationName'];
                    $row[] = $resultRow['requestTypeName'];
                    $row[] = (!empty($resultRow['username']) ? $resultRow['username'] : 'N/A');
                    $row[] = ($resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y h:i:s') : 'N/A');
                    $row[] = ($resultRow['updatedAt'] ? $resultRow['updatedAt']->format('M-d-Y h:i:s') : 'N/A');
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
        
        if (!($this->get('admin_permission')->checkPermission('solar_wind_location_add'))){
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to assign solar winds request type.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objsolarwindslocation = new SolarWindsSupportLocation();
        
        $form = $this->createForm(new SolarWindsRequestFormType(array("admin" => $admin,"id"=>0)),$objsolarwindslocation);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            $fdata = $request->get('dhi_admin_solar_winds_location');
            if (empty($fdata['supportsite'])) {
                $this->get('session')->getFlashBag()->add('danger', 'Please select site.');
                return $this->render('DhiAdminBundle:SolarWindsRequestTypes:new.html.twig',array('form' => $form->createView()));    
            }
            if (empty($fdata['supportLocation'])) {
                $this->get('session')->getFlashBag()->add('danger', 'Please select support location.');
                return $this->render('DhiAdminBundle:SolarWindsRequestTypes:new.html.twig',array('form' => $form->createView()));   
            }
            if (empty($fdata['solarWindsRequestType'])) {
                $this->get('session')->getFlashBag()->add('danger', 'Please Select Solar Winds Request type.');
                return $this->render('DhiAdminBundle:SolarWindsRequestTypes:new.html.twig',array('form' => $form->createView()));    
            }
            
            if ($form->isValid()) {
                $formData = $form->getData();
            }
            
            $isAlreadyAssignSupportLocation = $em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->findBy(array('supportLocation' => $formData->getSupportLocation(), 'isDeleted' => 0));
            if ($isAlreadyAssignSupportLocation) {
                $this->get('session')->getFlashBag()->add('danger', 'This support location is already assigned to other solar winds request type.');
                return $this->render('DhiAdminBundle:SolarWindsLocation:new.html.twig',array('form' => $form->createView())); 
            }
            
            $objsolarwindslocation->setCreatedBy($admin);
            $objsolarwindslocation->setUpdatedBy($admin);
            $em->persist($objsolarwindslocation);
            $em->flush();
            
            // $objsolarwinds = $objsolarwindslocation->getSolarWindsRequestType();
            // $objsolarwinds->setIsAssigned(true); 
            // $em->persist($objsolarwinds);
            $em->flush();
 
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Assign Solar Winds request type to support location';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has assign Solar Winds :'".$objsolarwindslocation->getSolarWindsRequestType()->getRequestTypeName()."' to support location :'".$objsolarwindslocation->getSupportLocation()->getName()."' ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $this->get('session')->getFlashBag()->add('success', 'Solar winds request type assigned to support location successfully.');
            return $this->redirect($this->generateUrl('dhi_admin_solar_winds_location_list'));
        }
        return $this->render('DhiAdminBundle:SolarWindsRequestTypes:new.html.twig',array('form' => $form->createView()));   
    }
    
    
    public function editAction(Request $request,$id) {
        
        if (!($this->get('admin_permission')->checkPermission('solar_wind_location_edit'))){
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update solar winds location.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        if(!$id){
            $this->get('session')->getFlashBag()->add('failure', "Solar wind request type not available.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objsolarwindslocation = $em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->find($id);
        $solarwindsrequesttypeid = $objsolarwindslocation->getSolarWindsRequestType()->getId();
        $form = $this->createForm(new SolarWindsRequestFormType(array("admin" => $admin,"id"=>$id)),$objsolarwindslocation);
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            
            $fdata = $request->get('dhi_admin_solar_winds_location');
            $formData = '';
            if ($form->isValid()) {
                $formData = $form->getData();
            }
            
            $checkobjsolarwindslocation = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->find($fdata['solarWindsRequestType']);
            if(!$checkobjsolarwindslocation){
                $this->get('session')->getFlashBag()->add('danger', 'Solar Winds Request type not available.');
                return $this->render('DhiAdminBundle:SolarWindsRequestTypes:edit.html.twig',array('form' => $form->createView(),'objsolarwindslocation' => $objsolarwindslocation));    
            }
            
            if (empty($fdata['solarWindsRequestType'])) {
               
                $this->get('session')->getFlashBag()->add('danger', 'Please Select Solar Winds Request type.');
                return $this->render('DhiAdminBundle:SolarWindsRequestTypes:edit.html.twig',array('form' => $form->createView(),'objsolarwindslocation' => $objsolarwindslocation));    
            }

            $objsolarwindslocation->setCreatedBy($admin);
            $objsolarwindslocation->setUpdatedBy($admin);
            $em->persist($objsolarwindslocation);
            $em->flush();

            /*
                if($formData && $formData->getSolarWindsRequestType()->getId()!=  $solarwindsrequesttypeid){

                    $oldobjsolarwinds = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->find($solarwindsrequesttypeid);
                    $oldobjsolarwinds->setIsAssigned(false); 
                    $em->persist($oldobjsolarwinds);
                    $em->flush(); 

                    $objsolarwinds = $objsolarwindslocation->getSolarWindsRequestType();
                    $objsolarwinds->setIsAssigned(true); 
                    $em->persist($objsolarwinds);
                    $em->flush(); 

                } 
            */

            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Edit Solar Winds request type';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has assign Solar Winds request type :'".$objsolarwindslocation->getSolarWindsRequestType()->getRequestTypeName()."' to suppport location :'".$objsolarwindslocation->getSupportLocation()->getName()."' ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $this->get('session')->getFlashBag()->add('success', 'Solar Winds request type updated successfully.');
            return $this->redirect($this->generateUrl('dhi_admin_solar_winds_location_list'));
        }
        return $this->render('DhiAdminBundle:SolarWindsRequestTypes:edit.html.twig',array('form' => $form->createView(),'objsolarwindslocation' => $objsolarwindslocation));   
    }

    public function deleteAction(Request $request) {

        if (!($this->get('admin_permission')->checkPermission('solar_wind_location_delete'))){
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete solar winds request type.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $id = $request->get('id');

        if(empty($id)){
            $resulterror = array('type' => 'danger', 'message' => 'Solar Winds Request Type not Exits.');
            $response = new Response(json_encode($resulterror));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $objSolarWindsSupportLocation = $em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->find($id);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Solar Winds request type';
        if ($objSolarWindsSupportLocation) {

            $solarwindname   = $objSolarWindsSupportLocation->getSolarWindsRequestType()->getRequestTypeName();
            $supportLocation = $objSolarWindsSupportLocation->getSupportLocation()->getName();
            $activityLog['description'] = "Admin  " . $admin->getUsername() . " has deleted assigned support location :'".$supportLocation."' from solar winds : '".$solarwindname."'";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            // $objsolarwinds = $objSolarWindsSupportLocation->getSolarWindsRequestType();
            // $objsolarwinds->setIsAssigned(false); 
            // $em->persist($objsolarwinds);
            // $em->flush();

            $objSolarWindsSupportLocation->setIsDeleted(true);
            $objSolarWindsSupportLocation->setUpdatedBy($admin);
            $em->remove($objSolarWindsSupportLocation);
            $em->flush();

            $result = array('type' => 'success', 'message' => 'Solar winds request type deleted successfully.');
        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Solar winds request type ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'Record does not exists.');
        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function supportlocationAction(Request $request){
        $siteId =  $request->get('siteId');

        $em = $this->getDoctrine()->getManager();
        $isAlreadyAssignSupportLocation = $em->getRepository('DhiUserBundle:SolarWindsSupportLocation')->getaassignedlocation($siteId);

        $existlocation = array();
        if($isAlreadyAssignSupportLocation){
            foreach($isAlreadyAssignSupportLocation as $supportlocation){
                $existlocation[] = $supportlocation['id'];
            }
        }

        $supportlocations = $em->getRepository('DhiUserBundle:SupportLocation')->getSitewiseLocation($siteId,$existlocation);

        $response = new Response(json_encode($supportlocations));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}