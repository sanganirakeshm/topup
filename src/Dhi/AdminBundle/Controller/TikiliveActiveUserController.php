<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TikiliveActiveUserController extends Controller
{
    public function indexAction(Request $request){
        
        if (!($this->get('admin_permission')->checkPermission('tikilive_active_user_list')) || !($this->get('admin_permission')->checkPermission('tikilive_active_user_export_csv'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view global promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        $objServiceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        $serviceLocations = array_map(function($location){ return $location['name']; } , $objServiceLocations);
        
        $allActualCountry = $em->getRepository("DhiAdminBundle:TikiliveActiveUser")->getAllDistinceActualCountry();
        $arrActualCountry = array();
        if(!empty($allActualCountry)){
            foreach ($allActualCountry as $coutry){
                $arrActualCountry[] = $coutry['actualCountry'];
            }
        }
        return $this->render('DhiAdminBundle:TikiliveActiveUser:index.html.twig', array("serviceLocations" => $serviceLocations, 'arrActualCountry' => $arrActualCountry));
    }
    
    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('Username', 'ServiceLocation', 'Country','LastLogin','LastLoginIp');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');

        $gridData = $helper->getSearchData($promoCodeColumns);

    	if(!empty($gridData['search_data'])) {
            $this->get('session')->set('tikiliveActiveUserSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('tikiliveActiveUserSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'tl.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Username') {

                $orderBy = 'u.username';
            }else if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }else if ($gridData['order_by'] == 'LastLogin') {

                $orderBy = 'tl.tikiliveLastLogin';
            }else if ($gridData['order_by'] == 'LastLoginIp') {

                $orderBy = 'tl.tikiliveLastIp';
            }else if($orderBy == 'Country'){
                $orderBy = 'tl.actualCountry';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiAdminBundle:TikiliveActiveUser')->getActiveUserGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    
                    if (!empty($resultRow['dhiPromoCode']) && !empty($resultRow['username']) && !empty($resultRow['isRedeemed']) && $resultRow['isRedeemed'] == 'Yes') {
                        $username        = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow['userId'])) . '">' . $resultRow['username'] . '</a>';
                        $serviceLocation = $resultRow['serviceLocation'];
                    } else {
                        $username        = $resultRow['tikiliveUserName'];
                        $serviceLocation = '-';
                    }
                    $country = (!empty($resultRow['actualCountry']) ? $resultRow['actualCountry'] : '-');

                    $row                = array();
                    $row[]              = $username;
                    $row[]              = $serviceLocation;
                    $row[]              = $country;
                    $row[]              = $resultRow['tikiliveLastLogin'] ? $resultRow['tikiliveLastLogin']->format('M-d-Y h:i:s') : 'N/A';
                    $row[]              = $resultRow['tikiliveLastIp'];
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
        if (!$this->get('admin_permission')->checkPermission('tikilive_active_user_export_csv')) {
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
        if($this->get('session')->has('tikiliveActiveUserSearchData') && $this->get('session')->get('tikiliveActiveUserSearchData') != '') {
            $searchData = $this->get('session')->get('tikiliveActiveUserSearchData');
        }

        $em = $this->getDoctrine()->getManager();
        $slotArr           = array();
        $slotArr['limit']  = $slot;
        $slotArr['offset'] = $offset;
        $tikiliveActiveUserData = $em->getRepository('DhiAdminBundle:TikiliveActiveUser')->getCsvTikiliveActiveUserData($searchData, $slotArr);

        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export Tikilive Active User to CSV';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export tikilive active user to CSV";
        $this->get('ActivityLog')->saveActivityLog($activityLog);


        $response = new StreamedResponse();
        $response->setCallback(function() use($tikiliveActiveUserData) {

            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Portal/Tikilive Username","Portal User ISP Plan Purchased Service Location", "Tikilive User Country", "Tikilive System Last Login Date Time","Tikilive System Last Login IP"), ',');

            foreach ($tikiliveActiveUserData as $key => $resultRow) {

                if (!empty($resultRow['dhiPromoCode']) && !empty($resultRow['username']) && !empty($resultRow['isRedeemed']) && $resultRow['isRedeemed'] == 'Yes') {
                    $username        = $resultRow['username'];
                    $serviceLocation = $resultRow['serviceLocation'];
                } else {
                    $username        = $resultRow['tikiliveUserName'];
                    $serviceLocation = '-';
                }
                $country           = (!empty($resultRow['actualCountry']) ? $resultRow['actualCountry'] : '-');
                $tikiliveLastLogin = $resultRow['tikiliveLastLogin'] ? $resultRow['tikiliveLastLogin']->format('M-d-Y h:i:s') : 'N/A';
                $tikiliveLastIp    = $resultRow['tikiliveLastIp'];

                fputcsv($handle, array(
                    $username,
                    $serviceLocation,
                    $country,
                    $tikiliveLastLogin,
                    $tikiliveLastIp,
                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'tikilive_active_user_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }

}
