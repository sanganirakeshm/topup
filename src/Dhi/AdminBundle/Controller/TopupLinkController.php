<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\TopupLink;
use Dhi\AdminBundle\Form\Type\TopupLinkFormType;

class TopupLinkController extends Controller {

    public function indexAction(Request $request) {
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('topup_link_list') || $this->get('admin_permission')->checkPermission('topup_link_edit'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view topup link list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        return $this->render('DhiAdminBundle:TopupLink:index.html.twig');
    }
    
    public function topupListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
     
        $settingColumns = array('Id', 'ServiceLocation', 'LinkName', 'Url', 'Status', 'Action');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($settingColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 't.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 't.id';
            }
             if ($gridData['order_by'] == 'ServiceLocation') {
                
                $orderBy = 't.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiAdminBundle:TopupLink')->getTopupLinkGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
       
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
                    $serviceLoc = '';
                    foreach ($resultRow->getServiceLocations() as $serLoc){
                        $serviceLoc .= $serLoc->getName().',';
                    }
                    $serviceLoc = rtrim($serviceLoc, ',');
                    
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $serviceLoc;
                    $row[] = $resultRow->getLinkName();
                    $row[] = $resultRow->getUrl();
                    $row[] = $resultRow->getStatus() == true ? 'Active' : 'Inactive';
                    $row[] = $resultRow->getId();
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function editTopupLinkAction(Request $request, $id){
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        if (!($this->get('admin_permission')->checkPermission('topup_link_edit'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to edit topup link.");
            return $this->redirect($this->generateUrl('dhi_admin_topup_link_list'));
        }
        
        $objTopupLink = $em->getRepository('DhiAdminBundle:TopupLink')->find($id);
        
        if(!$objTopupLink){
            $this->get('session')->getFlashBag()->add('failure', "Topup link does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_topup_link_list'));
        }
        
        $allServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
        $arrServiceLocation = array();
        foreach ($allServiceLocation as $key => $locationName) {
            $arrServiceLocation[$key] = $allServiceLocation[$key]['name'];
        }
        
        $form = $this->createForm(new TopupLinkFormType(), $objTopupLink);
        if($request->getMethod() == 'POST'){
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                $em->persist($objTopupLink);
                $em->flush();
                
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Topup Link';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated topup link ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Topup link updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_topup_link_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:TopupLink:editTopupLink.html.twig', array(
            'form' => $form->createView(),
            'serviceLocation' => $arrServiceLocation,
            'objTopupLink' => $objTopupLink
        ));
    }
    
    public function searchServiceLocationAction(Request $request) {
    
        $em = $this->getDoctrine()->getManager();
        $tag     = $request->get('tag');
        $jsonData   = array();
        
        if($tag){
    
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->getSearchServiceLocationTopupLink($tag);
            if($serviceLocations){
                
                foreach ($serviceLocations as $serviceLocation){
    
                    $tempArr = array();
                    
                    $country = '';
                    if($serviceLocation->getCountry()){
                        
                        $country = $serviceLocation->getCountry()->getName();
                    }
                    $locationName     = $serviceLocation->getName();
                    $locationId       = $serviceLocation->getId();
    
                    $tempArr['key']   = (string) $locationId;
                    $tempArr['value'] = $locationName;
    
                    $jsonData[] = $tempArr;
                }
            }
        }
        $response = new Response(json_encode($jsonData));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function newAction(Request $request){
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        if (!($this->get('admin_permission')->checkPermission('topup_link_new'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new topup link.");
            return $this->redirect($this->generateUrl('dhi_admin_topup_link_list'));
        }
        
        $allServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
        $arrServiceLocation = array();
        foreach ($allServiceLocation as $key => $locationName) {
            $arrServiceLocation[$key] = $allServiceLocation[$key]['name'];
        }
        $objTopupLink = new TopupLink();
        $form = $this->createForm(new TopupLinkFormType(), $objTopupLink);
        
        if($request->getMethod() == 'POST'){
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                $em->persist($objTopupLink);
                $em->flush();
                
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Topup Link';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has add new topup link ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Topup link added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_topup_link_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:TopupLink:add.html.twig', array(
            'form' => $form->createView(),
            'serviceLocation' => $arrServiceLocation
        ));
    }
    
    public function deleteAction(Request $request) {
        
        $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('topup_link_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete topup link.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objTopupLink = $em->getRepository('DhiAdminBundle:TopupLink')->find($id);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Topup Link';
        if ($objTopupLink) {

            $em->remove($objTopupLink);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Topup link deleted successfully!');
            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted topup link";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete topup link";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'Topup link does not exist.');
        }
        $response = new Response(json_encode($result));

	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
