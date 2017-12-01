<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\PromotionFormType;
use Dhi\AdminBundle\Entity\Promotion;
use \DateTime;

class PromotionController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('promotion_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view promotion list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();

        $admin = $this->get('security.context')->getToken()->getUser();              
        return $this->render('DhiAdminBundle:Promotion:index.html.twig', array('admin' => $admin));
    }
    
    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $columns = array("startDate", "endDate", "Amount", "Status", "Id");
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($columns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'p.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'startDate') {
                $orderBy = 'p.startDate';
            }else if ($gridData['order_by'] == 'endDate') {
                $orderBy = 'p.endDate';
            }else if ($gridData['order_by'] == 'Amount') {
                $orderBy = 'p.amount';
            }else if ($gridData['order_by'] == 'Status') {
                $orderBy = 'p.isActive';
            }else{
                $orderBy = 'p.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset   = $gridData['offset'];
        $em       = $this->getDoctrine()->getManager();
        $data     = $em->getRepository('DhiAdminBundle:Promotion')->getGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
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
                    $status = '<span class="btn btn-success btn-sm service">' .($resultRow['isActive'] == true ? 'Active':'Inactive'). '</span>';
                    $row = array();
                    $row[] = $resultRow['startDate']->format("M d Y");
                    $row[] = $resultRow['endDate']->format("M d Y");
                    $row[] = (($resultRow['amountType'] == 'a') ? '$' : '') . $resultRow['amount'] . (($resultRow['amountType'] == 'p') ? '%' : '');
                    $row[] = $status;
                    $row[] = $resultRow['id'];
                    $row[] = $resultRow['isActive'];
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function verifyDatesAction(Request $request, $startDate, $endDate){
        $em = $this->getDoctrine()->getManager();
        $action = $request->get('action');
        $id = $request->get('id');

        $sDate = new DateTime();
        $sDate = $sDate->createFromFormat('m-d-Y', $startDate);
        
        $eDate = new DateTime();
        $eDate = $eDate->createFromFormat('m-d-Y', $endDate);
        
        $promotion = $em->getRepository("DhiAdminBundle:Promotion")->checkDateRange($sDate, $eDate, $id);
        $status = null;

        if ($promotion) {
            $status =  false;
            $result = array('type' => 'error', "message" => "Promotion already exists for selected date range!");
        }else{
            $status = true;
            $result = array();
        }

        if (empty($action)) {
            return $status;   
        }else{
            $response = new Response(json_encode(array('status' => $status, 'result' => $result)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }

    public function newAction(Request $request) {
        if(! $this->get('admin_permission')->checkPermission('promotion_new')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new promotion.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objPromotion = new Promotion();
        $form = $this->createForm(new PromotionFormType('add'), $objPromotion);
        $form->remove('bannerImage');
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $promotion = $em->getRepository("DhiAdminBundle:Promotion")->checkDateRange($objPromotion->getStartDate(), $objPromotion->getEndDate());

                if(!$promotion){
                    
                    $amount = $objPromotion->getAmount();
                    $amountType = $objPromotion->getAmountType();
                    if($amountType == 'p' && $amount > 100){
                        $objPromotion->setAmount(100);
                    }
                    // upload images
                    $mainDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/promotionImages';
                    if($objPromotion->getBannerImage()) {
                        $uploadedFile = $objPromotion->getBannerImage();
                        $newFile = md5(uniqid()).'.'.$uploadedFile->guessExtension();
                        $uploadedFile->move($mainDir, $newFile);
                        $objPromotion->setBannerImage($newFile);
                    }

                    $endDate = $objPromotion->getEndDate();
                    $objPromotion->setEndDate((new DateTime($endDate->format("Y-m-d 23:59:59"))));
                    $objPromotion->setCreatedBy($admin);
                    $objPromotion->setCreatedAt(new DateTime());
                    $em->persist($objPromotion);
                    $em->flush();
                    
                    // Set activity log
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add Promotion';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new Promotion";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    $this->get('session')->getFlashBag()->add('success', "Promotion added successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_promotion_list'));
                }else{
                    $this->get('session')->getFlashBag()->add('danger', "You can not add promotion for this period. Promotion already exists for given period!");
                }
            }
        }
        return $this->render('DhiAdminBundle:Promotion:new.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Request $request, $id) {
        if(! $this->get('admin_permission')->checkPermission('promotion_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update promotion.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
                
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();        
        $objPromotion = $em->getRepository('DhiAdminBundle:Promotion')->find($id);

        if (!$objPromotion) {
            $this->get('session')->getFlashBag()->add('failure', "Promotion does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_business_list'));
        }
        if($objPromotion->getBannerImage()){
            $oldImage = $objPromotion->getBannerImage();
        }
        $form = $this->createForm(new PromotionFormType($objPromotion), $objPromotion);
        $form->remove('bannerImage');
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {

                $promotion = $em->getRepository("DhiAdminBundle:Promotion")->checkDateRange($objPromotion->getStartDate(), $objPromotion->getEndDate(), $id);

                if ($promotion) {
                    $this->get('session')->getFlashBag()->add('danger', "You can not add promotion for this period. Promotion already exists for given period!");

                }else{
                    $formData = $form->getData();
                    
                    $amount = $objPromotion->getAmount();
                    $amountType = $objPromotion->getAmountType();
                    if($amountType == 'p' && $amount > 100){
                        $objPromotion->setAmount(100);
                    }
                    
                    $mainUrl = $this->container->getParameter('kernel.root_dir').'/../web/uploads/promotionImages';
                    if($formData->getBannerImage() !== null && !empty($oldImage) ){
                        $this->removeFile($oldImage);
                        $file = $formData->getBannerImage();
                        $newFile = md5(uniqid()).'.'.$file->guessExtension();
                        $file->move($mainUrl, $newFile);
                        $objPromotion->setBannerImage($newFile);

                    } else if(!empty($oldImage)) {
                        $objPromotion->setBannerImage($oldImage);

                    }else if($formData->getBannerImage() !== null){
                        $file = $formData->getBannerImage();
                        $newFile = md5(uniqid()).'.'.$file->guessExtension();
                        $file->move($mainUrl, $newFile);
                        $objPromotion->setBannerImage($newFile);
                    }

                    $endDate = $objPromotion->getEndDate();
                    $objPromotion->setEndDate((new DateTime($endDate->format("Y-m-d 23:59:59"))));
                    $em->persist($objPromotion);
                    $em->flush();

                    // Set activity log
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Edit Promotion';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated Promotion";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Promotion updated successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_promotion_list'));
                }

                
            }
        }
        return $this->render('DhiAdminBundle:Promotion:edit.html.twig', array(
            'form' => $form->createView(),
            'promotion' => $objPromotion,
            'id' => $id
        ));
    }

    public function removeFile($file){
        $brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/promotionImages';
        $file_path = $brochuresDir.'/'.$file;
        if(file_exists($file_path)) {   unlink($file_path); }
    }

    public function changeStatusAction($id){
        
        if(! $this->get('admin_permission')->checkPermission('promotion_status')) {
            
            $result = array('type' => 'danger', "message" => "You are not allowed to change promition status");
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();        
        $objPromotion = $em->getRepository('DhiAdminBundle:Promotion')->find($id);
        if ($objPromotion) {
            $status = $objPromotion->getIsActive();
            $newStatus = $status == 0 ? 1 : 0;
            $objPromotion->setIsActive($newStatus);
            $em->persist($objPromotion);
            $em->flush();

            // Set activity log
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Promotion Status Changed';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated ".($newStatus == 1 ? "Activated" : "Deactivated")." Promotion";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'success', 'message' => "Promotion ".($newStatus == 1 ? "Activated" : "Deactivated")." successfully!");
        }else{
            $result = array('type' => 'danger', "message" => "Can not find promotion!");
        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}