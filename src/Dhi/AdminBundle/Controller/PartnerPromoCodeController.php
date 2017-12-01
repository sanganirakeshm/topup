<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dhi\AdminBundle\Entity\PartnerPromoCodes;
use Dhi\AdminBundle\Form\Type\PartnerPromoCodeFormType;
use Dhi\AdminBundle\Entity\PartnerPromoCodeBatch;
use Dhi\AdminBundle\Form\Type\PartnerPromoCodeBatchFormType;
use Dhi\AdminBundle\Entity\ServicePartner;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use \DateTime;

class PartnerPromoCodeController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('partner_promo_code_list') || $this->get('admin_permission')->checkPermission('partner_promo_code_create') || $this->get('admin_permission')->checkPermission('partner_promo_code_update') || $this->get('admin_permission')->checkPermission('partner_promo_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view partner promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $arrPartners  = $em->getRepository('DhiAdminBundle:ServicePartner')->getAllPartnerNames();
        return $this->render('DhiAdminBundle:PartnerPromoCode:index.html.twig', array('partners' => $arrPartners));
    }

    public function partnerPromoCodeListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('BatchPrefix', 'Status', 'CreationDate', 'Duration', 'NoOfRedeem', 'CustomerValue', 'PartnerValue', 'Plan', 'Qty', 'CreatedBy', 'PartnerName','Note');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        
        if (!empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'pb.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'PartnerName') {
                $orderBy = 'pat.name';
            }
            if ($gridData['order_by'] == 'BatchPrefix') {
                $orderBy = 'pb.batchName';
            }
            if ($gridData['order_by'] == 'Note') {
                $orderBy = 'pb.note';
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
        
        $data = $em->getRepository('DhiAdminBundle:PartnerPromoCodeBatch')->getPartnerPromoCodeBatchGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $serviceLocation);
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
                    $objCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('batchId'=>$resultRow->getId()));
                    if (isset($objCode) && !empty($objCode)) {
                        $objRedeemedCodes = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findBy(array('batchId'=>$resultRow->getId(), "isRedeemed" => 'Yes'));
                        $objPlan = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$objCode->getPackageId()));

                        $actualNotes = $resultRow->getNote();
                        $shortNote  = null;
                        if(strlen($actualNotes) > 10){
                            $shortNote = substr($actualNotes, 0, 10).'...';
                        }else{
                            $shortNote = $resultRow->getNote();
                        }

                        $shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $resultRow->getId() .');">' . $shortNote. '</a>';
                        $row[] = $resultRow->getBatchName();
                        $row[] = $resultRow->getStatus();
                        $row[] = $objCode->getCreatedAt()->format("m/d/Y");
                        $row[] = $objCode->getDuration()." Hour(s)";
                        $row[] = count($objRedeemedCodes);
                        $row[] = "$".$objCode->getCustomerValue();
                        $row[] = "$".$objCode->getPartnerValue();
                        $row[] = ($objPlan) ? $objPlan->getPackageName() : "N/A";
                        $row[] = $resultRow->getNoOfCodes();
                        $row[] = $objCode->getCreatedBy()->getusername();
                        $row[] = $resultRow->getPartner()->getName();
                        $row[] = $shortNote;
                        $row[] = $resultRow->getId();
                        $output['aaData'][] = $row;
                    }
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function viewNoteAction(Request $request){
        $id = $request->get('id');
        $type = $request->get('type');
        $objPromocodeBatch = null;
        if($type == 'partnerPromoCode'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('id' => $id ));
        }else if($type == 'partnerPromoCodeBatch'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:PartnerPromoCodeBatch')->findOneBy(array('id' => $id ));
        }else if($type == 'businessPromoCodeBatch'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:BusinessPromoCodeBatch')->findOneBy(array('id' => $id ));
        }else if($type == 'businessPromoCode'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('id' => $id ));
        }else if($type == 'employeePromoCode'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:EmployeePromoCode')->findOneBy(array('id' => $id ));
        }else if($type == 'customerPromoCode'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:PromoCode')->findOneBy(array('id' => $id ));
        }else if($type == 'globlePromoCode'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('id' => $id ));
        }else if($type == 'compensation'){
            $objPromocodeBatch = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:Compensation')->findOneBy(array('id' => $id ));
        }



        return $this->render('DhiAdminBundle:PartnerPromoCode:viewpopup.html.twig', array('noteDetails'=>$objPromocodeBatch->getNote()));
    }

    public function newAction(Request $request) {
        //Check permission
        if (!$this->get('admin_permission')->checkPermission('partner_promo_code_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add partner promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPartnerPromoCodeBatch = new PartnerPromoCodeBatch();
        $objPartnerPromoCode = new PartnerPromoCodes();
        $packages = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();


        $form_batch = $this->createForm(new PartnerPromoCodeBatchFormType(), $objPartnerPromoCodeBatch);
        $form_code = $this->createForm(new PartnerPromoCodeFormType($packages), $objPartnerPromoCode);
        
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randBatchNameCode = '';
        $random_string_length = 3;
        for ($i = 0; $i < $random_string_length; $i++) {
            $randBatchNameCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        //$randBatchNameCode =  rand(100, 999);
        
        if ($request->getMethod() == "POST") {

            $form_batch->handleRequest($request);
            $form_code->handleRequest($request);
            if ($form_batch->isValid() && $form_code->isValid()) {
            
                $servicePrefix = $request->get('hdnServicePrefix');
                $randBachName = $request->get('txtRandomChar');
                $inputBatchName = $request->get('dhi_admin_partner_promo_code_batch')['batchName'];
                $batchName = strtoupper($servicePrefix.$randBachName.$inputBatchName);
                
                $objPartnerPromoCodeBatch->setBatchName($batchName);
                $em->persist($objPartnerPromoCodeBatch);
                
                $noOfCodes =  $request->get('dhi_admin_partner_promo_code_batch')['noOfCodes'];
                $expiryDate =  $objPartnerPromoCode->getExpirydate();
                $isNeverExpire = $request->get('chkNeverExpire');

                for($code=0;$code < $noOfCodes;$code++){
                    $objPartnerPromoCode = new PartnerPromoCodes();
                    generateNew:
                    $randDigit = rand(1000,9999);
                    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                    $string = '';
                    $random_string_length = 2;
                    for ($i = 0; $i < $random_string_length; $i++) {
                        $string .= $characters[rand(0, strlen($characters) - 1)];
                    }
                    $string = $servicePrefix.$randDigit.$string;
                    $objPartnerPromoCodeExist = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findBy(array('code' => $string));
                    
                    if($objPartnerPromoCodeExist){
                        goto generateNew;
                    }

                    //$formData = $form->getData();
                    $serviveLocId = $request->get('dhi_admin_partner_promo_code')['serviceLocations'];
                    $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($serviveLocId);
                    
                    $objPartnerPromoCode->setPackageId($request->get('dhi_admin_partner_promo_code')['packageId']);    
                    $objPartnerPromoCode->setServiceLocations($objServiceLocation);    
                    $objPartnerPromoCode->setDuration($request->get('dhi_admin_partner_promo_code')['duration']);    
                    $objPartnerPromoCode->setBatchId($objPartnerPromoCodeBatch);    
                    $objPartnerPromoCode->setPartnerValue($request->get('dhi_admin_partner_promo_code')['partnerValue']);    
                    $objPartnerPromoCode->setCustomerValue($request->get('dhi_admin_partner_promo_code')['customerValue']);    
                    $objPartnerPromoCode->setCreatedBy($admin);
                    $objPartnerPromoCode->setCode($string);
                    $objPartnerPromoCode->setStatus($request->get('dhi_admin_partner_promo_code')['status']);

                    if(key_exists('expirydate',$request->get('dhi_admin_partner_promo_code')) && empty($isNeverExpire)){
                        $objPartnerPromoCode->setExpirydate($expiryDate);
                    }
                    $em->persist($objPartnerPromoCode);
                }
                
                $objPartnerPromoCodeBatch->setStatus($objPartnerPromoCode->getStatus());
                $em->persist($objPartnerPromoCodeBatch);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Partner Promo Code Batch';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added batch of promo code. Batch Name: ".$batchName;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'ISP Partner Promo Code batch generated successfully!');
                return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list'));
            }
        }

         $form_code->remove('note');
        return $this->render('DhiAdminBundle:PartnerPromoCode:new.html.twig', array(
                    'form_batch' => $form_batch->createView(),
                    'form_code' => $form_code->createView(),
                    'randBatchNameCode' => $randBatchNameCode
        ));
    }

    public function getPromoPackageAction(Request $request) {


        $locatoinId = $request->get('locationId');
        $partnerId = $request->get('partnerId');
        $serviceName = '';
        $em = $this->getDoctrine()->getManager();
        $package = array();
        if(!empty($locatoinId) && !empty($partnerId)){
            $objPartner =  $em->getRepository('DhiAdminBundle:ServicePartner')->find($partnerId);
            if($objPartner){
                $serviceName = $objPartner->getService()->getName();
            }
            
            $Packages = $em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName, $locatoinId, true, true);
            foreach($Packages as $key => $value){
                if($value['isBundlePlan'] == 0 && $value['packageType'] == 'IPTV'){
                    $package[$value['packageId']] = $value['packageName'].' - $'.$value['amount'];
                }
            }
        }            
        $response = new Response(json_encode($package));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function codeListAction(Request $request, $batchId){
        
        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('partner_promo_code_list') || $this->get('admin_permission')->checkPermission('partner_promo_code_create') || $this->get('admin_permission')->checkPermission('partner_promo_code_update') || $this->get('admin_permission')->checkPermission('partner_promo_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view partner promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:PartnerPromoCode:codeList.html.twig');
    }
    
    public function codeListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $batchId = $this->getRequest()->get('batchId');
        $helper = $this->get('grid_helper_function');
        if(!empty($batchId)){
            $promoCodeColumns = array('ServiceLocation', 'Code', 'IsRedeemed', 'RedeemedBy', 'PackageName', 'Duration', 'ExpiryDate', 'PartnerValue', 'CustomerValue', 'Status','Note');
        }else{
            $code = $this->getRequest()->get('code');
            $promoCodeColumns = array("Code", "CreationDate", "ExpirationDate", "Duration", "IsRedeemed", "RedemptionDate", "CustomerValue", "PartnerValue", "Plan", "CreatedBy", "RedeemedBy", "BatchPrefix");
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
            $orderBy = 'pc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Code') {
                $orderBy = 'pc.code';
            }
            if ($gridData['order_by'] == 'IsRedeemed') {
                $orderBy = 'pc.isRedeemed';
            }
            if ($gridData['order_by'] == 'PackageName') {
                $orderBy = 'p.packageName';
            }
            if ($gridData['order_by'] == 'Duration') {
                $orderBy = 'pc.duration';
            }
            if ($gridData['order_by'] == 'ExpiryDate') {
                $orderBy = 'pc.expirydate';
            }
            if ($gridData['order_by'] == 'ServiceLocation') {
                $orderBy = 'sp.name';
            }
            if ($gridData['order_by'] == 'Status') {
                $orderBy = 'pc.status';
            }
            if ($gridData['order_by'] == 'Note') {
                $orderBy = 'pc.note';
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
        
        $data = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->getPartnerPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $batchId, $serviceLocation);
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
                    $package = '';
                    if($resultRowArray['packageName']){
                        if($resultRow->getServiceLocations() && $resultRow->getServiceLocations()->getName() == "ISP"){
                            $package = $resultRowArray['validity']. " ". ($resultRowArray['isHourlyPlan'] == true ?'Hour(s) ':'Day(s) ') . $resultRowArray['packageName'].' plan (up to ' .$resultRowArray['bandwidth']. ' kbps) - $'.$resultRowArray['amount'] ;
                        }else{
                            $package = $resultRowArray['packageName'].' - $'.$resultRowArray['amount'] ;    
                        }
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
                        $shortNote  = null;
                        if(strlen($actualNotes) > 10){
                            $shortNote = substr($actualNotes, 0, 10).'...';
                        }else{
                            $shortNote = $resultRow->getNote();
                        }
                        $shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $resultRow->getId() .');">' . $shortNote. '</a>';

                        $row[] = $resultRow->getServiceLocations() ? $resultRow->getServiceLocations()->getName() : 'N/A';
                        $row[] = $resultRow->getCode();
                        $row[] = $resultRow->getIsRedeemed();
                        $row[] = $redeemedBy;
                        $row[] = $package;
                        $row[] = $resultRow->getDuration(). ' Hour(s)';
                        $row[] = $resultRow->getExpirydate() ? $resultRow->getExpirydate()->format('M-d-Y') : 'N/A';
                        $row[] = $resultRow->getPartnerValue() ? $resultRow->getPartnerValue() : 'N/A';
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

                        $row[] = $resultRow->getPartnerValue() ? $resultRow->getPartnerValue() : 'N/A';
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
        
        if(!$this->get('admin_permission')->checkPermission('partner_promo_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update partner promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $objPartnerPromoCodeBatch = $em->getRepository('DhiAdminBundle:PartnerPromoCodeBatch')->find($batchId);
        $objPartnerPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($codeId);
        
        if(!$objPartnerPromoCode || !$objPartnerPromoCodeBatch){
            $this->get('session')->getFlashBag()->add('failure', "No partner promo code found.");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_list',array('batchId' => $batchId)));
        }
        
        
        //$packages = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
        $objPartner =  $em->getRepository('DhiAdminBundle:ServicePartner')->find($objPartnerPromoCode->getBatchId()->getPartner()->getId());
        if($objPartner){
            $serviceName = $objPartner->getService()->getName();
        }
        
        $objPackages = $em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName, $objPartnerPromoCode->getServiceLocations()->getId());
        $packages = array();
        foreach($objPackages as $key => $value){
            
            if($value['isBundlePlan'] == 0 && $value['packageType'] == 'IPTV'){
                $packages[$value['packageId']] = $value['packageName'].' - $'.$value['amount'];
            }
        }
        $objPartnerPromoCodeBatch->setReason('');
        $objPartnerPromoCode->setNote('');
        $form_batch = $this->createForm(new PartnerPromoCodeBatchFormType(), $objPartnerPromoCodeBatch);
        $form_code = $this->createForm(new PartnerPromoCodeFormType($packages), $objPartnerPromoCode);
        
        $form_batch->remove('partner');
        $form_batch->remove('noOfCodes');
        $form_batch->remove('batchName');
        $form_batch->remove('note');
        $form_code->remove('partnerValue');
        $form_code->remove('customerValue');
        $form_code->remove('serviceLocations');
        $form_code->remove('packageId');
        $form_code->remove('duration');
        
        
        if($request->getMethod() == 'POST'){
            
            $form_batch->handleRequest($request);
            $form_code->handleRequest($request);
            if ($form_batch->isValid() && $form_code->isValid()) {
        
                $em->persist($objPartnerPromoCodeBatch);
                $em->flush();
                
                $em->persist($objPartnerPromoCode);
                $em->flush();
                
                $batchName = $objPartnerPromoCodeBatch->getBatchName();
                $code = $objPartnerPromoCode->getCode();
                $reason = $objPartnerPromoCodeBatch->getReason();
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update partner promo code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " update partner promo code. Batch  Name: ".$batchName. ". Code: " .$code.". Reason: ".$reason;
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "ISP Partner Promo Code has been updated successfully.");
                return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_list',array('batchId' => $batchId)));
            }
        }
        
        $partnerId = $objPartnerPromoCode->getBatchId()->getPartner()->getId();
        return $this->render('DhiAdminBundle:PartnerPromoCode:editCode.html.twig', array(
            'form_code' => $form_code->createView(),
            'form_batch' => $form_batch->createView(),
            'partnerId' => $partnerId,
        )); 
    }
    
    public function deleteBatchAction(Request $request){
        $admin = $this->get('security.context')->getToken()->getUser();
        $id = $request->get('id');
        $reason = $request->get('reason');

        if(!$this->get('admin_permission')->checkPermission('partner_promo_code_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete partner promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list',array('batchId' => $batchId)));
        }
        $em = $this->getDoctrine()->getManager();
        if (!empty($reason)) {
            # code...
            if($id){
                $objBatch = $em->getRepository("DhiAdminBundle:PartnerPromoCodeBatch")->find($id);
                if($objBatch){
                    $redeemedPromoCodes = $em->getRepository("DhiAdminBundle:PartnerPromoCodes")->findOneBy(array("batchId" => $objBatch, 'isRedeemed' => 'Yes'));
                    $activePromoCodes = $em->getRepository("DhiAdminBundle:PartnerPromoCodes")->findOneBy(array("batchId" => $objBatch, 'status' => 'Active'));
                    $promoCodes = $em->getRepository("DhiAdminBundle:PartnerPromoCodes")->findBy(array("batchId" => $objBatch));
                    if($redeemedPromoCodes){
                        $result = array('type' => 'failure', 'message' => 'You can not delete batch! Redeemed Promo Codes exist for this batch.');
                    }else if($activePromoCodes){
                        $result = array('type' => 'failure', 'message' => 'You can not delete batch! Active Promo Codes exist for this batch.');
                    }else{
                        foreach ($promoCodes as $promoCode) {
                            $em->remove($promoCode);
                        }
                        $em->remove($objBatch);
                        $em->flush();
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['activity'] = 'Delete partner promo code batch';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted promo code batch. Batch Name: " . $objBatch->getBatchName(). ", Reason: ".$reason;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        $result = array('type' => 'success', 'message' => 'ISP Partner Promo Code batch deleted successfully!');
                    }
                }else{
                    $result = array('type' => 'failure', 'message' => 'Batch does not exists!');
                }
            }else{
                $result = array('type' => 'failure', 'message' => 'Batch does not exists!');
            }
        }else{
            $result = array('type' => 'failure', 'message' => 'Please enter reason to delete batch');
        }
        $this->get('session')->getFlashBag()->add($result['type'], $result['message']);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportpdfAction(Request $request, $batchId){
        if (!$this->get('admin_permission')->checkPermission('partner_promo_code_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if(empty($batchId) || !is_numeric($batchId)){
            $this->get('session')->getFlashBag()->add('failure', "Invalid batch Id");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'partner_promocode_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf';
        $searchData ='';
        $output = array();
        $objBatch = $em->getRepository("DhiAdminBundle:PartnerPromoCodeBatch")->find($batchId);
        $arrPromoCodes = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->getExportDataOfPromoCode($batchId);
    
        if ($objBatch && $arrPromoCodes) {
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Export pdf partner promocode';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " export partner promocodes";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
            $html = '<style>' . $stylesheet . '</style>';
            $html .= $this->renderView('DhiAdminBundle:PartnerPromoCode:exportPdf.html.twig', array(
                    'PromoCodes' => $arrPromoCodes,
                    'servicePartner' => $objBatch
            ));

            unset($arrPromoCodes);
            unset($objBatch);
            
            return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
                200,
                array(
                    'Content-Type'          => 'application/pdf',
                    'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
                )
            );
            /*$pdf = $this->get("white_october.tcpdf")->create();
            $pdf->SetCreator('ExchangeVUE');
            $pdf->SetAuthor('ExchangeVUE');
            $pdf->SetTitle('ExchangeVUE');
            $pdf->SetSubject('Partner PromoCodes');
            if(file_exists($logoImgDirPath)){
                $pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="' . $dhiLogoImg . '" />');
            }
            $pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 9);
            $pdf->AddPage();
            $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
            $html = '<style>' . $stylesheet . '</style>';
            $html .= $this->renderView('DhiAdminBundle:PartnerPromoCode:exportPdf.html.twig', array(
                    'PromoCodes' => $arrPromoCodes,
                    'servicePartner' => $objBatch
            ));
            $pdf->writeHTML($html);
            $pdf->lastPage();
            $pdf->Output($file_name, 'D');
            exit();*/
        }else{
            $this->get('session')->getFlashBag()->add('failure', "No Record found");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list'));
        }
    }

    public function exportcsvAction(Request $request, $batchId){
        if (!$this->get('admin_permission')->checkPermission('partner_promo_code_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export CSV.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if(empty($batchId) || !is_numeric($batchId)){
            $this->get('session')->getFlashBag()->add('failure', "Invalid batch Id");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';
        $response = new StreamedResponse();
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export CSV partner promocode';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export partner promocodes";
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $arrPromoCodes = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->getExportDataOfPromoCode($batchId);
        $response->setCallback(function() use($arrPromoCodes) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Code", 'Plan Name', "Life", "Note", "Status","Use by Date"), ',');
            if ($arrPromoCodes) {
                foreach ($arrPromoCodes as $arrPromoCode) {
                    fputcsv($handle,array(
                        $arrPromoCode['code'],
                        $arrPromoCode['packageName'],
                        $arrPromoCode['duration']. ' Hour(s)',
                        $arrPromoCode['note'],
                        $arrPromoCode['status'],

                        $arrPromoCode['expirydate'] ? $arrPromoCode['expirydate']->format('m/d/Y') : 'N/A'
                    ), ',');
                }
            }
            fclose($handle);
        });
        $file_name = 'partner_promocodes.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');
        return $response;
    }

    public function exportExcelAction($batchId){
        if (!$this->get('admin_permission')->checkPermission('partner_promo_code_export_excel')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export excel.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if(empty($batchId) || !is_numeric($batchId)){
            $this->get('session')->getFlashBag()->add('failure', "Invalid batch Id");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_batch_list'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export Excel partner promocode';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export partner promocodes";
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $arrPromoCodes = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->getExportDataOfPromoCode($batchId);
        
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
           ->setLastModifiedBy("Admin")
           ->setTitle("DhiPortal Partner Promocodes")
           ->setSubject("Partner Promocodes")
           ->setDescription("Partner Promocodes")
           ->setKeywords("Partner Promocodes")
           ->setCategory("Partner Promocodes");
        $heading = false;
        if(!empty($arrPromoCodes)){
            $phpExcelObject->setActiveSheetIndex(0)
                ->setCellValue('A1', 'Code')
                ->setCellValue('B1', 'Package Name')
                ->setCellValue('C1', 'Duration')
                ->setCellValue('D1', 'Note')
                ->setCellValue('E1', 'Status')
                ->setCellValue('F1', 'Use by Date');
            $row = 2;
            foreach ($arrPromoCodes as $key => $arrPromoCode) {
                $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue('A'.$row, $arrPromoCode['code'])
                    ->setCellValue('B'.$row, $arrPromoCode['packageName'])
                    ->setCellValue('C'.$row, $arrPromoCode['duration']. ' Hour(s)')
                    ->setCellValue('D'.$row, $arrPromoCode['note'])
                    ->setCellValue('E'.$row, $arrPromoCode['status'])
                    ->setCellValue('F'.$row, $arrPromoCode['expirydate'] ? $arrPromoCode['expirydate']->format('m/d/Y') : 'N/A');
                $row++;
                
            }
        }

        $file_name = 'partner_promocodes_' . date('m-d-Y', time()) . '.xls';
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$file_name);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        return $response;
    }

    public function changeCodeStatusAction(Request $request, $batchId, $codeId){
    
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objPartnerPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($codeId);
        if(!$objPartnerPromoCode){
            $this->get('session')->getFlashBag()->add('failure', "Partner promo code not found");
            return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_list',array('batchId' => $batchId)));
        }
        
        $status = $objPartnerPromoCode->getStatus();
        $status = $status == 'Inactive' ? 'Active' : 'Inactive';
        $objPartnerPromoCode->setStatus($status);
        $em->persist($objPartnerPromoCode);
        $em->flush();
        
        $batchName = $objPartnerPromoCode->getBatchId()->getBatchName();
        $code = $objPartnerPromoCode->getCode();
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Update partner promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " change promo code status as ".$status. " Batch Name: " . $batchName.". Code: ".$code;
        $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
        $this->get('session')->getFlashBag()->add('success', "ISP Partner Promo Code has been successfully mark as ".strtolower($status));
        return $this->redirect($this->generateUrl('dhi_admin_partner_promo_code_list',array('batchId' => $batchId)));
        
    }
    
    public function deActivatedReportAction(Request $request){
        if (!( $this->get('admin_permission')->checkPermission('partner_promo_code_deactivation_report'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view Partner Promo Code Deactivation Report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();

        $objPartners  = $em->getRepository('DhiAdminBundle:ServicePartner')->findBy(array('status' => 1 ,'isDeleted' => 0), array('name' => 'ASC'));
        $partners = array();
        foreach ($objPartners as $objPartner) {
            $partners[] = $objPartner->getName();
        }
        $objAdmins  = $em->getRepository('DhiUserBundle:User')->getAllAdmin();
        return $this->render('DhiAdminBundle:PartnerPromoCode:deActivated.html.twig', 
            array(
                'admin'    => $admin,
                'partners' => $partners,
                'admins'   => $objAdmins
            )
        );
    }
    
    public function deActivatedReportJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $adminColumns = array("partnerName", 'Dates');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        $createdBy = $request->get('createdBy');
        $serviceLocation = $request->get('serviceLocation');
        
        if(!empty($gridData['search_data']['Dates'])){
            $dateRange = $gridData['search_data']['Dates'];
        }else{
            $dateRange = '';
        }

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sp.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'partnerName') {
                $orderBy = 'sp.name';
            }else if ($gridData['order_by'] == 'Id') {
                $orderBy = 'sp.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $data  = $em->getRepository('DhiAdminBundle:ServicePartner')->getpartnerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin);
        $locations = array();
        $output = array(
            "locations" => $locations,
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                $output = array(
                    "locations" => $locations,
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                $grandTotal = 0;
                foreach ($data['result'] AS $resultRow) {
                    if(!empty($serviceLocation) && $serviceLocation != ' '){
                        $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation(0, '', $serviceLocation,$adminServiceLocationPermission);
                    }else{
                        $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation($resultRow->getId(), '', '', $adminServiceLocationPermission);
                    }
                    
                    $innerHtml = '<table class="table table-bordered table-hover">';
                    $total = 0;
                    if($serviceLocations){
                        foreach ($serviceLocations as $location) {
                            $locations[$location['name']] = $location['name'];
                            $innerHtml .= '<tr><td width="100">'.$location['name'].'</td>';
                            $packages = $em->getRepository('DhiUserBundle:UserService')->getDeactivatedCount($location['id'], $resultRow->getId(), $createdBy, $dateRange,'', 'summary');

                            $subTotal = 0;
                            $innerHtml .= '<td>';
                            $innerHtml .= '<table class="table table-bordered table-hover">';
                            $innerHtml .= '<tr><th></th>';
                            $innerHtml .= '<th>Package Name</th>';
                            $innerHtml .= '<th>Total Deactivated</th>';
                            $innerHtml .= '</tr>';
                            foreach ($packages as $package) {
                                
                                $arrPackagesDetails = $em->getRepository('DhiUserBundle:UserService')->getDeactivatedCount($location['id'], $resultRow->getId(), $createdBy, $dateRange, $package['packageId'] ,'details');
                                
                                $subTotal += $package['totalDeactivated'];
                                $innerHtml .= '<tr><td width="40" class= "details-control"></td>';
                                $innerHtml .= '<td>'.$package['packageName'].'</td>';
                                $innerHtml .= '<td>'.$package['totalDeactivated'].'</td>';
                                $innerHtml .= '</tr><tr class="row-child"><td colspan="3">';
                                
                                $innerHtml .= '<table id="detailsTable" class="table table-bordered table-hover">';
                                $innerHtml .= '<tr><th>Username</th>';
                                $innerHtml .= '<th>Plan Name</th>';
                                $innerHtml .= '<th>Activation Date</th>';
                                $innerHtml .= '<th>Deactivated Date</th>';
                                $innerHtml .= '<th>Deactivated By</th></tr>';
                                    
                                foreach ($arrPackagesDetails as $planDetails){
                                    $innerHtml .= '<tr><td>'.$planDetails['username'].'</td>';
                                    $innerHtml .= '<td>'.$planDetails['packageName'].'</td>';
                                    $innerHtml .= '<td>'.$planDetails['activationDate']->format('m/d/Y').'</td>';
                                    $innerHtml .= '<td>'.$planDetails['deActivatedAt']->format('m/d/Y').'</td>';
                                    $innerHtml .= '<td>'.$planDetails['partnerName'].'</td>';
                                    $innerHtml .= '</tr>';
                                }
                                $innerHtml.= '</table></td></tr>';
                            }
                            $innerHtml .= '<tr><td colspan="2"><b>Total</b></td><td><b>'.$subTotal.'</b></td></tr>';
                            $innerHtml .= '</table>';
                            $innerHtml .= '</td>';
                            $total += $subTotal;
                            $innerHtml .= '</tr>';
                        }
                    }
                    if($total == 0){
                        $innerHtml = '<tr><td>No any deactivated plans</td></tr>';
                    }else{
                        $innerHtml .= '<tr><td><b>Total</b></td><td><b>'.$total.'</b></td></tr>';
                    }
                    $innerHtml .= '</table>';
                    $row = array();
                    $row[] = $resultRow->getName();
                    $row[] = $innerHtml;
                    $output['aaData'][] = $row;
                    $grandTotal += $total;
                }
                $row = array();
                $row[] = "<b>Total</b>";
                $row[] = "<b>".$grandTotal." Deactivated</b>";
                $output['aaData'][] = $row;
            }
        }
        $output['locations'] = $locations;
        $output['partnerName'] = !empty($gridData['search_data']['partnerName']) ? $gridData['search_data']['partnerName'] : '';
        $response = new Response(json_encode($output));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function ServiceLocationListJsonAction(Request $request){
        $id = $request->get('id');
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $locations = array();
        if($id != ''){
            
            $adminServiceLocationPermission = '';
            if ($admin->getGroup() != 'Super Admin') {
                $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
                $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
            }
            
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation(0, $id, '', $adminServiceLocationPermission);
            foreach ($serviceLocations as $location) {
                $locations[] = $location['name'];
            }
        }
        $response = new Response(json_encode($locations));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
