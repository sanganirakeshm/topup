<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\AradialPaymentHistory;

class AradialPaymentHistoryController extends Controller {

    public function indexAction(Request $request) {

        if (!($this->get('admin_permission')->checkPermission('aradial_purchase_history_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view aradial payment history list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:AradialPaymentHistory:index.html.twig');
    }

    public function historyListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $adminColumns = array('PaymentId', 'Firstname', 'Lastname', 'UserId', 'PaymentDate', 'Name', 'Amount');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'a.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'UserId') {

                $orderBy = 'a.userId';
            }
            if ($gridData['order_by'] == 'PaymentDate') {

                $orderBy = 'a.paymentdate';
            }
            if ($gridData['order_by'] == 'PaymentId') {

                $orderBy = 'a.paymentId';
            }
            if ($gridData['order_by'] == 'Firstname') {

                $orderBy = 'a.firstname';
            }
            if ($gridData['order_by'] == 'Lastname') {

                $orderBy = 'a.lastname';
            }
            if ($gridData['order_by'] == 'Name') {

                $orderBy = 'a.name';
            }
            if ($gridData['order_by'] == 'Amount') {

                $orderBy = 'a.amount';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiAdminBundle:AradialPaymentHistory')->getAradialPaymentHistoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    $row[] = $resultRow->getPaymentId();
                    $row[] = $resultRow->getFirstname();
                    $row[] = $resultRow->getLastname();
                    $row[] = $resultRow->getUserId();
                    $row[] = $resultRow->getPaymentdate() != null ? $resultRow->getPaymentdate()->format('M-d-Y H:i:s') : 'N/A';
                    $row[] = $resultRow->getName();
                    $row[] = $resultRow->getAmount();

                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function importAction(Request $request) {


        if (!($this->get('admin_permission')->checkPermission('aradial_purchase_history_import'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to import aradial purchase history.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();

        if ($request->getMethod() == 'POST') {

            if (isset($_FILES) && !empty($_FILES)) {
                $uploadedFile = $_FILES;
            } else {
                $this->get('session')->getFlashBag()->add('failure', "Can not upload the file, Please select another file.");
                return $this->redirect($this->generateUrl('dhi_admin_aradial_payment_history_import'));
            }

            $validExtensions = array('csv');

            if (isset($_FILES) && !empty($uploadedFile)) {
                $ext = pathinfo($uploadedFile['file']["name"], PATHINFO_EXTENSION);
                if (!in_array($ext, $validExtensions)) {

                    $this->get('session')->getFlashBag()->add('failure', "Please upload only csv file.");
                    return $this->redirect($this->generateUrl('dhi_admin_aradial_payment_history_import'));
                } else if ($uploadedFile['file']["size"] > 10000000) {

                    $this->get('session')->getFlashBag()->add('failure', "File size is more then 10MB, Please select another file.");
                    return $this->redirect($this->generateUrl('dhi_admin_aradial_payment_history_import'));
                }
            }
            $fileCSV = $this->uploadFile($uploadedFile);

            if ($fileCSV) {

                //$fileCSV = str_replace('/app/..', '', $fileCSV);

                $handle = fopen($fileCSV, 'r');

                while ($data = fgetcsv($handle)) {

                    $paymentId = $data[0];
                    $firstName = $data[1];
                    $lastName = $data[2];
                    $userId = $data[3];
                    $paymentdate = $data[4];
                    $name = $data[5];
                    $amount = $data[6];
                    if (!empty($paymentId) && $paymentId != '') {
                        $checkRecordExist = $em->getRepository('DhiAdminBundle:AradialPaymentHistory')->findBy(array('paymentId' => $paymentId));
                        if (!$checkRecordExist) {
                            $objAradialHistory = new AradialPaymentHistory;
                            $objAradialHistory->setPaymentId($paymentId);
                            $objAradialHistory->setFirstname($firstName);
                            $objAradialHistory->setLastname($lastName);
                            $objAradialHistory->setUserId($userId);

                            $payDate = new \DateTime($paymentdate);
                            $objAradialHistory->setPaymentdate($payDate);
                            $objAradialHistory->setName($name);
                            $objAradialHistory->setAmount($amount);
                            $em->persist($objAradialHistory);
                            $em->flush();
                        }
                    }
                }

                //unlink($fileCSV);

                $this->get('session')->getFlashBag()->add('success', "Successfully imported  aradial payment history.");
                return $this->redirect($this->generateUrl('dhi_admin_aradial_payment_history_list'));
            } else {

                $this->get('session')->getFlashBag()->add('failure', "Can not upload a file, Please select another file.");
                return $this->redirect($this->generateUrl('dhi_admin_aradial_payment_history_import'));
            }
        }

        return $this->render('DhiAdminBundle:AradialPaymentHistory:importHistory.html.twig');
    }

    private function uploadFile($uploadedFile) {
        $basicPath = $this->get('kernel')->getRootDir() . '/../web/uploads';

        if (null === $uploadedFile) {
            return false;
        } else {
            $ext = pathinfo($uploadedFile['file']["name"], PATHINFO_EXTENSION);
            $filename = rand(3, 100) . '_' . time();
            $filename = md5($filename) . '.' . $ext;
            $this->path = $basicPath . '/' . $filename;

            if (move_uploaded_file($uploadedFile['file']["tmp_name"], $this->path)) {
                return $this->path;
            } else {
                return false;
            }
        }
    }

}
