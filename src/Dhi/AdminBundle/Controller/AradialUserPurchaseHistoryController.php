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

class AradialUserPurchaseHistoryController extends Controller {

    public function purchaseHistoryAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('aradial_user_purchase_history') || $this->get('admin_permission')->checkPermission('user_purchase_history_export') )) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user purchase detail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $allPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->getAllPaymentMethod();
        $paymentMethod = array();
        foreach ($allPaymentMethod as $key => $value) {
            $paymentMethod[$key] = $allPaymentMethod[$key]['name'];
        }
        
        return $this->render('DhiAdminBundle:AradialUserPurchaseHistory:aradialUserPurchaseHistory.html.twig', array(
                'admin' => $admin,
                'currentDate' => date('m-d-Y', time()),
                'paymentMethod' => json_encode($paymentMethod)
        ));
    }

    public function purchaseHistoryListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $request = $this->getRequest();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');

        $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);

        if($admin->getGroup() != 'Super Admin'){
            $aColumns = array('id', 'orderNumber', 'transactionId', 'userName', 'purcasedService', 'paymentMethod', 'paymentStatus', 'totalAmount', 'refundAmount', 'purchaseDate');
        }
        else{
            $aColumns = array('id', 'orderNumber', 'transactionId', 'userName', 'purcasedService', 'paymentMethod', 'paymentStatus', 'totalAmount', 'refundAmount', 'purchaseDate', 'ipAddress');
        }


        $gridData = $helper->getSearchData($aColumns);

        if(!empty($gridData['search_data'])) {
            $this->get('session')->set('aradialUserPurchaseHistorySearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('aradialUserPurchaseHistorySearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $gridData['SearchType'] = 'ANDLIKE';
            $orderBy = 'po.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'orderNumber') {

                $orderBy = 'po.orderNumber';
            }

            if ($gridData['order_by'] == 'transactionId') {

                $orderBy = 'm.authTicket, pp.paypalTransactionId';
            }

            if ($gridData['order_by'] == 'userName') {

                $orderBy = 'u.username';
            }

            if ($gridData['order_by'] == 'paymentStatus') {

                $orderBy = 'sp.paymentStatus';
            }

            if ($gridData['order_by'] == 'totalAmount') {

                $orderBy = 'po.totalAmount';
            }

            if ($gridData['order_by'] == 'refundAmount') {

                $orderBy = 'po.refundAmount';
            }

            if ($gridData['order_by'] == 'purchaseDate') {

                $orderBy = 'po.createdAt';
            }
            if ($gridData['order_by'] == 'paymentMethod') {

                $orderBy = 'pm.name';
            }

            if ($gridData['order_by'] == 'purcasedService') {

                $orderBy = 's.name';
            }

            if ($gridData['order_by'] == 'ipAddress') {

                $orderBy = 'po.ipAddress';
            }

        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $country = '';
        if($admin->getGroup() != 'Super Admin') {
            $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $country = empty($country)?'0':$country;
        }

        $data = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getPurchaseHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, NULL, $ipAddressZones,$admin,$country, 1, array('Completed','Refunded', 'Expired', 'Voided'), true);

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

                    $purchasedService = '';
                    $isOnlyCredit     = true;
                    $compArr          = explode(',', $resultRow['isCompensation']);
                    $creditArr        = explode(',', $resultRow['isCredit']);
                    $serviceArr       = explode(',', $resultRow['serviceName']);

                    $purchasedTypeArr = array();
                    foreach ($serviceArr as $key => $serviceNm) {
                        if ($creditArr[$key] == 1) {
                            $purchasedTypeArr[] = "Credit";
                        }else if ($compArr[$key] == 1) {
                            $isOnlyCredit = false;
                            $purchasedTypeArr[] = "Compensation";
                        }else{
                            $isOnlyCredit = false;
                            $purchasedTypeArr[] = strtoupper($serviceNm);
                        }
                    }

                    if (!empty($purchasedTypeArr)) {
                        $purchasedService = implode('<br/>', array_unique($purchasedTypeArr));
                    }

                    $paymentMethod = '';
                    $transactionId = 'N/A';
                    if (!empty($resultRow['paymentmethodCode'])) {
                        $paymentMethod = $resultRow['paymentmethodName'];
                        if($resultRow['paymentmethodCode'] == 'Paypal' || $resultRow['paymentmethodCode'] == 'CreditCard'){
                            if(!empty($resultRow['paypalTransactionId'])) {
                                $transactionId = $resultRow['paypalTransactionId'];
                            }
                        }

                        if($resultRow['paymentmethodCode'] == 'Milstar'){
                            if(!empty($resultRow['authTicket'])) {
                                $transactionId = $resultRow['authTicket'];
                            }
                        }
                    } else {
                        if ($isOnlyCredit) {
                            if ($resultRow['paymentBy'] == 'Admin') {
                                if(!empty($resultRow['type'])){ 
                                    $paymentMethod = $resultRow['type'];
                                } else {
                                    $paymentMethod = 'Pay By Admin';
                                }
                            }
                        }
                    }

                    $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['uId'])) . '">' . $resultRow['username'] . '</a>';

                    $row = array();
                    $row[] = '';
                    $row[] = $resultRow['orderNumber'];
                    $row[] = $transactionId;
                    $row[] = (!empty($resultRow['username'])) ? $username : '';
                    $row[] = $purchasedService;
                    $row[] = $paymentMethod;
                    $row[] = $resultRow['paymentStatus'] == "Expired" ? "Plan Expired by Customer Support" : $resultRow['paymentStatus'];
                    $row[] = (!empty($resultRow['totalAmount'])) ? "$" . $resultRow['totalAmount'] : '';
                    $row[] = (!empty($resultRow['refundAmount'])) ? "$" . $resultRow['refundAmount'] : '';
                    $row[] = (!empty($resultRow['createdAt'])) ? $resultRow['createdAt']->format('M-d-Y H:i:s') : '';
                    $row[] = $resultRow['ipAddress'];
                    $row[] = (!empty($resultRow['prpId']))?$resultRow['prpId']:'';
                    $row[] = $resultRow['poId'];
                    $output['aaData'][] = $row;
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

    public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('aradial_user_purchase_history_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user purchase detail.");
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

        $file_name = 'purchase_aradial_user_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data
        $searchData = array();
        if($this->get('session')->has('aradialUserPurchaseHistorySearchData') && $this->get('session')->get('aradialUserPurchaseHistorySearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserPurchaseHistorySearchData');
        }

        $slotArr           = array();
        $slotArr['limit']  = $slot;
        $slotArr['offset'] = $offset;

        $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
        $searchData['isAradialMigrated'] = 1 ;
        $purchaseHistoryData = $this->get('DashboardSummary')->getPrintPurchaseHistoryData(NULL, $ipAddressZones,$searchData, $slotArr);

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf aradial user purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user aradial user purchase history";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:AradialUserPurchaseHistory:exportPdf.html.twig', array(
                'purchaseData' => $purchaseHistoryData
        ));

        unset($purchaseHistoryData);
        
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

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:AradialUserPurchaseHistory:exportPdf.html.twig', array(
                'purchaseData' => $purchaseHistoryData
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

        if (!$this->get('admin_permission')->checkPermission('aradial_user_purchase_history_export_csv')) {
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

        //   $searchParams = $request->query->all();
        // Get Purchase History Data
        $ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
        $country = '';
        if($admin->getGroup() != 'Super Admin') {
            $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $country = empty($country)?'0':$country;
        }

        $slotArr           = array();
        $slotArr['limit']  = $slot;
        $slotArr['offset'] = $offset;

        //Get Searching Data
        $searchData = array();
        if($this->get('session')->has('aradialUserPurchaseHistorySearchData')&& $this->get('session')->get('aradialUserPurchaseHistorySearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserPurchaseHistorySearchData');
            $searchData['isAradialMigrated'] = 1 ;
            
            $query = $em->getRepository('DhiServiceBundle:ServicePurchase')->getSearchCsvPurchaseHistory(null, $ipAddressZones,$country,$searchData, 'array', $slotArr);
        }
        else  {
            $query = $em->getRepository('DhiServiceBundle:ServicePurchase')->getUserPurchaseHistoryArr(null, $ipAddressZones,$country, $isAradialMigrated = 1, $slotArr);
        }

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv aradial user purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv aradial user purchase history";

        /*       if (!empty($searchParams) && isset($searchParams['searchTxt'])) {

        $activityLog['description'] = "Admin " . $admin->getUsername() . " searched export csv user purchase history" . json_encode($searchParams['searchTxt']);
        $em->getRepository('DhiUserBundle:UserService')->getSearchPurchaseHistory($query, $searchParams['searchTxt']);

        } */

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($result) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Order Number", "Username", "Purchased Service", "Package", "Total Amount", "Payment Status", "Purchase Date", "Transaction Id", "Payment Method", "Refund Amount", "Compensation Validity"), ',');
            // Query data from database

            foreach ($result as $key => $purchaseHistory) {

                $createdAt        = '';
                $transactionId    = '';
                $paymentMethod    = '';
                $refundAmount     = '';
                $orderNumber      = '';
                $compensationValidity = '';
                $paymentStatus    = '';
                $username             = !empty($purchaseHistory['username']) ? $purchaseHistory['username'] : '';
                $packageName          = !empty($purchaseHistory['packageName']) ? $purchaseHistory['packageName'] : '';
                $payableAmount        = !empty($purchaseHistory['payableAmount']) ? $purchaseHistory['payableAmount'] : '';
                $purchaseOrder        = $purchaseHistory['poId'];

                if (!empty($purchaseOrder)) {

                    $objcreatedAt         = $purchaseHistory['poCreatedAt'];
                    $createdAt            = $objcreatedAt->format('M-d-Y H:i:s');
                    $orderNumber          = !empty($purchaseHistory['orderNumber']) ? $purchaseHistory['orderNumber'] : '';
                    $compensationValidity = !empty($purchaseHistory['compensationValidity']) ? $purchaseHistory['compensationValidity'] . ' Hours' : '';
                    $paymentStatus        = !empty($purchaseHistory['paymentStatus']) ? ($purchaseHistory['paymentStatus'] == "Expired" ? "Plan Expired by Customer Support" : $purchaseHistory['paymentStatus']) : '';

                    if(!empty($purchaseHistory['PaymentMethodName'])) {

                        $paymentMethod = $purchaseHistory['PaymentMethodName'];

                        if($purchaseHistory['PaymentMethodCode'] == 'PayPal' || $purchaseHistory['PaymentMethodCode'] == 'CreditCard'){

                            if(!empty($purchaseHistory['paypalTransactionId'])) {

                                $transactionId = $purchaseHistory['paypalTransactionId'];
                            }
                        }

                        if($purchaseHistory['PaymentMethodCode'] == 'Milstar'){

                            if($purchaseHistory['authTicket']) {

                                $transactionId = $purchaseHistory['authTicket'];
                            }
                        }
                        
                        if($purchaseHistory['PaymentMethodCode'] == 'chase'){
                            if(!empty($purchaseHistory['chaseTransactionId'])) {
                                $transactionId = $purchaseHistory['chaseTransactionId'];
                            }
                        }

                    }

                    if ($purchaseHistory['refundAmount'] > 0) {

                        $refundAmount = $purchaseHistory['refundAmount'];
                    }
                }

                fputcsv($handle, array(
                        $orderNumber.' ',
                        $username,
                        !empty($purchaseHistory['service']) ? $purchaseHistory['service'] : '',
                        $packageName,
                        $payableAmount,
                        $paymentStatus,
                        $createdAt,
                        $transactionId,
                        $paymentMethod,
                        $refundAmount,
                        $compensationValidity
                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'aradial_user_purchase_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

    public function printAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('aradial_user_purchase_history_export_print')) {
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
        if($this->get('session')->has('aradialUserPurchaseHistorySearchData') && $this->get('session')->get('aradialUserPurchaseHistorySearchData') != '') {
            $searchData = $this->get('session')->get('aradialUserPurchaseHistorySearchData');
        }
        //$file_name = 'purchase_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data
        $ipAddressZones                  = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
        $searchData['isAradialMigrated'] = 1 ;
        $slotArr                         = array();
        $slotArr['limit']                = $slot;
        $slotArr['offset']               = $offset;

        $purchaseHistoryData = $this->get('DashboardSummary')->getPrintPurchaseHistoryData(NULL, $ipAddressZones,$searchData, $slotArr);

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Print aradial user purchase history';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " print aradial user purchase history";

        $this->get('ActivityLog')->saveActivityLog($activityLog);


        //  Rendering view for printing data
        return $this->render('DhiAdminBundle:AradialUserPurchaseHistory:print.html.twig', array(
                'purchaseData' => $purchaseHistoryData,
                'img' => $dhiLogoImg
        ));
    }

    public function recurringProfileListAction(Request $request) {

    	$recurringProfileId = $request->get('id');
    	$user = $this->get('security.context')->getToken()->getUser();
    	$em = $this->getDoctrine()->getManager();

    	$objRecurringProfile = $em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->find($recurringProfileId);

    	$user = '';
    	if ($objRecurringProfile) {

    		if ($objRecurringProfile->getPurchaseOrder()) {

    			$user = $objRecurringProfile->getPurchaseOrder()->getUser();
    		}
    	}

    	$viewData = array();
    	$viewData['recurringProfileId'] = $recurringProfileId;
    	$viewData['user'] = $user;
    	$viewData['objRecurringProfile'] = $objRecurringProfile;

    	return $this->render('DhiAdminBundle:PurchaseHistory:recurringHistory.html.twig',$viewData);
    }

    public function recurringProfileListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

    	$request  	= $this->getRequest();
    	$admin 		= $this->get('security.context')->getToken()->getUser();
    	$em       	= $this->getDoctrine()->getManager();
    	$helper   	= $this->get('grid_helper_function');
    	$recurringProfileId = $request->get('id');

    	$aColumns = array('profileId', 'profileStatus', 'paymentReceviedDate', 'nextBillingDate', 'finalDueDate', 'amount', 'completedCycle', 'remainingCycle');

    	$gridData = $helper->getSearchData($aColumns);

    	$sortOrder = $gridData['sort_order'];
    	$orderBy = $gridData['order_by'];

    	if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

    		$orderBy = 'rp.id';
    		$sortOrder = 'DESC';

    	} else {

    		if ($gridData['order_by'] == 'profileId') {

    			$orderBy = 'rp.profileId';
    		}
    		if ($gridData['order_by'] == 'profileStatus') {

    			$orderBy = 'rp.profileStatus';
    		}
    		if ($gridData['order_by'] == 'paymentReceviedDate') {

    			$orderBy = 'rp.billingDate';
    		}
    		if ($gridData['order_by'] == 'nextBillingDate') {

    			$orderBy = 'rp.nextBillingDate';
    		}
    		if ($gridData['order_by'] == 'finalDueDate') {

    			$orderBy = 'rp.finalDueDate';
    		}
    		if ($gridData['order_by'] == 'amount') {

    			$orderBy = 'rp.amount';
    		}
    		if ($gridData['order_by'] == 'completedCycle') {

    			$orderBy = 'rp.numCompletedCycle';
    		}
    		if ($gridData['order_by'] == 'remainingCycle') {

    			$orderBy = 'rp.numRemainingCycle';
    		}
    	}

    	// Paging
    	$per_page = $gridData['per_page'];
    	$offset = $gridData['offset'];

    	$data  = $em->getRepository('DhiServiceBundle:RecurringPaymentLog')->getRecurringHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $recurringProfileId);

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
    				$row[] = $resultRow->getProfileId();
    				$row[] = $resultRow->getProfileStatus();
    				$row[] = ($resultRow->getBillingDate())?$resultRow->getBillingDate()->format('m/d/Y'):'N/A';
    				$row[] = ($resultRow->getNextBillingDate())?$resultRow->getNextBillingDate()->format('m/d/Y'):'N/A';
    				$row[] = ($resultRow->getFinalDueDate())?$resultRow->getFinalDueDate()->format('m/d/Y'):'N/A';
    				$row[] = ($resultRow->getAmount())?"$".$resultRow->getAmount():'';
    				$row[] = $resultRow->getNumCompletedCycle();
    				$row[] = $resultRow->getNumCompletedCycle();

    				$output['aaData'][] = $row;
    			}
    		}
    	}

    	$response = new Response(json_encode($output));
    	$response->headers->set('Content-Type', 'application/json');

    	return $response;
    }
}
