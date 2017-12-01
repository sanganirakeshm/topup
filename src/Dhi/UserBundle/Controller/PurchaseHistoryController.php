<?php

namespace Dhi\UserBundle\Controller;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;

/**
 *
 */
class PurchaseHistoryController extends Controller {

    // User purchase history
    public function purchaseHistoryAction(Request $request) {

        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        return $this->render('DhiUserBundle:PurchaseHistory:purchaseHistory.html.twig');
    }

    public function purchaseHistoryJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $request  = $this->getRequest();
        $user     = $this->get('security.context')->getToken()->getUser();
        $em       = $this->getDoctrine()->getManager();
        $helper   = $this->get('grid_helper_function');

        $aColumns = array('id', 'orderNumber','transactionId','purcasedService', 'paymentMethod', 'paymentStatus', 'totalAmount', 'refundAmount', 'purchaseDate', 'purchaseId');

        $gridData = $helper->getSearchData($aColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'po.id';
            $sortOrder = 'DESC';

        } else {


            if ($gridData['order_by'] == 'orderNumber') {

                $orderBy = 'po.orderNumber';
            }
            if ($gridData['order_by'] == 'transactionId') {

                $orderBy = 'pp.paypalTransactionId';
            }


            if ($gridData['order_by'] == 'paymentStatus') {

                $orderBy = 'po.paymentStatus';
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


        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $data  = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getPurchaseHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $user, "",  '', '', null, array('Completed', 'Refunded', 'Expired', 'Voided', 'Refunded After Expired'));

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
                    $isOnlyCredit = true;
                    if($resultRow->getServicePurchases()){

                        $purchasedTypeArr = array();
                        foreach ($resultRow->getServicePurchases() as $servicePurchase){

                            if($servicePurchase->getIsCredit()){

                                $purchasedTypeArr[] = 'Credit';
                            }else if($servicePurchase->getIsCompensation()){

                                $purchasedTypeArr[] = 'Compensation';
                                $isOnlyCredit = false;
                            }else if($servicePurchase->getService()){

                                $purchasedTypeArr[] = strtoupper($servicePurchase->getService()->getName());
                                $isOnlyCredit = false;
                            }

                            if(count($purchasedTypeArr) > 0){

                                $purchasedService = implode('<br/>', array_unique($purchasedTypeArr));
                            }
                        }

                    }

                    $paymentMethod = '';
                    $transactionId = 'N/A';
                    if($resultRow->getPaymentMethod()){

                        $paymentMethod = $resultRow->getPaymentMethod()->getName();

                        if($resultRow->getPaymentMethod()->getCode() == 'PayPal' || $resultRow->getPaymentMethod()->getCode() == 'CreditCard'){

                            if($resultRow->getPaypalCheckout()) {

                                $transactionId = $resultRow->getPaypalCheckout()->getPaypalTransactionId();
                            }
                        }

                        if($resultRow->getPaymentMethod()->getCode() == 'Milstar'){

                            if($resultRow->getMilstar()) {

                                $transactionId = $resultRow->getMilstar()->getAuthTicket();
                            }
                        }

                        if(strtolower($resultRow->getPaymentMethod()->getCode()) == 'chase'){

                            if($resultRow->getChase()) {

                                $transactionId = $resultRow->getChase()->getChaseTransactionId();
                            }
                        }
                    }else{

                        if($isOnlyCredit){

                            if($resultRow->getPaymentBy() == 'Admin'){

                                if ($resultRow->getUserCreditLogs()) {

                                    foreach ($resultRow->getUserCreditLogs() as $userCreditLog){

                                        $paymentMethod = $userCreditLog->getType();
                                        if($userCreditLog->getType() == 'EagleCash'){
                                            $paymentMethod .= '<br/>('.$userCreditLog->getEagleCashNo().')';
                                        }

                                    }
                                }else{
                                    $paymentMethod = 'Pay By Admin';
                                }

                            }
                        }
                    }


                    $row = array();
                    $row[] = '';
                    $row[] = $resultRow->getOrderNumber();
                    $row[] = $transactionId;
                    $row[] = $purchasedService;
                    $row[] = $paymentMethod;
                    $row[] = $resultRow->getPaymentStatus() == "Expired" ? "Plan Expired by Customer Support" : $resultRow->getPaymentStatus();
                    $row[] = ($resultRow->getTotalAmount())?"$".$resultRow->getTotalAmount():'';
                    $row[] = ($resultRow->getRefundAmount())?"$".$resultRow->getRefundAmount():'';
                    $row[] = ($resultRow->getCreatedAt())?$resultRow->getCreatedAt()->format('M-d-Y H:i:s'):'';
                    $row[] = ($resultRow->getPaypalRecurringProfile())?$resultRow->getPaypalRecurringProfile()->getId():'';
                    $row[] = $resultRow->getId();
                    $output['aaData'][] = $row;
                }
            }
        }


        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function expandedPurchaseHistoryAction(Request $request){

        $poId = $request->get('poId');
        $em   = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        $purchaseHistoryDetail = array();
        if($poId){

            $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->getExpandedPurchaseDetail($poId);

            if($purchaseOrder){

                if($purchaseOrder->getServicePurchases()){

                    $i = 0;
                    foreach ($purchaseOrder->getServicePurchases() as $servicePurchased){

                        $tempArr = array();
                        $tempArr['packageName']         = $servicePurchased->getPackageName();
                        $tempArr['packageActualAmount'] = $servicePurchased->getActualAmount();
                        $tempArr['packagePaybleAmount'] = $servicePurchased->getPayableAmount();
                        $tempArr['paymentStatus']       = $servicePurchased->getPaymentStatus();
                        $tempArr['activationStatus']    = $servicePurchased->getActivationStatus();
                        $tempArr['serviceLocation']    = $servicePurchased->getServiceLocationId() ? $servicePurchased->getServiceLocationId()->getName() : 'N/A';
                        $tempArr['activationDate']		= '';
                        $tempArr['expiryDate']			= '';
                        $tempArr['service']  			= '';
                        $tempArr['validity']			= '';

                        if($servicePurchased->getService() && $servicePurchased->getIsCompensation() != 1){

                            if($servicePurchased->getIsAddon() == 1){

                                $serviceName = 'AddOn';
                            }else{

                                $serviceName = strtoupper($servicePurchased->getService()->getName());
                            }

                            $purchaseHistoryDetail[$serviceName][$servicePurchased->getId()] = $tempArr;
                        }

                        if($servicePurchased->getIsCredit() == 1 && $servicePurchased->getCredit()){

                            $credit = $servicePurchased->getCredit()->getCredit();
                            $tempArr['packageName'] = $credit .' ExchangeVUE Credits';
                            //$tempArr['packageName'] = 'Pay $'.$servicePurchased->getPayableAmount().' and get '.$credit.' credit in your account.';
                            $purchaseHistoryDetail['Credit'][$servicePurchased->getId()] = $tempArr;
                        }

                        if($servicePurchased->getIsCompensation() == 1){

                            $tempArr['service']  = strtoupper($servicePurchased->getService()->getName());
                            $tempArr['validity'] = $purchaseOrder->getCompensationValidity();
                            $purchaseHistoryDetail['Compensation'][$servicePurchased->getId()] = $tempArr;
                        }

                        $i++;
                    }
                }

                if($purchaseOrder->getUserService()){

                    foreach ($purchaseOrder->getUserService() as $userService){

                        if($userService->getService()){

                            if($userService->getIsAddon() == 1){

                                $serviceName = 'AddOn';
                            }else{

                                $serviceName = strtoupper($userService->getService()->getName());
                            }

                            if($userService->getServicePurchase()){

                                $servicePurchaseId = $userService->getServicePurchase()->getId();

                                if($userService->getActivationDate()){

                                    $activationDate = $userService->getActivationDate()->format('m/d/Y');

                                    $purchaseHistoryDetail[$serviceName][$servicePurchaseId]['activationDate'] = $activationDate;
                                }

                                if($userService->getExpiryDate()){

                                    $expiryDate = $userService->getExpiryDate()->format('m/d/Y');

                                    $purchaseHistoryDetail[$serviceName][$servicePurchaseId]['expiryDate'] = $expiryDate;
                                }
                            }
                        }
                    }
                }

            }
        }
        //echo "<pre>";print_r($purchaseHistoryDetail);exit;
        $view = array();
        $view['purchaseHistoryDetail'] = $purchaseHistoryDetail;
        return $this->render('DhiUserBundle:PurchaseHistory:expandedPurchaseHistory.html.twig', $view);
    }

    public function exportpdfAction(Request $request) {

        $isSecure   = $request->isSecure() ? 'https://' : 'http://';
        $user = $this->get('security.context')->getToken()->getUser(); //Login User Object
        $em = $this->getDoctrine()->getManager(); //Entity Manager

        $brandHeaderLogo = '';
        if($this->get('session')->has('brand'))
        {
            $whiteLabelBrand = $this->get('session')->get('brand');
            $brandHeaderLogo = $whiteLabelBrand['headerLogo'];
        }
        
        $dhiLogoImg = $isSecure.$this->getRequest()->getHost().$this->container->get('templating.helper.assets')->getUrl('uploads/whitelabel/headerlogo/'.$brandHeaderLogo);
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/uploads/whitelabel/headerlogo/'.$brandHeaderLogo;

        //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Export PDF';
        $activityLog['description']  = "User '".$user->getUsername()."' has export pdf for purchase history.";

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        //End here

        $rootDirPath = $this->container->get('kernel')->getRootDir(); //Get Application Root DIR path
        $file_name = 'purchase_'.$user->getUserName().'_'.date('m-d-Y', time()).'.pdf';// Create pdf file name for download

        //Get Purchase History Data
        $purchaseHistoryData = $this->get('DashboardSummary')->getPrintPurchaseHistoryData($user);

        // create html to pdf
        $pdf = $this->get("white_october.tcpdf")->create();

        // set document information
        $pdf->SetCreator('ExchangeVUE');
        $pdf->SetAuthor('ExchangeVUE');
        $pdf->SetTitle('ExchangeVUE');
        $pdf->SetSubject('Purchase History');

        // set default header data
        if(file_exists($logoImgDirPath)){

            $pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="'.$dhiLogoImg.'" />');
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

        # Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>'.$stylesheet.'</style>';
        $html .= $this->renderView('DhiUserBundle:PurchaseHistory:exportPdf.html.twig',
                array('purchaseData' => $purchaseHistoryData)
        );
        // output the HTML content
        $pdf->writeHTML($html);
        // reset pointer to the last page
        $pdf->lastPage();
        //Close and output PDF document
        $pdf->Output($file_name, 'D');
        exit;
    }

    public function printAction(Request $request) {

        $isSecure   = $request->isSecure() ? 'https://' : 'http://';
        $user = $this->get('security.context')->getToken()->getUser(); //Login User Object
        $em = $this->getDoctrine()->getManager(); //Entity Manager
        
        $brandHeaderLogo = '';
        if($this->get('session')->has('brand'))
        {
            $whiteLabelBrand = $this->get('session')->get('brand');
            $brandHeaderLogo = $whiteLabelBrand['headerLogo'];
        }
        
        $dhiLogoImg = $isSecure.$this->getRequest()->getHost().$this->container->get('templating.helper.assets')->getUrl('uploads/whitelabel/headerlogo/'.$brandHeaderLogo);
        
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        //Check Email Verified
        if (!$user->getIsEmailVerified()) {

            $tokenGenerator = $this->get('fos_user.util.token_generator');
            $token = $tokenGenerator->generateToken();
            $user->setConfirmationToken($token);
            $user->setEmailVerificationDate(new \DateTime());

            $em->persist($user);
            $em->flush();
        }

        //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user;
        $activityLog['activity']     = 'Print purchase history';
        $activityLog['description']  = "User '".$user->getUsername()."' has print purchase history.";

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        //End here

        //Get Purchase History Data
        $purchaseHistoryData = $this->get('DashboardSummary')->getPrintPurchaseHistoryData($user);

        return $this->render('DhiUserBundle:PurchaseHistory:print.html.twig',
                array('purchaseData' => $purchaseHistoryData,
                        'img' => $dhiLogoImg)
        );
    }

    public function viewRecurringProfileAction(Request $request) {

    	$recurringProfileId = $request->get('id');
    	$user = $this->get('security.context')->getToken()->getUser();
    	$em = $this->getDoctrine()->getManager();

    	$objRecurringProfile = $em->getRepository('DhiServiceBundle:PaypalRecurringProfile')->find($recurringProfileId);

    	$viewData = array();
    	$viewData['recurringProfileId'] = $recurringProfileId;
    	$viewData['user'] = $user;
    	$viewData['objRecurringProfile'] = $objRecurringProfile;

    	return $this->render('DhiUserBundle:PurchaseHistory:recurringHistory.html.twig',$viewData);
    }

    public function viewRecurringProfileJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

    	$request  = $this->getRequest();
    	$user     = $this->get('security.context')->getToken()->getUser();
    	$em       = $this->getDoctrine()->getManager();
    	$helper   = $this->get('grid_helper_function');
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

    	$data  = $em->getRepository('DhiServiceBundle:RecurringPaymentLog')->getRecurringHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $recurringProfileId, $user);

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
