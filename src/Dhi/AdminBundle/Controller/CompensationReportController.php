<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use \DateTime;

class CompensationReportController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('compensation_report_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view compensation list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $admin = $this->get('security.context')->getToken()->getUser();              
        return $this->render('DhiAdminBundle:CompensationReport:index.html.twig', array('admin' => $admin));
    }

    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $compensationColumns = array('Title','ISPHours','IPTVDays','Services','ServiceLocations','Cron Status','Cron run At');

        $admin     = $this->get('security.context')->getToken()->getUser();
        $helper    = $this->get('grid_helper_function');
        $gridData  = $helper->getSearchData($compensationColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy   = $gridData['order_by'];
        $per_page  = $gridData['per_page'];
        $offset    = $gridData['offset'];
        $em        = $this->getDoctrine()->getManager();
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'c.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'c.id';
            }
            if ($gridData['order_by'] == 'Title') {
                $orderBy = 'c.title';
            }
            if ($gridData['order_by'] == 'ISPHours') {
                $orderBy = 'c.ispHours';
            }
            if ($gridData['order_by'] == 'IPTVDays') {
                $orderBy = 'c.iptvDays';
            }           
            if ($gridData['order_by'] == 'Cron Status') {
                $orderBy = 'c.status';
            }   
        }

        $this->get('session')->remove('search_data');
        $this->get('session')->remove('offset');
        $this->get('session')->remove('per_page');
        $this->get('session')->remove('orderBy');
        $this->get('session')->remove('sortOrder');

        $this->get('session')->set('search_data', $gridData['search_data']);
        $this->get('session')->set('SearchType', $gridData['SearchType']);
        $this->get('session')->set('offset', $offset);
        $this->get('session')->set('per_page', $per_page);
        $this->get('session')->set('orderBy', $orderBy);
        $this->get('session')->set('sortOrder', $sortOrder);
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {

            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $data  = $em->getRepository('DhiUserBundle:Compensation')->getCompensationGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, true, $adminServiceLocationPermission);


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
                    $count = 1;
                    $servicesCount = count($resultRow->getServices());

                    $serviceName = '';
                    if($resultRow->getServices()){
                        foreach ($resultRow->getServices() as $service) {
                            if ($count == $servicesCount) {
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'<span>';
                            } else {
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName()."</span>";
                            }
                            $count++;
                        }
                    }

                    $serviceLocationCount = count($resultRow->getServiceLocations());
                    $locationCount = 1;
                    $locationName = '';
                    if($resultRow->getServiceLocations()){
                        foreach ($resultRow->getServiceLocations() as $location) {
                            if ($locationCount == $serviceLocationCount) {
                                $locationName .= $location->getName();
                            } else {
                                $locationName .= $location->getName().", ";
                            }
                            $locationCount++;
                        }
                    }

                    $executedAt = $resultRow->getExecutedAt();

                    $row = array();
                    $row[] = $resultRow->getTitle();
                    $row[] = $resultRow->getIspHours();
                    $row[] = $resultRow->getIptvDays(); 
                    $row[] = $serviceName;
                    $row[] = $locationName;
                    $row[] = $resultRow->getStatus(); 
                    $row[] = !empty($executedAt) ? $executedAt->format("m/d/Y H:i:s") : "N/A";
                    $row[] = $resultRow->getId(); 
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	    $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function exportCsvAction(Request $request){
        $search_data         = $this->get('session')->get('search_data');
        $SearchType          = $this->get('session')->get('SearchType');
        $offset              = $this->get('session')->get('offset');
        $per_page            = $this->get('session')->get('per_page');
        $orderBy             = $this->get('session')->get('orderBy');
        $sortOrder           = $this->get('session')->get('sortOrder');
        $helper              = $this->get('grid_helper_function');
        $em                  = $this->getDoctrine()->getManager();
        $compensationColumns = array('Title','ISPHours','IPTVDays','Services','ServiceLocations','CronStatus','CronRunAt');
        $gridData            = $helper->getSearchData($compensationColumns);
        $admin     = $this->get('security.context')->getToken()->getUser();
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {

            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $data                = $em->getRepository('DhiUserBundle:Compensation')->getCompensationGridList($per_page, $offset, $orderBy, $sortOrder, $search_data, $SearchType, $helper, false, $adminServiceLocationPermission);

        $response = new StreamedResponse();
        $records = array();
        if(isset($data) && !empty($data['result'])){
            $records = $data['result'];
        }

        $response->setCallback(function() use($records) {
            $handle = fopen('php://output', 'w+');
            fputcsv($handle, array("Title", "ISP Hours", "ExchangeVUE Hours", "Services", "ServiceLocations", "Cron Status", "Executed At"), ',');
            foreach ($records as $resultRow) {
                $serviceName = '';
                $count = 1;
                $servicesCount = count($resultRow->getServices());
                if($resultRow->getServices()){
                    foreach ($resultRow->getServices() as $service) {
                        if ($count == $servicesCount) {
                            $serviceName .= $service->getName();
                        } else {
                            $serviceName .= $service->getName().", ";
                        }
                        $count++;
                    }
                }

                $serviceLocationCount = count($resultRow->getServiceLocations());
                $locationCount = 1;
                $locationName = '';
                if($resultRow->getServiceLocations()){
                    foreach ($resultRow->getServiceLocations() as $location) {
                        if ($locationCount == $serviceLocationCount) {
                            $locationName .= $location->getName();
                        } else {
                            $locationName .= $location->getName().", ";
                        }
                        $locationCount++;
                    }
                }
                $executedAt = $resultRow->getExecutedAt();
                $row = array();
                $row[] = $resultRow->getTitle();
                $row[] = $resultRow->getIspHours();
                $row[] = $resultRow->getIptvDays(); 
                $row[] = $serviceName;
                $row[] = $locationName;
                $row[] = $resultRow->getStatus(); 
                $row[] = !empty($executedAt) ? $executedAt->format("m/d/Y H:i:s") : "N/A";
                fputcsv($handle, $row, ',');
            }
            fclose($handle);
        });
        $file_name = 'compensation-report_' . date('m-d-Y', time()) . '.csv';
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');
        return $response;
    }
}
