<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\PromoCode;
use Dhi\UserBundle\Entity\Service;
use Dhi\AdminBundle\Entity\EmployeePromoCode;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Form\Type\PromoCodeFormType;
use Dhi\AdminBundle\Form\Type\EmployeePromoCodeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use \DateTime;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EmployeePromoCodeController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('employee_promo_code_list') || $this->get('admin_permission')->checkPermission('employee_promo_code_create') || $this->get('admin_permission')->checkPermission('employee_promo_code_update') || $this->get('admin_permission')->checkPermission('employee_promo_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view employee promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiUserBundle:Service')->findAll();

        $serviceLocation = array();
        $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();

        $serviceLocationLog = array();
        foreach ($serviceLocation as $activity) {
            $serviceLocationLog[] = $activity['name'];
        }

        $arrActivityLog = array();
        foreach ($data as $key => $activity) {

            $arrActivityLog[] = $activity->getName();
        }

        // get all admin
        $objAdmins = $em->getRepository('DhiUserBundle:User')->getAllEmployee();
        $objemployeeUsername = $em->getRepository('DhiUserBundle:User')->getAllEmployeeUsername();

        return $this->render('DhiAdminBundle:EmployeePromoCode:index.html.twig', array(
                    'serviceLocations' => json_encode($serviceLocationLog),
                    'services' => json_encode($arrActivityLog),
                    'admins' => $objAdmins,
                    'employees' =>json_encode($objemployeeUsername)
        ));
    }

     // promocodelist
    public function employeePromoCodeListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

  
        $promoCodeColumns = array('EmployeeName','EmployeePromoCode', 'CreatedBy','Amount', 'Status',  'NumberOfRedeemtion', 'Note','Action');

//        $promoCodeColumns = array('name', 'Total');

        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        $createdBy = $request->get('createdBy');

        if (!empty($gridData['search_data'])) {
            $this->get('session')->set('employeePromoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('employeePromoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'epc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'EmployeeName') {

				$orderBy = 'epc.employeeName';
			}
            if ($gridData['order_by'] == 'EmployeePromoCode') {

				$orderBy = 'epc.employeePromoCode';
			}
            if ($gridData['order_by'] == 'CreatedBy') {
            	$orderBy = 'epc.createdAt';
			}
            if ($gridData['order_by'] == 'Amount') {
				$orderBy = 'epc.amount';
			}
            if ($gridData['order_by'] == 'Status') {
				$orderBy = 'epc.status';
			}
            if ($gridData['order_by'] == 'Note') {
				$orderBy = 'epc.note';
			}
        }
//       $response = new Response(json_encode($gridData));
//        $response->headers->set('Content-Type', 'application/json');
//
//        return $response;
        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->getEmployeePromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $createdBy);

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
                    // get the employee as per
//                    if(array_key_exists('Total', $gridData['search_data'])){
//                        $response = new Response(json_encode($gridData));
//                        $response->headers->set('Content-Type', 'application/json');
//
//                        return $response;
//                    }
//                    else{
//                         $response = new Response(json_encode("ds"));
//                        $response->headers->set('Content-Type', 'application/json');
//
//                        return $response;
//                    }
//                    $innerContent = $this->getEmployeeCodeByServiceLocation($resultRowArray->getId(),$gridData['search_data']);
//                        $response = new Response(json_encode($innerContent));
//            $response->headers->set('Content-Type', 'application/json');
//
//            return $response;
                    //amount type
                    $amountType = $resultRow->getAmountType()? $resultRow->getAmountType() : 'N/A';
                    $discountValue = ($amountType == 'amount')? '$'.$resultRow->getAmount() : $resultRow->getAmount(). '%';

                    // get count forcode
                     $objCustomerRedeem = $em->getRepository('DhiAdminBundle:EmployeePromoCodeCustomer')->findby(array('EmployeePromoCodeId' => $resultRow,'status' => 0));
                     $redemcount = count($objCustomerRedeem);
                             
                    $actualNotes = $resultRow->getNote();
                    $shortNote  = null;
                    if(strlen($actualNotes) > 10){
                        $shortNote = substr($actualNotes, 0, 10).'...';
                    }else{
                        $shortNote = $resultRow->getNote();
                    }

                    $shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $resultRow->getId() .');">' . $shortNote. '</a>';
                    
                    $row = array();
                    $row[0] = $resultRow->getEmployeeName();
                    $row[1] = $resultRow->getEmployeePromoCode();
                    $row[2] = $resultRow->getcreatedBy();
                    $row[3] = $discountValue;
                    $row[4] = $resultRow->getStatus() == true ? 'Active':'Inactive';
                    $row[5] = $redemcount;
                    $row[6] = $shortNote;
                    $row[7] = $resultRow->getId();


                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function employeePromoCodeReportAction(Request $request){
        //	Check permission

         if (!($this->get('admin_permission')->checkPermission('employee_promo_code_report') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view employee promo code report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiUserBundle:Service')->findAll();

        $serviceLocation = array();
        $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();

        $serviceLocationLog = array();
        foreach ($serviceLocation as $activity) {
            $serviceLocationLog[] = $activity['name'];
        }

        $arrActivityLog = array();
        foreach ($data as $key => $activity) {

            $arrActivityLog[] = $activity->getName();
        }
        // get all admin
        $objAdmins = $em->getRepository('DhiUserBundle:User')->getAllCustomer();

        return $this->render('DhiAdminBundle:EmployeePromoCode:employeePromoCodeReport.html.twig', array(
                    'serviceLocations' => json_encode($serviceLocationLog),
                    'services' => json_encode($arrActivityLog),
                    'admins' => $objAdmins
        ));
    }
    //report done
    public function employeePromoCodeReportJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0){

//        $promoCodeColumns = array('EmployeeNumber','EmployeePromoCode', 'CreatedBy','Amount', 'Status',  'NumberOfRedeemtion', 'Action');


        $promoCodeColumns = array('name', 'Total');

        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        $createdBy = $request->get('createdBy');

        if (!empty($gridData['search_data'])) {
            //print_r($gridData['search_data']);
            $this->get('session')->set('employeePromoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('employeePromoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'epc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'name') {

				$orderBy = 'sl.name';
			}
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getActiveEmployePromCodeReportGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, 0, 0);
        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {

                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );

                foreach ($data['result'] AS $resultRowArray) {
                    $innerContent = $this->getEmployeeCodeByServiceLocation($resultRowArray->getId(),$gridData['search_data']);
                    $row = array();
                    $row[] = $resultRowArray->getName();
                    $row[] = $innerContent['innerhtml'];
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function viewCustomerAction(Request $request) {

        if (!($this->get('admin_permission')->checkPermission('employee_promo_code_customer_view') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view employee promo code customer.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $id = $request->get('id');
        return $this->render('DhiAdminBundle:EmployeePromoCode:viewCustomer.html.twig', array('promoid' => $id));
    }

    //updated
    public function employeePromoCodeCustomerListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $id = $request->get('id');
        $promoCodeColumns = array('customer', 'employeePromoCode', 'redemptionTime');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);

        if (!empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'epcc.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'customer') {

                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'employeePromoCode') {

                $orderBy = 'epc.employeePromoCode';
            }
            if ($gridData['order_by'] == 'redemptionTime') {

                $orderBy = 'epcc.redeemDate';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:EmployeePromoCodeCustomer')->getEmployeePromoCodeCustomerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $id);
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
                    if ($resultRow->getUser()) {
                        $userName = $resultRow->getUser()->getUsername();
                    } else {
                        $userName = 'N/A';
                    }
                    $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow->getUser()->getId())) . '">' . $userName . '</a>';
                    $row[] = $username;
                    $row[] = $resultRow->getEmployeePromoCodeId()->getEmployeePromoCode();
                    $row[] = ($resultRow->getRedeemDate()) ? $resultRow->getRedeemDate()->format('Y-m-d H:i:s') : 'N/A';
                    $output['aaData'][] = $row;
                }
            }
        }


        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    // updated
    function getEmployeeCodeByServiceLocation($serviceLoction,$searchData) {
		$em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->getCountByServiceLocation($serviceLoction,$searchData);
        
        $html = '';
        $totalTransaction = 0;
         if (isset($data) && !empty($data)) {
			if (isset($data['result']) && !empty($data['result'])) {
                $html = "<table style='margin-bottom: 0 !important;' class='table table-bordered table-hover'>"
				. "<tbody><tr><th>Employee Name</th><th>Total Codes</th><th>Total Redemptions</th><th>Percentage</th><th>Rank</th></tr>";
                $output = array(
					"sEcho" => intval($_GET['sEcho']),
					"iTotalRecords" => count($data['result']),
					"iTotalDisplayRecords" => 0,
					"aaData" => array()
				);

                $totalRedeemption = 0;
                $totalRedeempArr = array_map(
                    function($var){
                        return $var['total_redemption'];
                    }
                , $data['result']);

                $totalRedeemption = array_sum($totalRedeempArr);
                $totalRedeempArr  = array_unique($totalRedeempArr);
                rsort($totalRedeempArr);

                foreach ($data['result'] as $Key => $employeeData) {
                    $html .="<tr><td>" . (!empty($employeeData['emp_name']) ? $employeeData['emp_name'] : 'N/A') . "</td><td>" . $employeeData['total_promocodes'] . "</td><td>" . $employeeData['total_redemption'] . "</td><td>". ($totalRedeemption > 0 ? number_format($employeeData['total_redemption'] * 100 / $totalRedeemption,2) : 0) ."%</td><td>".(array_search ($employeeData['total_redemption'], $totalRedeempArr)+1)."</td></tr>";
                }

                $html .="<tr><td colspan='4'><b>Total Transaction</b></td><td><b>".$totalRedeemption."</b></td></tr><tr></tr></tbody></table>";
            }else{
                $html .="<h5>No code found for this service location.</h5>";
            }
        }else{
                $html .="<h5>No code found for this service location.</h5>";
        }
       
       
        return array('innerhtml' => $html);
    }

    // updated
    public function newAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('employee_promo_code_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add employee promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objEmployeePromoCode = new EmployeePromoCode();

        // get all employees
        $objAdmins = $em->getRepository('DhiUserBundle:User')->getAllEmployee();
        $form = $this->createForm(new EmployeePromoCodeFormType($objAdmins), $objEmployeePromoCode);

        $form->add('noOfCodes', 'text', array('mapped' => false));

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();
                // get all submitted data
                $employeeCodeDetail = $request->get('dhi_admin_employee_promo_code');
                for ($a = 0; $a < $employeeCodeDetail['noOfCodes']; $a++) {
                    // generating code
                    $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                    $string = '';
                    $random_string_length = 5;
                    for ($i = 0; $i < $random_string_length; $i++) {
                        $string .= $characters[rand(0, strlen($characters) - 1)];
                    }
                    // add new code
                    $objEmployeePromoCode = new EmployeePromoCode();
                    $objEmployeePromoCode->setCreatedBy($admin->getUsername())
                            ->setEmployeePromoCode($string)
                            ->setEmployeeName($employeeCodeDetail['employeeName'])
//                            ->setReason($employeeCodeDetail['reason'])
                            ->setNote($employeeCodeDetail['note'])
                            ->setStatus($employeeCodeDetail['status'])
                            ->setAmountType($employeeCodeDetail['amountType'])
                            ->setAmount($employeeCodeDetail['amount']);
                    $em->persist($objEmployeePromoCode);
                    $em->flush();

                    // set audit log add promo code
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add Employee Promo Code';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added employee promo code " . $string;
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                }
                $this->get('session')->getFlashBag()->add('success', 'Employee Promo Code added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_employee_promo_code_list'));
            }
        }

        return $this->render('DhiAdminBundle:EmployeePromoCode:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    // updated
    public function previewEmployeePromoCodeAction(Request $request) {

        if (!$this->get('admin_permission')->checkPermission('employee_promo_code_preview')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to preview employee promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $data = array();
        $noOfCodes = '';
        if ($request->getMethod() == "POST") {
            $data = $request->get('dhi_admin_employee_promo_code');
        }
        return $this->render('DhiAdminBundle:EmployeePromoCode:previewEmployeePromoCode.html.twig', array('data' => $data));
    }

    // updated
    public function editAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('employee_promo_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update employee promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        // get all employees
        $objAdmins = $em->getRepository('DhiUserBundle:User')->getAllEmployee();

        $objEmployeePromoCode = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->find($id);
        $objEmployeePromoCode->setReason('');
        $objEmployeePromoCode->setNote('');
        $form = $this->createForm(new EmployeePromoCodeFormType($objAdmins), $objEmployeePromoCode);
        
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();
                 $employeeCodeDetail = $request->get('dhi_admin_employee_promo_code');
                $objEmployeePromoCode->setEmployeeName($employeeCodeDetail['employeeName'])
//                            ->setReason($employeeCodeDetail['reason'])
                            ->setNote($employeeCodeDetail['note'])
                            ->setStatus($employeeCodeDetail['status'])
                            ->setAmountType($employeeCodeDetail['amountType'])
                            ->setAmount($employeeCodeDetail['amount']);
                $em->persist($objEmployeePromoCode);
                $em->flush();
                
                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update Employee Promo Code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated employee promo code with id = " . $id;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Employee Promo Code updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_employee_promo_code_list'));
            }
        }

        return $this->render('DhiAdminBundle:EmployeePromoCode:edit.html.twig', array(
                    'form' => $form->createView(),
                    'promo' => $objEmployeePromoCode
        ));
    }

    //updated
    public function disableAction(Request $request, $id) {


        //Check permission
        if (!$this->get('admin_permission')->checkPermission('employee_promo_code_status_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update employee promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->find($id);

        if (!$objPromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find employee promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_employee_promo_code_list'));
        }
        if ($objPromoCode->getStatus() == 1) {
            $objPromoCode->setStatus(0);
            $changeStatus = 'Disabled';
        } else {
            $objPromoCode->setStatus(1);
            $changeStatus = 'Enabled';
        }

        $em->persist($objPromoCode);
        $em->flush();

        // set audit log add email campagin
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = $changeStatus . ' Employee Promo Code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has " . $changeStatus . " employee promo code " . $objPromoCode->getEmployeePromoCode();
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $this->get('session')->getFlashBag()->add('success', 'Promo Code ' . $changeStatus . ' successfully.');
        return $this->redirect($this->generateUrl('dhi_admin_employee_promo_code_list'));
    }

   
}
