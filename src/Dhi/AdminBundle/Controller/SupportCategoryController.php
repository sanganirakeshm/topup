<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\SupportCategory;
use Dhi\AdminBundle\Form\Type\SupportCategoryFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class SupportCategoryController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('support_category_list') || $this->get('admin_permission')->checkPermission('support_category_create') || $this->get('admin_permission')->checkPermission('support_category_update') || $this->get('admin_permission')->checkPermission('support_category_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view support category list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objCategory = $em->getRepository('DhiUserBundle:SupportCategory')->getAllSupportCategory();
        $category = array();
        if($objCategory){
            foreach ($objCategory as $country) {
                $category[] = $country['name'];
            }
        }
        $allWhiteLabelSites = $em->getRepository('DhiAdminBundle:WhiteLabel')->getWhiteLabelSites();

        return $this->render('DhiAdminBundle:SupportCategory:index.html.twig', array('supportCategory' => $category,'allWhiteLabelSites' => $allWhiteLabelSites));
    }

    public function supportCategoryListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $supportCategoryColumns = array('Id','Name','supportsite', 'SequenceNumber', 'createdBy');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($supportCategoryColumns);
        
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

            if ($gridData['order_by'] == 'Name') {
                
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
      
        $data  = $em->getRepository('DhiUserBundle:SupportCategory')->getSupportCategoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                    $row[] = ($resultRow->getSupportsite())? $resultRow->getSupportsite()->getCompanyName().' ('.$resultRow->getSupportsite()->getDomain().')' : '';
                    $row[] = $resultRow->getSequenceNumber() ? $resultRow->getSequenceNumber() : 'N/A';
                    $row[] = (($resultRow->getCreatedBy()) ? $resultRow->getCreatedBy()->getUsername() : 'N/A');
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
        if(! $this->get('admin_permission')->checkPermission('support_category_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add support category.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCategory = new SupportCategory();
        $form = $this->createForm(new SupportCategoryFormType(), $objCategory);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            $isFormValid = true;
            $siteId = $request->get('dhi_user_support_category')['supportsite'];
            $name   = $request->get('dhi_user_support_category')['name'];
            
            $isDublicateCategory = $em->getRepository('DhiUserBundle:SupportCategory')->findOneBy(array('supportsite' => $siteId, 'name' => $name, 'isDeleted' => 0));
            
            if($isDublicateCategory){
                $this->get('session')->getFlashBag()->add('danger', 'Support category is already exists in this site.');
                $isFormValid = false;
            }
            if ($form->isValid() && $isFormValid) {

                $objCategory = $form->getData();
                $siteId = $objCategory->getSupportsite()->getId();
                $objMaxSequenceNumber = $em->getRepository('DhiUserBundle:SupportCategory')->findOneBy(array('supportsite' => $siteId, 'isDeleted' => false), array('sequenceNumber' => 'DESC'));
                $sequenceNumber = 1;
                if($objMaxSequenceNumber){
                    $sequenceNumber = $objMaxSequenceNumber->getSequenceNumber();
                    $sequenceNumber = $sequenceNumber + 1;
                }
                $objCategory->setSequenceNumber($sequenceNumber);
                $objCategory->setCreatedBy($admin);
                $em->persist($objCategory);
                $em->flush();
                
                // set audit log search group
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add support category';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added support category " . $objCategory->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                 
                $this->get('session')->getFlashBag()->add('success', 'Support category added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_category_list'));
            }
        }
        return $this->render('DhiAdminBundle:SupportCategory:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_category_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update support category.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objCategory = $em->getRepository('DhiUserBundle:SupportCategory')->find($id);

        if (!$objCategory) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find support category.");
            return $this->redirect($this->generateUrl('dhi_admin_support_category_list'));
        }

        $form = $this->createForm(new SupportCategoryFormType(), $objCategory);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            
            $isFormValid = true;
            $siteId = $request->get('dhi_user_support_category')['supportsite'];
            $name   = $request->get('dhi_user_support_category')['name'];
            
            $isDublicateCategory = $em->getRepository('DhiUserBundle:SupportCategory')->findOneBy(array('supportsite' => $siteId, 'name' => $name, 'isDeleted' => 0));
            
            if($isDublicateCategory && $isDublicateCategory->getId() != $id){
                $this->get('session')->getFlashBag()->add('danger', 'Support category is already exists in this site.');
                $isFormValid = false;
            }
            
            if ($form->isValid() && $isFormValid) {

                $objCategory = $form->getData();

                $em->persist($objCategory);
                $em->flush();
                
                // set audit log edit support categoey
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit support category';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated support category " . $objCategory->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Support category updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_support_category_list'));
            }
        }

        return $this->render('DhiAdminBundle:SupportCategory:edit.html.twig', array(
                    'form' => $form->createView(),
                    'category' => $objCategory
        ));
    }
    
    public function deleteAction(Request $request) {
        $id = $request->get('id'); 
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('support_category_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete support category.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete support category';

        $objCategory = $em->getRepository('DhiUserBundle:SupportCategory')->findOneBy(array('id' => $id, 'isDeleted' => 0));

        if ($objCategory) {
            
            // set audit log delete support categoey
            $sequenceNum = $objCategory->getSequenceNumber();
            $siteId = $objCategory->getSupportsite()->getId();
            $siteName = $objCategory->getSupportsite()->getCompanyName();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted support category " . $objCategory->getName(). ' Site : '.$siteName;
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $objCategory->setIsDeleted(true);
            $em->persist($objCategory);
            $em->flush();
            
            $arrParams = array(
                'siteId' => $siteId,
                'sequenceNumber' => $sequenceNum
            );
            
            $objSupportCategory = $em->getRepository('DhiUserBundle:SupportCategory')->getSupportCategoryForUpdateSequenceNum($arrParams);
            
            if($objSupportCategory){
                foreach ($objSupportCategory as $suppotCategory){
                    $seqNum = $suppotCategory->getSequenceNumber();
                    $seqNum = $seqNum - 1;
                    $suppotCategory->setSequenceNumber($seqNum);
                    $em->persist($suppotCategory);
                    $em->flush();
                }
            }
            
            $result = array('type' => 'success', 'message' => 'Support category deleted successfully!');
           
        } else {
             $result = array('type' => 'danger', 'message' => 'Unable to find support category.');
        }
       
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }

    public function updateSequenceNumberAction(Request $request){
        
        if(!$this->get('admin_permission')->checkPermission('support_category_update')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to update display order.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $siteId = $request->get('siteId');
        $startlimit = $request->get('startlimit');
         
        $oldSequenceNum = $startlimit+$request->get('oldSequenceNum') + 1;
        $newSequenceNum = $startlimit+$request->get('newSequenceNum') + 1;
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Change Support Category Display Order';
        
        $objSupportCategory = $em->getRepository('DhiUserBundle:SupportCategory')->findOneBy(array('supportsite' => $siteId, 'sequenceNumber' => $oldSequenceNum, 'isDeleted' => 0));
        if($objSupportCategory){
            
            $arrParams = array(
                'supportsiteId' => $siteId,
                'oldSequenceNum' => $oldSequenceNum,
                'newSequenceNum' => $newSequenceNum
            );
            
            $objAllOttherSupportCategory = $em->getRepository('DhiUserBundle:SupportCategory')->getSiteWiseSupportCategory($arrParams);
            
            if($objAllOttherSupportCategory){
                foreach ($objAllOttherSupportCategory as $supportCategory){
                    $seqNum = $supportCategory->getSequenceNumber();
                    if($oldSequenceNum < $newSequenceNum){
                        $seqNum = $seqNum - 1 ;
                    }else{
                        $seqNum = $seqNum + 1 ;
                    }
                    $supportCategory->setSequenceNumber($seqNum);
                    $em->persist($supportCategory);
                    $em->flush();
                }
            }
            $objSupportCategory->setSequenceNumber($newSequenceNum);
            $em->persist($objSupportCategory);
            $em->flush();
            
            $result = array('type' => 'success', 'message' => 'Display order updated successfully!');
            $activityLog['description'] = "Admin ".$admin->getUsername()." has updated display order for site: " . $objSupportCategory->getSupportsite()->getCompanyName() .' Domain :'. $objSupportCategory->getSupportsite()->getDomain();
            
        }else{
            $result = array('type' => 'danger', 'message' => 'Unable to find support category.');
            $activityLog['description'] = "Admin ".$admin->getUsername()." has try to update display order in support category";
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
            
            $checkFlag = $em->getRepository("DhiUserBundle:SupportCategory")->checkSupportCategoryName($siteId, $name, $id);
            
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
