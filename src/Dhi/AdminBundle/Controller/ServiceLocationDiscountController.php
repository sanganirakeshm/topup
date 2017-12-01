<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\ServicesLocationDiscountFormType;
use Dhi\AdminBundle\Form\Type\ServiceLocationFormType;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Entity\ServiceLocationDiscount;
use Dhi\UserBundle\Entity\UserActivityLog;

class ServiceLocationDiscountController extends Controller {

    /**
     * List Services in Admin panel
     */
    public function indexAction(Request $request) {
/*
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location bundle discount list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        return $this->render('DhiAdminBundle:ServiceLocationDiscount:index.html.twig'); */
    }

    public function newAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location bundle discount.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objServiceLocation = new ServiceLocation();
        $objServiceLocationDiscount = new ServiceLocationDiscount();
        
        $objServiceLocation->addServiceLocationDiscount($objServiceLocationDiscount);
        
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                
                $objServiceLocation = $form->getData();
                $serviceLocations = $form->get('serviceLocation')->getData();
                $discountArr = array();
                if($objServiceLocation->getServiceLocationDiscounts()) {
                    foreach ($objServiceLocation->getServiceLocationDiscounts() as $key => $serviceLocationDiscount) {
                        $discountArr[$key]['min_amount'] = $serviceLocationDiscount->getMinAmount();
                        $discountArr[$key]['max_amount'] = $serviceLocationDiscount->getMaxAmount();
                        $discountArr[$key]['percentage'] = $serviceLocationDiscount->getPercentage();
                    }
                }
                
                if(!empty($discountArr) && !empty($serviceLocations)) {
                    
                    foreach($discountArr as $discount) {
                    
                        foreach($serviceLocations as $serviceLocation) {
                        
                            $objDiscount = new ServiceLocationDiscount();
                            $objDiscount->setServiceLocation($serviceLocation);
                            $objDiscount->setMinAmount($discount['min_amount']);
                            $objDiscount->setMaxAmount($discount['max_amount']);
                            $objDiscount->setPercentage($discount['percentage']);
                            $em->persist($objDiscount);
                        }
                    }
                }

                $em->flush();
                
                // set audit log add service location discount
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add service location discount';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new discount of service locations bundle successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service location bundle discount added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
            }
        }
        return $this->render('DhiAdminBundle:ServiceLocationDiscount:new.html.twig', array('form' => $form->createView()));        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service location bundle discount.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($id);

        if (count($objServiceLocation->getServiceLocationDiscounts()) <= 0) {
            $this->get('session')->getFlashBag()->add('failure', "Service location bundle discount does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_ip_zone_edit'));
        }
        
        $form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                
                $objServiceLocation = $form->getData();
                $serviceLocation = $form->get('serviceLocation')->getData();
                
                if($objServiceLocation->getServiceLocationDiscounts()){
                
                    $discountUpdatedId = array();
                    
                    foreach ($objServiceLocation->getServiceLocationDiscounts() as $serviceLocationDiscount){
                        
                        $serviceLocationDiscount->setServiceLocation($serviceLocation);
                        $em->persist($serviceLocationDiscount);
                        $em->flush();
                                        
                        $discountUpdatedId[] = $serviceLocationDiscount->getId();
                    }
                    
                    $deleteDiscountList = $em->getRepository('DhiAdminBundle:ServiceLocationDiscount')->getRemoveDiscountList($id,$discountUpdatedId);
                
                    if($deleteDiscountList){
                
                        foreach ($deleteDiscountList as $deleteDiscount){
                
                            $em->remove($deleteDiscount);
                        }
                    }
                }
                
                // set audit log edit service location discount
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit service location discount';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated discount of service location bundle ". $serviceLocation->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Service location bundle discount updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_ip_zone_list'));
            }
        }

        return $this->render('DhiAdminBundle:ServiceLocationDiscount:edit.html.twig', array('form' => $form->createView(), 'id' => $id));        
    }

    public function deleteAction(Request $request, $id) {
        /*
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('service_location_discount_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete service location bundle discount.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        
        // set audit log delete service location discount
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete service location discount';
        
        $objServices = $em->getRepository('DhiUserBundle:Service')->find($id);

        if ($objServices) {
            $serviceName = $objServices->getName();
            $em->remove($objServices);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Service deleted successfully!");
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted service ". $serviceName;
            

        } else {

            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service ";
            $this->get('session')->getFlashBag()->add('failure', "Service does not exist.");

        }
        
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        return $this->redirect($this->generateUrl('dhi_admin_service_list')); */
    }
	
	public function validateDiscountRangeAction(Request $request) {
        
        $em = $this->getDoctrine()->getManager();
        
        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            
            $serviceLocationData = $request->get('dhi_service_location');
            $id = $request->get('id');
			
			if($id) {
				
				$objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($id);
				$form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);                               
            }else {
				
				$objServiceLocation = new ServiceLocation();
				$objServiceLocationDiscount = new ServiceLocationDiscount();

				$objServiceLocation->addServiceLocationDiscount($objServiceLocationDiscount);
				$form = $this->createForm(new ServicesLocationDiscountFormType(), $objServiceLocation);                                
            }
			
			$form->handleRequest($request);
            $objServiceLocation = $form->getData();
            $serviceLocations = $form->get('serviceLocation')->getData();
            
            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error']  = array();
            
            if($objServiceLocation && $serviceLocationData['serviceLocationDiscounts']) {
                
                $serviceLocationData['serviceLocationDiscounts'] = array_values($serviceLocationData['serviceLocationDiscounts']);
                
                if($objServiceLocation->getServiceLocationDiscounts()) {
                    
                        $amountArr = array();
						$discountUpdatedIds = array();
                        $key = 0;
                        
                        foreach ($objServiceLocation->getServiceLocationDiscounts() as $serviceLocationDiscount) {
                            
                            $minAmount = '';
                            $maxAmount = '';
                            $isValid = 1;
                            
                            $collectionIndex = $serviceLocationData['serviceLocationDiscounts'][$key]['collectionIndex'];
                            
                            if($serviceLocationDiscount->getMinAmount()) {
                                
                                $minAmount = $serviceLocationDiscount->getMinAmount();                                 
                            }else{
                                    
                               // $jsonResponse['error'][$collectionIndex]['minAmount'] = 'Please enter minimum amount.';
                                $isValid = 0;
                            }
							
							if($serviceLocationDiscount->getMaxAmount()) {
                                
                                $maxAmount = $serviceLocationDiscount->getMaxAmount();                                 
                            }else{
                                    
                               // $jsonResponse['error'][$collectionIndex]['maxAmount'] = 'Please enter maximum amount.';
                                $isValid = 0;
                            }
                            
							if($id) {
								
								$discountUpdatedIds[$serviceLocationDiscount->getId()] = $serviceLocationDiscount->getId();
							}
                            $amountArr[$collectionIndex] = array('minAmount' => $minAmount, 'maxAmount' => $maxAmount, 'isValid' => $isValid);
                            $key++;
                        }
                        
                        if($amountArr) {
                            
                            $key = 0;
                            foreach ($objServiceLocation->getServiceLocationDiscounts() as $serviceLocationDiscount) {
                                
                                $compMinAmt = $serviceLocationDiscount->getMinAmount();
                                $compMaxAmt = $serviceLocationDiscount->getMaxAmount();
                                
                                $collectionIndex = $serviceLocationData['serviceLocationDiscounts'][$key]['collectionIndex'];
                                
                                $isError = 0;
                                
                                foreach ($amountArr as $key1 => $amountVal) {
                                    
                                    if($collectionIndex != $key1 && $amountVal['isValid'] == 1 && !$isError) {
                            
                                        if($serviceLocationDiscount->getMinAmount()) {
                                            
                                            if($compMinAmt >= $amountVal['minAmount'] && $compMinAmt <= $amountVal['maxAmount']) {
                                                
                                                $jsonResponse['error'][$collectionIndex]['minAmount'] = 'This range is already exists.';
                                                $isError = 1;
                                            }
                                        }
                                        
                                        if($serviceLocationDiscount->getMaxAmount()) {
                                            
                                            if($compMaxAmt >= $amountVal['minAmount'] && $compMaxAmt <= $amountVal['maxAmount']) {
                                                
                                                $jsonResponse['error'][$collectionIndex]['maxAmount'] = 'This range is already exists.';
                                                $isError = 1;
                                            }                      
                                        }                                                          
                                    }                                    
                                }
                                
                                if(!$isError) {
                                    
                                    if(!empty($serviceLocations)) {

                                        $minAmtExistsLocation = array();
                                        $maxAmtExistsLocation = array();
                                        
                                        foreach($serviceLocations as $serviceLocation) {
                                            
                                            $serviceLocationId     = $serviceLocation->getId();
                                            $serviceLocationName   = $serviceLocation->getName();
                                            $discountId            = $serviceLocationDiscount->getId();
                                                                                
                                            //Compare min amount service_location_discount table
                                            if($serviceLocationDiscount->getMinAmount()) {
                                                
                                                $resultMinAmount = $em->getRepository('DhiAdminBundle:ServiceLocationDiscount')->checkAmountRangeExists($serviceLocationId,$serviceLocationDiscount->getMinAmount(),$discountId,$discountUpdatedIds);
                                                
                                                if($resultMinAmount) {
                                                   
                                                    $minAmtExistsLocation[] = $serviceLocationName;                                                    
                                                }
                                            }
                                            
                                            //Compare max amount service_location_discount table
                                            if($serviceLocationDiscount->getMaxAmount()) {
                                                
                                                $resultMaxAmount = $em->getRepository('DhiAdminBundle:ServiceLocationDiscount')->checkAmountRangeExists($serviceLocationId,$serviceLocationDiscount->getMaxAmount(),$discountId,$discountUpdatedIds);
                                                
                                                if($resultMaxAmount) {
                                                
                                                    $maxAmtExistsLocation[] = $serviceLocationName;
                                                }
                                            }
                                        }
                                        
                                        if(!empty($minAmtExistsLocation)) {
                                            
                                            $jsonResponse['error'][$collectionIndex]['minAmount'] = 'This range is already exists in '.implode(',', $minAmtExistsLocation).' location';
                                        }
                                        if(!empty($maxAmtExistsLocation)) {

                                            $jsonResponse['error'][$collectionIndex]['maxAmount'] = 'This range is already exists in '.implode(',', $maxAmtExistsLocation).' location';
                                        }
                                        
                                    }
                                }
                                
                                $key++;
                            }                            
                        }
                        
                    
                    
                }
            }
            
            if(count($jsonResponse['error']) > 0) {

                $jsonResponse['status'] = 'error';
                
            }
            //echo "<pre>";print_r($jsonResponse);exit;
            echo json_encode($jsonResponse);
            exit;
                        
        }else{
    		
    		throw $this->createNotFoundException('Invalid Page Request');
    	}
    }
	
}
