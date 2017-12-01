<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\ServiceLocation;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RefundReportController extends Controller {

	public function indexAction(Request $request) {

		$admin = $this->get('security.context')->getToken()->getUser();

		//Check permission
		if (!( $this->get('admin_permission')->checkPermission('refund_report_view'))) {

			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to view refund report.");
		  	return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$em = $this->getDoctrine()->getManager();
		$allServiceLocation  = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();

		$serviceLocation = array();
    	
    	if ($admin->getGroup() != 'Super Admin') {
			$lo = $admin->getServiceLocations();
			foreach ($lo as $key => $value) {
				$serviceLocation[] = $value->getName();
			}
			sort($serviceLocation);
		}else{
    		foreach ($allServiceLocation as $key => $value) {
	      		$serviceLocation[$key] = $allServiceLocation[$key]['name'];
	      	}
    	}

    $allPaymentMethod  = $em->getRepository('DhiServiceBundle:PaymentMethod')->getAllPaymentMethod();
    $paymentMethod = array();

		foreach ($allPaymentMethod as $key => $value) {
			$paymentMethod[$key] = $allPaymentMethod[$key]['name'];
		}

		return $this->render('DhiAdminBundle:RefundReport:refundReport.html.twig', array(
					'admin' => $admin,
          'serviceLocation' => json_encode($serviceLocation),
          'paymentMethod' => json_encode($paymentMethod),
		));
	}

	public function refundReportListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

		$request = $this->getRequest();
		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$helper = $this->get('grid_helper_function');

		$aColumns = array('serviceType', 'userName', 'package', 'status', 'paymentMethod', 'refundDate', 'adminUser', 'actualAmount', 'refundAmount', 'refundType', 'serviceLocation');

		$gridData = $helper->getSearchData($aColumns);

		if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {

			$this->get('session')->set('refundReportSearchData', $gridData['search_data']);
		} else {

			$this->get('session')->remove('refundReportSearchData');
		}

		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

			$orderBy = 'po.id';
			$sortOrder = 'DESC';
		} else {

			if ($gridData['order_by'] == 'serviceType') {

				$orderBy = 'serviceName';
			}
			if ($gridData['order_by'] == 'userName') {

				$orderBy = 'username';
			}
			if ($gridData['order_by'] == 'package') {

				$orderBy = 'package_name';
			}
			if ($gridData['order_by'] == 'adminUser') {

				$orderBy = 'refunded_by';
			}
			if ($gridData['order_by'] == 'paymentMethod') {

				$orderBy = 'payment_method_name';
			}
			if ($gridData['order_by'] == 'status') {

				$orderBy = 'status';
			}
			if ($gridData['order_by'] == 'refundDate') {

				$orderBy = 'refunded_at';
			}
			if ($gridData['order_by'] == 'actualAmount') {

				$orderBy = 'actual_amount';
			}
			if ($gridData['order_by'] == 'refundAmount') {

				$orderBy = 'refund_amount';
			}
			if ($gridData['order_by'] == 'refundType') {

				$orderBy = 'payment_status';
			}
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];

                if($request->get('name') != null) {
//                    $gridData['search_data']['serviceLocation'] = str_replace(",","','",$request->get('name'));
                    $gridData['search_data']['serviceLocation'] = $request->get('name');
                    $gridData['SearchType'] = 'ANDLIKE';
                }

                $adminServiceLocationPermission = '';
                if ($admin->getGroup() != 'Super Admin') {
                    $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
                    $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
                }

        $data = $this->getRefundReportGridList($offset, $per_page, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, true, $adminServiceLocationPermission);

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

                $totalRefunds = 0;
                foreach ($data['result'] AS $resultRow) {
					$paymentMethod = '';
					if($resultRow['payment_method_name']){
						$paymentMethod = $resultRow['payment_method_name'];
					}
					$row      = array();
					$row[]    = (($resultRow['serviceName']) ? $resultRow['serviceName'] : 'N/A');
					$username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['user_id'])) . '">' . $resultRow['username'] . '</a>';
					$row[]    = $username;
					$row[]    = $resultRow['package_name'];
					$row[]    = $resultRow['status'] == 1 ? '<span class="btn btn-success btn-sm">Active<span>' : '<span class="btn btn-success btn-sm">Inactive</span>' ;
					$row[]    = $paymentMethod ;
					$row[]    = ($resultRow['refunded_at']) ? $resultRow['refunded_at'] : 'N/A';
					$row[]    = ($resultRow['refunded_by']) ? $resultRow['refunded_by'] : 'N/A';
					$row[]    = '$' . $resultRow['actual_amount'];
					$row[]    = '$' . number_format($resultRow['refund_amount'],2);
					$row[]    = $resultRow['payment_status'];
					$row[]    = '';

                    $output['aaData'][] = $row;
					$totalRefunds++;
                }
            }
		}

		$limit = $this->container->getParameter("dhi_admin_export_limit");
        $exportArr = array();
        if (!empty($limit)) {
            if ($output['iTotalRecords'] > 0) {
                $i = 0;
                while ($i < $output['iTotalRecords']) {
                    $exportArr[$i] = number_format($i+1)." - ".number_format($i+$limit).' Records';
                    $i = $i + $limit;
                }
            }
        }else{
            $exportArr[] = "0 - ".$output['iTotalRecords'];
        }
        $output['exportSlots'] = $exportArr;

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	// Export csv file
	public function exportCsvAction(Request $request) {

		$offset = $request->get("offset");
		$slot   = $this->container->getParameter("dhi_admin_export_limit");
		if (!isset($slot) || !isset($offset)) {
		    $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
		    return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$helper = $this->get('grid_helper_function');

		$searchData = array();

		if ($this->get('session')->get('refundReportSearchData')) {

			$searchData = $this->get('session')->get('refundReportSearchData');
		}

		$refundReportData = $this->getRefundReportGridList($offset, $slot, "refunded_at", "DESC", $searchData, "ANDLIKE", $helper, true);

		//Create object of streamed response
		$response = new StreamedResponse();
		$response->setCallback(function() use($refundReportData) {
			$handle = fopen('php://output', 'w+');
			// Add a row with the names of the columns for the CSV file
			fputcsv($handle, array("Type", 'Customer', "Plan Name", "Status","Payment Method", "Date", "Admin", "Plan Amount", "Refunded Amount", 'Refund Type'), ',');
			// Query data from database

			if (!empty($refundReportData['result'])) {
				$grandTotal = 0;
				$paymentMethod = '';
				foreach ($refundReportData['result'] as $refundService) {
					$grandTotal += number_format($refundService['refund_amount'], 2);
					if(!empty($refundService['payment_method_name'])){
						$paymentMethod = $refundService['payment_method_name'];
					}
					fputcsv($handle,
						array(
							(($refundService['serviceName']) ? $refundService['serviceName'] : 'N/A'),
								$refundService['username'],
								$refundService['package_name'],
								$refundService['status'] == 1 ? 'Active' : 'Inactive',
								$paymentMethod,
								($refundService['refunded_at']) ? $refundService['refunded_at'] : 'N/A',
								($refundService['refunded_by']) ? $refundService['refunded_by'] : 'N/A',
								'$' . $refundService['actual_amount'],
								'$' . number_format($refundService['refund_amount'],2),
                                                                $refundService['payment_status']
							),
						',');
				}
				$totalRefunds = count($refundReportData['result']);
				fputcsv($handle,
					array(
							'',
							'',
							'',
							'',
							'',
							'',
							'',
							'Grand Total',
							'$'.$grandTotal,
                                                        ''
						),
				',');
				fputcsv($handle,
					array(
							'',
							'',
							'',
							'',
							'',
							'',
							'',
							'Total Refunds',
							$totalRefunds,
                                                        ''
						),
				',');
			}
			fclose($handle);
		});


		// Set audit log for export csv refund report
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export csv refund report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export csv refund report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);
		$file_name = 'Refund-report.csv'; // Create pdf file name for download

		// set header
		$response->headers->set('Content-Type', 'text/csv; charset=utf-8');
		$response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

		return $response;
	}

	// Export excel file
	public function exportExcelAction(Request $request) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('refund_report_export_excel')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export refund report excel.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$offset = $request->get("offset");
		$slot   = $this->container->getParameter("dhi_admin_export_limit");
		if (!isset($slot) || !isset($offset)) {
		    $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
		    return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$helper = $this->get('grid_helper_function');

		$searchData = array();
		$output = array();
		$output['aaData'] = '';

		if ($this->get('session')->get('refundReportSearchData')) {

			$searchData = $this->get('session')->get('refundReportSearchData');
		}

		$refundReportData = $this->getRefundReportGridList($offset, $slot, "refunded_at", "DESC", $searchData, "ANDLIKE", $helper, true);
		if (!empty($refundReportData['result'])) {
			$grandTotal = 0;
			$paymentMethod = '';

			foreach ($refundReportData['result'] as $refundService) {
				if($refundService['payment_method_name']){
					$paymentMethod = $refundService['payment_method_name'];
				}
				$grandTotal += number_format($refundService['refund_amount'], 2);

				$row = array();
				$row['Type']			= (($refundService['serviceName']) ? $refundService['serviceName'] : 'N/A');
				$row['Customer'] 		= $refundService['username'];
				$row['Plan Name'] 		= $refundService['package_name'];
				$row['Status'] 			= $refundService['status'] == 1 ? 'Active' : 'Inactive';
				$row['Payment Method'] 			= $paymentMethod;
				$row['Date'] 			= ($refundService['refunded_at']) ? $refundService['refunded_at'] : 'N/A';
				$row['Admin'] 			= ($refundService['refunded_by']) ? $refundService['refunded_by'] : 'N/A';
				$row['Plan Amount'] 	= '$' . $refundService['actual_amount'];
				$row['Refunded Amount']	= '$' . number_format($refundService['refund_amount'],2);
				$row['Refund Type']	= $refundService['payment_status'];

				$output['aaData'][] = $row;
			}
			$totalRefunds = count($refundReportData['result']);


			$row = array();
			$row['Type']			= '';
			$row['Plan Name'] 		= '';
			$row['User Name'] 		= '';
			$row['Status'] 			= '';
			$row['Payment Method'] 	= '';
			$row['Date'] 			= '';
			$row['Admin'] 			= '';
			$row['Plan Amount'] 	= 'Grand Total';
			$row['Refunded Amount']	= '$' . $grandTotal;
			$row['Refund Type']	= '';
			$output['aaData'][] = $row;

			$row['Plan Amount'] 	= 'Total Refund(s)';
			$row['Refunded Amount']	= $totalRefunds;
			$output['aaData'][] = $row;
		}

		// Set audit log for export pdf purchase history
		$activityLog = array();
		$activityLog['admin'] = $admin;
		$activityLog['activity'] = 'Export excel refund report';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " export user refund report";

		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$file_name = 'refund_report.xls';

		header('Content-type: application/vnd-ms-excel');
		header("Content-Disposition: attachment; filename=\"$file_name\"");

		$heading = false;
		if (!empty($output['aaData'])) {

			foreach ($output['aaData'] as $row) {

				if (!$heading) {

					// display field/column names as a first row
					echo implode("\t", array_keys($row)) . "\n";
					$heading = true;
				}

				echo implode("\t", array_values($row)) . "\n";
			}
		}
		exit;

	}

    public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('refund_report_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

		$offset = $request->get("offset");
		$slot   = $this->container->getParameter("dhi_admin_export_limit");
		if (!isset($slot) || !isset($offset)) {
		    $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
		    return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'refund_report_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        $helper = $this->get('grid_helper_function');
        $searchData ='';
        if ($this->get('session')->get('refundReportSearchData')) {

                $searchData = $this->get('session')->get('refundReportSearchData');

        }

		$slotArr           = array();
		$slotArr['limit']  = $slot;
		$slotArr['offset'] = $offset;

		$output = array();
        $refundReportData = $this->getRefundReportGridList($offset, $slot, "refunded_at", "DESC", $searchData, "ANDLIKE", $helper, true);
		if (!empty($refundReportData['result'])) {
			$grandTotal = 0;
			$paymentMethod = '';

			foreach ($refundReportData['result'] as $refundService) {
				if($refundService['payment_method_name']){
					$paymentMethod = $refundService['payment_method_name'];
				}
				$grandTotal += number_format($refundService['refund_amount'], 2);

				$row = array();
				$row['Type']			= (($refundService['serviceName']) ? $refundService['serviceName'] : 'N/A');
				$row['Customer'] 		= $refundService['username'];
				$row['Plan Name'] 		= $refundService['package_name'];
				$row['Status'] 			= $refundService['status'] == 1 ? 'Active' : 'Inactive';
				$row['Payment Method'] 	= $paymentMethod;
				$row['Date'] 			= ($refundService['refunded_at']) ? $refundService['refunded_at']: 'N/A';
				$row['Admin'] 			= ($refundService['refunded_by']) ? $refundService['refunded_by']: 'N/A';
				$row['Plan Amount'] 	= '$' . $refundService['actual_amount'];
				$row['Refunded Amount']	= '$' . number_format($refundService['refund_amount'],2);
				$row['Refund Type']	= $refundService['payment_status'];

				$output['aaData'][] = $row;
			}
			$totalRefunds = count($refundReportData['result']);


			$row = array();
			$row['Type']			= '';
			$row['Plan Name'] 		= '';
			$row['User Name'] 		= '';
			$row['Status'] 			= '';
			$row['Payment Method'] 	= '';
			$row['Date'] 			= '';
			$row['Admin'] 			= '';
			$row['Plan Amount'] 	= 'Grand Total';
			$row['Refunded Amount']	= '$' . $grandTotal;
			$row['Refund Type']	= '';
			$output['aaData'][] = $row;

			$row['Plan Amount'] 	= 'Total Refund(s)';
			$row['Refunded Amount']	= $totalRefunds;
			$output['aaData'][] = $row;
		}

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf refund report';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export refund report";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:RefundReport:exportPdf.html.twig', array(
                'RefundData' => $output['aaData']
        ));

        unset($output);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
            )
        );
    }

    private function getRefundReportGridList($offset = 0, $limit = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $isLimit = true, $adminServiceLocationPermission = '') {
		$con = $this->get('database_connection');
		$em2 = $this->getDoctrine()->getManager();

    	$data = $em2->getRepository('DhiUserBundle:UserService')->trim_refund_report_serach_data($searchData, $SearchType);
		if (!empty($searchData['serviceType'])) {
			if ($searchData['serviceType'] == 'BUNDLE') {
				unset($searchData['serviceType']);
			}else{
				$searchData['serviceType'] = 'BUNDLE';
			}
		}
    	$bundleData = $em2->getRepository('DhiUserBundle:UserService')->trim_refund_report_serach_data($searchData, $SearchType);
        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
            $bundleLikeStr = $objHelper->orLikeSearch($bundleData);
        }
        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data, false);
            $bundleLikeStr = $objHelper->andLikeSearch($bundleData, false);
        }
        $likeStr = (!empty($likeStr) ? $likeStr.' AND ' : '').'(us.refunded_by_id >:refunded_by_id OR us.deactivated_by_id >:deactivated_by_id OR us.refund_after_expired_by_id > :refund_after_expired_by_id)';
		$bundleLikeStr = (!empty($bundleLikeStr) ? $bundleLikeStr.' AND ' : '').'(us.refunded_by_id >:refunded_by_id OR us.deactivated_by_id >:deactivated_by_id OR us.refund_after_expired_by_id > :refund_after_expired_by_id)';

		$aWhere = array('refunded_by_id' => 0, 'deactivated_by_id' => 0, 'refund_after_expired_by_id' => 0);
		if(!empty($searchData) && isset($searchData['refundDate']))
        {
	        $RequestDate = explode('~', $searchData['refundDate']);
	        $ReqFrom = trim($RequestDate[0]);
	        $ReqTo = trim($RequestDate[1]);

	        if($ReqFrom != "")
	        {
	           $startDate = new \DateTime($ReqFrom);

	            //$strDateCond = ' AND refunded_at >= :startdatetime OR us.deactivated_at >= :startdatetime ';
                   $strDateCond = ' AND (refunded_at >= :startdatetime OR us.deactivated_at >= :startdatetime OR refund_after_expired_at >= :startdatetime)';
	            $likeStr .= $strDateCond;
	            $bundleLikeStr .= $strDateCond;
	            $aWhere['startdatetime'] = $startDate->format('Y-m-d 00:00:00');
	        }
	        if($ReqTo != "") {

	           $endDate = new \DateTime($ReqTo);

	            //$strDateCond = ' AND refunded_at <= :enddatetime OR us.deactivated_at <= :enddatetime ';
                   $strDateCond = ' AND (refunded_at <= :enddatetime OR us.deactivated_at <= :enddatetime OR refund_after_expired_at <= :enddatetime)';
	            $likeStr .= $strDateCond;
	            $likeStr .= $strDateCond;
	            $bundleLikeStr .= $strDateCond;
				$aWhere['enddatetime'] = $endDate->format('Y-m-d 23:59:59');
	        }

        }
        if(!empty($searchData) && isset($searchData['adminUser'])){
            $strAdminCondtion = 'AND (adm.username LIKE :adminUsername OR admPar.username LIKE :adminUsername OR admAfterExp.username LIKE :adminUsername)';
            $likeStr       .= $strAdminCondtion;
            $bundleLikeStr .= $strAdminCondtion;
            $aWhere['adminUsername'] = '%'.$searchData['adminUser'].'%';
        }

        $strQry = "SELECT * FROM (SELECT
                    s.name as serviceName,
                    u.id as user_id,
                    u.username,
                    sp.package_id,
                    sp.package_name,
                    sp.purchase_order_id,
                    us.actual_amount,
                    us.status,
                    pm.name as payment_method_name,
                    (CASE WHEN us.refunded_at IS NOT NULL THEN us.refunded_at WHEN us.deactivated_at IS NOT NULL THEN us.deactivated_at ELSE us.refund_after_expired_at END) as refunded_at,
                    (CASE WHEN adm.username IS NOT NULL THEN adm.username WHEN admPar.username IS NOT NULL THEN admPar.username ELSE admAfterExp.username END) as refunded_by,
                    (CASE WHEN us.refund_amount IS NOT NULL THEN us.refund_amount ELSE us.refund_after_expired_amount END) as refund_amount,
                    sp.payment_status
                FROM
                    service_purchase sp
                LEFT JOIN user_services us on sp.id = us.service_purchase_id
                LEFT JOIN service s ON sp.service_id = s.id
                LEFT JOIN purchase_order po on sp.purchase_order_id = po.id
                LEFT JOIN payment_method pm on po.payment_method_id = pm.id
                LEFT JOIN dhi_user u on us.user_id = u.id
                LEFT JOIN service_location sl on u.user_service_location_id = sl.id
                LEFT JOIN dhi_user adm on us.refunded_by_id = adm.id
                LEFT JOIN dhi_user admPar on us.deactivated_by_id = admPar.id
                LEFT JOIN dhi_user admAfterExp on us.refund_after_expired_by_id = admAfterExp.id
                WHERE
                    purchase_type IS NULL AND us.purchase_order_id > 0 AND (us.refund = 1 OR us.refund_after_expired = 1) AND ".$likeStr. ((is_array($adminServiceLocationPermission) && count($adminServiceLocationPermission) > 0) ? " AND sl.id IN (" . implode(',', $adminServiceLocationPermission) . ") " : " ").
                "UNION
                SELECT
                    purchase_type as serviceName,
                    u.id as user_id,
                    u.username,
                    sp.bundle_id as package_id,
                    IF (b.display_bundle_name IS NOT NULL, b.display_bundle_name, sp.bundle_name) as package_name,
                    us.purchase_order_id,
                    sum(us.actual_amount) as actual_amount,
                    us.status,
                    pm.name as payment_method_name,
                    (CASE WHEN us.refunded_at IS NOT NULL THEN us.refunded_at ELSE us.refund_after_expired_at END) as refunded_at,
                    (CASE WHEN adm.username IS NOT NULL THEN adm.username ELSE admAfterExp.username END) as refunded_by,
                    (SUM(CASE WHEN us.refund_amount IS NOT NULL THEN (us.refund_amount/2) ELSE us.refund_after_expired_amount END)) as refund_amount,
                    sp.payment_status
                FROM
                    service_purchase sp
                LEFT JOIN user_services us on sp.id = us.service_purchase_id
                LEFT JOIN service s ON sp.service_id = s.id
                LEFT JOIN purchase_order po on sp.purchase_order_id = po.id
                LEFT JOIN bundle b on sp.bundle_id = b.bundle_id
                LEFT JOIN payment_method pm on po.payment_method_id = pm.id
                LEFT JOIN dhi_user u on us.user_id = u.id
                LEFT JOIN service_location sl on u.user_service_location_id = sl.id
                LEFT JOIN dhi_user adm on us.refunded_by_id = adm.id
                LEFT JOIN dhi_user admPar on us.deactivated_by_id = admPar.id
                LEFT JOIN dhi_user admAfterExp on us.refund_after_expired_by_id = admAfterExp.id
                WHERE
                    purchase_type = 'BUNDLE' AND us.purchase_order_id > 0 AND (us.refund = 1 OR us.refund_after_expired = 1) AND ".$bundleLikeStr. ((is_array($adminServiceLocationPermission) && count($adminServiceLocationPermission) > 0) ? " AND sl.id IN (" . implode(',', $adminServiceLocationPermission) . ") " : " ").
                "GROUP by purchase_order_id
                ) t ORDER BY ".$orderBy." ".$sortOrder."";

        $countSql = "SELECT SUM(total) as total FROM (SELECT
					    COUNT(sp.purchase_order_id) as total
					FROM
					    service_purchase sp
					LEFT JOIN user_services us on sp.id = us.service_purchase_id
					LEFT JOIN service s ON sp.service_id = s.id
					LEFT JOIN purchase_order po on sp.purchase_order_id = po.id
					LEFT JOIN payment_method pm on po.payment_method_id = pm.id
					LEFT JOIN dhi_user u on us.user_id = u.id
					LEFT JOIN service_location sl on u.user_service_location_id = sl.id
					LEFT JOIN dhi_user adm on us.refunded_by_id = adm.id
					LEFT JOIN dhi_user admPar on us.deactivated_by_id = admPar.id
                                        LEFT JOIN dhi_user admAfterExp on us.refund_after_expired_by_id = admAfterExp.id
					WHERE
					    purchase_type IS NULL AND us.purchase_order_id > 0 AND (us.refund = 1 OR us.refund_after_expired = 1) AND ".$likeStr.((is_array($adminServiceLocationPermission) && count($adminServiceLocationPermission) > 0) ? " AND sl.id IN (" . implode(',', $adminServiceLocationPermission) . ") " : " ").
					"UNION
					SELECT
					    COUNT(distinct us.purchase_order_id) as total
					FROM
					    service_purchase sp
					LEFT JOIN user_services us on sp.id = us.service_purchase_id
					LEFT JOIN service s ON sp.service_id = s.id
					LEFT JOIN purchase_order po on sp.purchase_order_id = po.id
					LEFT JOIN bundle b on sp.bundle_id = b.bundle_id
					LEFT JOIN payment_method pm on po.payment_method_id = pm.id
					LEFT JOIN dhi_user u on us.user_id = u.id
					LEFT JOIN service_location sl on u.user_service_location_id = sl.id
					LEFT JOIN dhi_user adm on us.refunded_by_id = adm.id
					LEFT JOIN dhi_user admPar on us.deactivated_by_id = admPar.id
                                        LEFT JOIN dhi_user admAfterExp on us.refund_after_expired_by_id = admAfterExp.id
					WHERE
					    purchase_type = 'BUNDLE' AND us.purchase_order_id > 0 AND (us.refund = 1 OR us.refund_after_expired = 1) AND ".$bundleLikeStr.((is_array($adminServiceLocationPermission) && count($adminServiceLocationPermission) > 0) ? " AND sl.id IN (" . implode(',', $adminServiceLocationPermission) . ") " : " ").
					") t ";

        if ($isLimit) {
            $strQry .= " LIMIT ". $offset.', '.$limit;
        }

		$query = $con->prepare($strQry);
        $countQuery = $con->prepare($countSql);

        $query->execute($aWhere);
        $countQuery->execute($aWhere);

        //  Count Total Records
		$row       = $countQuery->fetchAll();
		$countData = !empty($row[0]['total']) ? $row[0]['total'] : 0;

		$result     = $query->fetchAll();
		$dataResult = array();
    	if ($countData > 0) {
    		$dataResult['result'] = $result;
    		$dataResult['totalRecord'] = $countData;
    		return $dataResult;
    	}
    	return false;
    }

}
