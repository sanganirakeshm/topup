<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\PromoCode;
use Dhi\AdminBundle\Entity\DiscountCode;
use Dhi\AdminBundle\Entity\ServiceLocation;
//use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\DiscountCodeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Entity\DiscountCodeServiceLocation;

use Symfony\Component\HttpFoundation\StreamedResponse;

class DiscountCodeController extends Controller {

    public function indexAction(Request $request) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('discount_code_list') || $this->get('admin_permission')->checkPermission('discount_code_create') || $this->get('admin_permission')->checkPermission('discount_code_update') || $this->get('admin_permission')->checkPermission('discount_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view global promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

		$em = $this->getDoctrine()->getManager();
		$data  = $em->getRepository('DhiUserBundle:Service')->findAll();

		 $arrActivityLog = array();
        foreach ($data as $key => $activity) {

            $arrActivityLog[] = $activity->getName();
        }
        $serviceLocations = array();
        if ($admin->getGroup() != 'Super Admin') {
            $accessLocation = $admin->getServiceLocations();
            if($accessLocation){
                foreach ($accessLocation as $key => $value) {
                    $serviceLocations[] = $value->getName();
                }
            }
        }else{
            $objServiceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
            $serviceLocations = array_map(function($location){ return $location['name']; } , $objServiceLocations);
        }
        return $this->render('DhiAdminBundle:DiscountCode:index.html.twig',array('services' => $arrActivityLog, "serviceLocations"    => $serviceLocations));
    }

    public function checkDiscountCodeAction(Request $request){

        $userDiscountCode = $request->get('userDiscountCode');
        $userId= $request->get('userId');
        $codeType  = $request->get('codeType');
        $amount = $request->get('amount');
        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();

        // get discount code detaisl
        $objPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('discountCode' => $userDiscountCode));
        $result = array();
        // Check for code exists in Database
        if($objPromoCode){

            //check for code is not expire
            $paymentDate = date('M-d-Y');
            $codeDateBegin =  $objPromoCode->getStartDate()->format('M-d-Y');
            $codeDateEnd =  $objPromoCode->getEndDate()->format('M-d-Y');
            if (($paymentDate > $codeDateBegin) && ($paymentDate < $codeDateEnd)){
                $result['dateValid'] = true;
                $result['error'] = "codeIsActive";
            } else {
                $result['dateValid'] = false;
                if($paymentDate < $codeDateBegin){
                    $result['error'] = "codeInActive";
                }
                if($paymentDate > $codeDateEnd){
                    $result['error'] = "codeExpire";
                }
            }
            //
            if($result['dateValid']){

                // checkout for the user userd only once
                $userDetails = $em->getRepository('DhiUserBundle:User')->findOneBy(array('id' => $userId));
                $objDiscountCodeLocationDetails = $em->getRepository('DhiAdminBundle:DiscountCodeCustomer')->findOneBy(array('DiscountCodeId' => $objPromoCode,'user' => $userDetails));

                if($objDiscountCodeLocationDetails){
                    // if user alreary user discount code once
                   $result['locationuser'] = $objDiscountCodeLocationDetails->getId();
                   $result['msg'] = "useralready user code";
                   $result['error'] = "alreayUsed";
                }else{

                    // if user never used discount code
                    $result['msg'] = "Freash user code";
                    $discountPercentage = $objPromoCode->getAmount();
                    // dis
                    $discountAmount = ($discountPercentage * $amount) / 100;
                    $AmountAfterdiscount= $amount - $discountAmount;
                    $result['discountAmount'] = $discountAmount;
                    $result['percentage'] = $discountPercentage;

                    $result['discountPercentage'] = $discountPercentage;
                    $result['beformdisc'] = $amount;
                    $result['finalAmount'] = $AmountAfterdiscount;
                    $result['success'] = "codeIsValid";
                    $result['userid'] = $userDetails->getId();
                }
            }else{
                $result['msg'] = "code is not live";
            }
        }else{
             $result['error'] = "codeNotExists";
        }

         $result['codeid'] = $objPromoCode->getId();

        $response = new Response(json_encode($result));

	$response->headers->set('Content-Type', 'application/json');
        return $response;

    }
    //added for grid
   public function discountCodeListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('Id','ServiceLocation','DiscountCode','Amount','StartDate','EndDate','CreatedBy','Status','Note','Action');

        $admin = $this->get('security.context')->getToken()->getUser();

        $helper = $this->get('grid_helper_function');

        $gridData = $helper->getSearchData($promoCodeColumns);

    	if(!empty($gridData['search_data'])) {
            $this->get('session')->set('discountSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('discountSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'dc.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'pc.id';
            }
            if ($gridData['order_by'] == 'DiscountCode') {

                $orderBy = 'dc.discountCode';
            }
            if ($gridData['order_by'] == 'Amount') {

                $orderBy = 'dc.amount';
            }
            if ($gridData['order_by'] == 'StartDate') {

                $orderBy = 'dc.startDate';
            }
            if ($gridData['order_by'] == 'EndDate') {

                $orderBy = 'dc.endDate';
            }
            if ($gridData['order_by'] == 'CreatedBy') {

                $orderBy = 'dc.createdBy';
            }
            if ($gridData['order_by'] == 'Status') {

                $orderBy = 'dc.status';
            }
             if ($gridData['order_by'] == 'Note') {

                $orderBy = 'dc.note';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $serviceLocation = '';
        if ($admin->getGroup() != 'Super Admin') {
            $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $serviceLocation = empty($serviceLocation) ? '0' : $serviceLocation;
        }
        
        $data  = $em->getRepository('DhiAdminBundle:DiscountCode')
                    ->getDiscountCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $serviceLocation);

//        $data  = $em->getRepository('DhiUserBundle:PromoCode')->getPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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



                    $id =  $resultRow->getId();
                     $data  = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId'=>$id));
                     $serviceLocation = array();
                    foreach ($data AS $resultRow1) {

                                $serviceLocation[] = $resultRow1->getServiceLocation()->getName();

                    }
                    $serviceLocationStr = implode(', ', $serviceLocation);

//                    die();


                    $amountType = $resultRow->getAmountType()? $resultRow->getAmountType() : 'N/A';
                    $discountValue = ($amountType == 'amount')? '$'.$resultRow->getAmount() : $resultRow->getAmount(). '%';

                     $actualNotes = $resultRow->getNote();
                    $shortNote  = null;
                    if(strlen($actualNotes) > 10){
                        $shortNote = substr($actualNotes, 0, 10).'...';
                    }else{
                        $shortNote = $resultRow->getNote();
                    }

                    $shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $resultRow->getId() .');">' . $shortNote. '</a>';

                    
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = ($serviceLocationStr) ? $serviceLocationStr : '';
                    $row[] = $resultRow->getDiscountCode();
                    $row[] = $discountValue;
                    $row[] = ($resultRow->getStartDate()) ? $resultRow->getStartDate()->format('M-d-Y') : 'N/A';
                    $row[] = ($resultRow->getEndDate()) ? $resultRow->getEndDate()->format('M-d-Y') : 'N/A';
                    $row[] = $resultRow->getCreatedBy();
                    $row[] = $resultRow->getStatus() == true ? 'Active':'Inactive';
                    $row[] = $shortNote;
                    $row[] = $resultRow->getStatus();
                    $output['aaData'][] = $row;


//
//					 $count = 1;
//                    $servicesCount = count($resultRow->getService());
//                    $serviceName = '';
//                    if($resultRow->getService()){
//						foreach ($resultRow->getService() as $service) {
//
//                            if ($count == $servicesCount) {
//
//                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
//                            } else {
//
//                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
//                            }
//                            $count++;
//                        }
//					}
//
//                    $serviceLocationCount = count($resultRow->getServiceLocations());
//
//                    $locationCount = 1;
//                    $locationName = '';
//                    if($resultRow->getServiceLocations()){
//                        foreach ($resultRow->getServiceLocations() as $location) {
//
//                            if ($locationCount == $serviceLocationCount) {
//
//                                $locationName .= $location->getName();
//                            } else {
//
//                                $locationName .= $location->getName().", ";
//                            }
//                            $locationCount++;
//                        }
//					} else {
//						$locationName = 'N/A';
//					}
//					$row = array();
//
//
//					$row[] = $resultRow->getId();
//					$row[] = $serviceName;
//					$row[] = $resultRow->getCreatedBy();
//					$row[] = $locationName;
//
//					$row[] = $resultRow->getTotalTime().' Day';
//					$row[] = $resultRow->getPromoCode();
//					$row[] = ($resultRow->getEndDate()) ? $resultRow->getEndDate()->format('M-d-Y') : 'N/A';
//					$row[] = $resultRow->getMaxRedemption() ? $resultRow->getMaxRedemption().' Time' : "N/A";
//					$row[] = ($resultRow->getRedeemedTime()) ? $resultRow->getRedeemedTime().' Time' : '0';
//					$row[] = $resultRow->getReason();
//					//$row[] = ($resultRow->getCreatedAt()) ? $resultRow->getCreatedAt()->format('M-d-Y') : 'N/A';
//					$row[] = $resultRow->getStatus() == true ? 'Active':'InActive';
//
//					$row[] = $resultRow->getId().'^'.$resultRow->getStatus();


                }
                //die();
            }
        }


        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function duplicateDiscountCodeAction(Request $request) {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            $id = $request->get('id');
            if ($id) {
                $objPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->find($id);
                $form = $this->createForm(new DiscountCodeFormType(array()), $objPromoCode);
            } else {
                $objDiscountCode = new DiscountCode();
                $form = $this->createForm(new DiscountCodeFormType(array()), $objDiscountCode);
            }
            $form->handleRequest($request);
            $objPromoCode = $form->getData();
            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error'] = array();
            if ($id) {
                $resultPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->checkPromoCodeExist($id);
                foreach ($resultPromoCode as $key => $promoCode) {
                    if (trim($promoCode['discountCode']) == trim($objPromoCode->getDiscountCode())) {
                        $jsonResponse['error'] = 'Global Promo Code already exists';
                    }
                }
                if (count($jsonResponse['error']) > 0) {
                    $jsonResponse['status'] = 'error';
                }
            } else {
                $code = trim($objPromoCode->getDiscountCode());
                $resultPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array("discountCode" => $code));
                if (sizeof($resultPromoCode) == 1) {
                    $jsonResponse['error'] = 'Global Promo Code already exists';
                }
                if (count($jsonResponse['error']) > 0) {
                    $jsonResponse['status'] = 'error';
                }
            }

            echo json_encode($jsonResponse);
            exit;
        }
    }

    // functionality takeover by duplicateDiscountCodeAction
    public function uniqueDiscountCodeAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            $id = '';
           // $settopBoxData = $request->get('dhi_set_top_box');
//			print_r($settopBoxData); exit;
           $id = $request->get('id');
//
            if($id) {

                $objPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->find($id);
                $form = $this->createForm(new DiscountCodeFormType(array()), $objPromoCode);
            }else {

                $objDiscountCode = new DiscountCode();
                $objservicelocationCode = new ServiceLocation();

                $form = $this->createForm(new DiscountCodeFormType(array()),$objDiscountCode);

            }

            $form->handleRequest($request);

            $objPromoCode = $form->getData();

            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error']  = array();

            $resultPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->checkPromoCodeExist($id);
            foreach($resultPromoCode as $key => $promoCode) {
                    if($promoCode['discountCode'] == $objPromoCode->getDiscountCode()){
                  $jsonResponse['error'] = 'Global Promo Code Already Exist';
              }
            }
            if(count($jsonResponse['error']) > 0) {

                $jsonResponse['status'] = 'error';

            }

            echo json_encode($jsonResponse);
            exit;

            //$objServiceLocation->get



        }else{

    		throw $this->createNotFoundException('Invalid Page Request');
    	}
    }

    public function newAction(Request $request) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('discount_code_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add global promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $serviceLocation = array();
        if ($admin->getGroup() != 'Super Admin') {
            $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $serviceLocation = !empty($serviceLocation) ? $serviceLocation : array();
        }

        $objDiscountCode = new DiscountCode();
        $form = $this->createForm(new DiscountCodeFormType(array('serviceLocation' => $serviceLocation)), $objDiscountCode);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $postData = $request->get($form->getName());
                $formData = $form->getData();

                // upload images
                $brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';

				if($objDiscountCode->getDiscountImage()) {
    				$file1 = $objDiscountCode->getDiscountImage();
    				$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
    				$file1->move($brochuresDir, $fileName1);
    				$objDiscountCode->setDiscountImage($fileName1);
				}

                $objDiscountCode->setCreatedBy($admin->getUsername());
                $em->persist($objDiscountCode);
                $em->flush();

                //set Discount service location
                $lastInsertId = $objDiscountCode->getId();
                $objDiscountCode1 = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('id'=>$lastInsertId));

                if(isset($postData["serviceLocations"]) && $postData["serviceLocations"] != null){
                    foreach($postData["serviceLocations"] as $serLocation){
                        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('id'=>$serLocation));
                        $objDiscountServiceLocation  = new DiscountCodeServiceLocation();
                        $objDiscountServiceLocation->setDiscountCodeId($objDiscountCode1);
                        $objDiscountServiceLocation->setServiceLocation($objServiceLocation);
                        $em->persist($objDiscountServiceLocation);
                        $em->flush();
                    }
                }

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Global Promo Code';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added global promo code ".$formData->getDiscountCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Global Promo Code added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_discount_code_list'));
            }
        }
        return $this->render('DhiAdminBundle:DiscountCode:new.html.twig', array(
                    'form' => $form->createView(),
        ));

    }

    public function editAction(Request $request, $id) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('discount_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update global promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objDiscountCode = $em->getRepository('DhiAdminBundle:DiscountCode')->find($id);
        $discountCodeId = $objDiscountCode->getId();


        $objServiceLocation = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId' => $discountCodeId));
        $serviceLocation = array();
        foreach($objServiceLocation as $sd){
        $serviceLocation [] = $sd->getServiceLocation()->getId();
        }
        if($objDiscountCode->getDiscountImage()){
            $oldImagePath1 = $objDiscountCode->getDiscountImage();
		}
        $objDiscountCode->setNote('');
        $form = $this->createForm(new DiscountCodeFormType(array()),$objDiscountCode);


        if ($request->getMethod() == "POST") {



            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();

                //image

                $brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';

				if($formData->getDiscountImage() !== null && !empty($oldImagePath1) ){
					$this->removeFile($oldImagePath1); // remove old file, see this at the bottom
					$file1 = $formData->getDiscountImage();
					$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
					$file1->move($brochuresDir, $fileName1);
					$objDiscountCode->setDiscountImage($fileName1); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
				} else if(!empty($oldImagePath1)) {
					$objDiscountCode->setDiscountImage($oldImagePath1);
				}else if($formData->getDiscountImage() !== null){
					$file1 = $formData->getDiscountImage();
					$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
					$file1->move($brochuresDir, $fileName1);
					$objDiscountCode->setDiscountImage($fileName1);
				}


                 $postData = $request->get($form->getName());
                $objDiscountCode->setCreatedBy($admin->getUsername());
                $em->persist($objDiscountCode);
                $em->flush();

                 //set Discount service location
                $lastInsertId = $objDiscountCode->getId();


                $objDiscountCode1 = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('id'=>$lastInsertId));

                $objDiscountCodeServiceLocation = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId' => $objDiscountCode1));
                if($objDiscountCodeServiceLocation){
                    foreach($objDiscountCodeServiceLocation as $objDiscountCodeServiceLocation1){
                        $em->remove($objDiscountCodeServiceLocation1);
                    $em->flush();
                    }
//                    $em->remove($objDiscountCodeServiceLocation);
//                    $em->flush();
                }

                if(isset($postData["serviceLocations"]) && $postData["serviceLocations"] != null){
                    foreach($postData["serviceLocations"] as $serLocation){
                        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('id'=>$serLocation));
                        $objDiscountServiceLocation  = new DiscountCodeServiceLocation();
                        $objDiscountServiceLocation->setDiscountCodeId($objDiscountCode1);
                        $objDiscountServiceLocation->setServiceLocation($objServiceLocation);
                        $em->persist($objDiscountServiceLocation);
                        $em->flush();
                    }
                }

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update Global Promo Code';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated global promo code ".$formData->getDiscountCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Global Promo Code updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_discount_code_list'));
            }
        }
        if (!empty($oldImagePath1) && file_exists($this->getRequest()->server->get('DOCUMENT_ROOT').$this->getRequest()->getBasePath().'/uploads/'.$oldImagePath1)) {

            $isSecure       = $request->isSecure() ? 'https://' : 'http://';
            $imgUrl     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl("uploads");
            $img = $imgUrl.'/'.$oldImagePath1;
        }else{
            $img = '';
        }
        return $this->render('DhiAdminBundle:DiscountCode:edit.html.twig', array(
            'form' => $form->createView(),
            'discount' => $objDiscountCode,
            'discountCodeServiceLocation' =>$serviceLocation,
            'discountImg' => $img
        ));
    }


    public function removeFile($file)
	{

		$brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
		$file_path = $brochuresDir.'/'.$file;

		if(file_exists($file_path)) {   unlink($file_path); }
	}
    public function disableAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objDisocuntCode = $em->getRepository('DhiAdminBundle:DiscountCode')->find($id);

        if (!$objDisocuntCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find global promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_discount_code_list'));
        }
		if($objDisocuntCode->getStatus() == 1){
			    $objDisocuntCode->setStatus(0);
				$changeStatus = 'Disabled';
		}	else{
				$objDisocuntCode->setStatus(1);
				$changeStatus = 'Enabled';

		}

                $em->persist($objDisocuntCode);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = $changeStatus.' Global Promo Code';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has ". $changeStatus ." global promo code ".$objDisocuntCode->getDiscountCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Global Promo Code '.$changeStatus.' successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_discount_code_list'));



    }

    public function deleteImageAction(Request $request, $id){
        $response     = array('status' => 'fail', 'msg'=>'Can not remove promo code image');
        $admin        = $this->get('security.context')->getToken()->getUser();
        $em           = $this->getDoctrine()->getManager();
        $objPromoCode = $em->getRepository('DhiAdminBundle:DiscountCode')->find($id);
        if ($objPromoCode){
            if ($objPromoCode->getDiscountImage() && file_exists($this->getRequest()->server->get('DOCUMENT_ROOT').$this->getRequest()->getBasePath().'/uploads/'.$objPromoCode->getDiscountImage())) {
                unlink($this->getRequest()->server->get('DOCUMENT_ROOT').$this->getRequest()->getBasePath().'/uploads/'.$objPromoCode->getDiscountImage());
                $objPromoCode->setDiscountImage(null);
                $em->persist($objPromoCode);
                $em->flush();
                $response = array('status' => 'success', 'msg' => 'Image has been removed successfully!');
            }
        }
        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }   

    public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('promo_code_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete global promo code.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Global Promo Code';
        if ($objPromoCode) {

            // set audit log delete email campagin

            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted global promo code ".$objPromoCode->getPromoCode();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objPromoCode);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'Promo code deleted successfully!');

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete promo code ".$objPromoCode->getPromoCode();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete global promo code!');
        }
         $response = new Response(json_encode($result));

		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

	 public function previewPromoCodeAction(Request $request){

        $view = array();
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();


        $promocode= new DiscountCode();
        $form = $this->createForm(new DiscountCodeFormType(array()), $promocode);

        $data = array();
        if($request->getMethod() == "POST") {

            $form->handleRequest($request);

            $data = $form->getData();
        }

        $view['data'] = $data;

        return $this->render('DhiAdminBundle:PromoCode:previewPromoCode.html.twig', $view);
    }

	public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('discount_code_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin          = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $isSecure       = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath    = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg     = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT').'/bundles/dhiuser/images/logo.png';

        $file_name = 'Discount_Code_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data
        $searchData = array();
        if($this->get('session')->has('discountSearchData') && $this->get('session')->get('discountSearchData') != '') {
            $searchData = $this->get('session')->get('discountSearchData');
        }

        //	$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
		$em = $this->getDoctrine()->getManager();

        //$objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
                
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $promoData = $em->getRepository('DhiAdminBundle:DiscountCode')->getPdfDiscountData($searchData, $adminServiceLocationPermission);

        $serviceLocation = array();
        foreach ($promoData as $key => $resultRow) {
            $id = $resultRow->getId();

            $data = array();
            $data  = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId'=>$id));
            $row = array();
            foreach ($data as $key1=> $resultRow1 ){
               $row[] = $resultRow1->getServiceLocation()->getName();
            }
            $serviceLocation[] =  implode(', ', $row);
        }

        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf global promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user global promo code";

        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:DiscountCode:exportPdf.html.twig', array(
                'discountData' => $promoData,
                'serviceLocation' => $serviceLocation,
        ));

        unset($promoData);
        unset($serviceLocation);
        return new Response(
            $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
            200,
            array(
                'Content-Type'          => 'application/pdf',
                'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
            )
        );

        // create html to pdf
        /*$pdf = $this->get("white_october.tcpdf")->create();

        // set document information
        $pdf->SetCreator('ExchangeVUE');
        $pdf->SetAuthor('ExchangeVUE');
        $pdf->SetTitle('ExchangeVUE');
        $pdf->SetSubject('Promo Code');

        // set default header data
        // set default header data
        if(file_exists($logoImgDirPath)){

            $pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="' . $dhiLogoImg . '" />');
        }

        // set margins
        $pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

        // set auto page breaks
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

        // set image scale factor
        $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

        // set font
        $pdf->SetFont('helvetica', '', 9);

        // add a page
        $pdf->AddPage();

        // Load a stylesheet and render html
        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');

        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:DiscountCode:exportPdf.html.twig', array(
                'discountData' => $promoData,
                'serviceLocation' => $serviceLocation,
        ));
        //        echo $html;die();
        // output the HTML content
        $pdf->writeHTML($html);

        // reset pointer to the last page
        $pdf->lastPage();

        // Close and output PDF document
        $pdf->Output($file_name, 'D');
        exit();*/
    }


    public function exportCsvAction(Request $request) {

        //Check permission

        if (!$this->get('admin_permission')->checkPermission('discount_code_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $searchData = array();
        if($this->get('session')->has('discountSearchData') && $this->get('session')->get('discountSearchData') != '') {
            $searchData = $this->get('session')->get('discountSearchData');
        }

        //	$ipAddressZones = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserIpAddressZone($admin);
		 $em = $this->getDoctrine()->getManager();
        
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        $promoData = $em->getRepository('DhiAdminBundle:DiscountCode')->getPdfDiscountData($searchData, $adminServiceLocationPermission);


        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv global promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv global promo code";



        $this->get('ActivityLog')->saveActivityLog($activityLog);

        //$result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($promoData) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Service Location","Discount Code","Percentage/Amount","Start Date","End Date","Created By","Note","Status"), ',');
            // Query data from databaseexit;

            foreach ($promoData as $key => $resultRow) {

                $id = $resultRow->getId();
                //  get service location
                $em = $this->getDoctrine()->getManager();
                    $data  = $em->getRepository('DhiAdminBundle:DiscountCodeServiceLocation')->findBy(array('discountCodeId'=>$id));
                     $serviceLocation = array();
                    foreach ($data AS $resultRow1) {

                                $serviceLocation[] = $resultRow1->getServiceLocation()->getName();

                    }
                    $serviceLocationStr = implode(', ', $serviceLocation);

                $amountType = $resultRow->getAmountType()? $resultRow->getAmountType() : 'N/A';
                $discountValue = ($amountType == 'amount')? '$'.$resultRow->getAmount() : $resultRow->getAmount(). '%';
//                $discountValue = ($amountType == 'amount')? '$'.$resultRow->getAmount() : ($amountType == 'percentage')?$resultRow->getAmount(). '%':'N/A';
                $discountCode = $resultRow->getDiscountCode();
                $percentage = $discountValue;
                
                $startDAate =  $resultRow->getStartDate()->format('M-d-Y');
                $endDAate =  $resultRow->getEndDate()->format('M-d-Y');
                $createdBy =  $resultRow->getCreatedBy();
                $note =  $resultRow->getNote();
                $status = $resultRow->getStatus() == true ? 'Active' : 'InActive';

                 fputcsv($handle, array(
                        $serviceLocationStr,
                        $discountCode,
                     $percentage,
                        $startDAate,
                        $endDAate,
                        $createdBy,
                        $note,
                        $status,
                ), ',');

//
//
//
//				 $count = 1;
//                    $servicesCount = count($resultRow->getService());
//                    $serviceName = '';
//                    if($resultRow->getService()){
//						foreach ($resultRow->getService() as $service) {
//
//                            if ($count == $servicesCount) {
//
//                                $serviceName .= $service->getName();
//                            } else {
//
//                                $serviceName .= $service->getName();
//                            }
//                            $count++;
//                        }
//					}
//
//					 $customerCount = count($resultRow->getUsers());
//
//                    $customCount = 1;
//                    $customerName = '';
//                    if($resultRow->getUsers()){
//                        foreach ($resultRow->getUsers() as $user) {
//
//                            if ($customCount == $customerCount) {
//
//                                $customerName .= $user->getName();
//                            } else {
//
//                                $customerName .= $user->getName().", ";
//                            }
//                            $customCount++;
//                        }
//                    }
//
//                    $serviceLocationCount = count($resultRow->getServiceLocations());
//
//                    $locationCount = 1;
//                    $locationName = '';
//                    if($resultRow->getServiceLocations()){
//                        foreach ($resultRow->getServiceLocations() as $location) {
//
//                            if ($locationCount == $serviceLocationCount) {
//
//                                $locationName .= $location->getName();
//                            } else {
//
//                                $locationName .= $location->getName().", ";
//                            }
//                            $locationCount++;
//                        }
//                    }
//
//                $service		  = $serviceName;
//                $createdBy		  = $resultRow->getCreatedBy();
//                $serviceLocation  = $locationName;
//                $totalTime        = $resultRow->getTotalTime();
//                $promoCode        = $resultRow->getPromoCode();
//                $endDate          = $resultRow->getEndDate()->format('M-d-Y');
//				$maxRedemption    = $resultRow->getMaxRedemption();
//                $redeemedTime     = $resultRow->getRedeemedTime();
//                $reason			  = $resultRow->getReason();
//				//$dateTime         = ($resultRow->getCreatedAt()) ? $resultRow->getCreatedAt()->format('M-d-Y') : 'N/A' ;
//                $status			  = $resultRow->getStatus() == true ? 'Active' : 'InActive';
//
//                fputcsv($handle, array(
//                        $service.' ',
//                        $createdBy,
//                        $serviceLocation,
//                        $totalTime,
//                        $promoCode,
//					    $endDate,
//                        $maxRedemption,
//						$redeemedTime,
//					    $reason,
//					   // $dateTime,
//                        $status
//                ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'discount_code_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');

        return $response;
    }


    public function viewCustomerAction(Request $request) {

	$id = $request->get('id');
        return $this->render('DhiAdminBundle:DiscountCode:viewCustomer.html.twig',array('discountid' => $id));
    }

    public function discountCodeCustomerListJsonAction(Request $request,$orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
	$id = $request->get('id');
        $promoCodeColumns = array('customer','discountCode','redeemDate');
		$admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);

        if(!empty($gridData['search_data'])) {
            $this->get('session')->set('discountSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('discountSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'dcc.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'customer') {

                $orderBy = 'u.firstname';
            }
            if ($gridData['order_by'] == 'discountCode') {

                $orderBy = 'dc.discountCode';
            }
            if ($gridData['order_by'] == 'redeemDate') {

                $orderBy = 'dcc.redeemDate';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiAdminBundle:DiscountCodeCustomer')->getDiscountCodeCustomerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $id);

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
					//print_r($resultRow);
					$row = array();
					if($resultRow->getUser()){
//						$userName = $resultRow->getUser()->getName();
                                                $userName = $resultRow->getUser()->getUsername();
					}	else	{
						$userName = 'N/A';
					}
					$username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow->getUser()->getId())) . '">' . $userName . '</a>';
					$row[] =  $username;
					$row[] =  $resultRow->getDiscountCodeId()->getDiscountCode();
					$row[] = ($resultRow->getRedeemDate()) ? $resultRow->getRedeemDate()->format('Y-m-d H:i:s') : 'N/A';
                    $output['aaData'][] = $row;
                }
            }
        }


        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
