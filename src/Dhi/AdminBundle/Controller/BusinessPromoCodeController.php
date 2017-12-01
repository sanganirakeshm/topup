<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use \DateTime;

use Dhi\AdminBundle\Entity\BusinessPromoCodes;
use Dhi\AdminBundle\Form\Type\BusinessPromoCodeFormType;
use Dhi\AdminBundle\Form\Type\BusinessPromoCodeBatchFormType;


class BusinessPromoCodeController extends Controller {

    public function codeListAction(Request $request, $batchId){
        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('business_batch_list') || $this->get('admin_permission')->checkPermission('business_batch_create') || $this->get('admin_permission')->checkPermission('business_batch_update') || $this->get('admin_permission')->checkPermission('business_batch_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view business promocode list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:BusinessBatch:codeList.html.twig');
    }
    
    public function codeListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0){
        
        $batchId = $this->getRequest()->get('batchId');
        $helper = $this->get('grid_helper_function');
        if(!empty($batchId)){
            $promoCodeColumns = array('Code', 'IsRedeemed', 'RedeemedBy', 'PackageName', 'Duration', 'ExpiryDate', 'PartnerValue', 'CustomerValue', 'Status','Note');
        }else{
            $code = $this->getRequest()->get('code');
            $promoCodeColumns = array("Code", "CreationDate", "ExpirationDate", "Duration", "IsRedeemed", "RedemptionDate", "CustomerValue", "BusinessValue", "Plan", "CreatedBy", "RedeemedBy", "BatchPrefix");
        }
        $gridData = $helper->getSearchData($promoCodeColumns);
        $admin = $this->get('security.context')->getToken()->getUser();

        if(empty($batchId)){
            if (empty($gridData['search_data']['Code'])) {
                $gridData['search_data']['Code'] = !empty($code) ? $code : ' ';
                $gridData['SearchType'] = 'ANDLIKE';
            }

        }

        if (!empty($batchId) && !empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'bc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Code') {
                $orderBy = 'bc.code';
            }
            if ($gridData['order_by'] == 'IsRedeemed') {
                $orderBy = 'bc.isRedeemed';
            }
            if ($gridData['order_by'] == 'PackageName') {
                $orderBy = 'p.packageName';
            }
            if ($gridData['order_by'] == 'Duration') {
                $orderBy = 'bc.duration';
            }
            if ($gridData['order_by'] == 'ExpiryDate') {
                $orderBy = 'bc.expirydate';
            }
            if ($gridData['order_by'] == 'Status') {
                $orderBy = 'bc.status';
            }
            if ($gridData['order_by'] == 'Note') {
                $orderBy = 'bc.note';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getBusinessPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $batchId);
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

                foreach ($data['result'] AS $resultRowArray) {
                    $resultRow = $resultRowArray[0];
                    $package = 'N/A';
                    if ($resultRow->getService()) {
                        $serviceName = $resultRow->getService()->getName();
                    }
                    if($resultRowArray['packageName']){
                        if(!empty($serviceName) && strtoupper($serviceName) == "ISP"){
                            $package = $resultRowArray['validity']. " ". ($resultRowArray['validity'] == 1 ?'day ':'days ') . $resultRowArray['packageName'].' plan (up to ' .$resultRowArray['bandwidth']. ' kbps) - $'.$resultRowArray['amount'] ;
                        }else{
                            $package = $resultRowArray['packageName'].' - $'.$resultRowArray['amount'] ;
                        }
                    }else if(!empty($serviceName) && strtoupper($serviceName) == "BUNDLE"){
                        $package = $resultRowArray['displayBundleName'];
                    }
                    
                    if($resultRow->getRedeemedBy()){
                        $objRedeemedBy = $em->getRepository("DhiUserBundle:User")->find($resultRow->getRedeemedBy());
                        $redeemedBy = $objRedeemedBy->getFirstName().' '.$objRedeemedBy->getLastName();
                    }else{
                        $redeemedBy = 'N/A';
                    }
                    
                    $row = array();
                    if (!empty($batchId) && $batchId > 0) {

                        $actualNotes = $resultRow->getNote();
                    $shortNote = null;
                    if (strlen($actualNotes) > 10) {
                        $shortNote = substr($actualNotes, 0, 10) . '...';
                    } else {
                        $shortNote = $resultRow->getNote();
                    }
                    $shortNote = '<a href="javascript:void(0);" onclick="showDetail(' . $resultRow->getId() . ');">' . $shortNote . '</a>';

                        $row[] = $resultRow->getCode();
                        $row[] = $resultRow->getIsRedeemed();
                        $row[] = $redeemedBy;
                        $row[] = $package;
                        $row[] = $resultRow->getDuration(). ' Hour(s)';
                        $row[] = $resultRow->getExpirydate() ? $resultRow->getExpirydate()->format('M-d-Y') : 'N/A';
                        $row[] = $resultRow->getBusinessValue() ? $resultRow->getBusinessValue() : 'N/A';
                        $row[] = $resultRow->getCustomerValue() ? $resultRow->getCustomerValue() : 'N/A';
                        $row[] = $resultRow->getStatus();
                        $row[] = $shortNote;
                        $row[] = $resultRow->getId();
                    }else{
                        $row[] = $resultRow->getCode();
                        $row[] = $resultRow->getCreatedAt()->format('M-d-Y');
                        $row[] = $resultRow->getExpirydate() ? $resultRow->getExpirydate()->format('M-d-Y') : 'N/A';
                        $row[] = $resultRow->getDuration(). ' Hour(s)';
                        $row[] = $resultRow->getIsRedeemed();
                        $row[] = (!is_null($resultRow->getRedeemedDate()) ? $resultRow->getRedeemedDate()->format('M-d-Y') : "N/A");

                        $row[] = $resultRow->getBusinessValue() ? $resultRow->getBusinessValue() : 'N/A';
                        $row[] = $resultRow->getCustomerValue() ? $resultRow->getCustomerValue() : 'N/A';
                        $row[] = $package;
                        $row[] = $resultRow->getCreatedBy()->getUsername();
                        $row[] = $redeemedBy;
                        $row[] = $resultRow->getBatchId()->getBatchName();                        
                    }
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function editCodeAction(Request $request, $batchId, $codeId) {
        
        if(!$this->get('admin_permission')->checkPermission('business_promo_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update business promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $objBusinessPromoCodeBatch = $em->getRepository('DhiAdminBundle:BusinessPromoCodeBatch')->find($batchId);
        $objBusinessPromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->find($codeId);
        
        if(!$objBusinessPromoCode || !$objBusinessPromoCodeBatch){
            $this->get('session')->getFlashBag()->add('failure', "No business promo code found.");
            return $this->redirect($this->generateUrl('dhi_admin_business_promo_code_list',array('batchId' => $batchId)));
        }
     
        $packages = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
        
//        $objPartner =  $em->getRepository('DhiAdminBundle:ServicePartner')->find($objPartnerPromoCode->getBatchId()->getPartner()->getId());
//        
//        if($objPartner){
//            $serviceName = $objPartner->getService()->getName();
//        }
//        
//        $objPackages = $em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName, $objPartnerPromoCode->getServiceLocations()->getId());
//        $packages = array();
//        foreach($objPackages as $key => $value){
//            if($value['isBundlePlan'] == 0 && $value['packageType'] == 'IPTV'){
//                $packages[$value['packageId']] = $value['packageName'].' - $'.$value['amount'];
//            }
//        }
        $objBusinessPromoCodeBatch->setReason('');
        $objBusinessPromoCode->setNote('');
        $form_batch = $this->createForm(new BusinessPromoCodeBatchFormType(), $objBusinessPromoCodeBatch);
        $form_code = $this->createForm(new BusinessPromoCodeFormType($packages), $objBusinessPromoCode);
        
        $form_batch->remove('noOfCodes');
        $form_batch->remove('batchName');
        $form_batch->remove('business');
        $form_batch->remove('note');
        $form_code->remove('businessValue');
        $form_code->remove('customerValue');
        $form_code->remove('serviceLocations');
        $form_code->remove('packageId');
        $form_code->remove('duration');
        $form_code->remove('service');
        
        
        if($request->getMethod() == 'POST'){
            
            $form_batch->handleRequest($request);
            $form_code->handleRequest($request);
            if ($form_batch->isValid() && $form_code->isValid()) {
        
                $em->persist($objBusinessPromoCodeBatch);
                $em->flush();
                
                $em->persist($objBusinessPromoCode);
                $em->flush();
                
                $batchName = $objBusinessPromoCodeBatch->getBatchName();
                $code = $objBusinessPromoCode->getCode();
                $reason = $objBusinessPromoCodeBatch->getReason();
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update business promo code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " update business promo code. Batch  Name: ".$batchName. ". Code: " .$code.". Reason: ".$reason;
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Business promo code has been updated successfully.");
                return $this->redirect($this->generateUrl('dhi_admin_business_promo_code_list',array('batchId' => $batchId)));
            }
        }
        
//        $partnerId = $objBusinessPromoCode->getBatchId()->getPartner()->getId();
        return $this->render('DhiAdminBundle:BusinessPromoCode:editCode.html.twig', array(
            'form_code' => $form_code->createView(),
            'form_batch' => $form_batch->createView(),
//            'partnerId' => $partnerId,
        )); 
    }
}
