<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\UserBundle\Entity\User;
use \DateTime;

class PromoCodeRedemptionReportController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('promocode_redemption_report_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view Promo Code Redemption Report.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();

        $objPartners  = $em->getRepository('DhiAdminBundle:ServicePartner')->findBy(array('status' => 1, 'isDeleted' => 0), array('name' => 'ASC'));
        $partners = array();
        foreach ($objPartners as $objPartner) {
            $partners[] = $objPartner->getName();
        }
        $objAdmins  = $em->getRepository('DhiUserBundle:User')->getAllAdmin();
        return $this->render('DhiAdminBundle:PromoCodeRedemptionReport:index.html.twig', 
            array(
                'admin'    => $admin,
                'partners' => $partners,
                'admins'   => $objAdmins
            )
        );
    }

    public function ServiceLocationListJsonAction(Request $request){
        $id = $request->get('id');
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $locations = array();
        if($id != ''){
            
            $adminServiceLocationPermission = '';
            if ($admin->getGroup() != 'Super Admin') {
                $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
                $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
            }
            
            $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation(0, $id, '', $adminServiceLocationPermission);
            foreach ($serviceLocations as $location) {
                $locations[] = $location['name'];
            }
        }
        $response = new Response(json_encode($locations));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function PromoCodeRedemptionListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $adminColumns = array("partnerName", 'Dates');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        $createdBy = $request->get('createdBy');
        $serviceLocation = $request->get('serviceLocation');
        
        if(!empty($gridData['search_data']['Dates'])){
            $dateRange = $gridData['search_data']['Dates'];
        }else{
            $dateRange = '';
        }

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'sp.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'partnerName') {
                $orderBy = 'sp.name';
            }else if ($gridData['order_by'] == 'Id') {
                $orderBy = 'sp.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $data  = $em->getRepository('DhiAdminBundle:ServicePartner')->getpartnerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin);
        $locations = array();
        $output = array(
            "locations" => $locations,
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {
            if (isset($data['result']) && !empty($data['result'])) {
                $output = array(
                    "locations" => $locations,
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                $grandTotal = 0;
                foreach ($data['result'] AS $resultRow) {
                    if(!empty($serviceLocation) && $serviceLocation != ' '){
                        $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation(0, '', $serviceLocation, $adminServiceLocationPermission);
                    }else{
                        $serviceLocations = $em->getRepository('DhiAdminBundle:ServicePartner')->getServiceLocation($resultRow->getId(), '', '', $adminServiceLocationPermission);
                    }

                    $innerHtml = '<table class="table table-bordered table-hover">';
                    $total = 0;
                    if($serviceLocations){
                        foreach ($serviceLocations as $location) {
                            $locations[$location['name']] = $location['name'];
                            $innerHtml .= '<tr><td>'.$location['name'].'</td>';
                            $packages = $em->getRepository('DhiAdminBundle:PartnerPromocodes')->getRedeemedCount($location['id'], $resultRow->getId(), $createdBy, $dateRange);
                            $subTotal = 0;
                            $innerHtml .= '<td>';
                            $innerHtml .= '<table class="table table-bordered table-hover">';
                            $innerHtml .= '<tr><th>Package Name</th>';
                            $innerHtml .= '<th>Total Redemption</th>';
                            $innerHtml .= '</tr>';
                            foreach ($packages as $package) {
                                $subTotal += $package['totalRedeemed'];
                                $innerHtml .= '<tr><td>'.$package['packageName'].'</td>';
                                $innerHtml .= '<td>'.$package['totalRedeemed'].'</td>';
                                $innerHtml .= '</tr>';
                            }
                            $innerHtml .= '<tr><td><b>Total</b></td><td><b>'.$subTotal.'</b></td></tr>';
                            $innerHtml .= '</table>';
                            $innerHtml .= '</td>';
                            $total += $subTotal;
                            $innerHtml .= '</tr>';
                        }
                    }
                    $innerHtml .= '<tr><td><b>Total</b></td><td><b>'.$total.'</b></td></tr>';
                    $innerHtml .= '</table>';
                    $row = array();
                    $row[] = $resultRow->getName();
                    $row[] = $innerHtml;
                    $output['aaData'][] = $row;
                    $grandTotal += $total;
                }
                $row = array();
                $row[] = "<b>Total</b>";
                $row[] = "<b>".$grandTotal." Redemptions</b>";
                $output['aaData'][] = $row;
            }
        }
        $output['locations'] = $locations;
        $output['partnerName'] = !empty($gridData['search_data']['partnerName']) ? $gridData['search_data']['partnerName'] : '';
        $response = new Response(json_encode($output));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getLocationAction(Request $request){
        $locations = $request->get('sLocation');
        $em = $this->getDoctrine()->getManager();
        $uniqueService = $serviceLocations = array();
        $services = array();
        foreach ($locations as $key => $location) {
            $services = $em->getRepository('DhiAdminBundle:IpAddressZone')->getServicesByLocations($location, $services);
        }
        $response = new Response(json_encode($services));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
