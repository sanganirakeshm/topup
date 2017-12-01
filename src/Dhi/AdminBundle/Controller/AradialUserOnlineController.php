<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\AdminBundle\Entity\UserOnlineSession;



class AradialUserOnlineController extends Controller {

    public function indexAction(Request $request) {

//        //Check permission
        if (!($this->get('admin_permission')->checkPermission('aradial_online_user_list') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view online aradial user list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:AradialUserOnline:index.html.twig');
    }

    //added for grid
   public function aradialUserOnlineListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $userColumns = array('UserName', 'NasName', 'OnlineSince','TimeOnline', 'UserIP', 'Action');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($userColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'uos.userName';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'UserName') {
                $orderBy = 'uos.userName';
            }
            if ($gridData['order_by'] == 'NasName') {
                $orderBy = 'uos.nasName';
            }
            if ($gridData['order_by'] == 'OnlineSince') {
                $orderBy = 'uos.onlineSince';
            }
            if ($gridData['order_by'] == 'TimeOnline') {
                $orderBy = 'uos.timeOnline';
            }
            if ($gridData['order_by'] == 'UserIP') {
                $orderBy = 'uos.userIp';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();

        $country = '';
        if($admin->getGroup() != 'Super Admin') {
          $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
          $country = empty($country)?'0':$country;
        }
        $data = $em->getRepository('DhiAdminBundle:UserOnlineSession')->getAradialOnlineUserGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin,$country);
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
					$row[] = $resultRow['userName'];
					$row[] = $resultRow['nasName'];
					$row[] = $resultRow['onlineSince'];
					$row[] = $resultRow['timeOnline'];
					$row[] = $resultRow['userIp'];
					$row[] = $resultRow['userName'].'^'.$resultRow['nasId'].'^'.$resultRow['nasPort'].'^'.$resultRow['accountSessionId'];
					$output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function disconnectOnlineUserSessionAction(Request $request) {

        $wsParam = array();
        $wsParam['Page'] = 'RadUserRequest';
        $wsParam['Rad_ReqCode'] = '40';
        $wsParam['UserID'] = $request->get('id');

        $logoutUserResponse = $this->get('aradial')->sendWsRequest('logoutUser', $wsParam);

        if ($logoutUserResponse['status'] == 1) {
            $result = array('type' => 'success', 'message' => 'User disconnected successfully.');
        } else {
            $result = array('type' => 'danger', 'message' => 'User could not be disconnected. Please try again later');
        }
        
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
