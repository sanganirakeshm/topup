<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\ChaseMerchantIds;
use Dhi\AdminBundle\Form\Type\ChaseMerchantIdsFormType;

class ChaseMechantIdsController extends Controller {

    public function indexAction(Request $request) {
        
        if (!($this->get('admin_permission')->checkPermission('chase_merchant_ids_list') || $this->get('admin_permission')->checkPermission('chase_merchant_ids_create') || $this->get('admin_permission')->checkPermission('chase_merchant_ids_update'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view chase merchant ids list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:ChaseMerchantIds:index.html.twig');
    }

    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $credentialColumns = array('MerchantName', 'MerchantId', 'IsDefault', "CreatedBy", "CreatedAt", "UpdatedAt", "IsActive");
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($credentialColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'c.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'MerchantName') {
                $orderBy = 'c.merchantName';
            }
            if ($gridData['order_by'] == 'MerchantId') {
                $orderBy = 'c.merchantId';
            }
            if ($gridData['order_by'] == 'IsDefault') {
                $orderBy = 'c.isDefault';
            }
            if ($gridData['order_by'] == 'CreatedAt') {
                $orderBy = 'c.createdAt';
            }
            if ($gridData['order_by'] == 'CreatedBy') {
                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'UpdatedAt') {
                $orderBy = 'c.updatedAt';
            }
            if ($gridData['order_by'] == 'IsActive') {
                $orderBy = 'c.isActive';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->getChaseMarchantIdsGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
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
                    
                    $flagDelete = 1;
                    $row = array();
                    $row[] = ($resultRow['merchantName'] ? $resultRow['merchantName'] : 'N/A');
                    $row[] = $resultRow['merchantId'];
                    $row[] = ($resultRow['isDefault'] ? 'Yes' : 'No');
                    $row[] = ($resultRow['username'] ? $resultRow['username'] : 'N/A');
                    $row[] = ($resultRow['createdAt'] ? $resultRow['createdAt']->format('m/d/Y H:i:s') : 'N/A');
                    $row[] = ($resultRow['updatedAt'] ? $resultRow['updatedAt']->format('m/d/Y H:i:s') : 'N/A');
                    $row[] = ($resultRow['isActive'] == true ? 'Enabled' : 'Disabled');
                    $editFlag = 0;
                    $activeInactiveFlag = 0;
                    $chaseMidAvailableInServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocationWiseChaseMerchantId')->findBy(array('chaseMerchantIds' => $resultRow['id'],'isDeleted' => false));
                    if(!empty($chaseMidAvailableInServiceLocation)){
                        $activeInactiveFlag = 1;
                    }

                    $row[] = $resultRow['id'] . '^' . $editFlag . '^' . (($resultRow['isActive'] == true) ? 1 : 0) . '^' . $activeInactiveFlag;

                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function newAction(Request $request) {
        
        if (!$this->get('admin_permission')->checkPermission('chase_merchant_ids_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add chase merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array('admin' => $admin);
        $em = $this->getDoctrine()->getManager();


        $objChaseMerchantIds = new ChaseMerchantIds();
        $form = $this->createForm(new ChaseMerchantIdsFormType(), $objChaseMerchantIds);
        if ($request->getMethod() == "POST") {

            $merchantId = $request->get('dhi_admin_chase_merchatids')['merchantId'];
            $merchantName = $request->get('dhi_admin_chase_merchatids')['merchantName'];
            $isDefault = isset($request->get('dhi_admin_chase_merchatids')['isDefault']) ? $request->get('dhi_admin_chase_merchatids')['isDefault'] : 0;
            $countChaseMID = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findAll();
            
            if(count($countChaseMID) == 0 ){
                $isDefault = 1;
            }
            
            $isFormValid = true;
            $checkMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->checkUniqueMerchantId($merchantId, $merchantName);
            if ($checkMerchantId) {
                $isFormValid = false;
                $this->get('session')->getFlashBag()->add('failure', "This merchant name or merchant id is already exists!");
            }

            $form->handleRequest($request);
            if ($form->isValid() && $isFormValid) {

                if ($isDefault) {
                    $objChaseMerchantIds->setIsDefault(true);
                    $objAllMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findAll();
                    if ($objAllMerchantId) {
                        foreach ($objAllMerchantId as $record) {
                            $record->setIsDefault(false);
                            $em->persist($record);
                            $em->flush();
                        }
                    }
                }
                $ipAddress = $this->get('session')->get('ipAddress');
                if (!$ipAddress) {
                    $ipAddress = $this->get('GeoLocation')->getRealIpAddress();
                }
                $objChaseMerchantIds->setIpAddress($ipAddress);
                $objChaseMerchantIds->setCreatedBy($admin);
                $objChaseMerchantIds->setUpdatedBy($admin);
                $em->persist($objChaseMerchantIds);
                $em->flush();

                $isDefault = $isDefault ? 'Yes' : 'No';
                $activityLog['activity'] = 'Add Chase Merchant Id';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new chase merchant id : '".$merchantId."' and set as default :".$isDefault;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Chase merchant id added successfully.");
                return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
            }
        }
        return $this->render('DhiAdminBundle:ChaseMerchantIds:new.html.twig', array(
                    'form' => $form->createView()
        ));
    }

    public function editAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        if (!$this->get('admin_permission')->checkPermission('chase_merchant_ids_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update chase merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array(
            'admin' => $admin,
        );

        $objChaseMerchantIds = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->find($id);
        if (!$objChaseMerchantIds) {
            $this->get('session')->getFlashBag()->add('failure', "Record does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
        }
        
        $purchaseAvailable = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findBy(array('chaseMerchantId' => $id));
        $merchantIdReadOnly = false;
        if($purchaseAvailable){
            $merchantIdReadOnly = true;
        }
        
        $form = $this->createForm(new ChaseMerchantIdsFormType(), $objChaseMerchantIds);
        $form->remove('isDefault');
            
        if ($request->getMethod() == "POST") {
            
            $oldMechantId = $objChaseMerchantIds->getMerchantId();
            $form->handleRequest($request);
            
            $merchantId = $request->get('dhi_admin_chase_merchatids')['merchantId'];
            $merchantName = $request->get('dhi_admin_chase_merchatids')['merchantName'];
            
            $isFormValid = true;
            $checkMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->checkUniqueMerchantId($merchantId, $merchantName, $id);
            if ($checkMerchantId) {
                $isFormValid = false;
                $this->get('session')->getFlashBag()->add('failure', "This MerchantId is already exists!");
            }
            if ($form->isValid() && $isFormValid) {
                
                if($merchantIdReadOnly)
                {
                    $objChaseMerchantIds->setMerchantId($oldMechantId);
                }
                
                $objChaseMerchantIds->setUpdatedBy($admin);
                $em->persist($objChaseMerchantIds);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Chase Merchant Id';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has edited Chase merchant id : '".$oldMechantId."' to '".$merchantId."'";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Chase merchant id updated successfully.");
                return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
            }
        }

        return $this->render('DhiAdminBundle:ChaseMerchantIds:edit.html.twig', array(
                    'form' => $form->createView(),
                    'id' => $id,
                    'merchantIdReadOnly' => $merchantIdReadOnly
        ));
    }

    public function setDefaultMerchantIdAction(Request $request) {
        $id = $request->get('id');

        if (!$this->get('admin_permission')->checkPermission('chase_merchant_ids_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to set default chase merchant id.");
            return $this->redirect($this->generateUrl('dhi_admin_chase_merchant_ids_list'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Set Defalult Chase Merchant Id';

        $objChaseMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->find($id);
        if ($objChaseMerchantId) {
            $objChaseMerchantId->setUpdatedBy($admin);
            $objChaseMerchantId->setIsDefault(true);
            $em->persist($objChaseMerchantId);
            $em->flush();
            
            $chaseMerchantId = $objChaseMerchantId->getMerchantId();
            
            $objAllMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->findAll();
            if ($objAllMerchantId) {
                foreach ($objAllMerchantId as $record) {
                    if ($record->getId() != $id) {
                        $record->setIsDefault(false);
                        $em->persist($record);
                        $em->flush();
                    }
                }
            }

            $activityLog['description'] = "Admin " . $admin->getUsername() . " has set default merchant id '".$chaseMerchantId."'. Id: " . $objChaseMerchantId->getId();
            $result = array('type' => 'success', 'message' => 'Chase merchantId set default successfully!');
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to set default chase merchant id";
            $result = array('type' => 'danger', 'message' => 'You are not allowed to update chase merchant id!');
        }

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function activeInactiveAction(Request $request) {
        $id = $request->get('id');
        $status = $request->get('status');
        
        if (!$this->get('admin_permission')->checkPermission('chase_merchant_ids_update')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to update chase merchant id.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $label = 'disable';
        if ($status == 1) {

            $label = 'enable';
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = ucfirst($label) . ' chase MID';
        
        $objChaseMerchantId = $em->getRepository('DhiAdminBundle:ChaseMerchantIds')->find($id);
        
        if($objChaseMerchantId) {
            $curDate = new \DateTime(date('m/d/Y H:i:s'));
            $objChaseMerchantId->setUpdatedBy($admin);
            $objChaseMerchantId->setUpdatedAt($curDate);
            $objChaseMerchantId->setIsActive($status);
            $em->persist($objChaseMerchantId);
            $em->flush();
            
            $chaseMerchantId = $objChaseMerchantId->getMerchantId();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has ". $label ." chase merchant id: " . $objChaseMerchantId->getId();
            $result = array('type' => 'success', 'message' => 'Chase MID ' . $label . ' successfully.');
        }
        else    
        {
            $result = array('type' => 'danger', 'message' => 'Unable to find chase MID.');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

}
