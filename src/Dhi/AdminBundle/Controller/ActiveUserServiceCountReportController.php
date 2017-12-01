<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ActiveUserServiceCountReportController extends Controller {

    public function indexAction(Request $request) {
        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('active_service_count_report_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view active user count report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();

        //service location
        $allPackageType = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();
        $packageType = array();
        foreach ($allPackageType as $key => $packageName) {
            $packageType[$key] = $allPackageType[$key]['name'];
        }

        $services = $em->getRepository("DhiUserBundle:Service")->findAll();

        return $this->render('DhiAdminBundle:ActiveUserServiceCount:index.html.twig', array(
                    'admin' => $admin,
                    'serlocation' => $packageType,
                    "services" => $services
        ));
    }

    public function activeUserServiceCountListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $request = $this->getRequest();
        $toExpiryDate = $request->get('toActiveDate');
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');


        $aColumns = array('name', 'total');

        $gridData = $helper->getSearchData($aColumns);

        if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {
            $this->get('session')->set('salesReportSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('salesReportSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sl.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'name') {

                $orderBy = 'sl.name';
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
        $serviceLocation = $request->get('serviceLocation');
        if ($serviceLocation != null) {
            $gridData['search_data']['serviceLocation'] = str_replace(",", "','", $serviceLocation);
        }

        $gridData['SearchType'] = 'ANDLIKE';


        if ((isset($gridData) && !empty($gridData) && !empty($gridData['search_data']))) {

            $this->get('session')->set('activeUserCountReportSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('activeUserCountReportSearchData');
        }

        $data = $em->getRepository('DhiAdminBundle:ServiceLocation')->getActiveServiceCountReportGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );

        if (isset($data) && !empty($data)) {

            if (isset($data) && !empty($data)) {

                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => count($data),
                    "iTotalDisplayRecords" => 0,
                    "aaData" => array()
                );

                $finalArray = array();

                $locationKey = '';
                $serviceType = '';
                
                $totalMembers = 0;
                $serviceName = $request->get('serviceType');
                if ($serviceName != null) {
                    $serviceType = array($serviceName);
                    if (in_array("IPTV", $serviceType)) {
                        $serviceType[] = "PREMIUM";
                    }
                }
                foreach ($data['result'] as $locationKey => $recordLocation) {
                    $innerContent = $this->activeServicesCount($recordLocation->getName(), $serviceType, $toExpiryDate);
                    $row = array();
                    $row[] = $recordLocation->getName();
                    $row[] = $innerContent['innerhtml'];
                    $totalMembers += $innerContent['totalActiveService'];
                    $output['aaData'][] = $row;
                }
                $row = array();
                $row[] = 'Grand Total';
                $row[] = $totalMembers;

                $output['aaData'][] = $row;
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    function activeServicesCount($serviceLoction, $serviceType = array(), $toExpiryDate = '') {
        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiUserBundle:UserService')->getActiveUserServiceList($serviceLoction, $serviceType, $toExpiryDate);
        $html = "<table style='margin-bottom: 0 !important;' class='table table-bordered table-hover'>"
                . "<tbody><tr><th>Service Name</th><th>Package Name</th><th>Total Active Service</th></tr>";
        $totaluser = 0;
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => count($data['result']),
                    "iTotalDisplayRecords" => 0,
                    "aaData" => array()
                );

                foreach ($data['result'] as $locationKey => $recordLocation) {

                    $totaluser += $recordLocation[1];
                    $html .="<tr><td>" . (!empty($recordLocation['name']) ? $recordLocation['name'] : 'N/A') . "</td><td>" . $recordLocation['packageName'] . "</td><td>" . $recordLocation[1] . "</td></tr>";
                }
            }
        }


        $html .="<tr><td colspan='2'><b>Total Active Service</b></td><td><b>" . $totaluser . "</b></td></tr><tr></tr></tbody></table>";
        return array('innerhtml' => $html, 'totalActiveService' => $totaluser);
    }

}
