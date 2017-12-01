<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserActivityLogController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('audit_log_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view audit log list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $searchParams = $request->query->all();

        if (isset($searchParams['historicalData']) && $searchParams['historicalData'] == 'on') {

            $em = $this->getDoctrine()->getManager('secondary');
            $query = $em->getRepository('DhiUserBundle:UserActivityLog')->getAllActivityLogs();
        }
        else
        {
            $query = $em->getRepository('DhiUserBundle:UserActivityLog')->getAllActivityLogs();
        }
        if (isset($searchParams)) {

            if (isset($searchParams['startDate']) && $searchParams['startDate'] != '') {

                $startDate = new \DateTime($searchParams['startDate']);
                $searchParams['startDate'] = $startDate;
            }
            if (isset($searchParams['endDate']) && $searchParams['endDate'] != '') {

                $endDate = new \DateTime($searchParams['endDate']);
                $searchParams['endDate'] = $endDate;
            }

            $query = $em->getRepository('DhiUserBundle:UserActivityLog')->getActivityLogSearch($query, $searchParams);
        }

        //$paginator = $this->get('knp_paginator');
        //$pagination = $paginator->paginate($query, $request->query->get('page', 1), 10);

        $arrActivity = array();
        $arrActivity['Admin Logged In'] = 'Admin Logged In';
        $arrActivity['Logout'] = 'Logout';
        $arrActivity['Add Compensation for user'] = 'Add Compensation for user';

        $arrActivity['Registration'] = 'Registration';
        $arrActivity['Email Confirmation'] = 'Email Confirmation';

        $arrActivity['Customer Logged In'] = 'Customer Logged In';

        $arrActivity['Resend activation email'] = 'Resend activation email';
        $arrActivity['Forgot password request'] = 'Forgot password request';
        $arrActivity['Forgot Username Request'] = 'Forgot Username Request';
        $arrActivity['Reset password'] = 'Reset password';
        $arrActivity['Update user account'] = 'Update user account';
        $arrActivity['Change password'] = 'Change password';
        $arrActivity['Update account setting'] = 'Update account setting';

        $arrActivity['Print purchase history'] = 'Search admin';
        $arrActivity['Print aradial user purchase history'] = 'Search admin';
        $arrActivity['Add admin'] = 'Add admin';
        $arrActivity['Edit admin'] = 'Edit admin';
        $arrActivity['Admin change password'] = 'Admin change password';
        $arrActivity['Delete admin'] = 'Delete admin';
//        $arrActivity['Search user'] = 'Search user';
        $arrActivity['Add user'] = 'Add user';
        $arrActivity['Delete user'] = 'Delete user';
        $arrActivity['Edit user'] = 'Edit user';
        $arrActivity['Change user password'] = 'Change user password';
//        $arrActivity['Search Email Campaign'] = 'Search Email Campaign';
        $arrActivity['Add Email Campaign'] = 'Add Email Campaign';
        $arrActivity['Edit Email Campaign'] = 'Edit Email Campaign';
        $arrActivity['Delete Email Campaign'] = 'Delete Email Campaign';
        $arrActivity['Send Email Campaign'] = 'Send Email Campaign';

        $arrActivity['Add IP address range'] = 'Add IP address range';
        $arrActivity['Edit IP address range'] = 'Edit IP address range';

        $arrActivity['Add support location'] = 'Add support location';

        $arrActivity['Add to Cart ExchangeVUE package'] = 'Add to Cart ExchangeVUE package';
        $arrActivity['Add to Cart ISP'] = 'Add to Cart ISP';

        $arrActivity['Add to Cart AddOns Package'] = 'Add to Cart AddOns Package';
        $arrActivity['Add Credit'] = 'Add Credit';

        $arrActivity['Export PDF'] = 'Export PDF';

        $arrActivity['Edit group permission'] = 'Edit group permission';

        $arrActivity['Add user mac address'] = 'Add user mac address';
        $arrActivity['Add user mac address'] = 'Edit user mac address';
        $arrActivity['Delete user mac address'] = 'Delete user mac address';

        $arrActivity['Edit mac address'] = 'Add mac address';
        $arrActivity['Edit mac address'] = 'Edit mac address';
        $arrActivity['Edit mac address'] = 'Delete mac address';

        $arrActivity['Export csv purchase history'] = 'Export csv purchase history';
        $arrActivity['Export csv aradial user purchase history'] = 'Export csv aradial user purchase history';
        $arrActivity['Export pdf purchase history'] = 'Export pdf purchase history';
        $arrActivity['Export pdf aradial user purchase history'] = 'Export pdf aradial user purchase history';
        $arrActivity['Print purchase history'] = 'Print purchase history';
        $arrActivity['Print aradial user purchase history'] = 'Print aradial user purchase history';

        $arrActivity['Export csv Aradial User history'] = 'Export csv Aradial User history';
        $arrActivity['Export pdf Aradial User history'] = 'Export pdf Aradial User history';
        $arrActivity['Print Aradial User history'] = 'Print Aradial User history';

        $arrActivity['Export user login log pdf'] = 'Export user login log pdf';
        $arrActivity['Export user login log print'] = 'Export user login log print';
        $arrActivity['Print user login log'] = 'Print user login log';

        $arrActivity['Add Service Location'] = 'Add Service Location';
        $arrActivity['Edit Service location'] = 'Edit Service location';

        $arrActivity['Add service location discount'] = 'Add service location discount';
        $arrActivity['Edit service location discount'] = 'Edit service location discount';

        $arrActivity['Add Global Discount'] = 'Add Global Discount';
        $arrActivity['Edit Global Discount'] = 'Edit Global Discount';

        $arrActivity['Edit Permission'] = 'Edit Permission';

        $arrActivity['Edit Credit'] = 'Edit Credit';
        $arrActivity['Delete Credit'] = 'Delete Credit';

        $arrActivity['Add support category'] = 'Add support category';
        $arrActivity['Edit support category'] = 'Edit support category';
        $arrActivity['Delete support category'] = 'Delete support category';
        $arrActivity['Change Support Category Display Order'] = 'Change Support Category Display Order';

        $arrActivity['Edit support location'] = 'Edit support location';
        $arrActivity['Delete support location'] = 'Delete support location';
        $arrActivity['Change Support Location Display Order'] = 'Change Support Location Display Order';

        $arrActivity['Delete Global discount'] = 'Delete Global discount';

//        $arrActivity['Search user login log'] = 'Search user login log';
        $arrActivity['Export user login log pdf'] = 'Export user login log pdf';
        $arrActivity['Print user login log'] = 'Print user login log';
//        $arrActivity['Search service'] = 'Search service';
        $arrActivity['Add service'] = 'Add service';
        $arrActivity['Edit service'] = 'Edit service';
        $arrActivity['Delete service'] = 'Delete service';

        $arrActivity['User service status change'] = 'User service status change';

        $arrActivity['Search country wise service'] = 'Search country wise service';
        $arrActivity['Add country wise service'] = 'Add country wise service';

        $arrActivity['Edit country wise service'] = 'Edit country wise service';
        $arrActivity['Delete country wise service'] = 'Delete country wise service';
//        $arrActivity['Search Setting'] = 'Search Setting';
        $arrActivity['Add Setting'] = 'Add Setting';
        $arrActivity['Edit Setting'] = 'Edit Setting';
        $arrActivity['Delete Setting'] = 'Delete Setting';

        $arrActivity['User settings'] = 'User settings';

        $arrActivity['Remove Cart Item'] = 'Remove Cart Item';
        $arrActivity['Purchase Order'] = 'Purchase Order';
        
        $arrActivity['Add Partner Promo Code Batch'] = 'Add Partner Promo Code Batch';
        $arrActivity['Update partner promo code'] = 'Update partner promo code';
        $arrActivity['Delete partner promo code batch'] = 'Delete partner promo code batch';
        $arrActivity['Export pdf partner promocode'] = 'Export pdf partner promocode';
        $arrActivity['Export CSV partner promocode'] = 'Export CSV partner promocode';
        $arrActivity['Export Excel partner promocode'] = 'Export Excel partner promocode';
        $arrActivity['Deactivated partner promo code'] = 'Deactivated partner promo code';
        
        $arrActivity['Add Service Partner'] = 'Add Service Partner';
        $arrActivity['Edit Service Partner'] = 'Edit Service Partner';
        $arrActivity['Delete service partner'] = 'Delete service partner';
        $arrActivity['Delete business promo code batch'] = 'Delete business promo code batch';

        $arrActivity['Promo Code Reassign'] = 'Promo Code Reassign';
        $arrActivity['Unsuspend user'] = 'Delete UnAssigned Promo Code';
        $arrActivity['Export csv sales details report'] = 'Export csv sales details report';
        
        $arrActivity['Export Tikilive Active User to CSV'] = 'Export Tikilive Active User to CSV';

        $arrActivity['Assign Solar Winds request type to support location'] = 'Assign Solar Winds request type to support location';
        $arrActivity['Edit Solar Winds request type'] = 'Edit Solar Winds request type';
        $arrActivity['Delete Solar Winds request type'] = 'Delete Solar Winds request type';
        
        $arrActivity['Disable Free Recharge Card Package'] = 'Disable Free Recharge Card Package';
        $arrActivity['Enable Free Recharge Card Package'] = 'Enable Free Recharge Card Package';
        
        $arrActivity['Mark User for Free Recharge Card'] = 'Mark User for Free Recharge Card';
        $arrActivity['Promotion Status Changed'] = 'Promotion Status Changed';
        $arrActivity['Add Promotion'] = 'Add Promotion';
        $arrActivity['Edit Promotion'] = 'Edit Promotion';
        $arrActivity['Suspend user'] = 'Suspend user';
        $arrActivity['Unsuspend user'] = 'Unsuspend user';


        asort($arrActivity);

         $arrActivityLog = array();
        foreach ($arrActivity as $activity) {
            $arrActivityLog[] = $activity;
        }

        $objAdmins  = $em->getRepository('DhiUserBundle:User')->getAllAdmin();
        $allsites   = $em->getRepository('DhiAdminBundle:WhiteLabel')->getallsites();

        return $this->render('DhiAdminBundle:UserActivityLog:index.html.twig', array('activity' => $arrActivityLog, 'admins'   => $objAdmins, 'allsites' => $allsites));
    }

     public function activityLogListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $historical) {

        $aColumns = array('Admin', 'User', 'Activity', 'Description', 'IP', 'Date Time', 'SiteName', 'Id');

        $admin = $this->get('security.context')->getToken()->getUser();
        $admin = $request->get('admin');

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($aColumns);
        if (!empty($admin)) {
            $gridData['search_data']['Admin'] = $admin;
            $gridData['SearchType'] = "ANDLIKE";
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'al.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'Admin') {

                $orderBy = 'al.admin';
            }
            if ($gridData['order_by'] == 'User') {

                $orderBy = 'al.user';
            }
            if ($gridData['order_by'] == 'Activity') {

                $orderBy = 'al.activity';
            }
            if ($gridData['order_by'] == 'Description') {

                $orderBy = 'al.description';
            }
            if ($gridData['order_by'] == 'IP') {

                $orderBy = 'al.ip';
            }
            if ($gridData['order_by'] == 'Date Time') {

                $orderBy = 'al.timestamp';
            }
            if ($gridData['order_by'] == 'SiteName') {

                $orderBy = 'wl.companyName';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        if ($historical == 1) {
            $em = $this->getDoctrine()->getManager('secondary');
        } else {
            $em = $this->getDoctrine()->getManager();
        }
        
        $siteName = $request->get('sitename');
        if($siteName){
            $gridData['search_data']['SiteName'] = $siteName;
        }
        
        $data = $em->getRepository('DhiUserBundle:UserActivityLog')->getActivityLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin);

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
                    $row[] = $resultRow['admin'] ? $resultRow['admin'] : 'N/A';
                    $row[] = $resultRow['user'] ? $resultRow['user'] : 'N/A';
                    $row[] = $resultRow['activity'];
                    $row[] = $resultRow['description'];
                    $row[] = $resultRow['ip'];
                    $row[] = $resultRow['timestamp']->format('M-d-Y H:i:s');
                    $row[] = $resultRow['companyName'] ? $resultRow['companyName'] : 'N/A';
                    $row[] = $resultRow['id'];

                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
