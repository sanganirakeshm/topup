<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dhi\AdminBundle\Entity\FreeRechargeCard;
use Dhi\AdminBundle\Form\Type\FreeRechargeCardFormType;

class FreeRechargeCardController extends Controller
{
    public function indexAction(Request $request){
        
        if (!($this->get('admin_permission')->checkPermission('free_recharge_card_list') || $this->get('admin_permission')->checkPermission('free_recharge_card_mark_user') || $this->get('admin_permission')->checkPermission('free_recharge_card_export_csv'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view free recharge card list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        return $this->render('DhiAdminBundle:FreeRechargeCard:index.html.twig');
    }
    
    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('Username', 'Email', 'GivenDateTime','GivenBy');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');

        $gridData = $helper->getSearchData($promoCodeColumns);

    	if(!empty($gridData['search_data'])) {
            $this->get('session')->set('freeRechargeCardSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('freeRechargeCardSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'fr.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Username') {

                $orderBy = 'u.username';
            }else if ($gridData['order_by'] == 'Email') {

                $orderBy = 'u.email';
            }else if ($gridData['order_by'] == 'GivenDateTime') {

                $orderBy = 'fr.createdAt';
            }else if ($gridData['order_by'] == 'GivenBy') {

                $orderBy = 'u.username';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiAdminBundle:FreeRechargeCard')->getFreeRechargeCardGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    $username = 'N/A';
                    if(!empty($resultRow['Username']) && !empty($resultRow['UserId'])){
                        $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['UserId'])) . '">' . $resultRow['Username'] . '</a>';
                    }
                    
                    $row = array();
                    $row[] = $username;
                    $row[] = $resultRow['email'];
                    $row[] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y h:i:s') : 'N/A';
                    $row[] = $resultRow['CreatedBy'];
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
    
    public function exportCsvAction(Request $request) {

        $offset = $request->get("offset");
        if (!$this->get('admin_permission')->checkPermission('free_recharge_card_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $slot = $this->container->getParameter("dhi_admin_export_limit");
        if (!isset($slot) || !isset($offset)) {
            $this->get('session')->getFlashBag()->add('failure', "Invalid Request.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $searchData = array();
        if($this->get('session')->has('freeRechargeCardSearchData') && $this->get('session')->get('freeRechargeCardSearchData') != '') {
            $searchData = $this->get('session')->get('freeRechargeCardSearchData');
        }

        $em = $this->getDoctrine()->getManager();
        $slotArr           = array();
        $slotArr['limit']  = $slot;
        $slotArr['offset'] = $offset;
        $freeRechargeCardData = $em->getRepository('DhiAdminBundle:FreeRechargeCard')->getCsvFreeRechargeCardData($searchData, $slotArr);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export Free Recharge Card to CSV';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export free recharge card to CSV";
        $this->get('ActivityLog')->saveActivityLog($activityLog);


        $response = new StreamedResponse();
        $response->setCallback(function() use($freeRechargeCardData) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array('Username', 'Email', 'Given Date Time','Given By'), ',');
            // Query data from databaseexit;

            foreach ($freeRechargeCardData as $key => $resultRow) {
                
                $username   = $resultRow['Username'];
                $email      = $resultRow['email'];
                $givenDate  = $resultRow['createdAt'] ? $resultRow['createdAt']->format('M-d-Y h:i:s') : 'N/A';
                $givenBy    = $resultRow['CreatedBy'];

                fputcsv($handle, array(
                    $username,
                    $email,
                    $givenDate,
                    $givenBy,
                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'free_recharge_card_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }
    
    public function markUserAction(Request $request){
    
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('free_recharge_card_mark_user')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to mark user for free recharge card.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objFreeRechargeCard = new FreeRechargeCard();
        $form = $this->createForm(new FreeRechargeCardFormType(), $objFreeRechargeCard);

        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            $formIsValid = true;
            $currentMonth = date('M-Y');
            $usernameOrEmail = $request->get('dhi_admin_free_recharge_card')['userId'];
                        
            $objUser = $em->getRepository('DhiUserBundle:User')->getObjectUserByUsernameOrEmail($usernameOrEmail);
            
            if(!empty($usernameOrEmail)){
                if(!$objUser){
                    $this->get('session')->getFlashBag()->add('failure', "User does not exists.");
                    $formIsValid = false;
                }else{

                    $objFreeRechargeCard = $em->getRepository('DhiAdminBundle:FreeRechargeCard')->checkUserClaimedCurrentMonth($objUser->getId());

                    if($objFreeRechargeCard && $formIsValid){

                        $this->get('session')->getFlashBag()->add('failure', "User have already claimed for current month  ".$currentMonth);
                        $formIsValid = false;
                    }

                    $userServiceData = $this->getFirstPurchasePackage($usernameOrEmail);
                    
                    if(empty($userServiceData) && $formIsValid){

                        $this->get('session')->getFlashBag()->add('failure', "User is not eligible for Free Recharge Card for current month ". $currentMonth);
                        $formIsValid = false;
                    }
                }
            }
            
            if ($form->isValid() && $formIsValid) {
                
                $objUserService = $em->getRepository('DhiUserBundle:UserService')->find($userServiceData['userServiceId']);
                
                $ipAddress = $this->get('session')->get('ipAddress');
                $objFreeRechargeCard= $form->getData();
                $objFreeRechargeCard->setUserId($objUser->getId());
                $objFreeRechargeCard->setCreatedBy($admin->getId());
                $objFreeRechargeCard->setIpAddress($ipAddress);
                $objFreeRechargeCard->setUserService($objUserService);
                $em->persist($objFreeRechargeCard);
                $em->flush();
                
                $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $userServiceData['packageId']));
                if($objPackage){
                    $planName = $objPackage->getPackageName();
                }
                
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Mark User for Free Recharge Card';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has marked '". $usernameOrEmail ."' for free recharge card for month: " .$currentMonth. " PlanName : " . $planName;
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                 
                $this->get('session')->getFlashBag()->add('success', 'User marked for Free Recharge Card successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_free_recharge_card_list'));
            }
        }
        return $this->render('DhiAdminBundle:FreeRechargeCard:markUser.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    public function checkEligibilityAction(Request $request){
        
        $usernameOrEmail = $request->get('usernameOrEmail');
        $em = $this->getDoctrine()->getManager();
        if(!empty($usernameOrEmail)){
            
            $currentMonth = date('M-Y');
            
            $objUser = $em->getRepository('DhiUserBundle:User')->getObjectUserByUsernameOrEmail($usernameOrEmail);
            
            if(!$objUser){
                $result = array('result' => 'error', 'errMsg' => 'User does not exists.');
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
            
            $objFreeRechargeCard = $em->getRepository('DhiAdminBundle:FreeRechargeCard')->checkUserClaimedCurrentMonth($objUser->getId());
            if($objFreeRechargeCard){
                $result = array('result' => 'error', 'errMsg' => 'User has already claimed for Free Recharge Card in current month '. $currentMonth);
                $response = new Response(json_encode($result));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
            
            $jsonResponse = array();
            $jsonResponse['result']    = 'error';
            $jsonResponse['succMsg']   = '';
            $jsonResponse['errMsg']    = 'User is not eligible for Free Recharge Card for current month '.$currentMonth;
            
            $userServiceData = $this->getFirstPurchasePackage($usernameOrEmail);
            
            if(!empty($userServiceData)){
                
                $jsonResponse['result']    = 'success';
                $jsonResponse['succMsg']   = 'User is eligible for Free Recharge Card for current month '.$currentMonth;
            }
            
            
            $response = new Response(json_encode($jsonResponse));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
    }
    
    public function getFirstPurchasePackage($usernameOrEmail){
        
        $em = $this->getDoctrine()->getManager();
        
        $arrFreeRechargeCardPackage = array();
            
        $freeRechargeCardPackage = $em->getRepository('DhiAdminBundle:Package')->getFreeRechargeCardPackages();
        if(!empty($freeRechargeCardPackage)){
            $arrFreeRechargeCardPackage = array_map(function($freeRechargeCardPackage){ return $freeRechargeCardPackage['packageId']; } , $freeRechargeCardPackage);
        }

        $arrParams = array(
            'usernameOrEmail' => $usernameOrEmail,
            'freeRechargeCardPackege' => $arrFreeRechargeCardPackage
        );

        $userServiceData = $em->getRepository('DhiUserBundle:UserService')->checkUserEligibleFreeRechargeCard($arrParams);
        
        if(!empty($userServiceData)){
            return $userServiceData[0];
        }
        return false;
    }
}
