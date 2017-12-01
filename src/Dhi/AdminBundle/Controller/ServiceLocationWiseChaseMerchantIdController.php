<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId;
use Dhi\AdminBundle\Form\Type\ServiceLocationWiseChaseMerchantIdFormType;

class ServiceLocationWiseChaseMerchantIdController extends Controller {

    public function indexAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        if (!($this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_list') || $this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_create') || $this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_update') || $this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_delete'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location wise chase merchant id list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $objServiceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        $serviceLocations = array_map(function($location) {
            return $location['name'];
        }, $objServiceLocations);

        $chaseMerchantIds = $em->getRepository("DhiAdminBundle:ChaseMerchantIds")->getActiveChaseMerchantIds();

        return $this->render('DhiAdminBundle:ServiceLocationWiseChaseMerchantId:index.html.twig', array('serviceLocations' => $serviceLocations, 'chaseMerchantIdData' => $chaseMerchantIds));
    }

    public function listJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $credentialColumns = array('ServiceLocation', 'MerchantName', 'MerchantId', 'CreatedAt', 'UpdatedAt');
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($credentialColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        $merchantId = $request->get('MerchantId');
        
        if(!empty($merchantId))
        {
            $gridData['search_data']['MerchantId'] = $merchantId;
            $gridData['SearchType'] = 'ANDLIKE';
        }
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'c.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'ServiceLocation') {
                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'MerchantName') {
                $orderBy = 'cm.merchantName';
            }
            if ($gridData['order_by'] == 'MerchantId') {
                $orderBy = 'cm.merchantId';
            }
            if ($gridData['order_by'] == 'CreatedAt') {
                $orderBy = 'c.createdAt';
            }
            if ($gridData['order_by'] == 'UpdatedAt') {
                $orderBy = 'c.updatedAt';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->getServiceLocationWiseChaseMidGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
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
                    $row[] = $resultRow['serviceLocation'];
                    $row[] = $resultRow['merchantName'];
                    $row[] = $resultRow['merchantId'];
                    $row[] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('Y-m-d h:i:s') : 'N/A';
                    $row[] = $resultRow['updatedAt'] ? $resultRow['updatedAt']->format('Y-m-d h:i:s') : 'N/A';
                    $row[] = $resultRow['id'];
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function newAction(Request $request) {

        if (!$this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location wise merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array('admin' => $admin);
        $em = $this->getDoctrine()->getManager();


        $objServiceLocationWiseChaseMerchantId = new ServiceLocationWiseChaseMerchantId();
        $form = $this->createForm(new ServiceLocationWiseChaseMerchantIdFormType(), $objServiceLocationWiseChaseMerchantId);
        if ($request->getMethod() == "POST") {
            
            $serviceLocationId = $request->get('dhi_admin_service_location_wise_chase_merchantId')['serviceLocation'];
            $isFormValid = true;
            $checkServiceLocationExists = $em->getRepository("DhiAdminBundle:ServiceLocationWiseChaseMerchantId")->findOneBy(array('serviceLocation' => $serviceLocationId, 'isDeleted' => 0));
            if($checkServiceLocationExists){
                $isFormValid = false;
                $this->get('session')->getFlashBag()->add('failure', "Service location is already added.");
            }
            $form->handleRequest($request);
            if ($form->isValid() && $isFormValid) {

                $objServiceLocationWiseChaseMerchantId->setCreatedBy($admin);
                $objServiceLocationWiseChaseMerchantId->setUpdatedBy($admin);
                $em->persist($objServiceLocationWiseChaseMerchantId);
                $em->flush();

                $serviceLocation = $objServiceLocationWiseChaseMerchantId->getServiceLocation()->getName();
                $newMerchantId = $objServiceLocationWiseChaseMerchantId->getChaseMerchantIds()->getMerchantId();
                $newUpdatedAt = $objServiceLocationWiseChaseMerchantId->getUpdatedAt();
                $newUpdatedBy = $objServiceLocationWiseChaseMerchantId->getUpdatedBy()->getUsername();
                
                $activityLog['activity'] = 'Add Service Location wise Chase Merchant Id';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has assign chase merchant id : '".$newMerchantId."' to service location :". $serviceLocation;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $chaseMidAuditLog = array(
                    'ServiceLocationWiseChaseMerchantId' => $objServiceLocationWiseChaseMerchantId->getId(),
                    'ServiceLocation' => $serviceLocation,
                    'NewChaseMerchantId' => $newMerchantId,
                    'NewUpdatedAt' => $newUpdatedAt,
                    'NewUpdatedBy' => $newUpdatedBy,
                    'OperationType' => 'Inserted'
                );

                $this->get('ChaseMerchantIdAuditLog')->saveChaseMerchantIdAuditLog($chaseMidAuditLog);

                $this->get('session')->getFlashBag()->add('success', "Chase merchant id assigned successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
            }
        }
        return $this->render('DhiAdminBundle:ServiceLocationWiseChaseMerchantId:new.html.twig', array(
                    'form' => $form->createView()
        ));
    }

    public function editAction(Request $request, $id) {

        if (!$this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to edit service location wise merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array('admin' => $admin);
        $em = $this->getDoctrine()->getManager();


        $objServiceLocationWiseChaseMerchantId = $em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->find($id);
        if (!$objServiceLocationWiseChaseMerchantId) {
            $this->get('session')->getFlashBag()->add('failure', "Record does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
        }
        $form = $this->createForm(new ServiceLocationWiseChaseMerchantIdFormType(), $objServiceLocationWiseChaseMerchantId);
        $form->remove('serviceLocation');

        if ($request->getMethod() == "POST") {

            $oldMerchantId = $objServiceLocationWiseChaseMerchantId->getChaseMerchantIds()->getMerchantId();
            $oldUpdatedAt = $objServiceLocationWiseChaseMerchantId->getUpdatedAt();
            $oldUpdatedBy = $objServiceLocationWiseChaseMerchantId->getUpdatedBy()->getUsername();

            $form->handleRequest($request);
            if ($form->isValid()) {

                $objServiceLocationWiseChaseMerchantId->setUpdatedBy($admin);
                $em->persist($objServiceLocationWiseChaseMerchantId);
                $em->flush();

                $serviceLocation = $objServiceLocationWiseChaseMerchantId->getServiceLocation()->getName();
                $newMerchantId = $objServiceLocationWiseChaseMerchantId->getChaseMerchantIds()->getMerchantId();
                $newUpdatedAt = $objServiceLocationWiseChaseMerchantId->getUpdatedAt();
                $newUpdatedBy = $objServiceLocationWiseChaseMerchantId->getUpdatedBy()->getUsername();

                if ($oldMerchantId != $newMerchantId) {
                    $chaseMidAuditLog = array(
                        'ServiceLocationWiseChaseMerchantId' => $objServiceLocationWiseChaseMerchantId->getId(),
                        'ServiceLocation' => $serviceLocation,
                        'NewChaseMerchantId' => $newMerchantId,
                        'NewUpdatedAt' => $newUpdatedAt,
                        'NewUpdatedBy' => $newUpdatedBy,
                        'OldChaseMerchantId' => $oldMerchantId,
                        'OldUpdatedAt' => $oldUpdatedAt,
                        'OldUpdatedBy' => $oldUpdatedBy,
                        'OperationType' => 'Updated'
                    );

                    $this->get('ChaseMerchantIdAuditLog')->saveChaseMerchantIdAuditLog($chaseMidAuditLog);
                }
                $activityLog['activity'] = 'Edit Service Location wise Chase Merchant Id';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed chase merchant id '".$oldMerchantId."' to '".$newMerchantId."' for service location: " . $serviceLocation . ".";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Service location wise chase merchant id updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
            }
        }
        return $this->render('DhiAdminBundle:ServiceLocationWiseChaseMerchantId:edit.html.twig', array(
                    'form' => $form->createView(),
                    'objServiceLocationWiseChaseMerchantId' => $objServiceLocationWiseChaseMerchantId
        ));
    }

    public function deleteAction(Request $request) {

        $id = $request->get('id');

        if (!$this->get('admin_permission')->checkPermission('service_location_wise_chase_merchant_id_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service location wise chase merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_wise_chase_merchant_id_list'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objServiceLocationWiseChaseMerchantId = $em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->find($id);
        if ($objServiceLocationWiseChaseMerchantId) {
            
            $oldMerchantId = $objServiceLocationWiseChaseMerchantId->getChaseMerchantIds()->getMerchantId();
            $oldUpdatedAt = $objServiceLocationWiseChaseMerchantId->getUpdatedAt();
            $oldUpdatedBy = $objServiceLocationWiseChaseMerchantId->getUpdatedBy()->getUsername();
            $serviceLocation = $objServiceLocationWiseChaseMerchantId->getServiceLocation()->getName();
            
            $objServiceLocationWiseChaseMerchantId->setIsDeleted(true);
            $em->flush();
            
            $newUpdatedAt = $objServiceLocationWiseChaseMerchantId->getUpdatedAt();
            $newUpdatedBy = $objServiceLocationWiseChaseMerchantId->getUpdatedBy()->getUsername();
            
            $chaseMidAuditLog = array(
                'ServiceLocationWiseChaseMerchantId' => $id,
                'ServiceLocation' => $serviceLocation,
                'NewUpdatedAt' => $newUpdatedAt,
                'NewUpdatedBy' => $newUpdatedBy,
                'OldChaseMerchantId' => $oldMerchantId,
                'OldUpdatedAt' => $oldUpdatedAt,
                'OldUpdatedBy' => $oldUpdatedBy,
                'OperationType' => 'Deleted'
            );

            $this->get('ChaseMerchantIdAuditLog')->saveChaseMerchantIdAuditLog($chaseMidAuditLog);

            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete Service Location Wise Chase Merchant Id';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted service location wise chase merchant id Service location: '".$serviceLocation."'. Id: " . $objServiceLocationWiseChaseMerchantId->getId();
            $result = array('type' => 'success', 'message' => 'Service location wise chase merchant id deleted successfully!');
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service location wise chase merchant id";
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete service location wise chase merchant id!');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
