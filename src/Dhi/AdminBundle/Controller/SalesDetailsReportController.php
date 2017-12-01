<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesDetailsReportController extends Controller {

    public function indexAction(Request $request) {
        $admin = $this->get('security.context')->getToken()->getUser();
        
        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('sales_details_report_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view sales details report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $allActiveService = $em->getRepository('DhiUserBundle:Service')->getAllService();
        $services = array();
        foreach ($allActiveService as $key => $value) {

            $services[$key] = $allActiveService[$key]['name'];
        }
        
        $allServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
        $serviceLocation = array();
        foreach ($allServiceLocation as $key => $value) {

            $serviceLocation[$key] = $allServiceLocation[$key]['name'];
        }
        
        $allPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->getSalesReportMethods();
        $paymentMethod = array();
        foreach ($allPaymentMethod as $key => $value) {
            $paymentMethod[$key] = $allPaymentMethod[$key]['name'];
        }
        
        $allWhiteLabelSites = $em->getRepository('DhiAdminBundle:WhiteLabel')->getWhiteLabelSites();

        return $this->render('DhiAdminBundle:SalesDetailsReport:index.html.twig', array(
                    'admin' => $admin,
                    'service' => json_encode($services),
                    'serviceLocation' => json_encode($serviceLocation),
                    'paymentMethod' => json_encode($paymentMethod),
                    'allWhiteLabelSites' => $allWhiteLabelSites
        ));
    }

    public function salesDetailsListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');
        
        $aColumns = array('serviceType', 'customer', 'planName', 'bandwidth', 'validity', 'rechargeStatus', 'paymentMethod', 'createdDate', 'adminUser', 'actualAmount', 'payableAmount', 'serviceLocation', 'purchasedFrom');

        $gridData = $helper->getSearchData($aColumns);

        if ($request->get('purchasedFrom') != null) {
            $gridData['search_data']['purchasedFrom'] = $request->get('purchasedFrom');
            $gridData['SearchType'] = 'ANDLIKE';
        }
        
        if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {

            $this->get('session')->set('salesDetailsReportSearchData', $gridData['search_data']);
        } else {

            $this->get('session')->remove('salesDetailsReportSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'sp.id';
            $sortOrder = 'DESC';

        } else {

            if ($gridData['order_by'] == 'serviceType') {
                $orderBy = 's.name';

            }else if ($gridData['order_by'] == 'customer') {
                $orderBy = 'u.username';

            }else if ($gridData['order_by'] == 'planName') {
                $orderBy = 'sp.packageName';

            }else if ($gridData['order_by'] == 'bandwidth') {
                $orderBy = 'sp.bandwidth';

            }else if ($gridData['order_by'] == 'validity') {
                $orderBy = 'sp.validity';

            }else if ($gridData['order_by'] == 'rechargeStatus') {
                $orderBy = 'sp.rechargeStatus';

            }else if ($gridData['order_by'] == 'paymentMethod') {
                $orderBy = 'pm.name';

            }else if ($gridData['order_by'] == 'createdDate') {
                $orderBy = 'sp.createdAt';

            }else if ($gridData['order_by'] == 'adminUser') {
                $orderBy = 'pbu.username';

            }else if ($gridData['order_by'] == 'actualAmount') {
                $orderBy = 'sp.actualAmount';

            }else if ($gridData['order_by'] == 'payableAmount') {
                $orderBy = 'sp.payableAmount';

            }else if ($gridData['order_by'] == 'serviceLocation') {
                $orderBy = 'sl.name';

            }else if ($gridData['order_by'] == 'purchasedFrom') {
                $orderBy = 'wl.companyName';

            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        if ($request->get('name') != null) {
            $gridData['search_data']['serviceLocation'] = $request->get('name');
            $gridData['SearchType'] = 'ANDLIKE';
        }
        
        $fromip = '';
        $toip = '';
        $packageName = '';

        $serviceLocationCondition = $gridData['search_data'];

        if (!empty($serviceLocationCondition['serviceType'])) {
            unset($serviceLocationCondition['serviceType']);
        }
        if (!empty($gridData['search_data']['createdDate'])) {
            $serviceLocationCondition['serviceType'] = $gridData['search_data']['createdDate'];
        }


        $output = array(
            "totalPayableAmount" => 0,
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        
        $data = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getSalesDetailsReportGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {
                $output["totalPayableAmount"]        = $data['payableAmountGrandTotal'];
                $output["iTotalRecords"]        = $data['totalRecord'];
                $output["iTotalDisplayRecords"] = $data['totalRecord'];

                $rechargeStatus = '';
                $tmpTotal = $i = 0;
                foreach ($data['result'] AS $resultRow) {
                    $paymentMethod = '';
                    if (!empty($resultRow['paymentMethod'])) {
                        $paymentMethod = $resultRow['paymentMethod'];
                    }

                    if($resultRow['rechargeStatus'] == 1){
                        $rechargeStatus = '<span class="btn btn-success btn-sm">Success<span>'; 
                    }else if($resultRow['rechargeStatus'] == 2){
                        $rechargeStatus = '<span class="btn btn-danger btn-sm">Failed</span>'; 
                    }

                    if ($resultRow['purchaseType'] == 'BUNDLE') {
                        $packageName = empty($resultRow['displayBundleName']) ? $resultRow['displayBundleName'] : 'N/A';
                        $service     = 'BUNDLE';
                    }else{
                        $packageName = !empty($resultRow['packageName']) ? $resultRow['packageName'] : 'N/A';
                        $service     = !empty($resultRow['service']) ? $resultRow['service'] : 'N/A';
                    }

                    $bandwidth = '';
                    if (!empty($resultRow['bandwidth'])) {
                        $bwArr = explode(',', $resultRow['bandwidth']);
                        foreach ($bwArr as $key => $value) {
                            $bandwidth .= $value.'k, ';
                        }
                        $bandwidth = rtrim($bandwidth, ', ');
                    }
                    

                    $validity = '';
                    if (!empty(!empty($resultRow['validity']))) {
                        $validityArr = explode(',', $resultRow['validity']);
                        if (!empty($resultRow['validityType'])) {
                            $validityTypeArr = explode(',', $resultRow['validityType']);
                        }else{
                            $validityTypeArr = array();
                        }

                        foreach ($validityArr as $key => $value) {
                            $validityType = '';
                            if (!empty($validityTypeArr[$key])) {
                                if ($validityTypeArr[$key] == 'DAYS') {
                                    $validityType = ' day(s)';
                                }else if ($validityTypeArr[$key] == 'HOURS') {
                                    $validityType = ' hour(s)';
                                } else {
                                    $validityType = ' day(s)';
                                }
                            }else{
                                $validityType = ' day(s)';
                            }

                            $validity .= $value . $validityType . ', ';
                        }
                        $validity = rtrim($validity, ', ');

                    }else{
                        $validity = 'N/A';
                    }

                    $row = array();
                    $row[] = $service;
                    $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['user_id'])) . '">' . $resultRow['username'] . '</a>';
                    $row[] = $username;
                    $row[] = $packageName;
                    $row[] = $bandwidth;
                    $row[] = $validity;
                    $row[] = $rechargeStatus;
                    $row[] = $paymentMethod;
                    $row[] = ($resultRow['createdAt']) ? $resultRow['createdAt']->format('m/d/Y H:i:s') : 'N/A';
                    $row[] = (!empty($resultRow['paymentByUser']) ? $resultRow['paymentByUser'] : '')." (".$resultRow['paymentBy'].")";
                    $row[] = $resultRow['actualAmount'];
                    $row[] = $resultRow['payableAmount'];
                    $row[] = $resultRow['name'];
                    $row[] = ($resultRow['companyName']) ? $resultRow['companyName'] : 'N/A';
                    $tmpTotal = $tmpTotal+$resultRow['payableAmount'];
                    $output['aaData'][($resultRow['purchaseType'] == 'BUNDLE' ? $resultRow['orderNumber'] : $i)] = $row;
                    $i++;
                }
            }
        }
        
        $output['aaData'] = array_values($output['aaData']);
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportCsvAction(Request $request) {

        if (!( $this->get('admin_permission')->checkPermission('sales_details_report_export_csv'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export sales details report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin  = $this->get('security.context')->getToken()->getUser();
        $em     = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');
        
        $searchData = array();
        if ($this->get('session')->get('salesDetailsReportSearchData')) {
            $searchData = $this->get('session')->get('salesDetailsReportSearchData');
        }
        
        if (!empty($searchData['serviceLocation'])) {
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->findBy(array('name' => $searchData['serviceLocation']));
        }else{
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->findAll();
        }

        $salesDetailsData = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getExportDataOfSalesDetails($helper, $searchData, 'ANDLIKE', $serviceLocations);

        //Create object of streamed response
        $response = new StreamedResponse();

        $response->setCallback(function() use($salesDetailsData) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Type", 'Customer', "Plan Name", "Plan BW", "Plan Life", "Recharge Status", "Payment Method", "Date", "Admin", "Plan Amount", "Payable Amount", "Service Location", "Purchased From"), ',');

            if ($salesDetailsData) {

                $grandTotal     = 0;
                $paymentMethod  = '';
                $rechargeStatus = '';
                foreach ($salesDetailsData as $resultRow) {

                    $grandTotal += $resultRow['payableAmount'];
                    if (!empty($resultRow['paymentMethod'])) {
                        $paymentMethod = $resultRow['paymentMethod'];
                    }
                
                    if($resultRow['rechargeStatus'] == 1){
                       $rechargeStatus = 'Success'; 
                    }else if($resultRow['rechargeStatus'] == 2){
                        $rechargeStatus = 'Failed'; 
                    }

                    if ($resultRow['purchaseType'] == 'BUNDLE') {
                        $packageName = empty($resultRow['displayBundleName']) ? $resultRow['displayBundleName'] : 'N/A';
                        $service = 'BUNDLE';
                    }else{
                        $packageName = !empty($resultRow['packageName']) ? $resultRow['packageName'] : 'N/A';
                        $service     = !empty($resultRow['service']) ? $resultRow['service'] : 'N/A';
                    }

                    $bandwidth = '';
                    if (!empty($resultRow['bandwidth'])) {
                        $bwArr = explode(',', $resultRow['bandwidth']);
                        foreach ($bwArr as $key => $value) {
                            $bandwidth .= $value.'k, ';
                        }
                        $bandwidth = rtrim($bandwidth, ', ');
                    }
                    

                    $validity = '';
                    if (!empty(!empty($resultRow['validity']))) {
                        $validityArr = explode(',', $resultRow['validity']);
                        if (!empty($resultRow['validityType'])) {
                            $validityTypeArr = explode(',', $resultRow['validityType']);
                        }else{
                            $validityTypeArr = array();
                        }

                        foreach ($validityArr as $key => $value) {
                            $validityType = '';
                            if (!empty($validityTypeArr[$key])) {
                                if ($validityTypeArr[$key] == 'DAYS') {
                                    $validityType = ' day(s)';
                                }else if ($validityTypeArr[$key] == 'HOURS') {
                                    $validityType = ' hour(s)';
                                }else{
                                    $validityType = ' day(s)';
                                }
                            }else{
                                $validityType = ' day(s)';
                            }

                            $validity .= $value . $validityType . ', ';
                        }
                        $validity = rtrim($validity, ', ');

                    }else{
                        $validity = 'N/A';
                    }

                    fputcsv($handle, array(
                        (!empty($service)) ? $service : 'N/A',
                        $resultRow['username'],
                        !empty($packageName) ? $packageName : 'N/A',
                        $bandwidth,
                        $validity,
                        $rechargeStatus,
                        $paymentMethod,
                        ($resultRow['createdAt']) ? $resultRow['createdAt']->format('m/d/Y H:i:s') : 'N/A',
                        $resultRow['paymentByUser']." (".$resultRow['paymentBy'].")",
                        '$' . $resultRow['actualAmount'],
                        '$' . $resultRow['payableAmount'],
                        $resultRow['name'],
                        $resultRow['companyName']
                            ), ',');
                }
                $totalSales = count($salesDetailsData);

                fputcsv($handle, array(
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Grand Total',
                    '$' . $grandTotal
                        ), ',');
                fputcsv($handle, array(
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    '',
                    'Total Sales',
                    $totalSales
                        ), ',');
            }

            fclose($handle);
        });

        // Set audit log for export csv sales details report
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv sales details report';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv sales details report";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $file_name = 'sales_details_report.csv'; // Create file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

}
