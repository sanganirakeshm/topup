<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\PromoCode;
use Dhi\UserBundle\Entity\Service;
use Dhi\AdminBundle\Entity\ServiceLocation;
//use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\PromoCodeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use \DateTime;

use Symfony\Component\HttpFoundation\StreamedResponse;

class PromoCodeController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('promo_code_list') || $this->get('admin_permission')->checkPermission('promo_code_create') || $this->get('admin_permission')->checkPermission('promo_code_update') || $this->get('admin_permission')->checkPermission('promo_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $data  = $em->getRepository('DhiUserBundle:Service')->findAll();

        $arrServiceLocations = array();
        /*foreach ($serviceLocation as $activity) {
            $arrServiceLocations[] = $activity['name'];
        }*/
        if ($admin->getGroup() != 'Super Admin') {
            $lo = $admin->getServiceLocations();
            foreach ($lo as $key => $value) {
                $arrServiceLocations[] = $value->getName();
            }
            sort($arrServiceLocations);
        }else{
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
            if(!empty($serviceLocations)){
                foreach ($serviceLocations as $key => $serviceLocation){
                    $arrServiceLocations[] = $serviceLocation['name'];
                }
            }
        }
        
        $arrActivityLog = array();
        foreach ($data as $key => $activity) {

            $arrActivityLog[] = $activity->getName();
        }
        // get all admin 
        $objAdmins  = $em->getRepository('DhiUserBundle:User')->getAllAdmin();
        
        return $this->render('DhiAdminBundle:PromoCode:index.html.twig',array(
            'serviceLocations' => json_encode($arrServiceLocations), 
            'services' => json_encode($arrActivityLog),
            'admins' => $objAdmins
            ));
    }

    //added for grid
   public function promoCodeListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('ServiceLocation','Services','PlanType','PromoCode','CreatedBy','ExpiryDate','Status','Duration','IsReedemed','Note','Action', 'createdAt');
		$admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        $createdBy = $request->get('createdBy');
        if(!empty($gridData['search_data'])) {
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

            if ($gridData['order_by'] == 'Services') {

                $orderBy = 's.name';
            }
            if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'PromoCode') {

                $orderBy = 'pc.promoCode';
            }
            if ($gridData['order_by'] == 'ExpiryDate') {

                $orderBy = 'pc.expiredAt';
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
        
        $data  = $em->getRepository('DhiUserBundle:PromoCode')->getPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $createdBy, $serviceLocation);

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

                        if($resultRow->getService() && $resultRow->getService()->getName() == "ISP"){
                            $package = $resultRowArray['validity']. " ". ($resultRowArray['isHourlyPlan'] == true ?'Hour(s) ':'Day(s) ') . $resultRowArray['packageName'].' plan (up to ' .$resultRowArray['bandwidth']. ' kbps) - $'.$resultRowArray['amount'] ;
                        }else{
                            $package = $resultRowArray['packageName'].' - $'.$resultRowArray['amount'] ;    
                        }
						
					} else if($resultRow->getIsBundle()) {
						$package = $resultRowArray['description'].' - $'.$resultRowArray['bundleAmount'] ;
					}

                    $actualNotes = $resultRow->getNote();
                    $shortNote  = null;
                    if(strlen($actualNotes) > 10){
                        $shortNote = substr($actualNotes, 0, 10).'...';
                    }else{
                        $shortNote = $resultRow->getNote();
                    }

                    $shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $resultRow->getId() .');">' . $shortNote. '</a>';

					$row = array();
					$row[] = $resultRow->getServiceLocations() ? $resultRow->getServiceLocations()->getName() : 'N/A' ;
					$row[] = $resultRow->getService() ? '<span class="btn btn-success btn-sm service">'.$resultRow->getService()->getName().'</span>' : '';
					$row[] = $package;
					$row[] = $resultRow->getPromoCode();
					$row[] = $resultRow->getCreatedBy();
//					$row[] = $resultRow->getReason();
					$row[] = $resultRow->getExpiredAt() ? $resultRow->getExpiredAt()->format('M-d-Y') : 'N/A';
					$row[] = $resultRow->getDuration().' Hour(s)' ;
					$row[] = $resultRow->getStatus() == true ? 'Active':'InActive';
					$row[] = $resultRow->getNoOfRedemption() == 1 ? 'Yes':'No';
                    $row[] = $shortNote;
					$row[] = $resultRow->getId().'^'.$resultRow->getNoOfRedemption().'^'.$resultRow->getStatus();
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
        if(! $this->get('admin_permission')->checkPermission('promo_code_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPromoCode = new PromoCode();
		$packages1 = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
		$packages2 = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
		$packages =		$packages1 + $packages2;
        
        $form = $this->createForm(new PromoCodeFormType($packages), $objPromoCode);
        
        $form->add('noOfCodes', 'text',array('mapped' => false));
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                 $formData = $form->getData();
                 $serviceId = $request->get('dhi_admin_promo_code')['service'];
                 $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('id'=> $serviceId));
                 $serviceLocationsId = $request->get('dhi_admin_promo_code')['serviceLocations'];
                 $objServiceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('id'=> $serviceLocationsId));
                 $noOfCodes = $request->get('dhi_admin_promo_code')['noOfCodes'];
                 $expirationDate = $request->get('dhi_admin_promo_code')['duration'];
                 $expiresAt = new DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));
                for($a = 0 ;$a < $noOfCodes ; $a++){
                    // generating code
                    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                    $string = '';
                    $random_string_length = 5 ;
                    for ($i = 0; $i < $random_string_length; $i++) {
                             $string .= $characters[rand(0, strlen($characters) - 1)];
                    }
                    // add new code
                    $objPromoCode = new PromoCode();
                    $objPromoCode->setCreatedBy($admin->getUsername())
                                ->setPromoCode($string)
                                ->setNote($request->get('dhi_admin_promo_code')['note'])
                                ->setStatus($request->get('dhi_admin_promo_code')['status'])
                                ->setService($objService)
                                ->setPackageId($request->get('dhi_admin_promo_code')['packageId'])
                                ->setServiceLocations($objServiceLocations)
                                ->setDuration($request->get('dhi_admin_promo_code')['duration'])
                                ->setExpiredAt($expiresAt);
                    if($formData->getService()->getName() == 'BUNDLE'){
                            $objPromoCode->setIsBundle(1);
                    } else {
                            $objPromoCode->setIsBundle(0);
                    }

                    $em->persist($objPromoCode);
                    $em->flush();

                    // set audit log add promo code
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add Promo Code';
                    $activityLog['description'] = "Admin ".$admin->getUsername()." has added promo code ".$string;
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                }
                $this->get('session')->getFlashBag()->add('success', 'Promo Code added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));
          }
        }

        return $this->render('DhiAdminBundle:PromoCode:new.html.twig', array(
          'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('promo_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);

		$packages1 = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
		$packages2 = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
		$packages =		$packages1 + $packages2;
        if (!$objPromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));
        }
        $objPromoCode->setNote('');
        $form = $this->createForm(new PromoCodeFormType($packages), $objPromoCode);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();
				$objPromoCode->setCreatedBy($admin->getUsername());
				if($formData->getService()->getName() == 'BUNDLE'){
					$objPromoCode->setIsBundle(1);
				}
				$expiresAt = new DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));
				
		
				$objPromoCode->setExpiredAt($expiresAt);
                $em->persist($objPromoCode);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update Promo Code';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated promo code ".$formData->getPromoCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Promo Code updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));
            }
        }

        return $this->render('DhiAdminBundle:PromoCode:edit.html.twig', array(
                    'form' => $form->createView(),
                    'promo' => $objPromoCode
        ));
    }



    public function disableAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);

        if (!$objPromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));
        }
		if($objPromoCode->getStatus() == 1){
			    $objPromoCode->setStatus(0);
				$changeStatus = 'Disabled';
		}	else{
				$objPromoCode->setStatus(1);
				$changeStatus = 'Enabled';

		}

                $em->persist($objPromoCode);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = $changeStatus.' Promo Code';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has ".$changeStatus." promo code ".$objPromoCode->getPromoCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Promo Code '.$changeStatus.' successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));



    }

    public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('promo_code_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete promo code.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Promo Code';
        if ($objPromoCode) {

            // set audit log delete email campagin

            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted promo code ".$objPromoCode->getPromoCode();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objPromoCode);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'Promo code deleted successfully!');

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete promo code.";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete promo code!');
        }
         $response = new Response(json_encode($result));

		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

	 public function previewPromoCodeAction(Request $request){


        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();


        $promocode= new PromoCode();
		$packages1 = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
		$packages2 = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
		$packages =		$packages1 + $packages2;
        $form = $this->createForm(new PromoCodeFormType($packages), $promocode);

        $data = array();
        $noOfCodes = '';
        if($request->getMethod() == "POST") {

            $form->handleRequest($request);

            $data = $form->getData();
            if(isset($request->get('dhi_admin_promo_code')['noOfCodes'])){
                $noOfCodes = $request->get('dhi_admin_promo_code')['noOfCodes'];
            }
            
        }
		$packageName ='';
        $promoPackageName = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$data->getPackageId()));
		if($promoPackageName){
            if($promoPackageName->getPackageType() == "ISP"){
                $packageName = $promoPackageName->getValidity(). "  ". ($promoPackageName->getIsHourlyPlan() == true ? 'Hour(s) ' : 'Day(s) ') . $promoPackageName->getPackageName().' plan (up to ' .$promoPackageName->getBandwidth(). ' kbps)  - $'.$promoPackageName->getAmount() ;

            }else{
                $packageName = $promoPackageName->getPackageName().' - $'.$promoPackageName->getAmount() ;
            }
			
		}
		if(!$promoPackageName){
			$promoPackageName = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id'=>$data->getPackageId()));
			if($promoPackageName){
			$packageName = $promoPackageName->getDescription().' - $'.$promoPackageName->getAmount() ;
			}

		}




        return $this->render('DhiAdminBundle:PromoCode:previewPromoCode.html.twig', array('data'=>$data,'package'=>$packageName,'noOfCodes' => $noOfCodes));
    }

	public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('promo_code_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'customer_promocode_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data
        $searchData = array();
        if($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }

        //	$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
		 $em = $this->getDoctrine()->getManager();

        //$objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $promoData = $em->getRepository('DhiUserBundle:PromoCode')->getPdfPromoData($searchData, $adminServiceLocationPermission);


        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user promo code";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:PromoCode:exportPdf.html.twig', array(
                'promoData' => $promoData
        ));

        unset($promoData);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
            )
        );
                
        // create html to pdf
        /*$pdf = $this->get("white_october.tcpdf")->create();

        // set document information
        $pdf->SetCreator('ExchangeVUE');
        $pdf->SetAuthor('ExchangeVUE');
        $pdf->SetTitle('ExchangeVUE');
        $pdf->SetSubject('Promo Code');

        // set default header data
        // set default header data
        if(file_exists($logoImgDirPath)){

            $pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="' . $dhiLogoImg . '" />');
        }

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 9);

        // add a page
        $pdf->AddPage();

        // Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');

        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:PromoCode:exportPdf.html.twig', array(
                'promoData' => $promoData
        ));
        //        echo $html;die();
        // output the HTML content
        $pdf->writeHTML($html);

        // reset pointer to the last page
        $pdf->lastPage();

        // Close and output PDF document
        $pdf->Output($file_name, 'D');
        exit();*/
    }


    public function exportCsvAction(Request $request) {

        if (!$this->get('admin_permission')->checkPermission('promo_code_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

	$searchData = array();
        if($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }
        //	$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
		 $em = $this->getDoctrine()->getManager();
        //$objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
                 
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $promoData = $em->getRepository('DhiUserBundle:PromoCode')->getPdfPromoData($searchData, $adminServiceLocationPermission);

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv promo code";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        //$result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($promoData) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Service Location", "Services", "Package	 type", "Promo Code", "Created By", "Note", "Expiration Date", "Duration", "Status","Note","Is Redeemed?"), ',');

            foreach ($promoData as $key => $resultRowArray) {
				$resultRow = $resultRowArray[0];

				$packageType = '';
                if ($resultRowArray['packageName']) {

                    if ($resultRowArray['packageType'] == "ISP") {
                        $packageType = $resultRowArray['validity'] . " " . ($resultRowArray['isHourlyPlan'] == true ? 'Hour(s) ' : 'Day(s) ') . $resultRowArray['packageName'] . ' plan (up to ' . $resultRowArray['bandwidth'] . ' kbps) - $' . $resultRowArray['amount'];
                    } else {
                        $packageType = $resultRowArray['packageName'] . ' - $' . $resultRowArray['amount'];
                    }
                } elseif ($resultRow->getIsBundle()) {
                    $packageType = $resultRowArray['description'] . ' - $' . $resultRowArray['bundleAmount'];
                }

                $serviceLocation = $resultRow->getServiceLocations() ? $resultRow->getServiceLocations()->getName() : '';
                $service = $resultRow->getService() ? $resultRow->getService()->getName() : '';
                $packageType = $packageType;
                $createdBy = $resultRow->getCreatedBy();
                $promoCode = $resultRow->getPromoCode();
                $note = $resultRow->getNote();
                $expiredAt = $resultRow->getExpiredAt()->format('M-d-Y');
                $duration = $resultRow->getDuration() . ' Hour';
                $status = $resultRow->getStatus() == true ? 'Active' : 'InActive';
                $note = $resultRow->getNote();
                $IsReedemed = $resultRow->getNoOfRedemption() == 1 ? 'Yes' : 'No';


                //$dateTime         = ($resultRow->getCreatedAt()) ? $resultRow->getCreatedAt()->format('M-d-Y') : 'N/A' ;


                fputcsv($handle, array(
                        $serviceLocation,
                        $service,
                        $packageType,
                        $promoCode,
                        $createdBy,
                        $note,
                        $expiredAt,
                        $duration,
                        $status,
                        $note,
                        $IsReedemed
                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'promo_code_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }


	public function viewCustomerAction(Request $request) {

		$id = $request->get('id');
        return $this->render('DhiAdminBundle:PromoCode:viewCustomer.html.twig',array('promoid' => $id));
    }

	 public function promoCodeCustomerListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
		$id = $request->get('id');
        $promoCodeColumns = array('customer','promoCode','redemptionTime');
		$admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);

		 if(!empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'pcc.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'customer') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'promoCode') {

                $orderBy = 'pc.promoCode';
            }
            if ($gridData['order_by'] == 'redemptionTime') {

                $orderBy = 'pcc.redempTime';
            }
//            if ($gridData['order_by'] == 'EndDate') {
//
//                $orderBy = 'pc.endDate';
//            }
//
//            if ($gridData['order_by'] == 'MaxRedemption') {
//
//                $orderBy = 'pc.maxRedemption';
//            }
//            if ($gridData['order_by'] == 'RedeemedTime') {
//
//                $orderBy = 'pc.redeemedTime';
//            }

        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiUserBundle:PromoCustomer')->getPromoCodeCustomerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $id);

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
					//print_r($resultRow);
					$row = array();
					if($resultRow->getUser()){
//						$userName = $resultRow->getUser()->getName();
                                                $userName = $resultRow->getUser()->getUsername();
					}	else	{
						$userName = 'N/A';
					}
					$username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow->getUser()->getId())) . '">' . $userName . '</a>';
					$row[] =  $username;
					$row[] =  $resultRow->getPromoCodeId()->getPromoCode();
					$row[] = ($resultRow->getRedempTime()) ? $resultRow->getRedempTime()->format('Y-m-d H:i:s') : 'N/A';


//					$row[] = '';
//					$row[] = $resultRow['redempTime']->format('Y-m-d H:i:s');
//					$row[] = $resultRow['promoCode'];
//					//print_r($row);
                    $output['aaData'][] = $row;
                }
            }
        }


        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

	public function getPromoPackageAction(Request $request){

		$serviceId =  $request->get('serviceId');

		$locatoinId =  $request->get('locationId');
		$serviceName = '';
		$em = $this->getDoctrine()->getManager();
		if($serviceId){
			$serviceName = $em->getRepository('DhiUserBundle:Service')->getServiceName($serviceId);
		}
		if($serviceName != 'BUNDLE') {
			$Packages = $em->getRepository('DhiAdminBundle:Package')->getPackageTypeByService($serviceName,$locatoinId, false, true);
		} else {
			$Packages = $em->getRepository('DhiAdminBundle:Bundle')->getBundleTypeByService($serviceName,$locatoinId);
		}
	//	echo "<pre>";
	//	print_r($Packages);exit;
		$response = new Response(json_encode($Packages));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
//		return $Packages;

	}

	public function getPromoServiceAction(Request $request){

		$locatoinId =  $request->get('locationId');

		$em = $this->getDoctrine()->getManager();

		$services = $em->getRepository('DhiAdminBundle:IpAddressZone')->getServicesByLocation($locatoinId);

		$response = new Response(json_encode($services));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
//		return $Packages;

	}


	public function printAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('promo_code_print')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to print promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

		$searchData = array();
        if($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $promoData = $em->getRepository('DhiUserBundle:PromoCode')->getPdfPromoData($searchData, $adminServiceLocationPermission);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Print promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export print promo code";
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        //  Rendering view for printing data
        return $this->render('DhiAdminBundle:PromoCode:print.html.twig', array(
                'promoData' => $promoData,
                'img' => $dhiLogoImg
        ));
    }


}
