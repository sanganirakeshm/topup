<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dhi\AdminBundle\Entity\BusinessPromoCodes;
use Dhi\AdminBundle\Form\Type\BusinessPromoCodeFormType;
use Dhi\AdminBundle\Entity\BusinessPromoCodeBatch;
use Dhi\AdminBundle\Form\Type\BusinessPromoCodeBatchFormType;
use Dhi\AdminBundle\Entity\Business;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use \DateTime;

class BusinessBatchController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('business_batch_list') || $this->get('admin_permission')->checkPermission('business_batch_create') || $this->get('admin_permission')->checkPermission('business_batch_update') || $this->get('admin_permission')->checkPermission('business_batch_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view business promocode batch list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objBusiness = $em->getRepository("DhiAdminBundle:Business")->findBy(array("isDeleted" => false, "status"=>1), array('name' => 'ASC'));
        $arrBusiness = array("" => "Select Business");
        foreach ($objBusiness as $business) {
            $arrBusiness[$business->getName()] = $business->getName();
        }

        return $this->render('DhiAdminBundle:BusinessBatch:index.html.twig', array("arrBusiness" => $arrBusiness));
    }

    public function listJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $promoCodeColumns = array('BatchPrefix', 'Status', 'CreationDate', 'Duration', 'NoOfRedeem', 'CustomerValue', 'BusinessValue', 'Plan', 'Qty', 'CreatedBy','Note');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        $businessName = $request->get('businessName');
        
        if (!empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'bb.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'BatchPrefix') {
                $orderBy = 'bb.batchName';
            }
            if ($gridData['order_by'] == 'Status') {
                $orderBy = 'bb.status';
            }
            if ($gridData['order_by'] == 'Note') {
                $orderBy = 'bb.note';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        if(!empty($businessName) && $businessName != ''){
            $data = $em->getRepository('DhiAdminBundle:BusinessPromoCodeBatch')->getBusinessPromoCodeBatchGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $businessName);
        }else{
            $data = $em->getRepository('DhiAdminBundle:BusinessPromoCodeBatch')->getBusinessPromoCodeBatchGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        }
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
                    $objCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('batchId'=>$resultRow->getId()));
                    $objRedeemedCodes = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findBy(array('batchId'=>$resultRow->getId(), "isRedeemed" => 'Yes'));
                    $service = $objCode->getService();
                    $packageName = 'N/A';
                    if ($service) {
                        $serviceName    = $service->getName();
                        if (strtoupper($serviceName) == "BUNDLE") {
                            $objPlan = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id'=>$objCode->getPackageId()));
                            if ($objPlan) {
                                $packageName = $objPlan->getDisplayBundleName();
                            }
                        } else {
                            $objPlan = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$objCode->getPackageId()));
                            if ($objPlan) {
                                $packageName = $objPlan->getPackageName();
                            }
                        }
                    }
                    $actualNotes = $resultRow->getNote();
                    $shortNote = null;
                    if (strlen($actualNotes) > 10) {
                        $shortNote = substr($actualNotes, 0, 10) . '...';
                    } else {
                        $shortNote = $resultRow->getNote();
                    }
                    $shortNote = '<a href="javascript:void(0);" onclick="showDetail(' . $resultRow->getId() . ');">' . $shortNote . '</a>';


                    $row[] = $resultRow->getBatchName();
                    $row[] = $resultRow->getStatus();
                    $row[] = $objCode->getCreatedAt()->format("m/d/Y");
                    $row[] = $objCode->getDuration()." Hour(s)";
                    $row[] = count($objRedeemedCodes);
                    $row[] = "$".$objCode->getCustomerValue();
                    $row[] = "$".$objCode->getBusinessValue();
                    $row[] = $packageName;
                    $row[] = $resultRow->getNoOfCodes();
                    $row[] = $objCode->getCreatedBy()->getusername();
                    $row[] = $shortNote;
                    $row[] = $resultRow->getId();
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
        if (!$this->get('admin_permission')->checkPermission('business_batch_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add business promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objBusinessPromoCodeBatch = new BusinessPromoCodeBatch();
        $objBusinessPromoCode      = new BusinessPromoCodes();
        $packages                  = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
        $bundles                   = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
        $packages                  = $packages + $bundles;

        $businessPromoCodeBatchFormType = new BusinessPromoCodeBatchFormType();
        $form_batch = $this->createForm($businessPromoCodeBatchFormType, $objBusinessPromoCodeBatch);
        $form_code = $this->createForm(new BusinessPromoCodeFormType($packages), $objBusinessPromoCode);
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randBatchNameCode = '';
        $random_string_length = 3;
        for ($i = 0; $i < $random_string_length; $i++) {
            $randBatchNameCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        if ($request->getMethod() == "POST") {

            $form_batch->handleRequest($request);
            $form_code->handleRequest($request);
            if ($form_batch->isValid() && $form_code->isValid()) {
            
                $servicePrefix = $request->get('hdnServicePrefix');
                $randBachName = $request->get('txtRandomChar');
                $inputBatchName = $request->get('dhi_admin_business_promo_code_batch')['batchName'];
                $batchName = strtoupper($servicePrefix.$randBachName.$inputBatchName);
                
                $objBusinessPromoCodeBatch->setBatchName($batchName);
                $em->persist($objBusinessPromoCodeBatch);
                
                $noOfCodes     = $request->get('dhi_admin_business_promo_code_batch')['noOfCodes'];
                $expiryDate    = $objBusinessPromoCode->getExpirydate();
                $isNeverExpire = $request->get('chkNeverExpire');
                $serviceId     = $request->get('dhi_admin_business_promo_code')['service'];
                $objService    = $em->getRepository("DhiUserBundle:Service")->find($serviceId);

                for($code=0;$code < $noOfCodes;$code++){
                    generateNew:
                        $randDigit = rand(1000,9999);
                        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
                        $string = '';
                        $random_string_length = 2;
                        for ($i = 0; $i < $random_string_length; $i++) {
                            $string .= $characters[rand(0, strlen($characters) - 1)];
                        }
                        $string = $servicePrefix.$randDigit.$string;
                        $objBusinessPromoCodeExist = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('code' => $string));
                    
                    if($objBusinessPromoCodeExist){
                        goto generateNew;
                    }

                    $businessValue = $request->get('dhi_admin_business_promo_code')['businessValue'];
                    if(empty($businessValue)){
                        $businessValue = 0;
                    }

                    $objBusinessPromoCode = new BusinessPromoCodes();
                    $serviveLocId = $request->get('dhi_admin_business_promo_code')['serviceLocations'];
                    $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($serviveLocId);
                    
                    $objBusinessPromoCode->setPackageId($request->get('dhi_admin_business_promo_code')['packageId']);    
                    $objBusinessPromoCode->setServiceLocations($objServiceLocation);    
                    $objBusinessPromoCode->setDuration($request->get('dhi_admin_business_promo_code')['duration']);    
                    $objBusinessPromoCode->setBatchId($objBusinessPromoCodeBatch);    
                    $objBusinessPromoCode->setbusinessValue($businessValue);
                    $objBusinessPromoCode->setCustomerValue($request->get('dhi_admin_business_promo_code')['customerValue']);    
                    $objBusinessPromoCode->setCreatedBy($admin);
                    $objBusinessPromoCode->setCode($string);
                    $objBusinessPromoCode->setService($objService);
                    $objBusinessPromoCode->setStatus($request->get('dhi_admin_business_promo_code')['status']);

                    if(key_exists('expirydate',$request->get('dhi_admin_business_promo_code')) && empty($isNeverExpire)){
                        $objBusinessPromoCode->setExpirydate($expiryDate);
                    }
                    $em->persist($objBusinessPromoCode);
                }
                
                $objBusinessPromoCodeBatch->setStatus($objBusinessPromoCode->getStatus());
                $em->persist($objBusinessPromoCodeBatch);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Business Promo Code Batch';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added batch of promo code. Batch Name: ".$batchName;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Business promo code batch added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_business_batch_list'));
            }
        }

        $form_code->remove('note');
        return $this->render('DhiAdminBundle:BusinessBatch:new.html.twig', array(
                    'form_batch' => $form_batch->createView(),
                    'form_code' => $form_code->createView(),
                    'randBatchNameCode' => $randBatchNameCode
        ));
    }

    public function getPromoPackageAction(Request $request) {
        $locationId = $request->get('locationId');
        $serviceId = $request->get('serviceId');
        $em = $this->getDoctrine()->getManager();
        $serviceName = '';
        $package = array();
        if(!empty($locationId) && !empty($serviceId)){
            $objService =  $em->getRepository('DhiUserBundle:Service')->find($serviceId);
            if($objService){
                $serviceName = $objService->getName();
                if($serviceName != 'BUNDLE') {
                    $packages = $em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName, $locationId);
                    foreach($packages as $key => $value){
                        if($value['isBundlePlan'] == 0 && $value['isExpired'] == false){
                            $package[$value['packageId']] = $value['packageName'].' - $'.$value['amount'];
                        }
                    }
                }else{
                    $packages = $em->getRepository('DhiAdminBundle:Bundle')->getBundleTypeByService($serviceName,$locationId);
                    foreach($packages as $key => $value){
                        $package[$value['bundle_id']] = $value['description'].' - $'.$value['amount'];
                    }
                }
            }
        }
        $response = new Response(json_encode($package));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function deleteBatchAction(Request $request){
        $admin = $this->get('security.context')->getToken()->getUser();
        $id = $request->get('id');
        $reason = $request->get('reason');

        if(!$this->get('admin_permission')->checkPermission('business_batch_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete business promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_business_batch_list'));
        }
        $em = $this->getDoctrine()->getManager();
        if (!empty($reason)) {
            # code...
            if($id){
                $objBatch = $em->getRepository("DhiAdminBundle:BusinessPromoCodeBatch")->find($id);
                if($objBatch){
                    $redeemedPromoCodes = $em->getRepository("DhiAdminBundle:BusinessPromoCodes")->findOneBy(array("batchId" => $objBatch, 'isRedeemed' => 'Yes'));
                    $activePromoCodes = $em->getRepository("DhiAdminBundle:BusinessPromoCodes")->findOneBy(array("batchId" => $objBatch, 'status' => 'Active'));
                    $promoCodes = $em->getRepository("DhiAdminBundle:BusinessPromoCodes")->findBy(array("batchId" => $objBatch));
                    if($redeemedPromoCodes){
                        $result = array('type' => 'failure', 'message' => 'You can not delete batch! Redeemed promo codes already exists for this batch');
                    }else if($activePromoCodes){
                        $result = array('type' => 'failure', 'message' => 'You can not delete batch! Active promo codes already exists for this batch');
                    }else{
                        foreach ($promoCodes as $promoCode) {
                            $em->remove($promoCode);
                        }
                        $em->remove($objBatch);
                        $em->flush();
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['activity'] = 'Delete business promo code batch';
                        $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted promo code batch. Batch Name: " . $objBatch->getBatchName(). ", Reason: ".$reason;
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        $result = array('type' => 'success', 'message' => 'Business promocode batch deleted successfully!');
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

        $file_name = 'business_promocode_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf';
        $searchData ='';
        $output = array();
        $objBatch = $em->getRepository("DhiAdminBundle:BusinessPromoCodeBatch")->find($batchId);
        $arrPromoCodes = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getExportDataOfPromoCode($batchId);
    
        if ($objBatch && $arrPromoCodes) {
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Export pdf business promocode';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " export business promocodes";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
            $html = '<style>' . $stylesheet . '</style>';
            $html .= $this->renderView('DhiAdminBundle:BusinessBatch:exportPdf.html.twig', array(
                    'PromoCodes' => $arrPromoCodes,
                    'batch' => $objBatch
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
            $pdf->SetSubject('Business PromoCodes');
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
            $html .= $this->renderView('DhiAdminBundle:BusinessBatch:exportPdf.html.twig', array(
                    'PromoCodes' => $arrPromoCodes,
                    'batch' => $objBatch
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
        if (!$this->get('admin_permission')->checkPermission('business_batch_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export CSV.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if(empty($batchId) || !is_numeric($batchId)){
            $this->get('session')->getFlashBag()->add('failure', "Invalid batch Id");
            return $this->redirect($this->generateUrl('dhi_admin_business_batch_list'));
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
        $activityLog['activity'] = 'Export CSV Business Promo Code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export business promocodes";
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $arrPromoCodes = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getExportDataOfPromoCode($batchId);
        $response->setCallback(function() use($arrPromoCodes) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Code", 'Plan Name', "Life", "Status", "Note", "Use by Date"), ',');
            if ($arrPromoCodes) {
                foreach ($arrPromoCodes as $arrPromoCode) {
                    if($arrPromoCode['packageName']){
                        $package = $arrPromoCode['packageName'].' - $'.$arrPromoCode['amount'] ;
                    } else {
                        $package = $arrPromoCode['displayBundleName'];
                    }
                    fputcsv($handle,array(
                        $arrPromoCode['code'],
                        $package,
                        $arrPromoCode['duration']. ' Hour(s)',
                        $arrPromoCode['status'],
                        $arrPromoCode['note'],
                        $arrPromoCode['expirydate'] ? $arrPromoCode['expirydate']->format('m/d/Y') : 'N/A'
                    ), ',');
                }
            }
            fclose($handle);
        });
        $file_name = 'business_promocodes.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');
        return $response;
    }

    public function exportExcelAction($batchId){
        if (!$this->get('admin_permission')->checkPermission('business_batch_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export excel.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        if(empty($batchId) || !is_numeric($batchId)){
            $this->get('session')->getFlashBag()->add('failure', "Invalid batch Id");
            return $this->redirect($this->generateUrl('dhi_admin_business_batch_list'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export Excel business promocode';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export business promocodes";
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $arrPromoCodes = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getExportDataOfPromoCode($batchId);
        
        $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
        $phpExcelObject->getProperties()->setCreator("liuggio")
           ->setLastModifiedBy("Admin")
           ->setTitle("DhiPortal Business Promocodes")
           ->setSubject("Business Promocodes")
           ->setDescription("Business Promocodes")
           ->setKeywords("Business Promocodes")
           ->setCategory("Business Promocodes");
        $heading = false;
        if(!empty($arrPromoCodes)){
            $phpExcelObject->setActiveSheetIndex(0)
               ->setCellValue('A1', 'Code')
               ->setCellValue('B1', 'Package Name')
               ->setCellValue('C1', 'Duration')
               ->setCellValue('D1', 'Status')
               ->setCellValue('E1', 'Note')
               ->setCellValue('F1', 'Use by Date');
            $row = 2;
            foreach ($arrPromoCodes as $key => $arrPromoCode) {

                if($arrPromoCode['packageName']){
                    $package = $arrPromoCode['packageName'].' - $'.$arrPromoCode['amount'] ;
                } else {
                    $package = $arrPromoCode['displayBundleName'];
                }

                $phpExcelObject->setActiveSheetIndex(0)
                    ->setCellValue('A'.$row, $arrPromoCode['code'])
                    ->setCellValue('B'.$row, $package)
                    ->setCellValue('C'.$row, $arrPromoCode['duration']. ' Hour(s)')
                    ->setCellValue('D'.$row, $arrPromoCode['status'])
                    ->setCellValue('E'.$row, $arrPromoCode['note'])
                    ->setCellValue('F'.$row, $arrPromoCode['expirydate'] ? $arrPromoCode['expirydate']->format('m/d/Y') : 'N/A');
                $row++;
            }
        }

        $file_name = 'business_promo-codes_' . date('m-d-Y', time()) . '.xls';
        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$file_name);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);
        return $response;
    }

    public function getPromoServiceAction(Request $request){
        $locatoinId = $request->get('locationId');
        $businessId = $request->get('businessId');
        $em = $this->getDoctrine()->getManager();
        $arrService = $package = array();
        $locationServices = $em->getRepository('DhiAdminBundle:IpAddressZone')->getServicesByLocation($locatoinId);

        if(!empty($locatoinId) && !empty($businessId)){
            $objPartner =  $em->getRepository('DhiAdminBundle:Business')->find($businessId);
            if($objPartner){
                $services = $objPartner->getServices();
                foreach ($services as $service) {
                    if (in_array($service->getName(), $locationServices)) {
                        $arrService[$service->getId()] = $service->getName();
                    }
                }
            }
        }
        $response = new Response(json_encode($arrService));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
