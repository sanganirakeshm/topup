<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Symfony\Component\HttpFoundation\Response;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\UserBundle\Entity\User;
use Dhi\ServiceBundle\Entity\BillingAddress;
use Dhi\ServiceBundle\Form\Type\BillingAddressFormType;
use Dhi\ServiceBundle\Entity\Milstar;
use Dhi\ServiceBundle\Entity\ServiceApiErrorLog;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\UserBundle\Entity\UserCredit;

class CartProcessController extends Controller {

    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;

    public function __construct($container) {

        $this->container			= $container;
        $this->em                	= $container->get('doctrine')->getManager();
        $this->session           	= $container->get('session');
        $this->securitycontext   	= $container->get('security.context');

        $this->DashboardSummary   	= $container->get('DashboardSummary');

    }

    public function addToCartIPTVPlan($objServicePurchase, $servicePlanData, $objUser, $userType = 'user', $extendPlan, $isTvod = false) {
       
    	if($userType == 'admin'){
    		$sessionId = $this->session->get('adminSessionId');
    	}else{
    		$sessionId = $this->session->get('sessionId');
    	}

    	$jsonResponse = array();
    	$jsonResponse['result']   = '';
    	$jsonResponse['succMsg']  = '';
    	$jsonResponse['errMsg']  = '';
    	$jsonResponse['response'] = '';

    	$loggedInUser   = $this->securitycontext->getToken()->getUser();
        $summaryData 	= $this->DashboardSummary->getUserServiceSummary($userType,$objUser,$isTvod);
        $objService 	= $servicePlanData['Service'];

		
		
        $servicePlanData['User']		= $objUser;
        $servicePlanData['SessionId']	= $sessionId;
	$tmpPayableAmount = (!empty($servicePlanData['purchase_type']) && $servicePlanData['purchase_type'] == 'BUNDLE') ? $servicePlanData['PayableAmount'] : $servicePlanData['ActualAmount'];
        if ($servicePlanData['IsUpgrade'] == 1 && !$extendPlan && $isTvod == false) {

        	$servicePlanData['PayableAmount'] = $tmpPayableAmount - $summaryData['Purchased']['IPTVRemainCredit'];
        	$servicePlanData['UnusedDays']    = $summaryData['Purchased']['IPTVRemainDays'];
        	$servicePlanData['UnusedCredit']  = $summaryData['Purchased']['IPTVRemainCredit'];
        } else {

			if(!$extendPlan && $summaryData['IsIPTVAvailabledInPurchased'] == 1 && $isTvod == false) {
				
				$servicePlanData['PayableAmount'] = $tmpPayableAmount - $summaryData['Purchased']['IPTVRemainCredit'];
				$servicePlanData['UnusedDays']    = $summaryData['Purchased']['IPTVRemainDays'];
				$servicePlanData['UnusedCredit']  = $summaryData['Purchased']['IPTVRemainCredit'];
			
			} else {
			
				$servicePlanData['UnusedDays']    = NULL;
				$servicePlanData['UnusedCredit']  = NULL;
			}
        }

        //Insert IPTV data into servicePurchase table
        if($this->updateServicePurchaseData($objServicePurchase,$servicePlanData,$extendPlan, $summaryData['isPromotionAvailable'])){
            
        	//Check ISP added for IPTV if exist update related data
        	if($this->DashboardSummary->checkISPAddedForIPTV($userType, $objUser, $isTvod)) {

        		//$objService = $this->em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
//        		if($summaryData['IsISPAvailabledInCart'] == 0 && $summaryData['IsISPAvailabledInPurchased'] == 1) {
//
//        			$objServicePurchase = new ServicePurchase();
//
//        			$ISPPackages = $summaryData['Purchased']['ISP']['RegularPack'];
//
//        			if($ISPPackages) {
//
//        				foreach ($ISPPackages as $ISPPackage) {
//
//        					if($servicePlanData['Validity'] > $summaryData['Purchased']['ISPRemainDays']) {
//
//        						$ispPurchasedService = $this->em->getRepository('DhiUserBundle:UserService')->find($ISPPackage['userserviceId']);
//
//        						$objServicePurchase = new ServicePurchase();
//
//        						$ispServicePlanData = array();
//        						$ispServicePlanData['User']          = $objUser;
//        						$ispServicePlanData['Service']       = $ispPurchasedService->getService();
//        						$ispServicePlanData['PackageId']     = $ispPurchasedService->getPackageId();
//        						$ispServicePlanData['PackageName']   = $ispPurchasedService->getPackageName();
//        						$ispServicePlanData['ActualAmount']  = $ispPurchasedService->getActualAmount();
//        						$ispServicePlanData['PayableAmount'] = $ispPurchasedService->getActualAmount() - $summaryData['Purchased']['ISPRemainCredit'];
//        						$ispServicePlanData['FinalCost'] 	  = $ispPurchasedService->getActualAmount();
//        						$ispServicePlanData['TermsUse']      = 1;
//        						$ispServicePlanData['Bandwidth']     = $ispPurchasedService->getBandwidth();
//        						$ispServicePlanData['Validity']      = $ispPurchasedService->getValidity();
//        						$ispServicePlanData['SessionId']     = $sessionId;
//        						$ispServicePlanData['IsUpgrade']     = 1;
//        						$ispServicePlanData['UnusedDays']    = $summaryData['Purchased']['ISPRemainDays'];
//        						$ispServicePlanData['UnusedCredit']  = $summaryData['Purchased']['ISPRemainCredit'];
//
//        						$this->updateServicePurchaseData($objServicePurchase,$ispServicePlanData);
//        					}
//        				}
//        			}
//        		}
//

			}
        	//End here

			if($extendPlan) {
					$this->session->set("isExtendIPTV", true);
			}

        	$jsonResponse['result']   = 'success';
        	$jsonResponse['succMsg']  = 'ExchangeVUE Plan '.$servicePlanData['PackageName'].' has been added successfully in cart.';
        	$jsonResponse['response'] = array(
				'service' 		=> $objService->getName(),
				'packageId' 	=> $servicePlanData['PackageId'],
				'packageName'	=> $servicePlanData['PackageName'],
				'actualAmount'	=> $servicePlanData['ActualAmount'],
				'isAddons'		=> 0
			);
        } else {
        	$jsonResponse['result'] = 'error';
        	$jsonResponse['errMsg'] = $service.' service not available.';
        }

        return $jsonResponse;
    }

    public function addToCartAddOnsPlan($objServicePurchase, $servicePlanData, $objUser, $userType = 'user', $extendPlan){
    	if($userType == 'admin'){
    		$sessionId = $this->session->get('adminSessionId');
    	}else{
    		$sessionId = $this->session->get('sessionId');
    	}

    	$jsonResponse = array();
    	$jsonResponse['result']   = '';
    	$jsonResponse['succMsg']  = '';
    	$jsonResponse['errMsg']  = '';
    	$jsonResponse['response'] = '';

    	$loggedInUser   = $this->securitycontext->getToken()->getUser();
    	$summaryData 	= $this->DashboardSummary->getUserServiceSummary($userType,$objUser);
    	$objService 	= $servicePlanData['Service'];

    	$servicePlanData['User']          = $objUser;
    	$servicePlanData['SessionId']     = $sessionId;
    	$servicePlanData['IsUpgrade']     = 0;
    	$servicePlanData['IsAddon']       = 1;
    	$servicePlanData['UnusedDays']    = NULL;
    	$servicePlanData['UnusedCredit']  = NULL;

    	$premiumPerDayPrice = $servicePlanData['ActualAmount'] / $servicePlanData['Validity'];

    	if($summaryData['IsIPTVAvailabledInCart'] == 1) {

			$payableAmount = $premiumPerDayPrice * $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
    		$packageValidity = $summaryData['Cart']['IPTV']['CurrentIPTVPackvalidity'];
    	} elseif ($summaryData['IsIPTVAvailabledInPurchased'] == 1) {
			if ($extendPlan) {
				$this->session->set("isAddonExtendIPTV", true);
				$payableAmount = $summaryData['Purchased']['IPTV']['CurrentAddOnPackprice'];
				$packageValidity = $summaryData['Purchased']['IPTV']['CurrentAddOnPackvalidity'];
			} else {
				$payableAmount = $premiumPerDayPrice * $summaryData['Purchased']['IPTVRemainDays'];
				$packageValidity = $summaryData['Purchased']['IPTVRemainDays'];
			}
    	}

    	if($payableAmount > 5) {
    		$servicePlanData['PayableAmount'] = $payableAmount;
    		$servicePlanData['Validity']      = $packageValidity;

    		if($this->updateServicePurchaseData($objServicePurchase,$servicePlanData,$extendPlan, $summaryData['isPromotionAvailable'])){
   				$jsonResponse['result']   = 'success';
    			$jsonResponse['succMsg']  = 'Additional package '.$servicePlanData['PackageName'].' has been added successfully in cart.';
    			$jsonResponse['response'] = array(
					'service' 		=> $objService->getName(),
					'packageId' 	=> $servicePlanData['PackageId'],
					'packageName'	=> $servicePlanData['PackageName'],
					'actualAmount'	=> $servicePlanData['ActualAmount'],
					'isAddons'		=> 1
				);
    		}
    	} else {
            $jsonResponse['result'] = 'error';
            if($summaryData['IsIPTVAvailabledInCart'] == 1 && $summaryData['IsIPTVAvailabledInPurchased'] == 0){
                $jsonResponse['errMsg']	= 'You cannot purchase add-on plans as your selected Bundle / IPTV plan has price less than $5!';
            }  else {
    		$jsonResponse['errMsg']	= 'You can\'t add additional package because of activated ExchangeVUE service will be expiry soon.';
            }

    	}

    	return $jsonResponse;
    }

    public function updateServicePurchaseData($objServicePurchase, $servicePlanData, $extendPlan=false, $isPromotionAvailable = 0){
        
        if($this->session->has('brand'))
        {
            $whiteLabelBrand = $this->session->get('brand');
            if($whiteLabelBrand)
            {
                $whiteLabelBrandId = $whiteLabelBrand['id'];
                $whiteLabelBrandObj = $this->em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                if($whiteLabelBrandObj)
                {
                    $objServicePurchase->setWhiteLabel($whiteLabelBrandObj);
                }
            }
        }
        
    	$objServicePurchase->setUser($servicePlanData['User']);
    	$objServicePurchase->setService($servicePlanData['Service']);
    	$objServicePurchase->setPackageId($servicePlanData['PackageId']);
    	$objServicePurchase->setPackageName($servicePlanData['PackageName']);
    	$objServicePurchase->setPaymentStatus('New');
    	$objServicePurchase->setActualAmount($servicePlanData['ActualAmount']);
    	$objServicePurchase->setPayableAmount($servicePlanData['PayableAmount']);
    	$objServicePurchase->setFinalCost($servicePlanData['FinalCost']);
    	$objServicePurchase->setTermsUse($servicePlanData['TermsUse']);
    	$objServicePurchase->setBandwidth($servicePlanData['Bandwidth']);
    	$objServicePurchase->setValidity($servicePlanData['Validity']);
    	$objServicePurchase->setSessionId($servicePlanData['SessionId']);
    	$objServicePurchase->setIsUpgrade($servicePlanData['IsUpgrade']);
    	$objServicePurchase->setUnusedDays($servicePlanData['UnusedDays']);
    	$objServicePurchase->setUnusedCredit($servicePlanData['UnusedCredit']);

        $objServicePurchase->setBundleId(isset($servicePlanData['bundle_id'])?$servicePlanData['bundle_id']:null);
        $objServicePurchase->setDisplayBundleDiscount(isset($servicePlanData['displayBundleDiscount'])?$servicePlanData['displayBundleDiscount']:null);

        $objServicePurchase->setbundleDiscount(isset($servicePlanData['bundleDiscount'])?$servicePlanData['bundleDiscount']:null);
        $objServicePurchase->setPurchaseType(isset($servicePlanData['purchase_type'])?$servicePlanData['purchase_type']:null);
        $objServicePurchase->setBundleName(isset($servicePlanData['bundleName'])?$servicePlanData['bundleName']:null);
        $objServicePurchase->setDisplayBundleName(isset($servicePlanData['displayBundleName'])?$servicePlanData['displayBundleName']:null);
        $objServicePurchase->setBundleApplied(isset($servicePlanData['bundleApplied'])?$servicePlanData['bundleApplied']:0);
        $objServicePurchase->setDiscountCodeApplied(isset($servicePlanData['discountCodeApplied'])?$servicePlanData['discountCodeApplied']:0);
        $objServicePurchase->setPaypalCredential(isset($servicePlanData['paypalCredential'])?$servicePlanData['paypalCredential']:0);
        $objServicePurchase->setServiceLocationId(isset($servicePlanData['serviceLocation'])?$servicePlanData['serviceLocation']:null);
        $objServicePurchase->setIsPromotionalPlan(isset($servicePlanData['isPromotionalPlan'])?$servicePlanData['isPromotionalPlan']:0);
        

        if (!empty($servicePlanData['validityType']) && $servicePlanData['validityType'] == 1) {
            $objServicePurchase->setValidityType('HOURS');
        }else{
            $objServicePurchase->setValidityType('DAYS');
        }

        if($extendPlan){
            $objServicePurchase->setIsExtend(true);
        } else {
            $objServicePurchase->setIsExtend(false);
        }
          /*if(isset($servicePlanData['purchase_type']) && !empty($servicePlanData['purchase_type'])) {
              if($servicePlanData['purchase_type'] == "BUNDLE"){
              }
          }*/

    	if(isset($servicePlanData['IsAddon']) && !empty($servicePlanData['IsAddon'])) {
    		$objServicePurchase->setIsAddon($servicePlanData['IsAddon']);
    	}

    	$this->em->persist($objServicePurchase);

    	$this->em->flush();

    	$insertIdServicePurchase = $objServicePurchase->getId();
        if($insertIdServicePurchase){
            $this->DashboardSummary->setPromotionDiscount($insertIdServicePurchase, $isPromotionAvailable);
            return $insertIdServicePurchase;
    	}

    	return false;
    }

	 public function addISPServicePlan($objServicePurchase, $servicePlanData, $objUser, $userType = 'user', $extendPlan){
      $loggedInUser   = $this->securitycontext->getToken()->getUser();
      $returnFlag     = false;
			$jsonResponse = array();
    	$jsonResponse['result']   = '';
    	$jsonResponse['succMsg']  = '';
    	$jsonResponse['errMsg']  = '';
    	$jsonResponse['response'] = '';

      if($userType == 'admin') {
          $sessionId = $this->session->get('adminSessionId');
      }else{
            $sessionId = $this->session->get('sessionId');
      }

			$loggedInUser   = $this->securitycontext->getToken()->getUser();
            $summaryData 	= $this->DashboardSummary->getUserServiceSummary($userType,$objUser);
			$objService 	= $servicePlanData['Service'];
			$servicePlanData['SessionId']	= $sessionId;	
			$tmpPayableAmount = (!empty($servicePlanData['purchase_type']) && $servicePlanData['purchase_type'] == 'BUNDLE') ? $servicePlanData['PayableAmount'] : $servicePlanData['ActualAmount'];

			if($summaryData['IsISPAvailabledInPurchased'] == 1 && !$extendPlan) {
				$servicePlanData['PayableAmount'] = $tmpPayableAmount - $summaryData['Purchased']['ISPRemainCredit'];
                		$servicePlanData['FinalCost'] 	  = $tmpPayableAmount;
		                $servicePlanData['User']          = $objUser;
		                $servicePlanData['UnusedDays']    = $summaryData['Purchased']['ISPRemainDays'];
		                $servicePlanData['UnusedCredit']  = $summaryData['Purchased']['ISPRemainCredit'];
			} else{
				$servicePlanData['PayableAmount'] = $tmpPayableAmount;
				$servicePlanData['FinalCost'] 	  = $tmpPayableAmount;
				$servicePlanData['User']          = $objUser;
				$servicePlanData['UnusedDays']    = NULL;
				$servicePlanData['UnusedCredit']  = NULL;
			}

    	if($this->updateServicePurchaseData($objServicePurchase,$servicePlanData,$extendPlan, $summaryData['isPromotionAvailable'])){
				if($extendPlan) {
					$this->session->set("isExtendISP", true);
				}

          $jsonResponse['result']   = 'success';
    			$jsonResponse['succMsg']  = 'ISP package '.$servicePlanData['PackageName'].' has been added successfully in cart.';
    			$jsonResponse['response'] = array(
					'service' 		=> $objService->getName(),
					'packageId' 	=> $servicePlanData['PackageId'],
					'packageName'	=> $servicePlanData['PackageName'],
					'actualAmount'	=> $servicePlanData['ActualAmount'],
					'isAddons'		=> 0
				);
			} else {
					$jsonResponse['result'] = 'error';
    		$jsonResponse['errMsg']	= 'You can\'t add ISP package';
			}

           //  Add Activity Log
           // $activityLog = array();
           // $activityLog['user'] 	    = $loggedInUser;
           // $activityLog['activity']    = 'Add to Cart ISP';
           // $activityLog['description'] = 'User '.$loggedInUser->getUserName().' add to cart ISP plan';
           // $this->ActivityLog->saveActivityLog($activityLog);
           //  Activity Log end here

        return $jsonResponse;
    }
}
