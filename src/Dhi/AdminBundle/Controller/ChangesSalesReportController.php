<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Form\Type\UserFormType;
use Dhi\AdminBundle\Form\Type\UserSettingFormType;
use Dhi\AdminBundle\Form\Type\ChangePasswordFormType;
use Dhi\AdminBundle\Form\Type\LoginLogSearchFormType;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\UserBundle\Entity\UserSetting;
use Dhi\UserBundle\Entity\ServiceLocation;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\UserBundle\Entity\UserCredit;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ChangesSalesReportController extends Controller {

	public function salesReportAction(Request $request) {

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		//Check permission
		 if (!( $this->get('admin_permission')->checkPermission('sales_report_view'))) {

		  $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view sales report.");
		  return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		  }

		$serviceLocation = array();
		$serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();


		$serviceLocationLog = array();
    	if ($admin->getGroup() != 'Super Admin') {
			$lo = $admin->getServiceLocations();
			foreach ($lo as $key => $value) {
				$serviceLocationLog[] = $value->getName();
			}
		}else{
      		foreach ($serviceLocation as $activity) {
      			$serviceLocationLog[] = $activity['name'];
     		}
    	}

		$service = array();
		$services = $em->getRepository('DhiUserBundle:Service')->getAllService();

		$allPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->getSalesReportMethods();
        $paymentMethod = array();
        foreach ($allPaymentMethod as $key => $value) {
            $paymentMethod[] = $allPaymentMethod[$key]['name'];
        }

		$package = array();
		$package = $em->getRepository('DhiAdminBundle:Package')->getPackages();
		$bundle = $em->getRepository('DhiAdminBundle:Bundle')->getBundles();
		$package = array_merge($package, $bundle);

    $allWhiteLabelSites = $em->getRepository('DhiAdminBundle:WhiteLabel')->getWhiteLabelSites();
                
		return $this->render('DhiAdminBundle:ChangesSalesReport:salesReport.html.twig', array(
					'admin' => $admin,'serviceLocations' => json_encode($serviceLocationLog),'services' => $services,'paymentMethod'=>$paymentMethod,'packages'=>$package,'allWhiteLabelSites' => $allWhiteLabelSites
		));
	}

	public function salesReportListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0,$fromip,$toip,$packageName,$whiteLabel) {

		$request = $this->getRequest();
		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$helper = $this->get('grid_helper_function');

		// $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

		$aColumns = array('serviceLocation', 'serviceType');

		$gridData = $helper->getSearchData($aColumns);
		if ((!empty($gridData) && !empty($gridData['search_data']))) {

			$this->get('session')->set('salesReportSearchData', $gridData['search_data']);
		} else {

			$this->get('session')->remove('salesReportSearchData');
		}
		if(!empty($fromip)){
			$this->get('session')->set('salesReportService', $fromip);
		} else {
			$this->get('session')->remove('salesReportService');
		}
		if(!empty($toip)){
			$this->get('session')->set('salesReportPayment', $toip);
		} else {
			$this->get('session')->remove('salesReportPayment');
		}
		if(!empty($packageName)){
			$this->get('session')->set('salesReportPackage', $packageName);
		} else {
			$this->get('session')->remove('salesReportPackage');
		}
    if(!empty($whiteLabel)){
			$this->get('session')->set('salesReportPurchasedFrom', $whiteLabel);
		} else {
			$this->get('session')->remove('salesReportPurchasedFrom');
		}
		
		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

			$orderBy = 'sl.id';
			$sortOrder = 'DESC';
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];

		$adminServiceLocationPermission = '';
		if ($admin->getGroup() != 'Super Admin') {

			$adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
		}

		$data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getChangesSalesReportGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$fromip,$toip,$packageName,$whiteLabel, $adminServiceLocationPermission);
		
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => 0,
			"iTotalDisplayRecords" => 0,
			"aaData" => array()
		);
                $iTotalRecords = 0;
                if ($data) {
			
			if (!empty($data['result'])) {

				$grandTotal  = 0;
				$iptvTotal	 = 0;
				$ispTotal	 = 0;
				$tvodTotal	 = 0;

				$output = array(
                	"sEcho" => intval($_GET['sEcho']),
                    "aaData" => array()
				);
                                $service = '';
                                $payment = '';
                                
                                foreach ($data['result'] AS $resultRow) {
					
				   $summaryArr = $this->saleReportServicesArray($resultRow,$fromip,$toip,$packageName,$gridData['search_data'], $whiteLabel);
					
					if(!empty($summaryArr['html'])) {
						$iTotalRecords = $iTotalRecords + $summaryArr['countSp'];
						$row = array();
						$row[] = $resultRow->getName();
						$row[] = $summaryArr['html'];

						$grandTotal  += $summaryArr['grandTotal'];
						$iptvTotal	 += $summaryArr['totIPTV'];
						$ispTotal	 += $summaryArr['totISP'];
						$tvodTotal	 += $summaryArr['totTVOD'];

						$output['aaData'][] = $row;
					}
				}

				$row = array();
				$row[] = 'Grand Total';
				$row[] = '$'.$grandTotal.'^$'.$iptvTotal.'^$'.$ispTotal.'^$'.$tvodTotal;

				$output['aaData'][] = $row;
			}
		}

        $output['iTotalDisplayRecords'] = $output['iTotalRecords'] = $iTotalRecords;
		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');


		return $response;


	}
	
	public function saleReportServicesArray($resultRow,$service,$payment,$packageName,$searchData,$purchasedFrom){
		
		$em = $this->getDoctrine()->getManager();
		$html = '';
		$sum = 0;

		$summaryArr = array();
		$summaryArr['html'] = '';
		$summaryArr['totIPTV'] = 0;
		$summaryArr['totTVOD'] = 0;
		$summaryArr['totISP']  = 0;
		$summaryArr['totBUNDLE']  = 0;
		$summaryArr['grandTotal']  = 0;
		$summaryArr['locationWiseData']  = array();
		$summaryArr['countSp'] = 0;
		
			
		$html .= '<table class="table table-bordered table-hover" style="margin-bottom: 0 !important;">';
		$html .= '<tr><th>Service Name</th><th>Payment Method</th><th>PayPal Account</th><th>Total Sale</th></tr>';
			
        $objUserService = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getActiveByServiceLocationnew($resultRow->getId(),$service,$payment,$packageName,$searchData,$purchasedFrom);
        $arrBundleServices = $arrPlanServices = $arrUserServices = array();

        if($objUserService){
	           	if(!empty($objUserService[0]['paymentMethod'])){
	           $summaryArr['countSp']++;
           	}
			foreach ($objUserService as $userService) {
				if (!in_array(strtolower($userService['paymentMethodCode']), array('paypal', 'creditcard'))) {
					$userService['paypalCredential'] = 'N/A';
				}
				if(strtoupper($userService['purchase_type']) == 'BUNDLE'){
					$userService['serviceName'] = strtoupper($userService['purchase_type']);
					if (!empty($arrBundleServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']]['totalAmount'])) {
						$totalAmount = $arrBundleServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']]['totalAmount'] + $userService['totalAmount'];
					}else{
						$totalAmount = $userService['totalAmount'];
					}
					$userService['totalAmount'] = $totalAmount;
					$arrBundleServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']] = $userService;
				}else{
					if (!empty($arrPlanServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']]['totalAmount'])) {
						$totalAmount = $arrPlanServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']]['totalAmount'] + $userService['totalAmount'];
					}else{
						$totalAmount = $userService['totalAmount'];
					}
					$userService['totalAmount'] = $totalAmount;
					$arrPlanServices[$userService['paymentMethod']][$userService['serviceName']][$userService['paypalCredential']] = $userService;
				}
			}
        }

        unset($objUserService);
		$arrBundleServices = array_values($arrBundleServices);
		$arrPlanServices = array_values($arrPlanServices);
		$arrUserServices = array_merge($arrPlanServices, $arrBundleServices);


		if(count($arrUserServices) > 0){
			if ($arrUserServices) {
				foreach ($arrUserServices as $arrUserService) {
					foreach ($arrUserService as $serviceCredentials) {
						foreach ($serviceCredentials as $purchaseRecord) {
							$html .= '<tr>';
		                    	$html .= '<td>'.$purchaseRecord['serviceName'].'</td>';
		                        $html .= '<td>'.$purchaseRecord['paymentMethod'].'</td>';
		                        $html .= '<td>'.$purchaseRecord['paypalCredential'].'</td>';
								$html .= '<td>$'.$purchaseRecord['totalAmount'].'</td>';
		                    $html .= '<tr>';																		

							$sum += $purchaseRecord['totalAmount'];

							$summaryArr['tot'.strtoupper($purchaseRecord['serviceName'])]  += $purchaseRecord['totalAmount'];		

							$summaryArr['locationWiseData'][]  = $purchaseRecord;
						}
					}
				}
			}
		}	
	
		
		$summaryArr['grandTotal'] = $sum;
		
		$html .= '<tr>';
		$html .= '<td colspan="3"><b>Total</b></td>';
		$html .= '<td><b>$'.$sum.'</b></td>';
		$html .= '<tr>';
		
		$html .= '</table>';
			
		
		$summaryArr['html'] 	= $html;


		return $summaryArr;
	}


	 public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('sales_report_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'sales_report_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
		$searchData ='';
		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

		}
		$service='';
		if ($this->get('session')->get('salesReportService')) {

			$service = $this->get('session')->get('salesReportService');

		}
		$payment='';
		if ($this->get('session')->get('salesReportPayment')) {

			$payment = $this->get('session')->get('salesReportPayment');

		}
		$packageName='';
		if ($this->get('session')->get('salesReportPackage')) {

			$packageName = $this->get('session')->get('salesReportPackage');
		
		}
                $purchasedFrom=''; 
                if ($this->get('session')->get('salesReportPurchasedFrom')) {
                    
                        $purchasedFrom = $this->get('session')->get('salesReportPurchasedFrom');
                        
                }
                $adminServiceLocationPermission = '';
		if ($admin->getGroup() != 'Super Admin') {

			$adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
		}
                
        $saleReportData = $em->getRepository('DhiAdminBundle:ServiceLocation')->getExportSalesReport($searchData, $adminServiceLocationPermission);
		$extraData = array();
		if($saleReportData){
			foreach($saleReportData as $salesData) {
				
				$summaryArr = $this->saleReportServicesArray($salesData,$service,$payment,$packageName,$searchData,$purchasedFrom);
				
				if (!empty($summaryArr['locationWiseData'])) {

					$extraData[$salesData->getName()] = $summaryArr['locationWiseData'];
				}
			}
		}

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf sales report';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export sales report";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:ChangesSalesReport:exportPdf.html.twig', array(
                'salesData' => $extraData
        ));

        unset($extraData);

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
        $pdf->SetSubject('Purchase History');

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
        $html .= $this->renderView('DhiAdminBundle:ChangesSalesReport:exportPdf.html.twig', array(
                'salesData' => $extraData
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

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('sales_report_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }


        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        //   $searchParams = $request->query->all();
        // Get Purchase History Data
//

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv Sales Report';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export sales Report";



        $this->get('ActivityLog')->saveActivityLog($activityLog);

		$searchData ='';
		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

		}
		$service='';
		if ($this->get('session')->get('salesReportService')) {

			$service = $this->get('session')->get('salesReportService');

		}
		$payment='';
		if ($this->get('session')->get('salesReportPayment')) {

			$payment = $this->get('session')->get('salesReportPayment');

		}
		$packageName='';
		if ($this->get('session')->get('salesReportPackage')) {

			$packageName = $this->get('session')->get('salesReportPackage');
		
		}
                $purchasedFrom=''; 
		if ($this->get('session')->get('salesReportPurchasedFrom')) {

			$purchasedFrom = $this->get('session')->get('salesReportPurchasedFrom');
		
		}
                
                $adminServiceLocationPermission = '';
		if ($admin->getGroup() != 'Super Admin') {

                    $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
                    $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
		}
                
		$saleReportData = $em->getRepository('DhiAdminBundle:ServiceLocation')->getExportSalesReport($searchData, $adminServiceLocationPermission);
		$extraData = array();
		if($saleReportData){
			foreach($saleReportData as $salesData) {
				
				$summaryArr = $this->saleReportServicesArray($salesData,$service,$payment,$packageName,$searchData,$purchasedFrom);
				
				if (!empty($summaryArr['locationWiseData'])) {

					$extraData[$salesData->getName()] = $summaryArr['locationWiseData'];
				}
			}
		}

        //$result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($extraData) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Service Location", "Service Name", "Payment Method", "PayPal Account", "Total Sales"), ',');
            // Query data from database

			$grandTotal = 0;
            foreach ($extraData as $key => $salesData) {
				$total = 0;
					foreach($salesData as $sales) {
						$total = $total + $sales['totalAmount'];
					fputcsv($handle, array(
							$key,
							$sales['serviceName'],
							$sales['paymentMethod'],
							$sales['paypalCredential'],
							'$'.$sales['totalAmount']
					), ',');
				}
				if($total){
				fputcsv($handle, array(
							'',
							'',
							'',
							'Total',
							'$'.$total
					), ',');
				$grandTotal = $grandTotal + $total;
				}
			}
			fputcsv($handle, array(
							'',
							'',
							'',
							'Grand Total',
							'$'.$grandTotal
					), ',');
            fclose($handle);
        });

        // create filename
        $file_name = 'sales_report_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }


	public function exportExcelAction(Request $request) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('sales_report_export_excel')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export excel.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$file_name = 'sales_report_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.xls'; // Create pdf file name for download


		// Set audit log for export pdf purchase history
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export excel sales report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export user sales report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$searchData ='';
		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

		}
		$service='';
		if ($this->get('session')->get('salesReportService')) {

			$service = $this->get('session')->get('salesReportService');

		}
		$payment='';
		if ($this->get('session')->get('salesReportPayment')) {

			$payment = $this->get('session')->get('salesReportPayment');

		}
		$packageName='';
		if ($this->get('session')->get('salesReportPackage')) {

			$packageName = $this->get('session')->get('salesReportPackage');
		
		}	
		$purchasedFrom='';
                if ($this->get('session')->get('salesReportPurchasedFrom')) {
                    
                        $purchasedFrom = $this->get('session')->get('salesReportPurchasedFrom');
                        
                }
                
                $adminServiceLocationPermission = '';
		if ($admin->getGroup() != 'Super Admin') {

			$adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
		}
                
		$saleReportData = $em->getRepository('DhiAdminBundle:ServiceLocation')->getExportSalesReport($searchData, $adminServiceLocationPermission);
		$extraData = array();

		if($saleReportData){
			foreach($saleReportData as $salesData) {
				$summaryArr = $this->saleReportServicesArray($salesData,$service,$payment,$packageName,$searchData,$purchasedFrom);
				if (!empty($summaryArr['locationWiseData'])) {
					$extraData[$salesData->getName()] = $summaryArr['locationWiseData'];
				}
			}
		}

		$phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
		$phpExcelObject->getProperties()->setCreator("liuggio")
           ->setLastModifiedBy("Admin")
           ->setTitle("DhiPortal Sales Report")
           ->setSubject("Sales Report")
           ->setDescription("Sales Report")
           ->setKeywords("Sales Report")
           ->setCategory("Sales Report");
		$heading = false;

		if (!empty($extraData)){
			$grandTotal = 0;
			$row = 2;
			foreach ($extraData as $Key => $salesData) {
				$total = 0;
				foreach($salesData as $sales) {
					$total = $total + $sales['totalAmount'];
					if (!$heading) {
						// display field/column names as a first row
						$phpExcelObject->setActiveSheetIndex(0)
				           ->setCellValue('A1', 'Service Location')
				           ->setCellValue('B1', 'Service Name')
				           ->setCellValue('C1', 'Payment Method')
				           ->setCellValue('D1', 'PayPal Account')
				           ->setCellValue('E1', 'Total Sales');
						$heading = true;
					}

					$phpExcelObject->setActiveSheetIndex(0)
			           ->setCellValue('A'.$row, $Key)
			           ->setCellValue('B'.$row, $sales['serviceName'])
			           ->setCellValue('C'.$row, $sales['paymentMethod'])
			           ->setCellValue('D'.$row, $sales['paypalCredential'])
			           ->setCellValue('E'.$row, '$'.number_format($sales['totalAmount'], 2, '.', ''));
			    	$row++;
				}

				if($total){
					$phpExcelObject->setActiveSheetIndex(0)
			           ->setCellValue('A'.$row, "")
			           ->setCellValue('B'.$row, "")
			           ->setCellValue('C'.$row, "")
			           ->setCellValue('D'.$row, "Total")
			           ->setCellValue('E'.$row, '$'.number_format($total, 2, '.', ''));
			        $row++;
				}
				$grandTotal = $grandTotal + $total;
			}
			$phpExcelObject->setActiveSheetIndex(0)
	           ->setCellValue('A'.$row, "")
	           ->setCellValue('B'.$row, "")
	           ->setCellValue('C'.$row, "")
	           ->setCellValue('D'.$row, "Grand Total")
	           ->setCellValue('E'.$row, '$'.number_format($grandTotal, 2, '.', ''));
		}else{
			$phpExcelObject->setActiveSheetIndex(0)
	           ->setCellValue('A1', 'Service Location')
	           ->setCellValue('B1', 'Service Type')
	           ->setCellValue('C1', 'Payment Method')
	           ->setCellValue('D1', 'PayPal Account')
	           ->setCellValue('E1', 'Total Sales');
          	$phpExcelObject->setActiveSheetIndex(0)
	           ->setCellValue('A2', "")
	           ->setCellValue('B2', "")
	           ->setCellValue('C2', "")
	           ->setCellValue('D2', "Grand Total")
	           ->setCellValue('E2', '$0');
		}

        $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel5');
        $response = $this->get('phpexcel')->createStreamedResponse($writer);
        $dispositionHeader = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT,$file_name);
        $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Cache-Control', 'maxage=1');
        $response->headers->set('Content-Disposition', $dispositionHeader);

        return $response;
	}
}
