<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\AdminBundle\Entity\TikilivePromoCode;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Form\Type\ImportTikilivePromoCodeFormType;
use Dhi\AdminBundle\Form\Type\EditTikilivePromoCodeFormType;
// use \Doctrine\DBAL\DBALException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \DateTime;

class TikilivePromoCodeController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('tikilive_promo_code_list') || $this->get('admin_permission')->checkPermission('tikilive_promo_code_import'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view Tikilive promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $tikiLivePlan = $em->getRepository('DhiAdminBundle:TikilivePromoCode')->getDistinctTikiLivePlan();
        $arrTikiLivePlan = array();
        if($tikiLivePlan){
            foreach ($tikiLivePlan as $plan){
                $arrTikiLivePlan[] = $plan['planName'];
            }
        }
        return $this->render('DhiAdminBundle:TikilivePromoCode:index.html.twig',array('tikiliveplannames'=>$arrTikiLivePlan));
    }

    public function listJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $promoCodeColumns = array('id','batchName', 'promoCode', 'planName', 'isRedeemed', 'packageName', 'displayDate', 'redeemedDate', 'redeemedBy', 'status','createdAt', 'createdBy', 'Action');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        
        
        
        $em = $this->getDoctrine()->getManager();
        
        
        if(!empty($gridData['search_data'])) {
            $this->get('session')->set('tikilivepromoSearchData', $gridData);
        } else {
            $this->get('session')->remove('tikilivepromoSearchData');
        }
        
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'tc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'promoCode') {
                $orderBy = 'tc.promoCode';
            } else if ($gridData['order_by'] == 'batchName') {
                $orderBy = 'tc.batchName';
            } else if ($gridData['order_by'] == 'planName') {
                $orderBy = 'tc.planName';
            } else if ($gridData['order_by'] == 'isRedeemed') {
                $orderBy = 'tc.isRedeemed';
            } else if ($gridData['order_by'] == 'status') {
                $orderBy = 'tc.status';
            } else if ($gridData['order_by'] == 'createdAt') {
                $orderBy = 'tc.createdAt';
             } else if ($gridData['order_by'] == 'createdBy') {
                $orderBy = 'tc.createdBy';
            } else if ($gridData['order_by'] == 'redeemedBy') {
                $orderBy = 'u.username';
            } else if ($gridData['order_by'] == 'redeemedDate') {
                $orderBy = 'tc.redeemedDate';
            } else if ($gridData['order_by'] == 'displayDate') {
                $orderBy = 'tc.displayDate';
            } else if ($gridData['order_by'] == 'packageName') {
                $orderBy = 'sp.packageName';
            }
        }

        $data = $em->getRepository('DhiAdminBundle:TikilivePromoCode')->getPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    
                    if (!empty($resultRow['uId'])) {
                        $userUrl = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['uId'])) . '">' . $resultRow['username'] . '</a>';

                        $displayDate = $resultRow['displayDate']->format('m-d-Y H:i:s');
                    } else {
                        $userUrl = $displayDate = 'N/A';
                    }
                    $row = array();
                    if($resultRow['isRedeemed']=='No'){
                     $row[] = '<input type="checkbox" class="tikilivecheckbox" name="enabledisable[]" value="'.$resultRow['id'].'">';
                    } else {
                         $row[] = '';
                    }
                    $row[] = $resultRow['batchName'];
                    $row[] = $resultRow['promoCode'];
                    $row[] = $resultRow['planName'];
                    $row[] = $resultRow['isRedeemed'] == 'Yes' ? 'Yes' : 'No';
                    $row[] = !empty($resultRow['packageName']) ? $resultRow['packageName'] : 'N/A';
                    $row[] = $displayDate;
                    $row[] = ($resultRow['redeemedDate'] != NULL ? $resultRow['redeemedDate']->format('m-d-Y H:i:s') : "N/A");
                    $row[] = $userUrl;
                    $row[] = $resultRow['status'] == true ? 'Enabled' : 'Disabled';
                    $row[] = (!empty($resultRow['createdAt']) ? $resultRow['createdAt']->format('m-d-Y H:i:s') : 'N/A');
                    $row[] = $resultRow['createdBy'];
                    $row[] = $resultRow['id'];
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function importAction(Request $request) {
        //  Check permission
        if (!($this->get('admin_permission')->checkPermission('tikilive_promo_code_import'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to Import Tikilive promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $isexpirydate = false;
        $regex = '/^([a-z]|[A-Z]|[0-9])+$/';
        $validateheader = true;

        set_time_limit(0);
        ini_set('memory_limit','-1');

        $objTikilivePromoCode = new TikilivePromoCode();
        $form_code = $this->createForm(new ImportTikilivePromoCodeFormType(), $objTikilivePromoCode);
         
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randBatchNameCode = '';
        $random_string_length = 3;
        for ($i = 0; $i < $random_string_length; $i++) {
            $randBatchNameCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        if ($request->getMethod() == 'POST') {
            
           
            $formData = $request->get('dhi_admin_tikilive_promo_code');
            $dateprefix = $request->get('txtdateprefix');
            $fixprefix  = $request->get('txtRandomChar');
            
            if(!empty($formData['batchName']) && strlen($formData['batchName']) <>4){
                $this->get('session')->getFlashBag()->add('danger', 'Please enter 4 digit batch prefix');
                return $this->render('DhiAdminBundle:TikilivePromoCode:import.html.twig', array(
                    'form_code' => $form_code->createView(),
                    'randBatchNameCode'=>$randBatchNameCode
                 ));
            }
            
            if(!preg_match($regex, $formData['batchName'])){
               $this->get('session')->getFlashBag()->add('danger', 'Please enter valid batch prefix');
                return $this->render('DhiAdminBundle:TikilivePromoCode:import.html.twig', array(
                    'form_code' => $form_code->createView(),
                    'randBatchNameCode'=>$randBatchNameCode
                 )); 
            }
            
            
            $currentdate = new \DateTime();
            $cdata = $currentdate->format('mdY'); 
            if($cdata!=$dateprefix){
                $dateprefix = $cdata;
            }
            $batchname = $fixprefix.$formData['batchName'].$dateprefix;
             
            $fileData = $request->files->get('dhi_admin_tikilive_promo_code');
            if (!empty($fileData['csvFile'])) {
                $csvFile = $this->uploadFile($fileData['csvFile']);

                if ($fileHandler = fopen($csvFile, 'r')) {

                    $codeCount = $duplicateCount = $failedCount = $sucCount = $headernotvalid = 0;
                    $i = 0;
                    $admiUsername = $admin->getUsername();
                    $con = $em->getConnection();
                    $sql = '';
                    $params = array();
                    while ($record = fgetcsv($fileHandler)) {
                        
                        if($i == 0 && !empty($record[0]) && !empty($record[1]) && $record[0] != "Promo Code" && $record[1] != "Plan Name"){
                            $validateheader = false;
                        }

                        if($validateheader){
                            if (!empty($record[0]) && !empty($record[1]) && $record[0] != "Promo Code" && $record[1] != "Plan Name") {

                                $checkDuplicatePromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('promoCode' => $record[0]));

                                if (!$checkDuplicatePromocode) {

                                      $newPromoCode = new TikilivePromoCode();
                                      $newPromoCode->setPromoCode(trim($record[0]));
                                      $newPromoCode->setPlanName(trim($record[1]));
                                      $newPromoCode->setBatchName($batchname);
                                      $newPromoCode->setCreatedBy($admiUsername);
                                      $em->persist($newPromoCode);

                                      $sucCount++; $codeCount++;

                                        if ($codeCount % 500 == 0) {
                                            $em->flush();
                                            $em->clear();
                                        }

                                } else {

                                    $duplicateCount++;
                                }
                            } else {
                                if ($i != 0) {
                                    $failedCount++;
                                }
                            }
                        } else {
                            $headernotvalid++;
                        }

                        $i++;
                    }

                    if ($codeCount > 0) {
                        $em->flush();
                        $em->clear();
                    }

                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Import tikilive promocode';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " imported Tikilive promo code";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    if (file_exists($csvFile)) {
                        fclose($fileHandler);
                        unlink($csvFile);
                    }

                    if($headernotvalid > 0){
                        $this->get('session')->getFlashBag()->add('danger', 'Please enter valid Header of CSV as per Sample file');
                         return $this->render('DhiAdminBundle:TikilivePromoCode:import.html.twig', array(
                            'form_code' => $form_code->createView(),
                            'randBatchNameCode'=>$randBatchNameCode
                         )); 
                    } else {
                      $this->get('session')->getFlashBag()->add('success', "<b>Promo Codes imported successfully!</b><br/>Promo Codes imported Successfully: $sucCount, Promo Codes failed to Import: $failedCount, Duplicate Promo Codes found: $duplicateCount");  
                    }
                    
                    
                } else {
                    $this->get('session')->getFlashBag()->add('failure', "Invalid Format of CSV File");
                }
            } else {
                $this->get('session')->getFlashBag()->add('failure', "Please Upload CSV File");
            }
            return $this->redirect($this->generateUrl('dhi_admin_tikilive_promo_code_list'));
        }

        return $this->render('DhiAdminBundle:TikilivePromoCode:import.html.twig', array(
                    'form_code' => $form_code->createView(),
                    'randBatchNameCode'=>$randBatchNameCode
        ));
    }

    private function uploadFile($csvFile) {
        $basicPath = $this->get('kernel')->getRootDir() . '/../web/uploads';
        $csvFile->move($basicPath, $csvFile->getClientOriginalName());
        return $basicPath . '/' . $csvFile->getClientOriginalName();
    }

    public function downloadSampleCsvAction(Request $request){

        $admin = $this->get('security.context')->getToken()->getUser();
        if($admin){
            $request = $this->get('request');
            $path = $this->get('kernel')->getRootDir(). "/../app/Resources/";
            $content = file_get_contents($path.'Tikilive-Promo-Codes-Sample.csv');

            $response = new Response();

            //set headers
            $response->headers->set('Content-Type', 'mime/type');
            $response->headers->set('Content-Disposition', 'attachment;filename="'.'Tikilive-Promo-Codes-Sample.csv');

            $response->setContent($content);
            return $response;   
        }else{
            return $this->redirect($this->generateUrl('dhi_admin_tikilive_promo_code_import'));
        }
    }
    
    
    public function changeStatusAction(Request $request,$id,$status){
        
       if (!$this->get('admin_permission')->checkPermission('tikilive_promo_code_status')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed change status of tikilive promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objTikilivePromoCode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->find($id);

        if (!$objTikilivePromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find tikilive promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_tikilive_promo_code_list'));
        }
        if ($objTikilivePromoCode->getStatus() == 1) {
            $objTikilivePromoCode->setStatus(0);
            $changeStatus = 'Disabled';
        } else {
            $objTikilivePromoCode->setStatus(1);
            $changeStatus = 'Enabled';
        }

        $em->persist($objTikilivePromoCode);
        $em->flush();

        // set audit log add email campagin
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = $changeStatus . ' tikilive Promo Code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has " . $changeStatus . " Tikilive promo code " . $objTikilivePromoCode->getPromoCode();
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $this->get('session')->getFlashBag()->add('success', 'Tikilive Promo Code ' . $changeStatus . ' successfully.');
        return $this->redirect($this->generateUrl('dhi_admin_tikilive_promo_code_list')); 
    }
    
    
    public function changeStatusallAction(Request $request){
        if (!$this->get('admin_permission')->checkPermission('tikilive_promo_code_status')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed change status of tikilive promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $promocodes = $request->get('promocodeids');
        $statustype = $request->get('statustype'); 
        $promocodescnt = count($promocodes);
       
      
        if($promocodescnt > 1){
            
            
            for($i=0;$i<$promocodescnt;$i++){
              if($promocodes[$i]!='on'){
                $objTikilivePromoCode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->find($promocodes[$i]);
                if($statustype=='disable'){
                    $objTikilivePromoCode->setStatus(0);
                    $changeStatus = 'Disabled';
                } elseif($statustype=='enable'){
                    $objTikilivePromoCode->setStatus(1);
                    $changeStatus = 'Enabled';
                }
                
                $em->persist($objTikilivePromoCode);
                $em->flush();
                
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = $changeStatus . ' tikilive Promo Code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has " . $changeStatus . " tikilive promo code " . $objTikilivePromoCode->getPromoCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
              }
            }
           $this->get('session')->getFlashBag()->add('success', 'Tikilive Promo Code '.$changeStatus.'  successfully.');
           
        } else {
           $this->get('session')->getFlashBag()->add('success', 'No Tikilive Promo Codes found.');
          
        }
        $statuschange = 1;
        $response = new Response(json_encode($statuschange));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function exportCsvAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('tikilive_promocode_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export tikilive promocode.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin  = $this->get('security.context')->getToken()->getUser();
        $em     = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');
        
        $searchData = array('search_data' => array());
        if ($this->get('session')->get('tikilivepromoSearchData')) {
            $searchData = $this->get('session')->get('tikilivepromoSearchData');
        }
        
        $tikilivepromocodeData = $em->getRepository('DhiAdminBundle:TikilivePromoCode')->getExportDataOfTikliveePromocodes($helper, $searchData, 'ANDLIKE');
        
        //Create object of streamed response
        $response = new StreamedResponse();
        $response->setCallback(function() use($tikilivepromocodeData) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Batch Name", 'Promo Code', "IPTV Plan Name", "Is Displayed?", "Internet Plan Name", "Display Date", "Redeemed Date", "Redeemed By", "Status", "Imported Date", "Imported By"), ',');
            if ($tikilivepromocodeData) {
                foreach ($tikilivepromocodeData as $resultRow) {
                   $batchname        =  $resultRow['batchName'];
                   $promocode        =  $resultRow['promoCode'];
                   $iptvplanname     =  $resultRow['planName'];
                   $isdisplayed      =  $resultRow['isRedeemed'];
                   $internetplanname =  (!empty($resultRow['packageName']) ? $resultRow['packageName'] : "N/A");
                   $displaydate      =  (!empty($resultRow['username']) && ($resultRow['displayDate'] != NULL) ? $resultRow['displayDate']->format('m/d/Y H:i:s') : 'N/A');
                   $redeemeddate     =  (($resultRow['redeemedDate'] != NULL) ? $resultRow['redeemedDate']->format('m/d/Y H:i:s') : 'N/A');
                   $username         =  (!empty($resultRow['username']) ? $resultRow['username'] : 'N/A');
                   $status           =  (($resultRow['status'] == 1) ? 'Enabled' : 'Disabled');
                   $importdate       =  (($resultRow['createdAt'] != NULL) ? $resultRow['createdAt']->format('m/d/Y H:i:s') : 'N/A');
                   $importby         =  $resultRow['createdBy'];
                   
                   fputcsv($handle, array($batchname, $promocode, $iptvplanname, $isdisplayed, $internetplanname, $displaydate, $redeemeddate, $username, $status, $importdate, $importby), ',');
                }
            }
             fclose($handle);
        });
        
         // Set audit log for export csv sales details report
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv tikiive promocodes';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv of tikilive promocodes";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $file_name = 'tikilive_promocodes_report.csv'; // Create file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');
       
        return $response;
        
    }
}
