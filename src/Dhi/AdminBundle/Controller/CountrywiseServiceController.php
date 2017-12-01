<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\CountrywiseService;
use Dhi\UserBundle\Entity\Country;
use Dhi\AdminBundle\Form\Type\CountrywiseServiceFormType;
use \DateTime;

class CountrywiseServiceController extends Controller {

    public function indexAction(Request $request) {

        //Check Permission
        if (!($this->get('admin_permission')->checkPermission('country_service_list') || $this->get('admin_permission')->checkPermission('country_service_create') || $this->get('admin_permission')->checkPermission('country_service_update') || $this->get('admin_permission')->checkPermission('country_service_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view country wise service list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }      
        $em = $this->getDoctrine()->getManager();
        $objCountries = $em->getRepository('DhiUserBundle:Country')->getAllCountry();
        $countries = array_map(function($country){ return $country['name']; }, $objCountries);
        return $this->render('DhiAdminBundle:CountrywiseService:index.html.twig', array('countries' => $countries));
    }

    //Country wise service list
    public function countryWiseServiceListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $countryWiseSerivceColumns = array('Id', 'Country', 'showOnLanding', 'Services');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($countryWiseSerivceColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'c.id';
            $sortOrder = 'DESC';
            
        } else {

            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'c.id';
            }

            if ($gridData['order_by'] == 'Country') {

                $orderBy = 'c.name';
            }

        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiUserBundle:Country')->getCountryWiseGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
                    "iTotalDisplayRecords" => $data['displayRecord'],
                    "aaData" => array()
                );


                foreach ($data['result'] AS $resultRow) {
                    
                    
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getName();
                    $isShow = $resultRow->getCountrywiseService()[0]->getIsShowOnLanding() == 0 ? 'No' : 'Yes' ;
                    if($isShow == 'Yes'){
                        $linlkTitle = "Show on landing";
                    }else{
                        $linlkTitle = "Hide on landing";
                    }
                    
                    $showOnLandingUrl = $this->generateUrl('dhi_countrywiseservice_show_on_landing', array('id' => $resultRow->getId()));
                    $row[] = '<a href="javascript:void(0);" class="btn btn-success" title="'.$linlkTitle.'">'.$isShow.'</a> <a class="btn btn-info" href="'.$showOnLandingUrl.'" title="Change">Change</a>';
                    
                    $serviceTable = '<table class="table table-bordered table-hover" style="margin-bottom: 0 !important;">';
                    $serviceTable .= '<tr><td>Name</td><td>Status</td><td>Action</td></tr>';
                    
                        foreach($resultRow->getCountrywiseService() as $record) {
                            
                            $serviceTable .= '<tr>';
                            $serviceTable .= '<td>'.$record->getServices()->getName().'</td>';
                            $serviceTable .= '<td>'.($record->getStatus() ? 'Active' : 'Inactive').'</td>';
                            $serviceTable .="<td>";
                            
                                        if ($this->get('admin_permission')->checkPermission('country_service_update')) {
                                            
                                             $editUrl = $this->generateUrl('dhi_countrywiseservice_edit', array('id' => $record->getId()));
                                             $serviceTable .='<a href="'.$editUrl.'" class="btn btn-success" title="Edit">Edit</a>';
                                        }
                    					    
                                        if ($this->get('admin_permission')->checkPermission('country_service_delete')) {
                                            
                                             $deleteUrl = $this->generateUrl('dhi_countrywiseservice_delete', array('id' => $record->getId()));
                                             $serviceTable .='<a href="javascript:void(0)" class="btn btn-danger" title="Delete" onclick="return deleterecord('.$record->getId().',' ."'Delete Service'" . ',' . "'Are you sure you want to delete this service?'" .')">Delete</a>';
                                        }
                    					    
                    		
                            $serviceTable .="</td>";
                            $serviceTable .= '</tr>';
                            
                        }
                       
                    $serviceTable .='</table>';
                    
                    $row[] = $serviceTable;
                    
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {

        //Check Permission
        if (!$this->get('admin_permission')->checkPermission('country_service_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add country wise service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $errorArr = array();

        $objCountrywiseService = new CountrywiseService();

        $form = $this->createForm(new CountrywiseServiceFormType(), $objCountrywiseService);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            $objService = $form->getData();

            $countryId = $objService->getCountry()->getId();
            foreach ($objService->getServices() as $service) {

                $objServices = $em->getRepository('DhiUserBundle:CountrywiseService')->findOneBy(array('country' => $countryId, 'services' => $service->getId()));
                if ($objServices) {
                    $errorArr[] = 'Service ' . $service->getName() . ' already exist for Country ' . $objService->getCountry()->getName();
                }
            }

            if ($form->isValid() && empty($errorArr)) {
                $isShowOnLanding =  $request->get('dhi_countrywise_service_add')['isShowOnLanding'];
                foreach ($objService->getServices() as $service) {

                    $objCountrywiseService = new CountrywiseService();
                    $objCountrywiseService->setCountry($objService->getCountry());

                    $objCountrywiseService->setServices($service);
                    $objCountrywiseService->setStatus($objService->getStatus());
                    $objCountrywiseService->setIsShowOnLanding($isShowOnLanding);
                    $em->persist($objCountrywiseService);
                }

                $em->flush();

                // set audit log add country wise services
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add country wise service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added country wise service successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Service(s) added successfully!");
                return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
            }
        }

        return $this->render('DhiAdminBundle:CountrywiseService:new.html.twig', array('form' => $form->createView(), 'error' => $errorArr));
    }

    public function editAction(Request $request, $id) {
        //Check permission
        if (!$this->get('admin_permission')->checkPermission('country_service_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update country wise service.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $service = $em->getRepository('DhiUserBundle:CountrywiseService')->find($id);

        if (!$service) {
            $this->get('session')->getFlashBag()->add('failure', "Countrywise Service does not exist.");
            return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
        }

        $form = $this->createForm(new CountrywiseServiceFormType($service), $service);
        $form->remove('isShowOnLanding');
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                
                $em->persist($service);
                $em->flush();

                // set audit log update country wise services
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit country wise service';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated country wise service";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Countrywise Service updated successfully!");
                return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:CountrywiseService:edit.html.twig', array('form' => $form->createView(), 'id' => $id, 'service' => $service));
    }

    public function deleteAction(Request $request) {

        //Check permission
        if(!$this->get('admin_permission')->checkPermission('country_service_delete')) {
            
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete country wise service.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $id = $request->get('id');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        // set audit log delete country wise services
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete country wise service';
        
        $service = $em->getRepository('DhiUserBundle:CountrywiseService')->find($id);

        if ($service) {
            
            $em->remove($service);
            $em->flush();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted country wise service";
            $result = array('type' => 'success', 'message' => 'Countrywise Service deleted successfully!');
            
            
        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to delete country wise service";
            $result = array('type' => 'danger', 'message' => 'Countrywise Service does not exist.');
            
        }

        $this->get('ActivityLog')->saveActivityLog($activityLog);
       
        $response = new Response(json_encode($result));
        
        $response->headers->set('Content-Type', 'application/json');
     
        return $response;
        
    }
    
    public function changeShowonLangingAction(Request $request, $id){
        
        if(!$this->get('admin_permission')->checkPermission('country_service_change_show_on_landing')) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to change show on landing.");
            return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objCountryWiseService = $em->getRepository('DhiUserBundle:CountrywiseService')->findBy(array('country' => $id ));
        
        if(!$objCountryWiseService){
            $this->get('session')->getFlashBag()->add('failure', "Countrywise Service does not exist.");
            return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
        }
        
        $currShowonLanding = $objCountryWiseService[0]->getIsShowOnLanding();
        $updateShowonLanding = $currShowonLanding == 0 ? 1 : 0;
        
        foreach ($objCountryWiseService as $record){
            
            $record->setIsShowOnLanding($updateShowonLanding);
            $em->persist($record);
            $em->flush();
        }
        
        // set audit log update country wise services
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Edit country wise service';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated show on landing.";
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $this->get('session')->getFlashBag()->add('success', "Countrywise Service updated successfully!");
        return $this->redirect($this->generateUrl('dhi_countrywiseservice_list'));
    }
    
    public function bundlevalidateAction(Request $request){
        $em = $this->getDoctrine()->getManager();
        
        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            $countrywiseserviceData = $request->get('dhi_countrywise_service_add');
            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error']  = array();
            
            $objbundle = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'BUNDLE'));
            $objIPTV = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'IPTV'));
            $objISP = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name'=>'ISP'));
            
            $servicearray = $countrywiseserviceData['services'];
            $bundleID = $objbundle->getId();
            $iptvID = $objIPTV->getId();
            $ispID = $objISP->getId();
            
            if(in_array($bundleID, $servicearray)){
                if(!in_array($iptvID, $servicearray) || !in_array($ispID, $servicearray)){
                    $jsonResponse['error']['validbundle'] = 'Please select IPTV & ISP for Bundle';
                }
            }
            if(in_array($iptvID, $servicearray) && in_array($ispID, $servicearray)){
			    if(!in_array($bundleID, $servicearray)){
                    $jsonResponse['error']['validbundle'] = 'Please select Bundle.';
               }
            }
			
            if(count($jsonResponse['error']) > 0) {
                $jsonResponse['status'] = 'error';
            }
            echo json_encode($jsonResponse);
            exit;
        }
    }

}
