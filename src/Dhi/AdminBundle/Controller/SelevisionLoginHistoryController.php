<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dhi\AdminBundle\Entity\SelevisionLoginHistory;
use \DateTime;

class SelevisionLoginHistoryController extends Controller {

    public function indexAction(Request $request) {
        if (!($this->get('admin_permission')->checkPermission('selevision_login_history_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view selevision login list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:SelevisionLoginHistory:index.html.twig');
    }

    public function listJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $promoCodeColumns = array('username', 'startDate', 'refreshedDate', 'endDate');
        $admin            = $this->get('security.context')->getToken()->getUser();
        $helper           = $this->get('grid_helper_function');
        $gridData         = $helper->getSearchData($promoCodeColumns);
        $sortOrder        = $gridData['sort_order'];
        $orderBy          = $gridData['order_by'];
        $per_page         = $gridData['per_page'];
        $offset           = $gridData['offset'];
        $em               = $this->getDoctrine()->getManager();

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'slh.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'username') {
                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'startDate') {
                $orderBy = 'slh.startDate';
            }
            if ($gridData['order_by'] == 'refreshedDate') {
                $orderBy = 'slh.refreshedDate';
            }
            if ($gridData['order_by'] == 'endDate') {
                $orderBy = 'slh.endDate';
            }
        }

        $data = $em->getRepository('DhiAdminBundle:SelevisionLoginHistory')->getHistoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
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
                    $startDate     = $resultRow->getStartDate();
                    $refreshedDate = $resultRow->getRefreshedDate();
                    $endDate       = $resultRow->getEndDate();

                    $row = array();
                    $row[] = $resultRow->getUser()->getUsername();
                    $row[] = (!empty($startDate) && $startDate != "0000-00-00 00:00:00") ? $startDate->format('m/d/Y H:i:s') : 'N/A';
                    $row[] = (!empty($refreshedDate) && $refreshedDate != "0000-00-00 00:00:00") ? $refreshedDate->format('m/d/Y H:i:s') : 'N/A';
                    $row[] = (!empty($endDate) && $endDate != "0000-00-00 00:00:00") ? $endDate->format('m/d/Y H:i:s') : 'N/A';
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}