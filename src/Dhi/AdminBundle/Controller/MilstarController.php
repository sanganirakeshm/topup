<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Dhi\UserBundle\Entity\User;
use \DateTime;
use Dhi\ServiceBundle\Entity\Milstar;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dhi\ServiceBundle\Model\ExpressCheckout;

class MilstarController extends Controller {

    public function transactionLookupAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        //Check permission
        
        if (!( $this->get('admin_permission')->checkPermission('milstar_transaction'))) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view milstar transaction deatail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:Milstar:milstarTransaction.html.twig', array(
                    'admin' => $admin,
                    'currentDate' => date('m-d-Y', time())
        ));
    }

    public function milstarTransactionListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $request = $this->getRequest();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $helper = $this->get('grid_helper_function');

        $aColumns = array('Id', 'RequestId', 'TotalAmount', 'FacNbr', 'AuthTicket', 'AuthCode', 'IPAddress', 'UserName','FirstName','LastName','Email', 'PackageId', 'PurchaseDate');
        $gridData = $helper->getSearchData($aColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];


        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'm.id';
            $sortOrder = 'ASC';
        } else {
            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'm.id';
            }

            if ($gridData['order_by'] == 'RequestId') {

                $orderBy = 'm.requestId';
            }
            if ($gridData['order_by'] == 'TotalAmount') {

                $orderBy = 'm.payableAmount';
            }

            if ($gridData['order_by'] == 'FacNbr') {

                $orderBy = 'm.facNbr';
            }
            if ($gridData['order_by'] == 'AuthTicket') {

                $orderBy = 'm.authTicket';
            }
            if ($gridData['order_by'] == 'AuthCode') {

                $orderBy = 'm.authCode';
            }
            if ($gridData['order_by'] == 'IPAddress') {

                $orderBy = 'm.ip_address';
            }
            
            if ($gridData['order_by'] == 'UserName') {

                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'FirstName') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'LastName') {

                $orderBy = 'u.lastname';
            }
            
            if ($gridData['order_by'] == 'Email') {

                $orderBy = 'u.email';
            }

            if ($gridData['order_by'] == 'PurchaseDate') {

                $orderBy = 'm.createdAt';
            }

            if ($gridData['order_by'] == 'PackageId') {

                $orderBy = 'sp.packageId';
            }
        }
        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $data = $em->getRepository('DhiServiceBundle:Milstar')->getMilstarHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin);

        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        if (isset($data) && !empty($data)) {

            if (isset($data['result']) && !empty($data['result'])) {

                $output = array(
                    //"sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                
                foreach ($data['result'] AS $resultRow) {

                    $packages = array();
                    if(isset($resultRow['purchaseOrder']['servicePurchases'])){
                        foreach ($resultRow['purchaseOrder']['servicePurchases'] as $value) {
                            $packages[] = $value['packageId'];
                        }
                    }

                    $row = array();
                    $row[] = $resultRow['id'];
                    $row[] = $resultRow['requestId'];
                    $row[] = $resultRow['payableAmount'];
                    $row[] = $resultRow['facNbr'];
                    $row[] = $resultRow['authTicket'];
                    $row[] = $resultRow['authCode'];
                    $row[] = $resultRow['ip_address'];
                    $row[] = $resultRow['user']['username'];
                    $row[] = $resultRow['user']['firstname'];
                    $row[] = $resultRow['user']['lastname'];
                    $row[] = $resultRow['user']['email'];
                    $row[] = implode($packages, ', ');
                    $row[] = $resultRow['createdAt']->format('d-m-Y H:i:s');

                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function failureLookupAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        //Check permission
        if (!( $this->get('admin_permission')->checkPermission('milstar_transaction_failure') )) {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view milstar transaction deatail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:Milstar:milstarFailure.html.twig', array(
                    'admin' => $admin,
                    'currentDate' => date('m-d-Y', time())
        ));
    }

    public function milstarFailureListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $request = $this->getRequest();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $aColumns = array('Id', 'RequestId', 'TotalAmount', 'FacNbr', 'FailCode', 'IPAddress', 'UserName','FirstName','LastName','Email', 'PackageId', 'PurchaseDate');
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($aColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'm.id';
            $sortOrder = 'ASC';
        } else {
            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'm.id';
            }

            if ($gridData['order_by'] == 'RequestId') {

                $orderBy = 'm.requestId';
            }
            if ($gridData['order_by'] == 'TotalAmount') {

                $orderBy = 'm.payableAmount';
            }

            if ($gridData['order_by'] == 'FacNbr') {

                $orderBy = 'm.facNbr';
            }
            if ($gridData['order_by'] == 'FailCode') {

                $orderBy = 'm.failCode';
            }

            if ($gridData['order_by'] == 'IPAddress') {

                $orderBy = 'm.ip_address';
            }
            if ($gridData['order_by'] == 'UserName') {

                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'FirstName') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'LastName') {

                $orderBy = 'u.lastname';
            }
            if ($gridData['order_by'] == 'Email') {

                $orderBy = 'u.email';
            }

            if ($gridData['order_by'] == 'PurchaseDate') {

                $orderBy = 'm.createdAt';
            }

            if ($gridData['order_by'] == 'PackageId') {

                $orderBy = 'sp.packageId';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $data = $em->getRepository('DhiServiceBundle:Milstar')->getMilstarFailureHistoryGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin);

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

                    $packages = array();
                    if(isset($resultRow['purchaseOrder']['servicePurchases'])){
                        foreach ($resultRow['purchaseOrder']['servicePurchases'] as $value) {
                            $packages[] = $value['packageId'];
                        }
                    }

                    $row = array();
                    $row[] = $resultRow['id'];
                    $row[] = $resultRow['requestId'];
                    $row[] = $resultRow['payableAmount'];
                    $row[] = $resultRow['facNbr'];
                    $row[] = $resultRow['failCode'];
                    $row[] = $resultRow['ip_address'];
                    $row[] = $resultRow['user']['username'];
                    $row[] = $resultRow['user']['firstname'];
                    $row[] = $resultRow['user']['lastname'];
                    $row[] = $resultRow['user']['email'];
                    $row[] = implode($packages, ', ');
                    $row[] = $resultRow['createdAt']->format('d-m-Y H:i:s');

                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
