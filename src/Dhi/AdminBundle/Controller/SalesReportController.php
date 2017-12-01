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

class SalesReportController extends Controller {

	public function salesReportAction(Request $request) {

		$admin = $this->get('security.context')->getToken()->getUser();

		//Check permission
		 if (!( $this->get('admin_permission')->checkPermission('sales_report_view'))) {

		  $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view sales report.");
		  return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		  }


		return $this->render('DhiAdminBundle:SalesReport:salesReport.html.twig', array(
					'admin' => $admin
		));
	}

	public function salesReportListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

		$request = $this->getRequest();
		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$helper = $this->get('grid_helper_function');

		
		$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

		$aColumns = array('serviceLocation', 'serviceType', 'paymentMethod', 'totalSales', 'total');

		$gridData = $helper->getSearchData($aColumns);
		
		if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {

			$this->get('session')->set('salesReportSearchData', $gridData['search_data']);
		} else {

			$this->get('session')->remove('salesReportSearchData');
		}

		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

			$orderBy = 'po.id';
			$sortOrder = 'DESC';
		} else {

			if ($gridData['order_by'] == 'serviceLocation') {

				$orderBy = 'usl.name';
			}
			if ($gridData['order_by'] == 'paymentMethod') {

				$orderBy = 'pm.name';
			}
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];

		$country = '';

		if ($admin->getGroup() != 'Super Admin') {

			$country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}

		$data = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getSalesReportGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, NULL, $ipAddressZones, $admin, $country);
//		echo "<pre>";
//		print_r($data);exit;
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => 0,
			"iTotalDisplayRecords" => 0,
			"aaData" => array()
		);

		if (isset($data) && !empty($data)) {
			//print_r($data);exit;

			if (isset($data['result']) && !empty($data['result'])) {
				
				$totalRecord = 0;
				//$totalLocation= 0;
				foreach ($data['result'] as $locationKey => $recordLocation) {
					
					foreach ($recordLocation as $serviceKey => $serviceRecord) {
						
						foreach ($serviceRecord as $methodKey => $record) {
							$totalRecord++;
						}
						//$totalLocation++;
						$totalRecord++;
					}
					
				}
				//echo $totalLocation;
				//echo $totalRecord; 
				//exit;
				$output = array(
					"sEcho" => intval($_GET['sEcho']),
					"iTotalRecords" => $totalRecord,
					"iTotalDisplayRecords" => $totalRecord,
					"aaData" => array()
				);
								
                               
				$finalArray = array();

				$locationKey = '';
				$serviceType = '';
				$method = '';
				$grandTotalLocation = '';
				$service = ''; 
				//echo "<pre>";
						//print_r($data['result']);exit;
				foreach ($data['result'] as $locationKey => $recordLocation) {
					
					$grandTotal = 0;
//					echo "<pre>";
//						print_r($recordLocation);
					foreach ($recordLocation as $serviceKey => $serviceRecord) {
						
						$serviceType = $serviceKey;
						
						foreach ($serviceRecord as $methodKey => $record) {
							
							$grandTotal += $record['totalAmountPaymentMethod'];
							$row = array();
                                                        $lrow[] = $locationKey;
                                                        $row[] = $locationKey;
                                                        $srow[] = $serviceType;
														$service = $serviceType;
                                                        $row[] = $serviceType;
                                                        $row[] = $methodKey;
							$row[] = '$' . $record['totalAmountPaymentMethod'];
							
							//exit;
							$output['aaData'][] = $row;
						}
						
						
						
					}
					
					$row = array();
					$row[] = '';
					$row[] = '';
					$row[] = '<b>Sub Total For '.$locationKey. ' Location</b>';
					$row[] = '<b>$'.$grandTotal.'</b>';
					$output['aaData'][] = $row;
					
				}

//echo "<pre>";print_r($temp);exit;echo $count;
			}
//                    $Arrlocation = array_unique($lrow);
//                    $Arrservice = array_unique($srow);
//					
//                    foreach($output['aaData'] as $key=>$val){
//                        if(!array_key_exists($key, $Arrlocation)){
//                             $val[0] = '';
//                             $output['aaData'][$key]=$val;
//                         } 
//                         if(!array_key_exists($key, $Arrservice)){
//                             $val[1] = '';
//                             $output['aaData'][$key]=$val;
//                         } 
//                    }
	
                }
				if (isset($data) && !empty($data)) {
				$row = array();
				// $output['aaData'][] = 
				$countService = 0;
				$serviceVar = array();
				$keyVar = array();
                                foreach ($data['servicegrandtotal'] as $servicetotalkey=>$servicetotal){
									$countService++;
                                    $serviceVar[] = $servicetotal;
									$keyVar[] = $servicetotalkey;
                                }
					//print_r($serviceVar);
										if($countService < 2 && $keyVar[0]=='IPTV'){
											 $row[] = '$'.$serviceVar[0];
											$row[] = '';
									}
									else if($countService < 2 && $keyVar[0]=='ISP'){
											
											$row[] = '';
											 $row[] = '$'.$serviceVar[0];
									} else {	
									
											
											$row[] = '$'.$serviceVar[0];
											 $row[] = '$'.$serviceVar[1];
									}	
                                //$output['aaData'][] = ;
					//$row = array();
					
					//$row[] = $data['servicegrandtotal'];;
					//$row[] = $data['currentmonthsale'];
					$row[] = '<b>Grand Total</b>';
					$row[] = '$'.$data['grandTotal'];
					$output['aaData'][] = $row;
				}
              
		
		
		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
                
               
		return $response;
	}

	// Export pdf file
	public function exportpdfAction(Request $request) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('user_purchase_history_export_pdf')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}
                
		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$isSecure = $request->isSecure() ? 'https://' : 'http://';
		$rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
		$dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
		$logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT') . '/bundles/dhiuser/images/logo.png';

		$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

		$country = '';

		if ($admin->getGroup() != 'Super Admin') {

			$country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}
		
		//Get Purchase History Data

		$searchData = array();
		$objDate = new \DateTime('-1 month');
		$saleDate = "(" . $objDate->format('F') . ' ' . $objDate->format('Y') . ")";

		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

			if (isset($searchData['totalSales'])) {

				$RequestDate = explode('~', $searchData['totalSales']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				$startDate = new \DateTime($ReqFrom);
				$endDate = new \DateTime($ReqTo);

				if (!$ReqTo) {

					$endDate = new \DateTime();
				}

				$saleDate = '(From ' . $startDate->format("m/d/Y") . ' ' . ' To ' . $endDate->format('m/d/Y') . ')';
			}
		}

		$file_name = 'sales_report_'. strtolower($saleDate) . '.pdf'; // Create pdf file name for download

		$salesReportData = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getSalesReportData($searchData, $ipAddressZones, $admin, $country);

		
		// Set audit log for export pdf purchase history
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export pdf sales report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export user sales report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);
                   
                
                $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
                $html = '<style>' . $stylesheet . '</style>';
                $html .= $this->renderView('DhiAdminBundle:SalesReport:exportPdf.html.twig', array(
			'salesData' => $salesReportData ? $salesReportData['result'] : $salesReportData,
			'saleDate' => $saleDate
		));
                
                unset($salesReportData);
                unset($saleDate);
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
		if (file_exists($logoImgDirPath)) {

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
		$html .= $this->renderView('DhiAdminBundle:SalesReport:exportPdf.html.twig', array(
			'salesData' => $salesReportData ? $salesReportData['result'] : $salesReportData,
			'saleDate' => $saleDate
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

	// Export csv file
	public function exportCsvAction(Request $request) {

		//Check permission

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		//   $searchParams = $request->query->all();
		// Get Purchase History Data
		$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
		$country = '';
		if ($admin->getGroup() != 'Super Admin') {
			$country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}

		$searchData = array();
		$objDate = new \DateTime('-1 month');
		$saleDate = "(" . $objDate->format('F') . ' ' . $objDate->format('Y') . ")";

		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

			if (isset($searchData['totalSales'])) {

				$RequestDate = explode('~', $searchData['totalSales']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				$startDate = new \DateTime($ReqFrom);
				$endDate = new \DateTime($ReqTo);

				if (!$ReqTo) {

					$endDate = new \DateTime();
				}

				$saleDate = '(From ' . $startDate->format("m/d/Y") . ' ' . ' To ' . $endDate->format('m/d/Y') . ')';
			}
		}

		$salesReportData = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getSalesReportData($searchData, $ipAddressZones, $admin, $country);

		// Set audit log for export csv sales report
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export csv sales report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export csv sales report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$result = $salesReportData;

		$response = new StreamedResponse();
		$response->setCallback(function() use($result) {

			$handle = fopen('php://output', 'w+');

			// Add a row with the names of the columns for the CSV file
			fputcsv($handle, array("Service Location", "Service Type", "Payment Method", "Total Sales"), ',');
			// Query data from database

			$locationKey = '';
			$serviceType = '';
			$method = '';
			$grandTotal = '';
                        $output = array();
			foreach ($result['result'] as $locationKey => $recordLocation) {

				$locationKey = $locationKey;

				foreach ($recordLocation as $serviceKey => $serviceRecord) {

					$serviceType = $serviceKey;
					
					foreach ($serviceRecord as $methodKey => $record) {

                                                        $grandTotal += $record['totalAmountPaymentMethod'];
							$row = array();
                                                        $lrow[] = $locationKey;
                                                        $row[] = $locationKey;
                                                        $srow[] = $serviceType;
                                                        $row[] = $serviceType;
                                                        $row[] = $methodKey;
							$row[] = '$' . $record['totalAmountPaymentMethod'];
							
							//exit;
							$output[] = $row;
						fputcsv($handle, array(
							$locationKey,
							$serviceType,
							$methodKey,
							'$' . $record['totalAmountPaymentMethod']
								), ',');
					}
				}
				$row = array();
                                $row[] = '';
                                $row[] = '';
                                $row[] = 'Grand Total For '.$locationKey. ' Location';
                                $row[] = '$'.$grandTotal;
                                $output[] = $row;
				
				fputcsv($handle, array(
				'',
				'',
				'Grand Total For '.$locationKey. ' Location',
				'$'.$grandTotal
					), ',');	
			}
                        
                       
                        
//                        $Arrlocation = array_unique($lrow);
//                        $Arrservice = array_unique($srow);
//
//                        foreach($output as $key=>$val){
//                           
//                            if(!array_key_exists($key, $Arrlocation)){
//                                 $val[0] = '';
//                                 //$output[$key]=$val;
//                             } 
//                             if(!array_key_exists($key, $Arrservice)){
//                                 $val[1] = '';
//                                 //$output[$key]=$val;
//                             } 
//                             fputcsv($handle,array($val[0],$val[1],$val[2],$val[3]));
//                         }
                      
			fclose($handle);
		});
               
                //die;
		$file_name = 'sales_report_'. strtolower($saleDate) . '.csv'; // Create pdf file name for download
		// set header
		$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
		$response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

		return $response;
	}

	// Export excel file
	public function exportExcelAction(Request $request) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('user_purchase_history_export_pdf')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		//Get Purchase History Data
                $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
                
		$country = '';

		if ($admin->getGroup() != 'Super Admin') {

			$country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}

		$searchData = array();
		$objDate = new \DateTime('-1 month');
		$saleDate = "(" . $objDate->format('F') . ' ' . $objDate->format('Y') . ")";

		if ($this->get('session')->get('salesReportSearchData')) {

			$searchData = $this->get('session')->get('salesReportSearchData');

			if (isset($searchData['totalSales'])) {

				$RequestDate = explode('~', $searchData['totalSales']);
				$ReqFrom = trim($RequestDate[0]);
				$ReqTo = trim($RequestDate[1]);

				$startDate = new \DateTime($ReqFrom);
				$endDate = new \DateTime($ReqTo);

				if (!$ReqTo) {

					$endDate = new \DateTime();
				}

				$saleDate = '(From ' . $startDate->format("m/d/Y") . ' ' . ' To ' . $endDate->format('m/d/Y') . ')';
			}
		}

		$file_name = 'sales_report_'. strtolower($saleDate) . '.xls'; // Create pdf file name for download

		$salesReportData = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getSalesReportData($searchData, $ipAddressZones, $admin, $country);

		// Set audit log for export pdf purchase history
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export excel sales report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export user sales report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$locationKey = '';
		$serviceType = '';
		$method = '';
		$grandTotal = '';
		foreach ($salesReportData['result'] as $locationKey => $recordLocation) {

			$locationKey = $locationKey;

			foreach ($recordLocation as $serviceKey => $serviceRecord) {

				$serviceType = $serviceKey;

				foreach ($serviceRecord as $methodKey => $record) {

					$grandTotal += $record['totalAmountPaymentMethod'];
					
					$row = array();
					$row['Service Location'] = $locationKey;
					$lrow[] = $locationKey;
                                        $row['Service Type'] = $serviceType;
                                        $srow[] = $serviceType;
					$row['Payment Method'] = $methodKey;
					$row['Total Sales'] = '$' . $record['totalAmountPaymentMethod'];
					$output['aaData'][] = $row;
				}
			}
			
			$row = array();
			$row['Service Location'] = '';
			$row['Service Type'] = '';
			$row['Payment Method'] = 'Grand Total For '.$locationKey. ' Location';
			$row['Total Sales'] = '$'.$grandTotal;
			$output['aaData'][] = $row;

			
			
		}
              
//                    $Arrlocation = array_unique($lrow);
//                    $Arrservice = array_unique($srow);
//                   
//                    
//                    foreach($output['aaData'] as $key=>$val){
//                                              
//                        if(!array_key_exists($key, $Arrlocation)){
//                           
//                              $val['Service Location'] = '';
//                             $output['aaData'][$key]=$val;
//                         }
//                         if(!array_key_exists($key, $Arrservice)){
//                            $val['Service Type'] = '';
//                             $output['aaData'][$key]=$val;
//                         }  else {
//                             $output['aaData'][$key]=$val;
//                         }
//                    }
		   
                                      
		header('Content-type: application/vnd-ms-excel');
		header("Content-Disposition: attachment; filename=\"$file_name\"");
		
		$heading = false;
		if (!empty($output['aaData']))
			foreach ($output['aaData'] as $row) {
				if (!$heading) {
					// display field/column names as a first row
					echo implode("\t", array_keys($row)) . "\n";
					$heading = true;
				}
				echo implode("\t", array_values($row)) . "\n";
			}
		exit;
		
	}
	
}
