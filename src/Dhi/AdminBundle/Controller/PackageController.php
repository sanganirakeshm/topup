<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\Package;
use Dhi\AdminBundle\Form\Type\PackageFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Entity\PackageWiseTikiLivePlan;
use Dhi\AdminBundle\Form\Type\PackageWiseTikiLivePlanFormType;

class PackageController extends Controller {

    public function indexAction(Request $request) {
        $admin = $this->get('security.context')->getToken()->getUser();

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('package_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view package list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
				$em = $this->getDoctrine()->getManager();

        // Service Location
        $serviceLocations = array();
        if ($admin->getGroup() != 'Super Admin') {
            $adminLocation = $admin->getServiceLocations();
            if ($adminLocation) {
                foreach ($adminLocation as $key => $value) {
                    $serviceLocations[] = $value->getName();
                }
            }
            sort($serviceLocations);
        } else {
            $locations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
            if (!empty($locations)) {
                foreach ($locations as $location) {
                    $serviceLocations[] = $location['name'];
                }
            }
        }

				$allPackageType  = $em->getRepository('DhiAdminBundle:Package')->getPackageType();
				$packageType = array();
        foreach ($allPackageType as $key => $packageName) {
            $packageType[$key] = $allPackageType[$key]['packageType'];
        }
        
        $tikiLivePlan = $em->getRepository('DhiAdminBundle:TikilivePromoCode')->getDistinctTikiLivePlan();
        $arrTikiLivePlan = array();
        if($tikiLivePlan){
            foreach ($tikiLivePlan as $plan){
                $arrTikiLivePlan[] = $plan['planName'];
            }
        }
        
        return $this->render('DhiAdminBundle:Package:index.html.twig',array('serviceLocation' => $serviceLocations,'packageType' => $packageType, 'tikiLivePlanName' => $arrTikiLivePlan ));
    }

	public function packageListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $admin = $this->get('security.context')->getToken()->getUser();
        $packageColumns = array('Id','PackageName','PackageType','Amount','BandWidth','ServiceLocation','Validaity', 'TikiLivePlanName', 'FreeRechargeCard');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($packageColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'p.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'p.id';
            }

            if ($gridData['order_by'] == 'PackageName') {

                $orderBy = 'p.packageName';
            }
            if ($gridData['order_by'] == 'PackageType') {

                $orderBy = 'p.packageType';
            }
            if ($gridData['order_by'] == 'Amount') {

                $orderBy = 'p.amount';
            }

            if ($gridData['order_by'] == 'BandWidth') {

                $orderBy = 'p.bandwidth';
            }
            if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'Validaity') {

                $orderBy = 'p.validity';
            }
            if ($gridData['order_by'] == 'TikiLivePlanName') {

                $orderBy = 'pkw.tikiLivePlanName';
            }
            if ($gridData['order_by'] == 'FreeRechargeCard') {

                $orderBy = 'p.freeRechargeCard';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        // Service Location
        $serviceLocations = array();
        if ($admin->getGroup() != 'Super Admin') {
            $adminLocation = $admin->getServiceLocations();
            if ($adminLocation) {
                foreach ($adminLocation as $key => $value) {
                    $serviceLocations[] = $value->getName();
                }
            }
        }
        $isAssignedTikiLivePlan = $request->get('isAssignedTikiLivePlan');

        $data  = $em->getRepository('DhiAdminBundle:Package')->getPackageGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $serviceLocations, $isAssignedTikiLivePlan);
        
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
                    $row[] = $resultRow['id'];
                    $row[] = $resultRow['packageName'];
                    $row[] = $resultRow['packageType'];
                    $row[] = $resultRow['amount'];
                    $row[] = $resultRow['bandwidth'];
                    $row[] = $resultRow['serviceLocation'];
                    $row[] = $resultRow['validity'];
                    $row[] = $resultRow['tikiLivePlanName'] ? $resultRow['tikiLivePlanName'] : 'N/A';
                    $row[] = $resultRow['freeRechargeCard'] ? 'Enable' : 'Disable';
                    $row[] = $resultRow['id'] .'^'. $resultRow['packageId'] .'^'. $resultRow['freeRechargeCard'];

                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPackage = new Package();
        $form = $this->createForm(new PackageFormType(), $objPackage);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $objPackage = $form->getData();

                $em->persist($objPackage);
                $em->flush();

                // set audit log add package
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Package';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added package " . $objPackage->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Package added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_package_list'));
            }
        }
        return $this->render('DhiAdminBundle:SupportLocation:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPackage = $em->getRepository('DhiServiceBundle:Package')->find($id);

        if (!$objPackage) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find package.");
            return $this->redirect($this->generateUrl('dhi_admin_package_list'));
        }

        $form = $this->createForm(new PackageFormType(), $objPackage);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objPackage = $form->getData();

                $em->persist($objPackage);
                $em->flush();

                // set audit log add package
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Package';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated package " . $objPackage->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Package updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_package_list'));
            }
        }

        return $this->render('DhiAdminBundle:Package:edit.html.twig', array(
                    'form' => $form->createView(),
                    'package' => $objPackage
        ));
    }

    public function deleteAction(Request $request) {

        $id = $request->get('id');

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPackage = $em->getRepository('DhiServiceBundle:Package')->find($id);

        if ($objPackage) {

            // set audit log delete package
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete Package';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted package " . $objPackage->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objPackage);
            $em->flush();

            $this->get('session')->getFlashBag()->add('success', 'Package deleted successfully.');
        } else {
            $this->get('session')->getFlashBag()->add('failure', 'Unable to find package.');
        }

        return $this->redirect($this->generateUrl('dhi_admin_package_list'));
    }

    public function deleteCompensationAction(Request $request) {

        $id = $request->get('id');

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objCompensation = $em->getRepository('DhiUserBundle:Compensation')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete compensation';

        if ($objCompensation && $objCompensation->getIsStarted() == 0) {
            
            // set audit log delete compensation

            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted compensation " . $objCompensation->getTitle();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objCompensation);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Compensation deleted successfully!');
        
        } else {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to delete compensation " . ($objCompensation ? $objCompensation->getTitle() : '');
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete compensation!');
        }

        $response = new Response(json_encode($result));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function deleteTikiLivePlanAction(Request $request) {

        $packageId = $request->get('id');

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $isAllowedToDelete = true;
        
        if (!$this->get('admin_permission')->checkPermission('package_wise_tikilive_plan_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to unassigned tikilive plan.');
            $isAllowedToDelete = false;
        }
        
        if(empty($packageId)){
            $result = array('type' => 'danger', 'message' => 'Invalid page request.');
            $isAllowedToDelete = false;
        }
        
        if($isAllowedToDelete){
            $objPackageWiseTikiLivePlan = $em->getRepository('DhiAdminBundle:PackageWiseTikiLivePlan')->findOneBy(array('packageId' => $packageId));
            $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $packageId));
            $packageName = 'N/A';
            if($objPackage){
                $packageName = $objPackage->getPackageName();
            }
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Unassign TikiLive Plan';

            if ($objPackageWiseTikiLivePlan) {

                $activityLog['description'] = "Admin " . $admin->getUsername() . " has unassigned tikilive plan '".$objPackageWiseTikiLivePlan->getTikiLivePlanName()."' from package:" . $packageName;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $em->remove($objPackageWiseTikiLivePlan);
                $em->flush();
                $result = array('type' => 'success', 'message' => 'Tikilive plan has been unassigned successfully!');
            } else {

                $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to unassigned tikilive plan from package:" . $packageName;
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                $result = array('type' => 'danger', 'message' => 'Record does not exist.');
            }
        }

        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editTikiLivePlanAction(Request $request, $packageId) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $result = 'success';
        $label = 'Update';
        $msg = '';
        $class = '';
        $isError = false;
        
        if (!$this->get('admin_permission')->checkPermission('package_wise_tikilive_plan_edit')) {
            $isError = true;
            $result = 'danger';
            $class = 'danger';
            $msg = 'You are not allowed to edit tikilive plan' ;
        }

        $em = $this->getDoctrine()->getManager();
        
        $objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId' => $packageId));
        
        if(!$objPackage && !$isError){
            $isError = true;
            $result = 'danger';
            $class = 'danger';
            $msg = 'Package does not exist.';
        }
        
        $objPackageWiseTikiLivePlan = $em->getRepository('DhiAdminBundle:PackageWiseTikiLivePlan')->findOneBy(array('packageId' => $packageId));
        if(!$objPackageWiseTikiLivePlan){
            $label = 'Assign';
            $objPackageWiseTikiLivePlan = new PackageWiseTikiLivePlan();
        }
        
        $tikiLivePlan = $em->getRepository('DhiAdminBundle:TikilivePromoCode')->getDistinctTikiLivePlan();
        
        $arrTikiLivePlan = array();
        $arrTikiLivePlan[''] = 'Select Plan';
        
        if($tikiLivePlan){
            foreach ($tikiLivePlan as $plan){
                $arrTikiLivePlan[$plan['planName']] = $plan['planName'];
            }
        }
        if(!$tikiLivePlan && !$isError){
            $isError = true;
            $result = 'danger';
            $class = 'warning';
            $msg = 'TikiLive Plan not found.' ;
        }
        
        $form = $this->createForm(new PackageWiseTikiLivePlanFormType($arrTikiLivePlan), $objPackageWiseTikiLivePlan);

        if ($request->getMethod() == "POST") {
            $oldTikilivePlan = $objPackageWiseTikiLivePlan->getTikiLivePlanName() ? $objPackageWiseTikiLivePlan->getTikiLivePlanName() : 'N/A';
            $form->handleRequest($request);
            if ($form->isValid() && !$isError) {
                
                $objPackageWiseTikiLivePlan->setPackageId($packageId);
                if($label == 'Assign'){
                    $objPackageWiseTikiLivePlan->setCreatedBy($admin->getId());
                }
                $objPackageWiseTikiLivePlan->setUpdatedBy($admin->getId());
                $em->persist($objPackageWiseTikiLivePlan);
                $em->flush();
                
                $newTikilivePlan = $objPackageWiseTikiLivePlan->getTikiLivePlanName();
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] =  $label. ' Tikilive Plan';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has ".$label." tikilive plan Old Tikilive plan: '".$oldTikilivePlan."' New Tikilive plan: '".$newTikilivePlan."' to PackageName: " . $objPackage->getPackageName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', "Tikilive plan has been assigned successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_package_list'));
            }else{
                if(!$objPackage){
                    $this->get('session')->getFlashBag()->add('failure', 'Package does not exist.');
                    return $this->redirect($this->generateUrl('dhi_admin_package_list'));
                }
                if($form->isSubmitted() && !$form->isValid()){
                    
                    $tikiLivePlanName = $request->get('dhi_admin_package_wise_tikilive_plan')['tikiLivePlanName'];
                    if($tikiLivePlanName){
                        if(!in_array($tikiLivePlanName, $arrTikiLivePlan)){
                            $this->get('session')->getFlashBag()->add('failure', 'TikiLive Plan not found.');
                            return $this->redirect($this->generateUrl('dhi_admin_package_list'));
                        }
                    }
                    
                    $errMsg = $form['tikiLivePlanName']->getErrorsAsString();
                    if(!empty($errMsg)){
                        $errMsg = str_replace("ERROR:","", $errMsg);
                        $this->get('session')->getFlashBag()->add('failure', $errMsg);
                    }
                    return $this->redirect($this->generateUrl('dhi_admin_package_list'));
                }
            }
        }

        return $this->render('DhiAdminBundle:Package:editTikiLivePlan.html.twig', array(
                    'form' => $form->createView(),
                    'result' => $result,
                    'packageId' => $packageId,
                    'msg' => $msg,
                    'label' => $label,
                    'class' => $class
        ));
       
    }
    
    public function changeFreeRechargeStatusAction(Request $request) {

        $id = $request->get('id');
        $status = $request->get('status');
        
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('package_update_free_recharge_card_status')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to change free recharge card status.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        
        
        $objPackage = $em->getRepository('DhiAdminBundle:Package')->find($id);
        
        if ($objPackage) {
            $label = 'disable';
            if ($status == 1) {
                $label = 'enable';
            }
            $activityLog = array();
            
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = ucfirst($label) . ' Free Recharge Card Package';
        
            $objPackage->setFreeRechargeCard($status);
            $em->persist($objPackage);
            $em->flush();
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has ". $label ." free recharge card for '" . $objPackage->getPackageName(). "'package.";
            $result = array('type' => 'success', 'message' => 'Free recharge card  ' . $label . ' for package successfully.');
            
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
        }else{
            $result = array('type' => 'danger', 'message' => 'Package does not exist.');
        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function syncPackageAction(Request $request) {

        $response = $this->get('packageActivation')->syncPackages();

        $errorMsg = '';
        $successMsg = '';

        if($response) {

            $errorMsg   = ($response['errorMsg'])?implode('<br/>',$response['errorMsg']):'';
            $successMsg = ($response['successMsg'])?implode('<br/>',$response['successMsg']):'';;
        }

        if($errorMsg) {
            $this->get('session')->getFlashBag()->add('failure', $errorMsg);
        }
        if($successMsg) {
            $this->get('session')->getFlashBag()->add('success', $successMsg);
        }


        return $this->redirect($this->getRequest()
            ->headers
            ->get('referer'));
        //echo "<pre>";print_r($response);exit;
    }
}
