<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \DateTime;

class RefundExpiredPlansController extends Controller {

    public function indexAction($id) {
        if (
        	!(
        		$this->get('admin_permission')->checkPermission('refund_expired_plans_list') || 
        		$this->get('admin_permission')->checkPermission('refund_expired_plans_refund')
        	)
        ) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view expired plans of user.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

		$admin = $this->get('security.context')->getToken()->getUser();
		$em    = $this->getDoctrine()->getManager();
		$user  = $em->getRepository("DhiUserBundle:User")->find($id);
        if (!$user) {
        	$this->get('session')->getFlashBag()->add('failure', "User does not exists");
            return $this->redirect($this->generateUrl('dhi_admin_user_list'));
        }

        return $this->render(
            'DhiAdminBundle:RefundExpiredPlans:index.html.twig', 
            array(
                'admin' => $admin, 
                'id' => $id
            )
        );
    }

    public function listJsonAction($id, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $promoCodeColumns = array('Service', 'PackageName', 'Validity', 'ServiceLocation', 'PackageAmount', 'DiscountAmount', 'PaidAmount', 'ActivatedDate', 'ExpiryDate');
		$admin     = $this->get('security.context')->getToken()->getUser();
		$helper    = $this->get('grid_helper_function');
		$gridData  = $helper->getSearchData($promoCodeColumns);
		$sortOrder = $gridData['sort_order'];
		$orderBy   = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Service') {
                $orderBy = 'service';
            }else if ($gridData['order_by'] == 'ServiceLocation') {
                $orderBy = 'serviceLocation';
            }else if ($gridData['order_by'] == 'PackageName') {
                $orderBy = 'packageName';
            }else if ($gridData['order_by'] == 'PackageAmount') {
                $orderBy = 'actualAmount';
            }else if ($gridData['order_by'] == 'DiscountAmount') {
                $orderBy = 'totalDiscount';
            }else if ($gridData['order_by'] == 'PaidAmount') {
                $orderBy = 'payableAmount';
            }else if ($gridData['order_by'] == 'ActivatedDate') {
                $orderBy = 'activationDate';
            }else if ($gridData['order_by'] == 'ExpiryDate') {
                $orderBy = 'expiryDate';
            }
        }

        // Paging
		$per_page   = $gridData['per_page'];
		$offset     = $gridData['offset'];
		$em         = $this->getDoctrine()->getManager();
		
        $data = $this->getRefundReportGridList($offset, $per_page, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, true, $id);

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
                    $isExtendedOrRefunded = $em->getRepository("DhiUserBundle:UserService")->isRefundedorExtendedPlan($resultRow['id'], $resultRow['userId'], $resultRow['purchaseOrderId']);

                    $service = (($resultRow['isAddon'] == 1) ? 'IPTVPremium' : $resultRow['service']);
                    $activationDate = new \DateTime($resultRow['activationDate']);
                    $expiryDate = new \DateTime($resultRow['expiryDate']);

					$row                = array();
					$row[]              = $service;
					$row[]              = $resultRow['packageName'];
					$row[]              = $resultRow['validity'].' '. (($resultRow['validityType'] == 'DAYS') ? 'Day(s)' : 'Hour(s)');
					$row[]              = (!empty($resultRow['serviceLocation']) ? $resultRow['serviceLocation'] : 'N/A');
					$row[]              = '$'.$resultRow['actualAmount'];
					$row[]              = (!empty($resultRow['totalDiscount']) ? $resultRow['totalDiscount'] : 0);
					$row[]              = '$'.$resultRow['payableAmount'];
					$row[]              = $activationDate->format('m/d/Y H:i:s');
					$row[]              = $expiryDate->format('m/d/Y H:i:s');
                    $row[]              = $resultRow['id'];
					$row[]              = $isExtendedOrRefunded;
					$row[]              = $resultRow['suspended_status'];
					$output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function refundAction($id, Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $defaultRefundType = array('ISP', 'IPTV', 'IPTVPremium', 'BUNDLE');
        $errorMsg = '';
        if (!$this->get('admin_permission')->checkPermission('refund_expired_plans_refund')) {
            $errorMsg = 'You are not allowed to refund expired plans of user.';
        }

        $userId              = $id;
        $userServiceId       = $request->get('userServiceId');
        $packageType         = $request->get('packageType');
        $confirmPage         = $request->get('confirmPage');
        $submitRefundPayment = $request->get('submitRefundPayment');
        $refundAmount        = $request->get('processAmount');
        $finalRefundAmount   = $request->get('finalRefundAmount');

        if (!$userId && !in_array($packageType, $defaultRefundType)) {
            $errorMsg = 'You are not allowed to refund the amount.';
        }

        $user = $em->getRepository("DhiUserBundle:User")->find($userId);
        if (!$user) {
            $errorMsg = 'User does not exists';
        }

        if (!empty($errorMsg)) {
            $view = array('error' => $errorMsg, 'id' => $id);
            if ($confirmPage) {
                return $this->render('DhiAdminBundle:RefundExpiredPlans:confirmRefundPayment.html.twig', $view);
            } else {
                return $this->render('DhiAdminBundle:RefundExpiredPlans:refundPayment.html.twig', $view);
            }
        }

        $isAddOn = 0;
        $serviceTypeArr = array();
        if ($packageType == 'ISP' || $packageType == 'BUNDLE') {
            $serviceTypeArr = array('ISP', 'IPTV');

        }if ($packageType == 'IPTV') {
            $serviceTypeArr = array('IPTV');

        }else if ($packageType == 'IPTVPremium') {
            $serviceTypeArr = array('IPTV');
            $isAddOn = 1;
        }

        $refundSummary = $em->getRepository("DhiUserBundle:UserService")->getExpiredPlansForRefund($serviceTypeArr, $userServiceId);

        if ($request->isXmlHttpRequest() && ($submitRefundPayment == 1 || $confirmPage == 1)) {
            $displayJsonResponse    = false;
            $jsonResponse           = array();
            $jsonResponse['status'] = 'failed';
            $jsonResponse['msg']    = 'Error No: #1003, Something went wrong in ajax request. please again';

            if ($refundAmount >= 0) {
                if ($refundAmount > $refundSummary['TotalActualAmt']) {
                    $jsonResponse['msg'] = 'Refund amount $' . $refundAmount . ' is not valid.';
                    $displayJsonResponse = true;
                }
            } else {
                $jsonResponse['msg'] = 'Please check refund amount.';
                $displayJsonResponse = true;
            }

            $refundServiceId = $request->get('refundServiceId');
            if ($submitRefundPayment == 1) {
                $displayJsonResponse = true;
                $isPaymentRefundedSuccess = false;

                if ($refundAmount == 0 && $refundSummary['TotalRefundAmt'] == 0) {

                    $isPaymentRefundedSuccess = true;
                } else {

                    $isPaymentRefundedSuccess = true;
                }

                if ($isPaymentRefundedSuccess) {

                    $isRefundedAnyOnePack = false;
                    $purchaseOrderDetail = array();
                    $i = 0;

                    $userServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServiceFromIds($refundServiceId, 0);

                    if ($userServices) {
                        $refundAmo = $refundAmount / count($userServices);
                        foreach ($userServices as $userService) {
                            if ($userService->getPurchaseOrder() && $i == 0) {
                                $purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] = $userService->getPurchaseOrder()->getRefundAfterExpiredAmount();
                            }
                           
                            if ($userService->getService() ) {
                                $serviceName = strtoupper($userService->getService()->getName());

                                if (in_array($serviceName, $serviceTypeArr)) {

                                    //Update user service data
                                    $userService->setRefundAfterExpired(1);
                                    $userService->setRefundAfterExpiredAmount($refundAmo);
                                    $userService->setRefundAfterExpiredBy($admin);
                                    $userService->setRefundAfterExpiredAt(new \DateTime());
                                    $em->persist($userService);

                                    //Update service purchase data
                                    $servicePurchase = $userService->getServicePurchase();
                                    if ($servicePurchase) {
                                        $servicePurchase->setPaymentStatus('Refunded After Expired');
                                        $servicePurchase->setRechargeStatus(2);
                                        $em->persist($servicePurchase);
                                    }

                                    if ($userService->getPurchaseOrder()) {
                                        $purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] += $refundAmo;
                                    }

                                    $isRefundedAnyOnePack = true;
                                }
                            }
                            $i++;
                        }

                        if ($isRefundedAnyOnePack && $purchaseOrderDetail) {
                            foreach ($purchaseOrderDetail as $key => $val) {
                                $totalRefundAmount = $val['totalRefundAmount'];
                                $purchaseOrder     = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($key);
                                if ($purchaseOrder) {
                                    $purchaseOrder->setPaymentStatus('Completed');
                                    $purchaseOrder->setRefundAfterExpiredAmount($totalRefundAmount);
                                    $em->persist($purchaseOrder);
                                }
                            }
                            $jsonResponse['status'] = 'success';
                            $jsonResponse['msg'] = "$" . $refundAmount . " has been refunded successfully.";
                        }
                        $em->flush();
                    } else {

                        $jsonResponse['msg'] = 'Data not exist for refund payment.';
                    }
                }
            }

            if ($displayJsonResponse) {
                $response = new Response(json_encode($jsonResponse));
                return $response;
            }
        }

        $view                      = array();
        $view['refundSummary']     = $refundSummary;
        $view['packageType']       = $packageType;
        $view['userId']            = $userId;
        $view['userServiceId']     = $userServiceId;
        $view['refundAmount']      = $refundAmount;
        $view['finalRefundAmount'] = $finalRefundAmount;
        $view['id']                = $id;

        if ($confirmPage) {
            return $this->render('DhiAdminBundle:RefundExpiredPlans:confirmRefundPayment.html.twig', $view);
        } else {
            return $this->render('DhiAdminBundle:RefundExpiredPlans:refundPayment.html.twig', $view);
        }
    }

    private function getRefundReportGridList($offset = 0, $limit = 10, $orderBy = "id", $sortOrder = "asc", $searchData, $SearchType, $objHelper, $isLimit = true, $userId){
        $con = $this->get('database_connection');
        $em = $this->getDoctrine()->getManager();

        /*if (!empty($searchData['serviceType'])) {
            if ($searchData['serviceType'] == 'BUNDLE') {
                unset($searchData['serviceType']);
            }else{
                $searchData['serviceType'] = 'BUNDLE';
            }
        }*/
        $data = $bundleData = $em->getRepository('DhiUserBundle:UserService')->trimSerachDataForExpiredPlans($searchData, $SearchType);
        if ($SearchType == 'ORLIKE') {
            $likeStr = $objHelper->orLikeSearch($data);
            $bundleLikeStr = $objHelper->orLikeSearch($bundleData);
        }

        if ($SearchType == 'ANDLIKE') {
            $likeStr = $objHelper->andLikeSearch($data, false);
            $bundleLikeStr = $objHelper->andLikeSearch($bundleData, false);
        }

        $likeStr = (!empty($likeStr) ? $likeStr.' AND ' : '') . '(sp.payment_status IN ("Completed", "Expired") AND us.status = 0 AND us.refund = 0 AND (us.refund_after_expired = 0 OR us.refund_after_expired IS NULL) AND u.id = :userId)';
        $bundleLikeStr = (!empty($bundleLikeStr) ? $bundleLikeStr.' AND ' : '').'(sp.payment_status IN ("Completed", "Expired") AND us.status = 0 AND us.refund = 0 AND (us.refund_after_expired = 0 OR us.refund_after_expired IS NULL) AND u.id = :userId)';

        $aWhere = array('userId' => $userId);
        
        $strQry = "SELECT * FROM (SELECT 
                    us.id as id, 
                    s.name as service, 
                    us.package_name as packageName, 
                    us.validity, 
                    sp.actual_amount as actualAmount, 
                    us.payable_amount as payableAmount, 
                    us.expiry_date as expirySate, 
                    sl.name as serviceLocation, 
                    us.activation_date as activationDate, 
                    us.expiry_date as expiryDate,
                    u.id as userId, 
                    us.is_addon as isAddon, 
                    po.id as purchaseOrderId, 
                    us.total_discount as totalDiscount, 
                    sp.validity_type as validityType, 
                    us.purchase_order_id,
                    us.suspended_status
                FROM user_services us
                    INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id
                    INNER JOIN service s ON us.service_id = s.id
                    INNER JOIN purchase_order po on us.purchase_order_id = po.id
                    INNER JOIN dhi_user u on us.user_id = u.id
                    LEFT JOIN service_location sl ON sp.service_location_id = sl.id
                WHERE
                    purchase_type IS NULL AND ".$likeStr."
                UNION 
                SELECT 
                    GROUP_CONCAT(us.id) as id, 
                    'BUNDLE' as service, 
                    GROUP_CONCAT(us.package_name) as packageName,
                    us.validity, 
                    SUM(sp.actual_amount) as actualAmount, 
                    SUM(us.payable_amount) as payableAmount, 
                    GROUP_CONCAT(us.expiry_date) as expirySate, 
                    sl.name as serviceLocation, 
                    us.activation_date as activationDate, 
                    us.expiry_date as expiryDate,
                    u.id as userId, 
                    0 as isAddon, 
                    po.id as purchaseOrderId, 
                    SUM(us.total_discount) as totalDiscount, 
                    sp.validity_type as validityType,
                    us.purchase_order_id,
                    us.suspended_status

                FROM
                    user_services us
                INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id
                INNER JOIN service s ON us.service_id = s.id
                INNER JOIN purchase_order po on us.purchase_order_id = po.id
                INNER JOIN dhi_user u on us.user_id = u.id
                LEFT JOIN service_location sl ON sp.service_location_id = sl.id
                LEFT JOIN bundle b on sp.bundle_id = b.bundle_id
                WHERE
                    purchase_type = 'BUNDLE' AND ".$bundleLikeStr."
                GROUP by purchase_order_id
                ) t ORDER BY ".$orderBy." ".$sortOrder."";


        $countSql = "SELECT SUM(total) as total FROM (SELECT 
                        COUNT(po.id) as total
                    FROM
                        user_services us 
                        INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id 
                        INNER JOIN service s ON us.service_id = s.id 
                        INNER JOIN purchase_order po on us.purchase_order_id = po.id 
                        INNER JOIN dhi_user u on us.user_id = u.id 
                        LEFT JOIN service_location sl ON sp.service_location_id = sl.id 
                    WHERE
                        purchase_type IS NULL AND ".$likeStr."
                    UNION 
                    SELECT 
                        COUNT(distinct po.id) as total
                    FROM user_services us 
                        INNER JOIN service_purchase sp ON us.service_purchase_id = sp.id 
                        INNER JOIN service s ON us.service_id = s.id 
                        INNER JOIN purchase_order po on us.purchase_order_id = po.id 
                        INNER JOIN dhi_user u on us.user_id = u.id 
                        LEFT JOIN service_location sl ON sp.service_location_id = sl.id 
                        LEFT JOIN bundle b on sp.bundle_id = b.bundle_id 
                    WHERE
                        purchase_type = 'BUNDLE' AND ".$bundleLikeStr."
                    ) t ";

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