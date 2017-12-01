<?php
namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\AdminBundle\Entity\Credit;

class PurchaseProcessController extends Controller {

    public $paymentStep    = array('1', '2', '3');
    public $paymentOptions = array();

    public function getPatmentMethod(){
		$em			= $this->getDoctrine()->getManager();
		$adminMethods = array('cash', 'eagleCash', 'pos');
		$objPaymentOptions = $em->getRepository("DhiServiceBundle:PaymentMethod")->getPaymentMethodByCode($adminMethods);
		foreach ($objPaymentOptions as $objPaymentOption) {
			$this->paymentOptions[$objPaymentOption->getId()] = lcfirst($objPaymentOption->getCode());
		}
    }

    public function viewPlanAction(Request $request) {

        $admin 		= $this->get('security.context')->getToken()->getUser();
        $em			= $this->getDoctrine()->getManager();

        $defaultService = array('ISP','IPTV','Premium','Credit','BUNDLE');
        $summaryData 	= array();
        $packages 		= array();
        $discount 		= '';

        $userId  = $request->get('userId');
        $service = $request->get('service');

        if($userId && $service && in_array($service,$defaultService)){
        	$user 		  	= $em->getRepository('DhiUserBundle:User')->find($userId);
        	$summaryData  	= $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

        	$isError = false;
		    if($service == 'IPTV' && (!empty($summaryData['Cart']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0)){
		        $responseJson['msg'] = 'Invalid package request';
		        $isError = true;

		    }else if($service == 'ISP' && (!empty($summaryData['Cart']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0)){
		        $responseJson['msg'] = 'Invalid package request';
		        $isError = true;
		    }

		    if(($service == 'IPTV' || $service == 'Premium') && !empty($summaryData['Purchased']['ISP']) && $summaryData['IsBundleAvailabledInPurchased'] == 0 && $summaryData['IsBundleAvailabledInCart'] == 0){
		        $responseJson['msg'] = 'Invalid package request';
		        $isError = true;

		    }else if($service == 'ISP' && !empty($summaryData['Purchased']['IPTV'])  && $summaryData['IsBundleAvailabledInPurchased'] == 0 && $summaryData['IsBundleAvailabledInCart'] == 0){
		        $responseJson['msg'] = 'Invalid package request';
		        $isError = true;
		    }else if($service == 'BUNDLE' && !empty($summaryData['isPartnerPromocodeApplied'])  && $summaryData['isPartnerPromocodeApplied'] == 1){
            $responseJson['msg'] = 'Invalid package request';
		        $isError = true;
		    }else if($service == 'BUNDLE' && !empty($summaryData['isBusinessPromocodeApplied'])  && $summaryData['isBusinessPromocodeApplied'] == 1){
            $responseJson['msg'] = 'Invalid package request';
	       		$isError = true;
        }

		    if(!$isError){
	        	$serviceLocationId = ($user->getUserServiceLocation())?$user->getUserServiceLocation()->getId():$summaryData['serviceLocationId'];
	        	$packages	  	= $this->get('SelevisionPackage')->getAdminPackage($user,$serviceLocationId, $service);
	        	$discount		= $this->get('BundleDiscount')->getBundleDiscount('admin',$user);
	        }
        }
              
        $view_data = array();
        $credits = $em->getRepository('DhiAdminBundle:Credit')->findBy(array(), array('amount' => 'ASC'));
        $view_data['userId']  	  	= $userId;
        $view_data['service'] 	  	= $service;
        $view_data['summaryData'] 	= $summaryData;
        $view_data['packages'] 		= $packages;
        $view_data['discount'] 		= ($discount) ? $discount['Precentage'] : '';
        $view_data['credits'] 		= $credits;
        $view_data['promotionalpackages'] = (@$packages['PROMOTIONAL']) ? @$packages['PROMOTIONAL'] : '';
        $view_data['autobundlisp']       = (@$packages['BUNDLE']['autobundleisp']) ? @$packages['BUNDLE']['autobundleisp'] : '';
        $view_data['autobundleiptv']     = (@$packages['BUNDLE']['autobundleiptv']) ? @$packages['BUNDLE']['autobundleiptv'] : '';
        $view_data['autobundleArr']     = (@$packages['BUNDLE']['autobundleArr']) ? @$packages['BUNDLE']['autobundleArr'] : '';
        $view_data['promobundleArr']     =     (@$packages['PROMOTIONALBUNDLE']) ? @$packages['PROMOTIONALBUNDLE'] : '';
        
        $view_data['isError'] 		= $isError;
        
        return $this->render('DhiAdminBundle:PurchaseProcess:viewPlan.html.twig',$view_data);
    }

    public function addToCartPlanAction(Request $request, $extendPlan){

		$defaultService = array('ISP','IPTV','Premium','Credit','BUNDLE');
    	$em				= $this->getDoctrine()->getManager();

		if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {

    		$jsonResponse = array();
    		$jsonResponse['status'] = 'failed';
    		$jsonResponse['msg'] 	= 'Something went worng in ajax request.';


    		$userId  	= $request->get('userId');
        	$sessionId	= $this->get('paymentProcess')->generateCartSessionId('admin');
        	$user 		= $em->getRepository('DhiUserBundle:User')->find($userId);

        	if($user) {

        		$processService	= $request->get('service');

        		if(in_array($processService,$defaultService)){

        		    if($processService == 'Premium'){

        		        $service = 'IPTV';
        		    }else{

        		        $service = $processService;
        		    }

	        		$creditId 		= $request->get('creditId');
	        		$flagError		= 0;

	        		$summaryData  	= $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);
	        		$objService 	= $em->getRepository('DhiUserBundle:Service')->findOneByName($service);
	        		$savedResult 	= '';

		        	if ($objService || $service == 'BUNDLE') {

						if($extendPlan > 0) {

							if($service == 'BUNDLE'){
								$objBundle = $em->getRepository('DhiAdminBundle:Bundle')->findOneBy(array('bundle_id' => $extendPlan));
								if ($objBundle) {
									$packageNameArr[$objBundle->getBundleId()]            = $objBundle->getBundleName();
									$packagePriceArr[$objBundle->getBundleId()]           = 0;
									$bandwidthArr[$objBundle->getBundleId()]              = 0;
									$validityArr[$objBundle->getBundleId()]               = 0;
									$premiumPackageNameArr[$objBundle->getBundleId()]     = $objBundle->getBundleName();
									$premiumPriceArr[$objBundle->getBundleId()]           = 0;
									$premiumPackageValidityArr[$objBundle->getBundleId()] = 0;
									$packageId                                            = $objBundle->getBundleId();
									$premiumPackageIds[$objBundle->getBundleId()]         = $objBundle->getBundleId();
								}
							}else{
								$objPackage = $em->getRepository('DhiAdminBundle:Package')->find($extendPlan);

								if($objPackage) {
									$packageNameArr[$objPackage->getPackageId()]            = $objPackage->getPackageName();
									$packagePriceArr[$objPackage->getPackageId()]           = $objPackage->getAmount();
									$bandwidthArr[$objPackage->getPackageId()]              = $objPackage->getBandwidth();
									$validityArr[$objPackage->getPackageId()]               = $objPackage->getValidity();
									$premiumPackageNameArr[$objPackage->getPackageId()]     = $objPackage->getPackageName();
									$premiumPriceArr[$objPackage->getPackageId()]           = $objPackage->getAmount();
									$premiumPackageValidityArr[$objPackage->getPackageId()] = $objPackage->getValidity();
									if($processService != 'Premium'){
										$packageId = $objPackage->getPackageId();
									}
									$premiumPackageIds[$objPackage->getPackageId()] = $objPackage->getPackageId();
								}
							}

						} else {

							$packageNameArr 			= $request->get('packageName');
							$packagePriceArr 			= $request->get('price');
							$bandwidthArr 				= $request->get('bandwidth');
							$validityArr 				= $request->get('validity');
							$premiumPackageNameArr 	   	= $request->get('premiumPackageName');
							$premiumPriceArr 		   	= $request->get('premiumPrice');
							$premiumPackageValidityArr 	= $request->get('premiumPackageValidity');
							$packageId 					= $request->get('packageId');
							$premiumPackageIds 			= $request->get('premiumPackageId');
						}

		        		//Check Validation
		        		if ($processService == 'IPTV' || $processService == 'BUNDLE') {

							if(in_array('ISP',$summaryData['AvailableServicesOnLocation']) && in_array('IPTV',$summaryData['AvailableServicesOnLocation'])){

		        		        if($summaryData['IsISPAvailabledInPurchased'] == 0 && $summaryData['IsISPAvailabledInCart'] == 0){

		        		            /*$jsonResponse['msg']    = 'Please select ISP plan for ExchangeVUE plan.';
		        		            $flagError = 1;*/
		        		        }
		        		    }

		        		    if($processService == 'IPTV' && $packageId == '') {

		        		        $jsonResponse['msg']	= 'Please select valid ExchangeVUE plan.';
		        		        $flagError = 1;
		        		    }

		        		    if ($flagError != 1 && in_array($processService, array('IPTV', 'AddOns'))) {
		        		    	$objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$packageId));

		        		    	if ($objPackage) {
		        		    		if ($objPackage->getIsExpired() == 1) {
	                            		$flagError = 1;
	                            		$jsonResponse['msg'] = 'Sorry! You can not add the plan to cart. Plan has already been expired.';
		        		    		}
		        		    	}
	                        }
		        		}
                                       
		        		if ($processService == 'ISP' && $packageId == '') {

		        		    $jsonResponse['msg']	= 'Please select valid ISP plan.';
		        			$flagError = 1;
		        		}
                                        if ($processService == 'BUNDLE' && $packageId == '') {

		        		    $jsonResponse['msg']	= 'Please select valid Bundle plan.';
		        			$flagError = 1;
		        		}

		        		if ($processService == 'Premium') {

		        		    if($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0) {

		        		        $jsonResponse['msg'] = 'Please select valid ExchangeVUE plan for premium plan.';
		        		        $flagError = 1;
		        		    }
		        		}

		        		if ($processService != 'BUNDLE' && !empty($packageId)) {
		        			$objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$packageId));
		        			if(!$objPackage){
		        				$jsonResponse['msg'] = 'Please select valid plan.';
		        		        $flagError = 1;
		        			}
		        		}


		        		if (!$flagError) {

		        			if ($packageId) {


								if ($processService == 'BUNDLE') {
									$bundleArr = $em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array('bundle_id'=>$packageId));

									if($bundleArr){
										$iptvArr = $bundleArr->getIptv();
										$ispArr  = $bundleArr->getIsp();
					                    $discountPer = $bundleArr->getDiscount();

										// Iptv Plan
                    					$objService = $em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');
			        					$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
										$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);
				        				if (!$objServicePurchase) {
											$objServicePurchase = new ServicePurchase();
				        				}
				        				if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
			                                $validity = $summaryData['employeeDefaultValidity'];
			                            }else{
			                                $validity = $ispArr->getValidity();
			                            }
                        				//$discountedAmo = ($iptvArr->getAmount() * $discountPer)/100;
										$servicePlanData = array();
										$servicePlanData['Service'] 		= $objService;
				        				$servicePlanData['PackageId'] 		= $iptvArr->getPackageId();
				        				$servicePlanData['PackageName'] 	= $iptvArr->getPackageName();
				        				$servicePlanData['ActualAmount'] 	= $bundleArr->getIptvAmount();
										$servicePlanData['PayableAmount'] 	= $iptvArr->getAmount();
		        				$servicePlanData['TermsUse'] 		= 1;
		        				$servicePlanData['Bandwidth'] 		= $iptvArr->getBandwidth();
		        				$servicePlanData['Validity'] 		= $validity;
		        				$servicePlanData['SessionId'] 		= $sessionId;
	        					$servicePlanData['IsUpgrade'] 		= 0;
	        					$servicePlanData['displayBundleDiscount']  = $bundleArr->getIptvAmount() - $iptvArr->getAmount();

	        					if ($extendPlan > 0) {
	        						$servicePlanData['isExtend'] 		= 1;
	        					}

										// Bundle
										$servicePlanData['bundle_id']         = $bundleArr->getBundleId();
										$servicePlanData['bundleDiscount']    = $discountPer;
										$servicePlanData['bundleName']        = $bundleArr->getbundleName();
										$servicePlanData['displayBundleName'] = $bundleArr->getdisplayBundleName();;
										$servicePlanData['purchase_type']     = 'BUNDLE';
                                        $servicePlanData['serviceLocation']   = (($iptvArr->getServiceLocation()) ? $iptvArr->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                                                                                
										if ($summaryData['IsIPTVAvailabledInPurchased'] == 1 && $extendPlan == 0) {
				        					$servicePlanData['IsUpgrade'] = 1;
			        						$savedResult['IPTV'] = $this->get('DashboardSummary')->upgradeIPTVServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');

				        				}else{
				        					$savedResult['IPTV'] = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');
				        				}
			        					


			        					// ISP Plan
                    					$objService = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
										$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
										$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);
				        				if (!$objServicePurchase) {
											$objServicePurchase = new ServicePurchase();
				        				}
                        				//$discountedAmo = ($ispArr->getAmount() * $discountPer)/100;

                        				if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
			                                $validity = $summaryData['employeeDefaultValidity'];
			                            }else{
			                                $validity = $ispArr->getValidity();
			                            }
										$servicePlanData = array();
										$servicePlanData['Service'] 		= $objService;
		        				$servicePlanData['PackageId'] 		= $ispArr->getPackageId();
		        				$servicePlanData['PackageName'] 	= $ispArr->getPackageName();
		        				$servicePlanData['ActualAmount'] 	= $bundleArr->getIspAmount();
										$servicePlanData['PayableAmount'] 	= $ispArr->getAmount();
		        				$servicePlanData['TermsUse'] 		= 1;
		        				$servicePlanData['Bandwidth'] 		= $ispArr->getBandwidth();
		        				$servicePlanData['Validity'] 		= $validity;
		        				$servicePlanData['SessionId'] 		= $sessionId;
	        					$servicePlanData['IsUpgrade'] 		= 0;
	        					$servicePlanData['displayBundleDiscount']  = $bundleArr->getIspAmount() - $ispArr->getAmount();

	        					if ($extendPlan > 0) {
	        						$servicePlanData['isExtend'] 		= 1;
	        					}
										// Bundle
										$servicePlanData['bundle_id']         = $bundleArr->getBundleId();
										$servicePlanData['bundleDiscount']    = $discountPer;
										$servicePlanData['bundleName']        = $bundleArr->getbundleName();
										$servicePlanData['displayBundleName'] = $bundleArr->getdisplayBundleName();;
										$servicePlanData['purchase_type']     = 'BUNDLE';
                    $servicePlanData['serviceLocation']   = ($ispArr->getServiceLocation() ? $ispArr->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));

										if ($summaryData['IsISPAvailabledInPurchased'] == 1 && $extendPlan == 0) {
				        					$servicePlanData['IsUpgrade'] = 1;
			        						$savedResult['ISP'] = $this->get('DashboardSummary')->upgradeISPServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');
				        				}else{
			        						$savedResult['ISP'] = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');
			        					}
								    }

								}else{

									$price = 0;
			        				if ($packagePriceArr[$packageId] > 0) {

			        					$price = $packagePriceArr[$packageId];
			        				}

			        				//Bandwidth for ISP pack
			        				$bandwidth = NULL;
			        				if (isset($bandwidthArr[$packageId]) && !empty($bandwidthArr[$packageId])) {

			        					$bandwidth = $bandwidthArr[$packageId];
			        				}

			        				$validity = NULL;

			        				if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
						                    $validity = $summaryData['employeeDefaultValidity'];
			                      	}else{
					                    if (isset($validityArr[$packageId]) && !empty($validityArr[$packageId])) {
							        					$validity = $validityArr[$packageId];
							        				}
					                  }

						        				if (isset($validityArr[$packageId]) && !empty($validityArr[$packageId])) {

						        					$validity = $validityArr[$packageId];
						        				}

						        				$objPackage = $em->getRepository('DhiAdminBundle:Package')->findOneBy(array('packageId'=>$packageId));

														$servicePlanData = array();
														$servicePlanData['Service'] 		= $objService;
						        				$servicePlanData['PackageId'] 		= $packageId;
						        				$servicePlanData['PackageName'] 	= $packageNameArr[$packageId];
						        				$servicePlanData['ActualAmount'] 	= $price;
						        				$servicePlanData['TermsUse'] 		= 1;
						        				$servicePlanData['Bandwidth'] 		= $bandwidth;
						        				$servicePlanData['Validity'] 		= $validity;
						        				$servicePlanData['SessionId'] 		= $sessionId;
					        					$servicePlanData['IsUpgrade'] 		= 0;
					        					$servicePlanData['serviceLocation'] = (($objPackage->getServiceLocation()) ? $objPackage->getServiceLocation() : (($user->getUserServiceLocation()) ? $user->getUserServiceLocation() : null));
					        					$servicePlanData['validityType'] 	= $objPackage->getIsHourlyPlan();
					        					if ($extendPlan > 0) {
															$servicePlanData['isExtend'] = 1;
					        					}
					        					$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
						        				$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

						        				if (!$objServicePurchase) {

													$objServicePurchase = new ServicePurchase();
						        				}

												if ($summaryData['Is' . strtoupper($service) . 'AvailabledInPurchased'] == 1 && $extendPlan == 0) {
						        					$servicePlanData['IsUpgrade'] = 1;

						        					if (strtoupper($service) == 'IPTV') {

						        						$savedResult = $this->get('DashboardSummary')->upgradeIPTVServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');
						        					}

						        					if (strtoupper($service) == 'ISP') {

						        						$savedResult = $this->get('DashboardSummary')->upgradeISPServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');
						        					}

						        				} else {

						        					$savedResult = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData, 'admin');

													if($savedResult) {

														if($processService == 'IPTV' && $extendPlan > 0){

															$this->get('session')->set('isExtendIPTV', true);
														}

														if($processService == 'ISP' && $extendPlan > 0){

															$this->get('session')->set('isExtendISP', true);
														}
													}
						        				}

											}

											if($savedResult) {
												if($processService == 'BUNDLE' && $extendPlan > 0){
													$this->get('session')->set('isExtendIPTV', true);
													$this->get('session')->set('isExtendISP', true);
													$this->get('session')->set('isExtendBundle', true);
												}
											}

					        			}

										//Save AddOn Package
					        			if (isset($premiumPackageIds) && !empty($premiumPackageIds) && $processService == 'Premium') {

											$savedResult = $this->get('DashboardSummary')->addPremiumPackage($user,$objService, $premiumPackageIds, $premiumPackageNameArr, $premiumPriceArr, $premiumPackageValidityArr, 'admin', $extendPlan);
					        			}
					        		}
		        	}

		        	// add user credit purchase code.
		        	if ($service == "Credit" && $creditId != "" && !$flagError) {

		        		$savedResult = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);
		        	}

		        	if(!$flagError){

		        	    if ($savedResult) {

							$jsonResponse['msg']	= 'Data has been saved successfully.';
		        	        $jsonResponse['status'] = 'success';
							$jsonResponse['PREMIUM'] = 0;
							$jsonResponse['IPTV'] = 0;
							$jsonResponse['ISP'] = 0;

							if($this->get('session')->get('isAddonExtendIPTV')) {

								 $jsonResponse['PREMIUM'] = 1;
							}

							if($this->get('session')->get('isExtendIPTV')) {

								 $jsonResponse['IPTV'] = 1;
							}

							if($this->get('session')->get('isExtendISP')) {

								 $jsonResponse['ISP'] = 1;
							}



		        	    }else{

		        	        $jsonResponse['msg']	= 'Data has not been saved successfully.';
		        	    }
		        	}

		        	//Update Discount data
		        	$discount = $this->get('BundleDiscount')->getBundleDiscount('admin',$user);
        		}
        	}else{

        		$jsonResponse['msg'] 	= 'Invalid user.';
        	}

        	echo json_encode($jsonResponse);
        	exit;

    	}else{

    		throw $this->createNotFoundException('Invalid Page Request');
    	}
    }


    public function validateTabsAction(Request $request){

        $em    = $this->getDoctrine()->getManager();

        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {

            $type      = $request->get('type');
            $eventType = $request->get('eventType');
            $tabId     = $request->get('tabId');
            $userId    = $request->get('userId');

            $user 		= $em->getRepository('DhiUserBundle:User')->find($userId);
            $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

            $jsonResponse = array();
            $jsonResponse['disabledTabId'] = '';
            $jsonResponse['enabledTabId']  = '';
            $jsonResponse['msg'] = '';

            if($type == 'disabledTabs'){

                $isIPTVDisabled = 0;
                $isPremiumDisabled = 0;

                if(in_array('ISP',$summaryData['AvailableServicesOnLocation']) && in_array('IPTV',$summaryData['AvailableServicesOnLocation'])){

                    if($summaryData['IsISPAvailabledInPurchased'] == 1 || $summaryData['IsISPAvailabledInCart'] == 1 ){
                        $jsonResponse['disabledTabId'][] = 'tabIPTV';
                    }else{
                         $jsonResponse['enabledTabId'][] = 'tabIPTV';
                    }


                    if($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0){

                        $jsonResponse['disabledTabId'][] = 'tabPremium';
                        $isPremiumDisabled  = 1;
                    } else {

                        $jsonResponse['enabledTabId'][] = 'tabPremium';
                    }

                    /*if($summaryData['IsISPAvailabledInPurchased'] == 0 && $summaryData['IsISPAvailabledInCart'] == 0){

                        $jsonResponse['disabledTabId'][] = 'tabIPTV';
                        $jsonResponse['disabledTabId'][] = 'tabPremium';

                        $isIPTVDisabled     = 1;
                        $isPremiumDisabled  = 1;
                    }else{

                        $jsonResponse['enabledTabId'][] = 'tabIPTV';

                        if($summaryData['IsIPTVAvailabledInPurchased'] == 1 || $summaryData['IsIPTVAvailabledInCart'] == 1){

                            $jsonResponse['enabledTabId'][] = 'tabPremium';
                            $isPremiumDisabled  = 1;
                        }
                    }*/


                }else if(in_array('IPTV',$summaryData['AvailableServicesOnLocation'])){

                    if($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0){

                        $jsonResponse['disabledTabId'][] = 'tabPremium';
                        $isPremiumDisabled  = 1;
                    }else{

                        $jsonResponse['enabledTabId'][] = 'tabPremium';
                    }

                }

                if($eventType == 'tabclick'){

                    if($tabId == 'tabIPTV' && $isIPTVDisabled){

                        $jsonResponse['msg'] = 'Please select ISP plan for ExchangeVUE.';
                    }

                    if($tabId == 'tabPremium' && $isPremiumDisabled){

                        $jsonResponse['msg'] = 'Please select ExchangeVUE plan for Premium plan.';
                    }
                }

                if($summaryData['IsBundleAvailabledInPurchased'] || $summaryData['IsBundleAvailabledInCart']){
                	$jsonResponse['disabledTabId'] = $jsonResponse['enabledTabId'] = array();
                	$jsonResponse['disabledTabId'][] = 'tabIPTV';
                	$jsonResponse['disabledTabId'][] = 'tabISP';
                	$jsonResponse['enabledTabId'][] = 'tabBundle';
                	$jsonResponse['enabledTabId'][] = 'tabPremium';
                	if($eventType == 'tabclick'){

	                    if($tabId == 'tabIPTV' || $tabId == 'tabISP'){
	                        $jsonResponse['msg'] = 'You have already purchased Bundle Plan.';
	                    }

	                    if($tabId == 'tabPremium' && $isPremiumDisabled){
	                        $jsonResponse['msg'] = 'Please select ExchangeVUE plan for Premium plan.';
	                    }
	                }
                }

                if($summaryData['isPartnerPromocodeApplied'] == 1 || $summaryData['isBusinessPromocodeApplied'] == 1) {
                    $jsonResponse['disabledTabId'][] = 'tabBUNDLE';
                }
            }

            echo json_encode($jsonResponse);
            exit;
        }
    }

    public function ajaxAccountSummaryAction(Request $request, $tab)
    {
        $view = array();

        $userId   = $request->get('userId');
        $em       = $this->getDoctrine()->getManager();
        $admin    = $this->get('security.context')->getToken()->getUser();
        $isShowLocationAlert = 0;
        $serviceLocationName = '';

        if($userId){

            $user = $em->getRepository('DhiUserBundle:User')->find($userId);

            if($user){

                if( count($admin->getServiceLocations()) > 1 ) {

                    $isShowLocationAlert = 1;
                    $serviceLocationName = ($user->getUserServiceLocation())?$user->getUserServiceLocation()->getName():'';
                }

                $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

                $view['summaryData'] = $summaryData;
                $view['userId'] = $userId;
                $view['serviceLocationName'] = $serviceLocationName;
                $view['isShowLocationAlert'] = $isShowLocationAlert;

                if(in_array($tab,array('1','2'))){

                    if ($request->isXmlHttpRequest()) {

                        if($tab == 1){

                            return $this->render('DhiAdminBundle:PurchaseProcess:ajaxAccountSummaryTabOne.html.twig', $view);
                        }

                        if($tab == 2){

                            return $this->render('DhiAdminBundle:PurchaseProcess:ajaxAccountSummaryTabTwo.html.twig', $view);
                        }
                    }
                }

            }else{

                echo 'Invalid user.';exit;
            }
        }else{

            echo 'Invalid user.';exit;
        }
    }

    public function confirmPurchaseAction(Request $request){

        $userId = $request->get('userId');
        $em    = $this->getDoctrine()->getManager();
        
        $allowToApplyDiscount = 0;
        if ($this->get('admin_permission')->checkPermission('admin_allow_to_apply_discount')) {
            $allowToApplyDiscount = 1;
        }
        
        $user = '';

		if($userId){

            $user = $em->getRepository('DhiUserBundle:User')->find($userId);

            if($user){

                $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);
            }else{

                throw $this->createNotFoundException('Invalid Page Request');
            }
        }else{

            throw $this->createNotFoundException('Invalid Page Request');
        }

        $form = $this->createFormBuilder(array())->getForm();
        return $this->render('DhiAdminBundle:PurchaseProcess:confirmPurchase.html.twig', array(
                'form'        => $form->createView(),
                'summaryData' => $summaryData,
                'userId'      => $userId,
                'user'        => $user,
                'allowToApplyDiscount' => $allowToApplyDiscount
        ));
    }

    public function validatePaymentAction(Request $request, $step){

        $jsonResponse = array();
        $jsonResponse['status']    = '';
        $jsonResponse['message']   = '';
        $jsonResponse['stepTwoResponse']    = '';

        $jsonResponse['requestStep']        = '';

        if ($request->isXmlHttpRequest()) {

            $requestParams = $request->request->all();
            if(!empty($requestParams)) {

                if($step == 2) {

                    $stepTwoResponse = $this->paymentStepTwo($request);

                    $jsonResponse['stepTwoResponse'] = $stepTwoResponse;
                    $jsonResponse['requestStep']     = 2;
                }
            }else {

                $jsonResponse['status']  = 'failed';
                $jsonResponse['message'] = 'Invalid request.';
            }
        }else{

            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }

        echo json_encode($jsonResponse);
        exit;
    }

    public function paymentStepTwo(Request $request) {

        $em          = $this->getDoctrine()->getManager(); //Init Entity Manager
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('adminSessionId'); //Get Session Id

        $requestParams   = $request->request->all();
        $errorMsgArr     = $this->paymentOptionAtrributesError();

        $eagleCashData = '';
        $cacData = '';

        $jsonResponse = array();
        $jsonResponse['errorMessages'] = '';
        $jsonResponse['status'] = '';
        $jsonResponse['paymentBy'] = '';
        $jsonResponse['message'] = '';
        $this->getPatmentMethod();

        if($requestParams){
            $isError = false;
            if($requestParams['paybleAmount'] > 0){

                if(in_array($requestParams['paymentBy'],$this->paymentOptions)){

                    $jsonResponse['paymentBy'] = $requestParams['paymentBy'];
                    $jsonResponse['status']    = 'success';
                    $this->get('session')->set('paymentBy',$requestParams['paymentBy']);
                    $this->get('session')->set('posOrderNo',$requestParams['posOrderNo']);
                }else{

                    $jsonResponse['status']  = 'failed';
                    $jsonResponse['message'] = 'Please select valid payment request.';

                    $isError = true;
                }
            }

            if($requestParams['isDeersRequiredPlanAdded'] == 1 && !$isError){

                    if(isset($requestParams['cac'])){

                        $cacData  = $requestParams['cac'];

                        if(isset($cacData['Number'])){

                            if($cacData['Number'] == ''){

                                $jsonResponse['errorMessages']['cacNumber'] = 'Please enter CAC card number.';
                            }
                        }

                        if($jsonResponse['errorMessages']){

                            $jsonResponse['status']   = 'error';
                        }else{

                            if($requestParams['paybleAmount'] <= 0){

                                $jsonResponse['paymentBy'] = 'FreePurchase';
                            }

                            $this->get('session')->set('CACCardInfo',$cacData);
                            $jsonResponse['status']      = 'success';
                        }
                    }else{

                        $jsonResponse['status']  = 'failed';
                        $jsonResponse['message'] = 'Please check CAC Card Number.';
                    }
            }

        }else{
            $jsonResponse['status']  = 'failed';
            $jsonResponse['message'] = 'Invalid request.';
        }

        return $jsonResponse;
    }

    public function doPaymentProcessAction(Request $request, $userId){
       
        $em         = $this->getDoctrine()->getManager();
        $sessionId  = $this->get('session')->get('adminSessionId'); //Get Session Id
        $admin      = $this->get('security.context')->getToken()->getUser();
        $ipAddress	= $this->get('session')->get('ipAddress');
        $this->getPatmentMethod();
        if($userId && $sessionId){
           
            $user = $em->getRepository('DhiUserBundle:User')->find($userId);

            if($user){

                $paymentBy     = $this->get('session')->get('paymentBy');
                if(in_array($paymentBy,$this->paymentOptions)){

                    $orderNumber   = $this->get('PaymentProcess')->generateOrderNumber();
                    $cacCardInfo   = $this->get('session')->get('CACCardInfo');
                    $posOrderNo   = $this->get('session')->get('posOrderNo');
                   
                    $summaryData   = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);
                    
                   
                    if($summaryData['CartAvailable'] == 1){

                    	$this->get('session')->set('IsISPAvailabledInCart',$summaryData['IsISPAvailabledInCart']);
                    	$this->get('session')->set('IsIPTVAvailabledInCart',$summaryData['IsIPTVAvailabledInCart']);


                        $isUpdateDeersField = false;
                        if($cacCardInfo && isset($cacCardInfo['Number'])) {
                            if($cacCardInfo['Number'] != '') {

                                $isUpdateDeersField = true;
                            }
                        }

                        if($summaryData['IsISPAvailabledInCart'] == 1) {

				                    if(!empty($summaryData['Cart']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0){
				                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
				                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
				                    }

				                    if(!empty($summaryData['Purchased']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0){
				                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
				                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
				                    }

				                    $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($userId);
														if(!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {

															$this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
									            return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));

														}
				                }

                        if($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart']) {

                        	if(!empty($summaryData['Cart']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0){
		                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
		                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
		                    	}

			                    if(!empty($summaryData['Purchased']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0  && $summaryData['IsBundleAvailabledInPurchased'] == 0){
			                        $this->get('session')->getFlashBag()->add('failure', 'Invalid package request');
			                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
			                    }

                        	$isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        	if($isSelevisionUser == 0) {

	                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists');
	                            return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
	                        }
                        }

                        $objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->find(array_search($paymentBy, $this->paymentOptions));

                        //Save paypal response in PaypalExpressCheckOutCustomer table
                        $objPurchaseOrder = new PurchaseOrder();
                        $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                        $objPurchaseOrder->setSessionId($sessionId);
                        if($paymentBy == 'pos'){
                            $objPurchaseOrder->setOrderNumber($posOrderNo);
                        } else {
                            $objPurchaseOrder->setOrderNumber($orderNumber);
                        }
                        $objPurchaseOrder->setUser($user);
                        $objPurchaseOrder->setTotalAmount($summaryData['TotalCartAmount']);
                        $objPurchaseOrder->setPaymentStatus('Completed');
                        $objPurchaseOrder->setPaymentBy('Admin');
                        $objPurchaseOrder->setPaymentByUser($admin);
                        $objPurchaseOrder->setEagleCashNo('');
                        $objPurchaseOrder->setCacCardNo(($cacCardInfo)?$cacCardInfo['Number']:'');
                        $objPurchaseOrder->setIpAddress($ipAddress);


                        $em->persist($objPurchaseOrder);
                        $em->flush();
                        $insertIdPurchaseOrder = $objPurchaseOrder->getId();

                        if($insertIdPurchaseOrder){

                            if($this->get('session')->get('serviceLocationSelection')) {
                                $serviceLocationObj  = $em->getRepository('DhiAdminBundle:ServiceLocation')->find($this->get('session')->get('serviceLocationSelection'));

                                if($serviceLocationObj) {
                                    $user->setUserServiceLocation($serviceLocationObj);
                                }
                            }
                            //Update deers authentication data
                            if($isUpdateDeersField) {

                                //Deers log
                                $this->deersCACIdLog($user->getUserName(),$cacCardInfo['Number']);

                                $user->setIsDeersAuthenticated(1);
                                $user->setDeersAuthenticatedAt(new \DateTime());
                                $em->persist($user);
                                $em->flush();
                            }

                            $this->get('session')->set('PurchaseOrderId', $insertIdPurchaseOrder);

                            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy(array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user->getId()));

                            if($objServicePurchase){

                                foreach ($objServicePurchase as $servicePurchase){

                                    $servicePurchase->setPurchaseOrder($objPurchaseOrder);
                                    $servicePurchase->setPaymentStatus('Completed');

                                    $em->persist($servicePurchase);
                                    $em->flush();
                                }
                            }

                            $creditPaymentRefundStatus = $this->get('packageActivation')->addCreditInUserAccount($user);

                            //Activate Purchase packages
            		        $paymentRefundStatus = $this->get('packageActivation')->activateServicePack($user, 'admin', $summaryData['AvailableServicesOnLocation']);

            		        if($paymentRefundStatus || $creditPaymentRefundStatus){

            		            $isPaymentRefund = $this->get('paymentProcess')->refundPayment();
            		        }

            		        $this->get('session')->set('sendAdminPurchaseEmail',1);
                          
                            if($paymentBy == 'pos'){
                                return $this->redirect($this->generateUrl('dhi_admin_user_order_confirm',array('ord' => $posOrderNo)));
                            } else {
                                return $this->redirect($this->generateUrl('dhi_admin_user_order_confirm',array('ord' => $orderNumber)));
                            }
                        }

                    }else{

                        $this->get('session')->getFlashBag()->add('failure', 'Purchase data not found in cart.');
                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
                    }
                }else{

                    $this->get('session')->getFlashBag()->add('failure', 'Please select valid payment option.');
                    return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
                }
            }else {

                $this->get('session')->getFlashBag()->add('failure', 'User is invalid for purchase.');
                return $this->redirect($this->generateUrl('dhi_admin_user_service_details', array('id' => $userId)));
            }

        }else{

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }
    
    public function uniquePosOrderAction(Request $request){
        
          $em = $this->getDoctrine()->getManager();
        
        if ($request->isXmlHttpRequest()) {
            
            $posOrderNo = $request->get('orderNo');
            
         
            $jsonResponse = array();
            $jsonResponse['status'] = 'success';
            $jsonResponse['error']  = array();
			
            $resultPosOrderNo = $em->getRepository('DhiServiceBundle:PurchaseOrder')->checkPosOrderNoExist($posOrderNo);
            
            
            if($resultPosOrderNo){
                $jsonResponse['error'] = 'Order Number already exists';
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

    public function activateFreePlanAction(Request $request, $userId) {

        $em 		 = $this->getDoctrine()->getManager();
        $admin      = $this->get('security.context')->getToken()->getUser();
        $sessionId  = $this->get('session')->get('adminSessionId'); //Get Session Id

        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();

        if ($userId && $sessionId) {

            $user = $em->getRepository('DhiUserBundle:User')->find($userId);

            if($user){

                $summaryData   = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

                if($summaryData){

                    $paybleAmount = $request->get('paybleAmount');

                    if ($paybleAmount == 0 || $summaryData['isEmployee'] == 1) {

                        if ($summaryData['CartAvailable'] == 1) {

                        	$this->get('session')->set('IsISPAvailabledInCart',$summaryData['IsISPAvailabledInCart']);
                        	$this->get('session')->set('IsIPTVAvailabledInCart',$summaryData['IsIPTVAvailabledInCart']);

                            $cacCardInfo   = $this->get('session')->get('CACCardInfo');
                            
                            if($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart'] == 1) {

                            	$isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
	                            if($isSelevisionUser == 0) {

	                                $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists');
	                                return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
	                            }
	                        }

	                        if($summaryData['IsISPAvailabledInCart'] == 1) {
	                        	$isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());
								if(!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {

									$this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
			            			return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
								}
	                        }

                            $isUpdateDeersField = false;
                            if($cacCardInfo && isset($cacCardInfo['Number'])) {
                                if($cacCardInfo['Number'] != '') {

                                    $isUpdateDeersField = true;
                                }
                            }

                            $cartTotalAmount = $summaryData['TotalCartAmount'];

                            if($summaryData['isEmployee'] == 1){
                            	$objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('EmployeePurchase');
                            }else{
                            	$objPaymentMethod = $em->getRepository('DhiServiceBundle:PaymentMethod')->findOneByCode('FreePlan');
                            }

                            if(!$objPaymentMethod) {
                                $this->get('session')->getFlashBag()->add('failure', 'Error No: #1000, Something went wrong on server. Please contact support if the issue persists');
                                return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase', array('userId' => $userId)));
                            }

                            $objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneBy(array('sessionId' => $sessionId, 'paymentStatus' => 'InProcess'));

                            if (!$objPurchaseOrder) {

                                $objPurchaseOrder = new PurchaseOrder();
                            }
                            $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                            $objPurchaseOrder->setSessionId($sessionId);
                            $objPurchaseOrder->setOrderNumber($orderNumber);
                            $objPurchaseOrder->setUser($user);
                            $objPurchaseOrder->setPaymentBy('Admin');
                        		$objPurchaseOrder->setPaymentByUser($admin);
                            $objPurchaseOrder->setTotalAmount($cartTotalAmount);
                            $objPurchaseOrder->setPaymentStatus('Completed');

                            $em->persist($objPurchaseOrder);
                            $em->flush();
                            $insertIdPurchaseOrder = $objPurchaseOrder->getId();

                            if ($insertIdPurchaseOrder) {

                                //Update deers authentication data
                                if($isUpdateDeersField) {
                                    
                                    //Deers log
                                    $this->deersCACIdLog($user->getUserName(),$cacCardInfo['Number']);

                                    $user->setIsDeersAuthenticated(1);
                                    $user->setDeersAuthenticatedAt(new \DateTime());
                                    $em->persist($user);
                                    $em->flush();
                                }

                                $this->get('session')->set('PurchaseOrderId', $insertIdPurchaseOrder);
                                $this->get('session')->set('PurchaseOrderNumber', $orderNumber);

                                $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy(array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user->getId()));

                                if($objServicePurchase){

                                    foreach ($objServicePurchase as $servicePurchase){

                                        $servicePurchase->setPurchaseOrder($objPurchaseOrder);
                                        $servicePurchase->setPaymentStatus('Completed');

                                        $em->persist($servicePurchase);
                                        $em->flush();
                                    }
                                }

                                //Activate Purchase packages
                                $paymentRefundStatus = $this->get('packageActivation')->activateServicePack($user, 'admin', $summaryData['AvailableServicesOnLocation']);
                                if($paymentRefundStatus){

                                    $isPaymentRefund = $this->get('paymentProcess')->refundPayment();
                                }

                                $this->get('session')->set('sendAdminPurchaseEmail',1);

                                return $this->redirect($this->generateUrl('dhi_admin_user_order_confirm', array('ord' => $orderNumber)));
                            }else{

                                $this->get('session')->getFlashBag()->add('notice', 'Order could not procced, please check order amount.');
                                return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase'));
                            }
                        } else {

                            $this->get('session')->getFlashBag()->add('notice', 'Data not found in cart.');
                            return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase'));
                        }
                    } else {

                        $this->get('session')->getFlashBag()->add('notice', 'Order could not procced, please check order amount.');
                        return $this->redirect($this->generateUrl('dhi_admin_user_confirm_purchase'));
                    }

                }else{

                    $this->get('session')->getFlashBag()->add('failure', 'Purchase data not found in cart.');
                    return $this->redirect($this->generateUrl('dhi_admin_user_service_details', array('id' => $userId)));
                }


            }else{

                $this->get('session')->getFlashBag()->add('failure', 'User is invalid for purchase.');
                return $this->redirect($this->generateUrl('dhi_admin_user_service_details', array('id' => $userId)));
            }

        } else {

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }

    public function orderConfirmationAction() {


		if($this->get('session')->has('isExtendISP')) {

			if($this->get('session')->has('isExtendISP') == true && $this->get('session')->has('ISPExtendData')) {

				$ispData = $this->get('session')->get('ISPExtendData');

				if(!empty($ispData)) {

					$flagIsp = $this->get('PaymentProcess')->extendExpiryDateCurrentServices($ispData['IspExpiryDate'], $ispData['IspUserServiceId']);

					if($flagIsp) {

						$this->get('session')->remove('ISPExtendData');
						$this->get('session')->remove('isExtendISP');

					}

				}
			}

		}

		if($this->get('session')->has('isExtendIPTV')) {

			if($this->get('session')->has('isExtendIPTV') == true && $this->get('session')->has('IPTVExtendData')) {

				$iptvData = $this->get('session')->get('IPTVExtendData');

				if(!empty($iptvData)) {

					$flagIptv = $this->get('PaymentProcess')->extendExpiryDateCurrentServices($iptvData['IptvExpiryDate'], $iptvData['IptvUserServiceId']);

					if($flagIptv) {

						$this->get('session')->remove('IPTVExtendData');
						$this->get('session')->remove('isExtendIPTV');

					}

				}
			}

		}

		if($this->get('session')->has('isAddonExtendIPTV')) {

			if($this->get('session')->has('isAddonExtendIPTV') == true && $this->get('session')->has('AddonIPTVExtendData')) {

				$addonIptvData = $this->get('session')->get('AddonIPTVExtendData');

				if(!empty($addonIptvData)) {

					$flagAddonIptv = $this->get('PaymentProcess')->extendExpiryDateCurrentServices($addonIptvData['AddonIptvExpiryDate'], $addonIptvData['AddonIptvUserServiceId']);

					if($flagAddonIptv) {

						$this->get('session')->remove('AddonIPTVExtendData');
						$this->get('session')->remove('isAddonExtendIPTV');

					}

				}
			}

		}


        //Clear Session Data
        $this->get('PaymentProcess')->clearAdminPaymentSession();
        $this->get('session')->remove('serviceLocationSelection');

        $view = array();
        $orderNumber = $this->getRequest()->get('ord');

        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);
        
       
        if (!$purchasedSummaryData) {

            throw $this->createNotFoundException('Invalid Page Request');
        }

        ## Tikilive promocode ## 
    	$tikiLivePromoCodeResponse = $this->get('paymentProcess')->redeemTikilivePromoCode($purchasedSummaryData);

        $em  = $this->getDoctrine()->getManager();
        $objpromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('redeemedBy'=>$purchasedSummaryData['UserId'],'purchaseId'=>$purchasedSummaryData['PurchaseOrderId']));

    	$view['istikilivepromocode'] = false;
        if($objpromocode){
			$view['tikilivepromocode'] = $objpromocode->getPromoCode();

			$tikiliveMsg = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'tikilive_promo_code_success_message'));
            if ($tikiliveMsg) {
                $view['tikiliveMsg'] = $tikiliveMsg->getValue();
                $view['tikiliveMsg'] = str_replace("TIKILIVE-PROMO-CODE", $view['tikilivepromocode'], $view['tikiliveMsg']);
                $view['istikilivepromocode'] = true;
            }
        }

        ## End here##

        if($this->get('session')->get('sendAdminPurchaseEmail') == 1 && $purchasedSummaryData['PurchaseEmailSent'] != 1){

            if($this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, false, $view)){

                $purchasedSummaryData['PurchaseEmailSent'] = 1;
                $this->get('session')->remove('sendAdminPurchaseEmail');
            }
        }

        $view['purchasedSummaryData'] = $purchasedSummaryData;
        
        if($this->get("session")->has('isISPOnly')){
                if($this->get("session")->get('isISPOnly') == true){
                        $this->get('session')->remove('isISPOnly');
                }
        }
        if($this->get("session")->has('isISPOnlyUpgrade')){
                if($this->get("session")->get('isISPOnlyUpgrade') == true){
                        $this->get('session')->remove('isISPOnlyUpgrade');
                }
        }
        if($this->get("session")->has('ISPSetStatusunset')){
                if($this->get("session")->get('ISPSetStatusunset') == true){
                        $this->get('session')->remove('ISPSetStatusunset');
                }
        }
		
		if($this->get("session")->has('reCreateISP')){
            if($this->get("session")->get('reCreateISP') == true){
            	$this->get("packageActivation")->reCreateIspUser($purchasedSummaryData['UserName']);
                $this->get('session')->remove('reCreateISP');
            }
        }
        
        return $this->render('DhiAdminBundle:PurchaseProcess:purchaseSuccess.html.twig', $view);
    }

    public function removeServiceAction(Request $request, $userId, $service, $id) {


		$em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        $sessionId = $this->get('session')->get('adminSessionId'); //Get Session Id

        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

        $deleteServiceArr = array();
        $addon = false;

        if($user) {

            $resultDelete = false;
            $isDeletedService = false;
            $isDeletedCredit  = false;
            $creditDeleteIds = array();

            if($service == 'credit') {

                $creditDeleteIds[] = $id;
                $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteItemFromCart($sessionId, $user, $creditDeleteIds);

                if ($objDeletePurchase) {

                    $resultDelete = true;
                }
            }

            if($service == 'ISP' || $service == 'IPTV' || $service == 'BUNDLE') {

                if($service == 'ISP') {

                    if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {

                        throw $this->createNotFoundException('Invalid Page Request');
                    }

                    $deleteServiceArr[] = $service;
                }

                if($service == 'IPTV') {

                    if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {

                        throw $this->createNotFoundException('Invalid Page Request');
                    }

                    if($id != "" && $id != 0) {

                        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->find($id);

                        if($objServicePurchase) {

                            $addon = true;
                        } else {

                            $this->get('session')->getFlashBag()->add('danger', 'Something went to wrong!');
                            return $this->redirect($request->headers->get('referer'));

                        }
                    }
                    $deleteServiceArr[] = $service;
				}

                if($service == 'BUNDLE'){

                	// ISP
                    // if (!in_array(strtoupper('ISP'), $summaryData['AvailableServicesOnLocation'])) {
                    //     throw $this->createNotFoundException('Invalid Page Request');
                    // }
                    $deleteServiceArr[] = 'ISP';


	            	// IPTV
                    // if (!in_array(strtoupper('IPTV'), $summaryData['AvailableServicesOnLocation'])) {

                    //     throw $this->createNotFoundException('Invalid Page Request');
                    // }

                    if($id != "" && $id != 0) {

                        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->find($id);

                        if($objServicePurchase) {

                            $addon = true;
                        } else {

                            $this->get('session')->getFlashBag()->add('danger', 'Something went to wrong!');
                            return $this->redirect($request->headers->get('referer'));

                        }
                    }
                	$deleteServiceArr[] = 'IPTV';
            	}

                if (isset($deleteServiceArr) && !empty($deleteServiceArr)) {

                    foreach ($deleteServiceArr as $deleteService) {

                        $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($deleteService)));

                        $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $id, $addon);

                        if ($objDeletePurchase) {
                        	$this->get('session')->remove('isExtendIPTV');
							$this->get('session')->remove('isExtendISP');
							$this->get('session')->remove('isExtendBundle');

                            $resultDelete = true;
                        }
                    }
                }
            }

            if ($resultDelete) {

                if (!empty($deleteServiceArr)) {

                    $descriptionLog = 'Admin has removed ' . strtoupper($deleteService) . ' pack from cart for '.$user->getUsername().' user';
                    $activityTitle = 'Remove Cart Item';
                    if ($addon) {

                        $activityTitle = 'Remove Cart Item';
                        $descriptionLog = 'Admin has removed ' . strtoupper($deleteService) . ' AddOns pack from cart for '.$user->getUsername().' user';

                    }

                    //Add Activity Log
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = $activityTitle;
                    $activityLog['description'] = $descriptionLog;

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    //Activity Log end here
                    //Update Discount data
                    $discount = $this->get('BundleDiscount')->getBundleDiscount('admin',$user);

					$this->get('session')->getFlashBag()->add('success', 'Service package has been deleted successfully.');
                }

                if($service == 'credit'){

                    $this->get('session')->getFlashBag()->add('success', 'Credit has been deleted successfully.');
                }

            } else {

                $this->get('session')->getFlashBag()->add('danger', 'Service has been failed to delete.');
            }

        } else {

            $this->get('session')->getFlashBag()->add('danger', 'No user found!');

        }

        return $this->redirect($request->headers->get('referer'));


    }

    public function paymentOptionAtrributesError(){

        $errorMsg = array(
                'eagleCashNumber'   => 'Please enter eagle cash number.',
        );

        return $errorMsg;
    }

    public function deersCACIdLog($username, $cacId) {

        if($username && $cacId) {

            $ipAddress = $this->get('session')->get('ipAddress');

            $apiurl = $this->container->getParameter('deers_api_url');
            $apiurl = $apiurl . '?action=cac-authenticate&auth_type=CAC&username='.$username.'&ip_address='.$ipAddress.'&cac_id='.$cacId;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiurl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HTTPGET, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($ch);
            curl_close($ch);

            $result = json_decode($response);

            try {
            	if($result) {
                	if ($result->result == 1) {
                    	return true;
                	} else {
                		return false;
                	}
            	} else {
					return false;
                }
            } catch ( \Exception $e ) {
            	return false;
			}
        }
        return false;
    }
    
    public function applyDiscountAction(Request $request){
     
        $adminDiscount           = $request->get('adminDiscount');
        $discountType         = $request->get('discountType');
        $amount           = $request->get('amount');
        $userId           = $request->get('userId');
        $em               = $this->getDoctrine()->getManager();
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        $sessionId  = $this->get('session')->get('adminSessionId'); //Get Session Id
        
        if($adminDiscount){

            $grossDiscountAmount = 0;
            $grossPayableAmount = 0;
            
            $result['discountAmount'] = 0;
            $result['finalAmount'] = $amount;
            
            //$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy(array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user->getId(), 'isAddon' => 0));
            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->getAdminUserCartItems($user->getId(), $sessionId);
            
            $totalCartPlan = count($objServicePurchase);
            
            if ($discountType == 'amount') {
                
                $totalPaybleAmount = 0.00;
                foreach ($objServicePurchase as $resultRow){
                    
                    $totalPaybleAmount = $totalPaybleAmount + $resultRow->getPayableAmount();
                }
                
                // check inputed discount amount is greater than payable amount
                if( $adminDiscount > $totalPaybleAmount){
                    
                        $result['error'] = 'greaterThanPayable';
                        $response = new Response(json_encode($result));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                }
                $result['percentageType'] = 'amount';
                $result['discountAmount']     = 0;
                $discountAmountPerPlan = $adminDiscount / $totalCartPlan ;
            }else if($discountType == 'percentage'){
                $result['percentageType'] = 'percentage';
                if($adminDiscount > 100 ){
                    $adminDiscount = 100;
                }
            } 
            
            foreach ($objServicePurchase as $item){
                
                if ($discountType == 'amount') {
                    
                    $amountAfterdiscount = $discountAmountPerPlan;
                    $payableAmount = $item->getPayableAmount();
                    $discountPercentage = (100 * $amountAfterdiscount) /  $payableAmount;
                    if($discountPercentage > 100){
                        $discountPercentage = 100;
                        $amountAfterdiscount = $payableAmount;
                    }
                    $grossDiscountAmount = $grossDiscountAmount + $amountAfterdiscount; 
                    $item->setDiscountCodeRate($discountPercentage);
                    $item->setDiscountCodeAmount($amountAfterdiscount);
                    $finalPayableAmount = $payableAmount - $amountAfterdiscount;
                    $finalcost = $item->getFinalCost() - $amountAfterdiscount ;
                    $result['finalAmount'] = $result['finalAmount'] - $amountAfterdiscount;
                    
                }else if($discountType == 'percentage'){
                    
                    $payableAmount = $item->getPayableAmount();
                    $discountAmount = ($payableAmount * $adminDiscount) / 100;
                    $finalPayableAmount = $payableAmount - $discountAmount;
                    $finalcost = $item->getFinalCost() - $discountAmount ;
                    $item->setDiscountCodeRate($adminDiscount);
                    $item->setDiscountCodeAmount($discountAmount);
                    $grossDiscountAmount = $grossDiscountAmount + $discountAmount;
                    $result['finalAmount'] = $result['finalAmount'] - $discountAmount;
                }
                $grossPayableAmount = $grossPayableAmount + $payableAmount;
                $item->setFinalCost($finalcost);
                $item->setPayableAmount($finalPayableAmount);
                $item->setDiscountCodeApplied(6);
                $em->persist($item);
                $em->flush();
            }
            
        }else{
            $result['error'] = "emptyDiscount";
        }
        $applliedDiscountPercentages = (100 * $grossDiscountAmount) /  $grossPayableAmount;;
       
        $result['percentage']     = number_format($applliedDiscountPercentages, 2, '.', '');
        $result['discountPercentage'] = $applliedDiscountPercentages;
        $result['discountAmount']  = number_format($grossDiscountAmount, 2, '.', '');
        $result['finalAmount']  = number_format($result['finalAmount'], 2, '.', '');
        $result['success']            = "applyDiscount";
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function removeDiscountCouponAction(Request $request, $value=''){
        
        $em    = $this->getDoctrine()->getManager();
        
        $response = array();
        $userId = $request->get('userId');
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        $sessionId  = $this->get('session')->get('adminSessionId'); //Get Session Id
        $summaryData  	= $this->get('DashboardSummary')->getUserServiceSummary('admin',$user);

        $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user);
        if($summaryData['isDiscountCodeApplied'] == 1){
            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($condition);
            foreach ($objServicePurchase as $purchase) {
                $isApplied = $purchase->getDiscountCodeApplied();
              
                if($isApplied){
                    $discountPercentage = $purchase->getDiscountCodeRate();
                    $payableAmount = $purchase->getPayableAmount();

                    if($discountPercentage < 100){
                        $discountCodeAmount = ((($payableAmount*100)/(100 - $discountPercentage)) - $payableAmount);
                    }else{
                        $discountCodeAmount =  $purchase->getDiscountCodeAmount();
                    }

                    $finalPayableAmount = $payableAmount + $discountCodeAmount;
                    $finalAmount = $purchase->getFinalCost() + $discountCodeAmount;

                    $purchase->setPayableAmount($finalPayableAmount);
                    $purchase->setFinalCost($finalAmount);
                    $purchase->setDiscountCodeRate(0);
                    $purchase->setDiscountCodeAmount(0);
                    $purchase->setDiscountCodeApplied(0);
                    $em->persist($purchase);
                    $em->flush();
                    $response['status'] = "success";
                }
            }

            if($response['status']){
                $response['msg']    = "Discount code has been successfully removed.";
                $this->get('session')->getFlashBag()->add('success', 'Discount  has been successfully removed.');
            }else{
                $response['status'] = "fail";
                $response['msg']    = "Discount does not exists.";
            }

        }else{
            $response['status'] = "fail";
            $response['msg']    = "Discount does not exists.";
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
