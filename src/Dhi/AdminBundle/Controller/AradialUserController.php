<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use \DateTime;


class AradialUserController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('aradial_user_list') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view email campaign list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:AradialUser:index.html.twig');
    }

    //added for grid
    public function aradialUserListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $userColumns = array('UserName', 'Email', 'NasName', 'StartTime','StopTime', 'CallerId', 'CalledId', 'FramedAddress');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($userColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if(!empty($gridData['search_data'])) {
            $this->get('session')->set('aradialUserSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('aradialUserSearchData');
        }

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'ush.startTime';
            $sortOrder = 'DESC';
        } else {
			if ($gridData['order_by'] == 'StartTime') {

                $orderBy = 'ush.startDateTime';
            }
            if ($gridData['order_by'] == 'UserName') {

                $orderBy = 'ush.userName';
            }
            if ($gridData['order_by'] == 'Email') {

                $orderBy = 'ush.email';
            }

            if ($gridData['order_by'] == 'NasName') {

                $orderBy = 'ush.nasName';
            }

            if ($gridData['order_by'] == 'StopTime') {

                $orderBy = 'ush.stopDateTime';
            }
            if ($gridData['order_by'] == 'CallerId') {

                $orderBy = 'ush.callerId';
            }
            if ($gridData['order_by'] == 'CalledId') {

                $orderBy = 'ush.calledId';
            }
            if ($gridData['order_by'] == 'FramedAddress') {

                $orderBy = 'ush.framedAddress';
            }
			$orderBy = 'ush.startTime';
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $country = '';
        if($admin->getGroup() != 'Super Admin') {
          $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
          $country = empty($country)?'0':$country;
        }

        $data = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getAradialUserHistoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin,$country);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        $sort = array();
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
					$seconds = 0;

                    $startTime = $resultRow['startDateTime'];
					$stopTime = $resultRow['stopDateTime'];
                    /*
                        // Removing old calculation
                        if (!empty($resultRow['stopDateTime'])) {
        					$parts   = date_parse($resultRow['stopDateTime']->format("H:i:s"));
        					$seconds = $parts['hour'] * 3600 + $parts['minute'] * 60 + $parts['second'];

        					$dateTime = clone $startTime;
        					$dateTime->modify('+'.$seconds.' seconds');
                        }
                    */

					$row[] = $resultRow['userName'];
					$row[] = $resultRow['email'];
					$row[] = $resultRow['nasName'];
					$row[] = !empty($startTime) ? $startTime->format('M-d-Y H:i:s') : "N/A";
                    $row[] = !empty($stopTime) ? $stopTime->format('M-d-Y H:i:s') : "N/A";
					$row[] = $resultRow['callerId'];
					$row[] = $resultRow['calledId'];
					$row[] = $resultRow['framedAddress'];
					$output['aaData'][] = $row;

                    if ($gridData['order_by'] == 'StopTime') {
                        $sort[] = !empty($dateTime) ? $dateTime->format('M-d-Y H:i:s') : "N/A";
                    }
                }
            }
        }

        if ($gridData['order_by'] == 'StopTime') {
            array_multisort($sort, ($sortOrder == "desc" ? SORT_DESC : SORT_ASC), $output['aaData']);
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

    public function printAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('aradial_user_list_export_print')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to print user purchase detail.");
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
        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

        $searchData = array();
        if($this->get('session')->has('aradialUserSearchData') && $this->get('session')->get('aradialUserSearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserSearchData');
        }
        //$file_name = 'purchase_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download

        //Get Purchase History Data
        $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        // $purchaseHistoryData = $this->get('DashboardSummary')->getPdfPurchaseHistoryData(NULL, $ipAddressZones,$searchData);
        $slotArr = array('limit'  => $slot, 'offset' => $offset);
        $aradialUserHistoryData = $this->getPdfAradialUserHistoryData($searchData, $slotArr);

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Print Aradial User history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " print aradial user history";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $i = 0;
        foreach ($aradialUserHistoryData AS $resultRow) {
            $row = array();
            $seconds = 0;
            $parts = explode(':', $resultRow['stopTime']);
            if (count($parts) > 2) {
                $seconds += $parts[0] * 3600;
            }
            $seconds += $parts[1] * 60;
            $seconds += $parts[2];

            $dateTime = new DateTime($resultRow['startTime']);
            $startTime = new DateTime($resultRow['startTime']);
            $dateTime->format('Y-m-d H:i:s');
            $dateTime->modify('+' . $seconds . ' seconds');
            $startTime->format('M-d-Y H:i:s');
            $aradialUserHistoryData[$i]['startTime'] = ($startTime->format('M-d-Y H:i:s'));
            $aradialUserHistoryData[$i]['stopTime'] = ($dateTime->format('M-d-Y H:i:s'));
            $i++;
        }

        //  Rendering view for printing data
        return $this->render('DhiAdminBundle:AradialUser:print.html.twig', array(
                'aradialUserHistoryData' => $aradialUserHistoryData,
                'img' => $dhiLogoImg
        ));
    }

    public function getPdfAradialUserHistoryData($searchData, $slotArr = array()){

        $em = $this->getDoctrine()->getManager();
        if(isset($searchData) && $searchData != '' ) {
            $purchaseOrderData = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getSearchAradialUserHistory($searchData, $slotArr);
        } else {
            $purchaseOrderData = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getAradialUserHistory($slotArr);
        }
        return $purchaseOrderData;
    }

    public function exportCsvAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('aradial_user_list_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
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

        // Get Purchase History Data
        $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
        $country = '';
        if($admin->getGroup() != 'Super Admin') {
            $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $country = empty($country)?'0':$country;
        }

        $slotArr = array('limit'  => $slot, 'offset' => $offset);

        $result = '';
        //Get Searching Data
        $searchData = array();
        if($this->get('session')->has('aradialUserSearchData')&& $this->get('session')->get('aradialUserSearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserSearchData');
//            $query = $em->getRepository('DhiServiceBundle:ServicePurchase')->getSearchCsvPurchaseHistory(null, $ipAddressZones,$country,$searchData);
            $result = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getSearchAradialUserHistory($searchData, $slotArr);
        } else {
//            $query = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserPurchaseHistory(null, $ipAddressZones,$country);
            $result = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getAradialUserHistory($slotArr);
        }

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv user purchase history";

        /*       if (!empty($searchParams) && isset($searchParams['searchTxt'])) {

        $activityLog['description'] = "Admin " . $admin->getUsername() . " searched export csv user purchase history" . json_encode($searchParams['searchTxt']);
        $em->getRepository('DhiUserBundle:UserService')->getSearchPurchaseHistory($query, $searchParams['searchTxt']);

        } */

        $this->get('ActivityLog')->saveActivityLog($activityLog);

//        $result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($result) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Username", "Email", "NasName", "StartTime", "StopTime", "CallerId", "CalledId", "FramedAddress"), ',');
            // Query data from database


            $i = 0;
            foreach ($result AS $resultRow) {
                $row = array();
                $seconds = 0;
                $parts = explode(':', $resultRow['stopTime']);
                if (count($parts) > 2) {
                    $seconds += $parts[0] * 3600;
                }
                $seconds += $parts[1] * 60;
                $seconds += $parts[2];

                $dateTime = new DateTime($resultRow['startTime']);
                $startTime = new DateTime($resultRow['startTime']);
                $dateTime->format('Y-m-d H:i:s');
                $dateTime->modify('+' . $seconds . ' seconds');
                $startTime->format('M-d-Y H:i:s');
                $result[$i]['startTime'] = ($startTime->format('M-d-Y H:i:s'));
                $result[$i]['stopTime'] = ($dateTime->format('M-d-Y H:i:s'));
                $i++;
            }
            foreach ($result as $key => $purchaseHistory) {

                $username      = $purchaseHistory['userName'] ? $purchaseHistory['userName'] : '';
                $email         = $purchaseHistory['email'] ? $purchaseHistory['email'] : '';
                $nasname       = $purchaseHistory['nasName'] ? $purchaseHistory['nasName'] : '';
                $starttime     = $purchaseHistory['startTime'] ? $purchaseHistory['startTime'] : '';
                $stoptime      = $purchaseHistory['stopTime'] ? $purchaseHistory['stopTime'] : '';
                $callerid      = $purchaseHistory['callerId'] ? $purchaseHistory['callerId'] : '';
                $calledid      = $purchaseHistory['calledId'] ? $purchaseHistory['calledId'] : '';
                $framedaddress = $purchaseHistory['framedAddress'] ? $purchaseHistory['framedAddress'] : '';

                fputcsv($handle, array(
                        $username,
						$email,
                        $nasname,
                        $starttime,
                        $stoptime,
                        $callerid,
                        $calledid,
                        $framedaddress,
                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'aradial_session_history_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

    public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('aradial_user_list_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $offset = $request->get("offset");
        $slot   = $this->container->getParameter("dhi_admin_export_limit");
        if (!isset($slot) || !isset($offset)) {
            $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $slotArr        = array('limit'  => $slot, 'offset' => $offset);
        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'aradial_session_history_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data

        $result = '';
        $searchData = array();

        if($this->get('session')->has('aradialUserSearchData')&& $this->get('session')->get('aradialUserSearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserSearchData');
            $result = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getSearchAradialUserHistory($searchData, $slotArr);
        }
        else  {
            $result = $em->getRepository('DhiAdminBundle:UserSessionHistory')->getAradialUserHistory($slotArr);
        }
         $i = 0;
        foreach ($result AS $resultRow) {
            $row = array();
            $seconds = 0;
            $parts = explode(':', $resultRow['stopTime']);
            if (count($parts) > 2) {
                $seconds += $parts[0] * 3600;
            }
            $seconds += $parts[1] * 60;
            $seconds += $parts[2];

            $dateTime = new DateTime($resultRow['startTime']);
            $startTime = new DateTime($resultRow['startTime']);
            $dateTime->format('Y-m-d H:i:s');
            $dateTime->modify('+' . $seconds . ' seconds');
            $startTime->format('M-d-Y H:i:s');
            $result[$i]['startTime'] = ($startTime->format('M-d-Y H:i:s'));
            $result[$i]['stopTime'] = ($dateTime->format('M-d-Y H:i:s'));
            $i++;
        }

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user purchase history";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:AradialUser:exportPdf.html.twig', array(
                'aradialUserData' => $result
        ));

        unset($result);
        
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
            )
        );
        // create html to pdf
       /* $pdf = $this->get("white_october.tcpdf")->create();

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
        
         $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:AradialUser:exportPdf.html.twig', array(
                'aradialUserData' => $result
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
}
