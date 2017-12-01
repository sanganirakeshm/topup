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
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\AdminBundle\Entity\Credit;
use Dhi\UserBundle\Entity\PromoCode;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Dhi\AdminBundle\Entity\DiscountCodeCustomer;
use Dhi\AdminBundle\Entity\EmployeePromoCodeCustomer;
//use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\PromoCodeFormType;
use Dhi\UserBundle\Entity\ReferralPromoCode;

class ServiceController extends Controller {

    protected $failedErrNo = array('1001', '1002', '1003');

    public function packageAction(Request $request, $service, $addon) {

        $service = strtoupper($service);

        $em               = $this->getDoctrine()->getManager();
        $user             = $this->get('security.context')->getToken()->getUser();
        $discount         = $this->get('BundleDiscount')->getBundleDiscount();
        $availableService = $this->get('UserLocationWiseService')->getUserLocationService();
        $summaryData      = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

        //Check Email address verify for after first purchase
        if(!$this->get('paymentProcess')->emailVerifiedForNextPurchase()){

            $this->get('session')->getFlashBag()->add('notice', 'You need to verify your email address.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        //Check service available on location
        if (empty($summaryData['AvailableServicesOnLocation'])) {

            //$this->get('session')->getFlashBag()->add('notice', 'IPTV/Internet service not available at your location.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        //Check IPTV or ISP service available on location
        if (!in_array($service, $summaryData['AvailableServicesOnLocation'])) {

            throw $this->createNotFoundException('Invalid Page Request');
        }


        $myPackages = array();
        $allPackages = array();
        $premiumPackage = array();

        $em = $this->getDoctrine()->getManager();

        $packageArr = $this->get('SelevisionPackage')->userLocationPackages();

        // check if service is IPTV
        if ($service == 'IPTV') {

            /*if (!$this->get('DashboardSummary')->checkISPAddedForIPTV('user',$user)) {

                $this->get('session')->getFlashBag()->add('notice', 'Internet is required for ExchangeVUE package at your location.');
                return $this->redirect($this->generateUrl('dhi_user_account'));
            }*/

            if(!empty($packageArr) && !empty($packageArr['IPTV'])) {

                $allPackages    = $packageArr['IPTV'];
            }

            if(!empty($packageArr) && !empty($packageArr['PREMIUM'])) {

               $premiumPackage = $packageArr['PREMIUM'];
            }
        }

        if ($service == 'ISP') {

            if(!empty($packageArr['ISP'])) {

                $allPackages =  $allPackages = $packageArr['ISP']  ;
            }
        }

        $packageColumnArray = array('Package', 'Price', 'Number Of Channels');

        $credits = $em->getRepository('DhiAdminBundle:Credit')->findBy(array('isDeleted'=>'0'), array('amount' => 'ASC'));

        return $this->render('DhiServiceBundle:Service:plan.html.twig', array(
                    'packageColumnArray' => $packageColumnArray,
                    'allPackages'        => $allPackages,
                    'premiumPackage'     => $premiumPackage,
                    'addonPrimium'       => $addon == 1 ? 1 : 0,
                    'discount'           => ($discount) ? $discount['Precentage'] : '',
                    'summaryData'        => $summaryData,
                    'credits'            => $credits,
                                        'totalPremiumPackage' => count($premiumPackage),
                    'totalPackages'      => count($allPackages),
                    'isDeersAuthenticated' => $isDeersAuthenticated
        ));
    }

    // package list
    public function packageSelectAction(Request $request, $service) {

        $service        = strtoupper($service);
        $em             = $this->getDoctrine()->getManager();
        $user           = $this->get('security.context')->getToken()->getUser();
        $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
        $summaryData    = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

        //Check Email address verify for after first purchase
        if(!$this->get('paymentProcess')->emailVerifiedForNextPurchase()){

            $this->get('session')->getFlashBag()->add('notice', 'You need to verify your email address.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        //Check IPTV/ISP service available on location
        if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {

            throw $this->createNotFoundException('Invalid Page Request');
        }


        if ($request->getMethod() == "POST") {

            $userPurchaseCredit = $request->get('userPurchaseCredit');
            $termsUse = $request->get('termsUse');
            $creditId = $request->get('creditId');

            if ($userPurchaseCredit != "" && $userPurchaseCredit == 1) {

                if($termsUse != 1) {

                    $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');

                }else {

                    if ($creditId != "") {

                        $flagCredit = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);

                        if($flagCredit) {

                            if($user->getUserCredit() && $user->getUserCredit()->getTotalCredits() > 0)
                            {
                                $this->get('session')->getFlashBag()->add('success', 'Credit has been updated successfully.');

                            } else {

                                $this->get('session')->getFlashBag()->add('success', 'Credit has been added successfully.');
                            }
                        }
                        else {

                            $this->get('session')->getFlashBag()->add('failure', 'Something went to wrong.');
                        }
                    }
                    else {

                         $this->get('session')->getFlashBag()->add('failure', 'Please select credit.');
                    }
                }

             return $this->redirect($this->generateUrl('dhi_user_account'));
            } else {

                $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName($service);

                if ($objService) {

                    $packageNameArr             = $request->get('packageName');
                    $packagePriceArr            = $request->get('price');
                    $bandwidthArr               = $request->get('bandwidth');
                    $validityArr                = $request->get('validity');
                    $premiumPackageNameArr      = $request->get('premiumPackageName');
                    $premiumPriceArr            = $request->get('premiumPrice');
                    $premiumPackageValidityArr  = $request->get('premiumPackageValidity');


                    $packageId = $request->get('packageId');
                    $premiumPackageIds = $request->get('premiumPackageId');
                    $termsUse = $request->get('termsUse');

                    $creditId = $request->get('creditId');

                    $flagError = 0;

                    if ($service == 'IPTV') {

                        if ($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0) {

                            if ($packageId == '') {

                                $this->get('session')->getFlashBag()->add('failure', 'Please select valid ExchangeVUE package.');
                                $flagError = 1;
                            }
                        }
                    } else if ($service == 'ISP' && $packageId == '') {

                        $this->get('session')->getFlashBag()->add('failure', 'Please select valid ISP package.');
                        $flagError = 1;
                    } else if ($termsUse != 1) {

                        $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');
                        $flagError = 1;
                    }

                    if (!$flagError) {

                        $this->get('session')->set('termsUse', $termsUse);

                        if ($packageId) {

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
                            if (isset($validityArr[$packageId]) && !empty($validityArr[$packageId])) {

                                $validity = $validityArr[$packageId];
                            }

                            $servicePlanData = array();

                            $servicePlanData['Service'] = $objService;
                            $servicePlanData['PackageId'] = $packageId;
                            $servicePlanData['PackageName'] = $packageNameArr[$packageId];
                            $servicePlanData['ActualAmount'] = $price;
                            $servicePlanData['TermsUse'] = $termsUse;
                            $servicePlanData['Bandwidth'] = $bandwidth;
                            $servicePlanData['Validity'] = $validity;
                            $servicePlanData['SessionId'] = $sessionId;
                            $servicePlanData['IsUpgrade'] = 0;

                            $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
                            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

                            if (!$objServicePurchase) {

                                $objServicePurchase = new ServicePurchase();
                            }

                            if ($summaryData['Is' . strtoupper($service) . 'AvailabledInPurchased'] == 1) {

                                $servicePlanData['IsUpgrade'] = 1;

                                if (strtoupper($service) == 'IPTV') {

                                    $savedResult = $this->get('DashboardSummary')->upgradeIPTVServicePlan($user,$objServicePurchase, $servicePlanData);
                                }

                                if (strtoupper($service) == 'ISP') {

                                    $savedResult = $this->get('DashboardSummary')->upgradeISPServicePlan($user,$objServicePurchase, $servicePlanData);
                                }
                            } else {

                                $savedResult = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData);
                            }
                        }

                        // add user credit purchase code.
                        if ($creditId != "") {

                            $savedResult = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);
                        }


                        $savedResult = $this->get('DashboardSummary')->addPremiumPackage($user,$objService, $premiumPackageIds, $premiumPackageNameArr, $premiumPriceArr, $premiumPackageValidityArr);

                        if ($savedResult) {

                            $this->get('session')->getFlashBag()->add('success', 'Package data has been saved successfully.');
                        }

                        //Update Discount data
                        $discount = $this->get('BundleDiscount')->getBundleDiscount();

                        echo json_encode($servicePlanData);
                        exit;
                    } else {

                        return $this->redirect($this->generateUrl('dhi_service_plan', array('service' => $service)));
                    }
                } else {

                    throw $this->createNotFoundException('Invalid Page Request');
                }
            }
        } else {

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }

    public function checkAvaibilityAction() {

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $response = array();
        $response['result'] = true;

        // check araliable active
        if ($summaryData['IsISPAvailabledInCart'] == 1) {
            $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());

            if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                $this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                $response['result'] = false;

            }
        }
        // check selevision active

        if (($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart']) && $response['result'] == true ) {

            $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
            if($isSelevisionUser == 0) {
                $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                $response['result'] = false;
            }
        }
        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ajaxAddToCartPackageAction(Request $request, $extendPlan) {

        $jsonResponse = array();
        $jsonResponse['result']     = 'success';
        $jsonResponse['errMsg']     = '';
        $jsonResponse['succMsg']    = '';
        $jsonResponse['lastTriggerId']  = '';

        $user           = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
        $pid            = $request->get('pid');
        $packageId      = $request->get('packageId');
        $packageType    = $request->get('packageType');
        $service        = $request->get('service');
        $isAddonsPack   = $request->get('isAddonsPack');
        $ispValidity    = $request->get('ispValidity');
        $checkExtendISP = $request->get('iSExtendISP');
        $premiumPackageIds = $request->get('premiumPackageId');
        $addOnsPackageId = $request->get('addOnsPackageId');
        
        $summaryData            = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated   = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $discount               = $this->get('BundleDiscount')->getBundleDiscount();

        if ($this->get('session')->has('ChaseWPInfo')) {
            $this->get('session')->remove('ChaseWPInfo');
        }
        if ($this->get('session')->has('chaseUserErrorMsg')) {
            $this->get('session')->remove('chaseUserErrorMsg');
        }
        
        //Check Email address verify for after first purchase
        if(!$this->get('paymentProcess')->emailVerifiedForNextPurchase()){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'You need to verify your email address.';
        }

        //Check IPTV/ISP service available on location
        if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = strtoupper($service). ' service not available on your location.';
        }

        if ($summaryData['isServiceLocationChanged'] == 1) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'You have loggedin in wrong Service Location.';
        }
        
        if (isset($summaryData['isSiteChanged']) && $summaryData['isSiteChanged'] == 1) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Internal server error! Please contact to customer support.';
        }

        if ($isAddonsPack) {

            if(isset($summaryData['Cart']['IPTV'])){
                $iptvPackages = $summaryData['Cart']['IPTV'];
            }else if(isset($summaryData['Purchased']['IPTV'])){
                $iptvPackages = $summaryData['Purchased']['IPTV'];
            }


           /* if (!empty($iptvPackages['AddOnPack']) && !empty($summaryData['Cart']['IPTV'])) {

                foreach ($iptvPackages['AddOnPack'] as $addOnPackage) {

                    $addOnDeleteIds[] = $addOnPackage['servicePurchaseId'];

                    if ($premiumPackageIds) {

                        if (!in_array($addOnPackage['packageId'], $premiumPackageIds)) {

                            $objDeleteAddOnPackage = $em->getRepository('DhiServiceBundle:ServicePurchase')->find($addOnPackage['servicePurchaseId']);
                            $em->remove($objDeleteAddOnPackage);
                            $em->flush();
                        }
                    }
                }
            }*/

            if(!empty($premiumPackageIds)){
                //$pid = $premiumPackageIds[0];
                $pid = $addOnsPackageId;
            }else{
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Invalid package request';
            }
        }

        if($packageType == 'IPTV' && !empty($summaryData['Cart']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Invalid package request';

        }

        if($packageType == 'IPTV' && !empty($summaryData['Purchased']['ISP']) && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 0){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Invalid package request';

        }/*else if($packageType == 'ISP' && !empty($summaryData['Purchased']['IPTV']) && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 0){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Invalid package request';
            
        }*/else if($packageType == 'AddOns' && !empty($summaryData['PurchasedAddOnPackageId']) && in_array($request->get('packageIdAddOns')[$addOnsPackageId], $summaryData['PurchasedAddOnPackageId'])){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Invalid package request';
        }

        if ($jsonResponse['result'] == 'success') {

            if ($pid && in_array($packageType, array('IPTV', 'AddOns', 'ISP')) && in_array($service, array('IPTV','ISP'))) {

                $jsonResponse['lastTriggerId']  = 'iptvAddToCartBtn'.$pid;
                $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName($service);

                if ($objService) {

                    $servicePlanData = array();
                    $servicePlanData['IsUpgrade']   = 0;

                    //Get pacakge
                    $objPackage = $em->getRepository('DhiAdminBundle:Package')->find($pid);

                    if ($objPackage) {

//                        if ($objPackage->getIsDeers() == 1 && $isDeersAuthenticated == 2) {
//
//                            $jsonResponse['result'] = 'deersMsg';
//                            $jsonResponse['errMsg'] = '';
//                        }else 
                        if ($objPackage->getIsExpired() == 1 && in_array($packageType, array('IPTV', 'AddOns'))) {

                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'Sorry! You can not add the plan to cart. Plan has already been expired.';
                        }
//                      else if ($checkExtendISP == 0 && $summaryData['IsISPAvailabledInPurchased'] == 1 && $objPackage->getValidity() > $ispValidity){
//
//                          $jsonResponse['result'] = 'extendISPMsg';
//                          $jsonResponse['errMsg'] = 'You existing internet plan cycle will be upgrade with ExchangeVUE plan. Do you want to continue?';
//
//                      }
                       else if ($packageType == 'AddOns' && $summaryData['IsIPTVAvailabledInCart'] != 1 && $summaryData['IsIPTVAvailabledInPurchased'] != 1){

                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = 'You have to choose ExchangeVUE plan for additional plan.';
                        } else {

                            //Check data added into service purchase
                            if ($isAddonsPack) {
                                $condition = array('packageId' => $objPackage->getPackageId(), 'sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService->getId(), 'user' => $user->getId(), 'isAddon' => $isAddonsPack);
                            } else {
                                $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user->getId());
                                if ($service != 'ISP') {
                                    $condition['isAddon'] = $isAddonsPack;
                                }
                            }

                            $objServicePurchases = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($condition);
                            foreach ($objServicePurchases as $objServicePurchase) {
                                $em->remove($objServicePurchase);
                                $em->flush();
                            }

                            // if (!$objServicePurchase) {

                                $objServicePurchase = new ServicePurchase();
                            // } else {

                                if ($summaryData['Is' . strtoupper($service) . 'AvailabledInPurchased'] == 1) {

                                    $servicePlanData['IsUpgrade']   = 1;
                                }
                            // }

                            $validity = 0;
                            if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
                                $validity = $summaryData['employeeDefaultValidity'];
                            }else{
                                $validity = $objPackage->getValidity();
                            }


                            $servicePlanData['Service']           = $objService;
                            $servicePlanData['PackageId']         = $objPackage->getPackageId();
                            $servicePlanData['PackageName']       = $objPackage->getPackageName();
                            $servicePlanData['ActualAmount']      = $objPackage->getAmount();
                            $servicePlanData['PayableAmount']     = $objPackage->getAmount();
                            $servicePlanData['FinalCost']         = $objPackage->getAmount();
                            $servicePlanData['isPromotionalPlan'] = $objPackage->getIsPromotionalPlan();
                            $servicePlanData['TermsUse']          = 1;
                            $servicePlanData['Bandwidth']         = $objPackage->getBandwidth();
                            $servicePlanData['Validity']          = $validity;
                            $servicePlanData['Discount']          = ($discount) ? $discount['Precentage'] : 0;
                            $servicePlanData['purchase_type']     = null;
                            $servicePlanData['bundle_id']         = null;
                            $servicePlanData['bundleDiscount']    = null;
                            $servicePlanData['bundleName']        = null;
                            $servicePlanData['displayBundleName'] = null;
                            $servicePlanData['paypalCredential']  = $summaryData['paypalCredentialKey'];
                            $servicePlanData['serviceLocation']   = ($objPackage->getServiceLocation() ? $objPackage->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));

                            if (strtoupper($service) == 'IPTV') {
                                if (strtoupper($packageType) == 'IPTV') {
                                    $jsonResponse = $this->get('cartProcess')->addToCartIPTVPlan($objServicePurchase, $servicePlanData, $user, 'user', $extendPlan);
                                    $this->get('session')->set('hideISP', true);
                                }

                                if (strtoupper($packageType) == 'ADDONS') {
                                    $objPackage = $em->getRepository('DhiAdminBundle:Package')->find($pid);
                                    if($objPackage){

                                        $condition = array('packageId' => $objPackage->getPackageId(), 'sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService->getId(), 'user' => $user->getId(), 'isAddon' => $isAddonsPack);

                                        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);
                                        if (!$objServicePurchase) {
                                            $objServicePurchase = new ServicePurchase();
                                        }

                                        if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
                                            $validity = $summaryData['employeeDefaultValidity'];
                                        }else{
                                            $validity = $objPackage->getValidity();
                                        }

                                        $servicePlanData['Service']           = $objService;
                                        $servicePlanData['PackageId']         = $objPackage->getPackageId();
                                        $servicePlanData['PackageName']       = $objPackage->getPackageName();
                                        $servicePlanData['ActualAmount']      = $objPackage->getAmount();
                                        $servicePlanData['PayableAmount']     = $objPackage->getAmount();
                                        $servicePlanData['FinalCost']         = $objPackage->getAmount();
                                        $servicePlanData['TermsUse']          = 1;
                                        $servicePlanData['Bandwidth']         = $objPackage->getBandwidth();
                                        $servicePlanData['Validity']          = $validity;
                                        $servicePlanData['Discount']          = ($discount) ? $discount['Precentage'] : 0;
                                        $servicePlanData['purchase_type']     = null;
                                        $servicePlanData['bundle_id']         = null;
                                        $servicePlanData['bundleDiscount']    = null;
                                        $servicePlanData['bundleName']        = null;
                                        $servicePlanData['displayBundleName'] = null;
                                        $servicePlanData['paypalCredential']  = $summaryData['paypalCredentialKey'];
                                        $servicePlanData['serviceLocation']   = ($objPackage->getServiceLocation() ? $objPackage->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                                        
                                        $jsonResponse = $this->get('cartProcess')->addToCartAddOnsPlan($objServicePurchase, $servicePlanData, $user, 'user', $extendPlan);
                                    }else{
                                        $jsonResponse['result'] = 'error';
                                        $jsonResponse['errMsg'] = 'Package not available.';
                                    }

                                    if ($summaryData['IsBundleAvailabledInCart'] == 1 || $summaryData['IsBundleAvailabledInPurchased'] == 1) {
                                        $jsonResponse['redirect'] = 'IPTV-BUNDLE';
                                        $jsonResponse['cartIspPackageId'] = $summaryData['PurchasedISPPackageId'];

                                    }else if (($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsIPTVAvailabledInPurchased'] == 1) && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 0) {
                                        $jsonResponse['redirect'] = 'IPTV';
                                    }else if ($summaryData['IsBundleAvailabledInCart'] == 1) {
                                        $jsonResponse['redirect'] = 'BUNDLE';
                                    }
                                }
                            } else if(strtoupper($service) == 'ISP'){
                                $servicePlanData['validityType'] = $objPackage->getIsHourlyPlan();
                                $jsonResponse = $this->get('cartProcess')->addISPServicePlan($objServicePurchase,$servicePlanData, $user, 'user' ,$extendPlan);
                            } else {

                                $jsonResponse['result'] = 'error';
                                $jsonResponse['errMsg'] = $service.' service not available.';
                            }

                            if (empty($jsonResponse['cartIspPackageId']) && !empty($summaryData['Cart']['ISP']['RegularPack'][0]['packageId']) && $summaryData['IsBundleAvailabledInCart']  == 1) {
                                $jsonResponse['cartIspPackageId'] = $summaryData['Cart']['ISP']['RegularPack'][0]['packageId'];
                            }else{
                                $jsonResponse['cartIspPackageId'] = 0;
                            }

                            //Update Discount data
                            $discount = $this->get('BundleDiscount')->getBundleDiscount();
                        }
                    } else {

                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'Package not available.';
                    }
                } else {

                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = $service.' service not available.';
                }
            } else {

                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Invalid package request.';
            }
        }

        $response = new Response(json_encode($jsonResponse));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function ajaxRemoveCartPackageAction(Request $request) {

        $jsonResponse = array();
        $jsonResponse['result']     = 'success';
        $jsonResponse['errMsg']     = '';
        $jsonResponse['succMsg']    = '';

        $user           = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();

        $sessionId          = $this->get('paymentProcess')->generateCartSessionId();
        $servicePurchaseId  = $request->get('servicePurchaseId');
        $service            = $request->get('service');
        $isAddonsPack       = $request->get('isAddonsPack');
        $packageName       = $request->get('packageName');

        if ($servicePurchaseId && in_array($service, array('IPTV','ISP', 'BUNDLE'))) {

            $displayName = $service;
            if (strtoupper($service) == 'IPTV') {

                    if($this->get('session')->get('hideISP') == true) {
                        $this->get('session')->set('hideISP', false);
                    }

                if ($isAddonsPack) {

                    $displayName = 'Additional';
                } else {

                    $displayName = 'ExchangeVUE';
                }
            } else if((strtoupper($service) == 'ISP')){
                $displayName = 'ISP';
            } else if((strtoupper($service) == 'BUNDLE')){
                $displayName = 'Bundle';
                $purchase = $em->getRepository("DhiServiceBundle:ServicePurchase")->find($servicePurchaseId);

            } else{

            }

            if((strtoupper($service) == 'BUNDLE')){
                if($purchase){
                    $bundleId = $purchase->getBundleId();

                    //Get Service object
                    $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($purchase->getService()->getName())));
                    $servicePurchase = $em->getRepository("DhiServiceBundle:ServicePurchase")->findOneBy(array('bundle_id'=>$bundleId, "sessionId"=> $sessionId, 'paymentStatus'=>'New', 'user' => $user));

                    //Delete cart service
                    $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $purchase->getId(), $isAddonsPack);

                    if($servicePurchase){
                        $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($servicePurchase->getService()->getName())));
                        $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $servicePurchase->getId(), $isAddonsPack);
                    }
                }
            } else {
                //Get Service object
                $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($service)));

                //Delete cart service
                $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $servicePurchaseId, $isAddonsPack);
            }

            $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
            if ($objDeletePurchase) {
                if ($isAddonsPack) {
                    $jsonResponse['redirect']         = 'IPTV-BUNDLE';
                    $jsonResponse['cartIspPackageId'] = $summaryData['PurchasedISPPackageId'];
                    $jsonResponse['succMsg']          = $displayName.' plan '.$packageName.' has been removed successfully from cart.';
                }else{
                    $jsonResponse['succMsg'] = $displayName.' plan has been removed successfully from cart.';
                }
                
                $jsonResponse['response'] = array('service' => $service);
            } else {

                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Something went to wrong in delete action. please try again.';
            }

            if (empty($jsonResponse['cartIspPackageId']) && !empty($summaryData['Cart']['ISP']['RegularPack'][0]['packageId']) && $summaryData['IsBundleAvailabledInCart']  == 1) {
                $jsonResponse['cartIspPackageId'] = $summaryData['Cart']['ISP']['RegularPack'][0]['packageId'];
            }else{
                $jsonResponse['cartIspPackageId'] = 0;
            }

            $discount = $this->get('BundleDiscount')->getBundleDiscount();
        } else {

            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Invalid package request.';
        }

        echo json_encode($jsonResponse);
        exit;
    }

    public function purchaseverificationAction(Request $request) {
        $user       = $this->get('security.context')->getToken()->getUser();
        $em         = $this->getDoctrine()->getManager();
        $sessionId  = $this->get('session')->get('sessionId'); //Get Session Id

        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $this->get('session')->set('chaseOrderNumber', $orderNumber);

        $userServiceLocation         = $this->get('UserLocationWiseService')->getUserLocationService();
        $summaryData                 = $this->get('DashboardSummary')->getUserServiceSummary();
        $isISPAddedForIPTV           = $this->get('DashboardSummary')->checkISPAddedForIPTV('user',$user);
        $isDeersAuthenticated        = $this->get('DeersAuthentication')->checkDeersAuthenticated();
        $isDeersPackageExistInCart   = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);



        if($summaryData['CartAvailable'] == 0){
            throw $this->createNotFoundException('Invalid Page Request');

        }else if($summaryData['IsISPAvailabledInCart'] == 1 && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 1){
            $this->get('session')->getFlashBag()->add('failure', 'Please select Internet and XVUE Plan To Upgrade the Plan.');
            return $this->redirect($this->generateUrl('dhi_user_account'));

        }else if($summaryData['IsIPTVAvailabledInCart'] == 1 && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 1){
            $this->get('session')->getFlashBag()->add('failure', 'Please select Internet and XVUE Plan To Upgrade the Plan.');
            return $this->redirect($this->generateUrl('dhi_user_account'));

        }else if ($summaryData['IsIPTVAvailabledInPurchased'] == 1 && $summaryData['IsISPAvailabledInCart'] == 1 && $summaryData['IsBundleAvailabledInCart'] == 0 && $summaryData['IsBundleAvailabledInPurchased'] == 0){
            $this->get('session')->getFlashBag()->add('failure', 'Please select Internet and XVUE Plan To Upgrade the Plan.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }else if($isDeersPackageExistInCart && $isDeersAuthenticated == 2) {
            $this->get('session')->getFlashBag()->add('failure', 'DEERS authentication is required for purchase package.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        $form = $this->createFormBuilder(array())->getForm();

        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findby(array('user' => $user, 'paymentStatus' => 'New', 'sessionId' => $sessionId));
        $isDearsAuthenticatedForMilstar = '';
        $isDearsAuthenticatedForMilstar = $this->get('session')->get('isDearsAuthenticatedForMilstar');
        if($isDearsAuthenticatedForMilstar){
            $isDearsAuthenticatedForMilstar = 1;
        } else {
            $isDearsAuthenticatedForMilstar = 2;
        }

        if ($this->get('session')->has('ChaseWPInfo')) {
            $chaseWPInfo = $this->get('session')->get('ChaseWPInfo');
        }else{
            $chaseWPInfo = array();
        }
        $chaseUserErrorMsg = array();
        if ($this->get('session')->has('chaseUserErrorMsg')) {
            $chaseUserErrorMsg = $this->get('session')->get('chaseUserErrorMsg');
        }
        
        $chaseMerchantData = $this->get('DashboardSummary')->getUserLocationWiseChaseMID($user->getId());
        $states = $this->get('GeoLocation')->getStates();
        return $this->render('DhiServiceBundle:Service:purchaseConfirm.html.twig', array(
            'objServicePurchase'             => $objServicePurchase,
            'form'                           => $form->createView(),
            'expiresMonth'                   => $this->creditCardYearMonth('month'),
            'expiresYear'                    => $this->creditCardYearMonth('year'),
            'country'                        => $em->getRepository('DhiUserBundle:Country')->getCreditCardCountry(),
            'state'                          => $states,
            'summaryData'                    => $summaryData,
            'isISPAddedForIPTV'              => $isISPAddedForIPTV,
            'userServiceLocation'            => $userServiceLocation,
            'creditBalance'                  => ($user->getUserCredit()) ? $user->getUserCredit()->getTotalCredits() : 0,
            'isDeersAuthenticated'           => $isDeersAuthenticated,
            'isDearsAuthenticatedForMilstar' => $isDearsAuthenticatedForMilstar,
            'userEmail'                      => $user->getEmail(),
            'orderNumber'                    => $orderNumber,
            'isChaseProfileExists'           => $chaseMerchantData['isProfileExist'],
            'chaseWPInfo'                    => $chaseWPInfo,
            'chaseUserErrorMsg'              => $chaseUserErrorMsg
        ));
    }

    public function orderComfirmationAction() {

        //Clear Session Data

        $this->get('PaymentProcess')->clearPaymentSession();

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

        if($this->get('session')->has('isExtendBundle')) {
            if($this->get('session')->has('isExtendBundle') == true) {
                $this->get('session')->remove('isExtendBundle');
                $this->get('session')->remove('BundleExtendPlan');
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

        $this->get('PaymentProcess')->clearPaymentSession();

        $view = array();

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->getRequest()->get('ord');

        $purchasedSummaryData = $this->get('paymentProcess')->paymentSuccessSummary($orderNumber);

        if (!$purchasedSummaryData) {

            throw $this->createNotFoundException('Invalid Page Request');
        }

        if($user->getId()!= $purchasedSummaryData['UserId']) {

            throw $this->createNotFoundException('Invalid Page Request');
        }


        ## Tikilive promocode ## 
        $tikiLivePromoCodeResponse = $this->get('paymentProcess')->redeemTikilivePromoCode($purchasedSummaryData);

        $objpromocode = $em->getRepository("DhiAdminBundle:TikilivePromoCode")->findOneBy(array('redeemedBy' => $purchasedSummaryData['UserId'],'purchaseId'=>$purchasedSummaryData['PurchaseOrderId']));
        $view['istikilivepromocode'] = false;
        if($objpromocode){
            $view['tikilivepromocode']   = $objpromocode->getPromoCode();

            $tikiliveMsg   = $em->getRepository("DhiAdminBundle:Setting")->findOneBy(array("name" => 'tikilive_promo_code_success_message'));
            if ($tikiliveMsg) {
                $view['tikiliveMsg'] = $tikiliveMsg->getValue();
                $view['tikiliveMsg'] = str_replace("TIKILIVE-PROMO-CODE", $view['tikilivepromocode'], $view['tikiliveMsg']);
                $view['istikilivepromocode'] = true;
            }
        }

        ## End here ##

        if($this->get('session')->get('sendPurchaseEmail') == 1 && $purchasedSummaryData['PurchaseEmailSent'] != 1){

            if($this->get('paymentProcess')->sendPurchaseEmail($purchasedSummaryData, false, $view)){

                $purchasedSummaryData['PurchaseEmailSent'] = 1;
                $this->get('session')->remove('sendPurchaseEmail');
            }
        }

        $view['purchasedSummaryData'] = $purchasedSummaryData;



        // Discount code redeem add in discount_code_customer
        $discountcodeId = $this->get('session')->get('usedDiscountCodeId');
        if($discountcodeId){
                $em = $this->getDoctrine()->getManager();
                $discountcodeDetails  = $em->getRepository('DhiAdminBundle:DiscountCode')->findOneBy(array('id'=>$discountcodeId));
                $objDiscountCodeCustomer = new DiscountCodeCustomer();
                $objDiscountCodeCustomer->setDiscountCodeId($discountcodeDetails);
                $objDiscountCodeCustomer->setUser($user);
                $em->persist($objDiscountCodeCustomer);
                $em->flush();
                $this->get('session')->remove('usedDiscountCodeId');
        }

        // Partner Discount code
        $em = $this->getDoctrine()->getManager();
        $partnerDiscountcodeId = $this->get('session')->get('usedPDiscountCodeId');
        if($partnerDiscountcodeId){
            if ($purchasedSummaryData['PaymentStatus'] == "Completed") {
                $objPackagePromoCode  = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('id' => $partnerDiscountcodeId));

                $objPackagePromoCode->setIsRedeemed('Yes');
                $objPackagePromoCode->setRedeemedBy($user->getId());
                $objPackagePromoCode->setRedeemedDate(new \DateTime(date('Y-m-d H:i:s')));
                $em->persist($objPackagePromoCode);
                $em->flush();
            }
            $this->get('session')->remove('usedPDiscountCodeId');
        }

        // Business Discount code
        $businessDiscountcodeId = $this->get('session')->get('businessDiscountCodeId');
        if($businessDiscountcodeId){
            if ($purchasedSummaryData['PaymentStatus'] == "Completed") {
                $objPackagePromoCode  = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findOneBy(array('id' => $businessDiscountcodeId));

                $objPackagePromoCode->setIsRedeemed('Yes');
                $objPackagePromoCode->setRedeemedBy($user->getId());
                $objPackagePromoCode->setRedeemedDate(new \DateTime(date('Y-m-d H:i:s')));
                $em->persist($objPackagePromoCode);
                $em->flush();
            }
            $this->get('session')->remove('businessDiscountCodeId');
        }

        // Discount code redeem add in Employee_code_customer
        $EmployeeDiscountcodeId = $this->get('session')->get('usedEmployeePromoCodeId');

        if($EmployeeDiscountcodeId){
            $em = $this->getDoctrine()->getManager();
            $employeeCodeDetails  = $em->getRepository('DhiAdminBundle:EmployeePromoCode')->findOneBy(array('id'=>$EmployeeDiscountcodeId));
            $objEmployeeCodeCustomer = new EmployeePromoCodeCustomer();
            $objEmployeeCodeCustomer->setEmployeePromoCodeId($employeeCodeDetails);
            $objEmployeeCodeCustomer->setUser($user);
            $objEmployeeCodeCustomer->setStatus(0);
            $em->persist($objEmployeeCodeCustomer);
            $em->flush();
            $this->get('session')->remove('usedEmployeePromoCodeId');
        }
        
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
        
        ## Update Referral Promo Code
        
        $referralDiscountcodeId = $this->get('session')->get('usedReferralDiscountCodeId');
        if($referralDiscountcodeId){
            $em = $this->getDoctrine()->getManager();
            $objreferralPromoCode  = $em->getRepository('DhiUserBundle:ReferralPromoCode')->findOneBy(array('id' => $referralDiscountcodeId));

            $objreferralPromoCode->setIsRedeemed(1);
            $em->persist($objreferralPromoCode);
            $em->flush();
            $this->get('session')->remove('usedReferralDiscountCodeId');
        }
        ## End Here
        
        ## Update Referral Invitee
            $objCheckIsInvited = $em->getRepository('DhiUserBundle:ReferralInvitees')->findOneBy(array('emailId' => $user->getEmail()));
            if($objCheckIsInvited){
                
                $objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->checkFirstPurchase($user->getId());

                if(count($objPurchaseOrder) == 1 && $objPurchaseOrder[0]['validity'] >= 30){
                    
                    $invitedEmailId = '';
                    $objReferralInvitees = $em->getRepository('DhiUserBundle:ReferralInvitees')->findOneBy(array('emailId' => $user->getEmail()));
                    
                    if($objReferralInvitees && $objReferralInvitees->getPromoCodeEmailSent() == 0){
                        
                        $invitedEmailId = $objReferralInvitees->getEmailId();
                        $invitedFistname = $objReferralInvitees->getUserId()->getFirstname();
                        $invitedLastname = $objReferralInvitees->getUserId()->getLastname();
                        
                        $invitedFullName = $invitedFistname.' '.$invitedLastname;
                        $objReferralInvitees->setIsPurchased(1);
                        $em->persist($objReferralInvitees);
                        $em->flush();

                        // generating Referral promo code
                        $refPromocode = '';
                        $random_string_length = 6 ;
                        $refPromocode = $this->generateReferralUniquePromoCode($random_string_length, 'ReferralPromoCode');

                        $objReferralPromoCode = new ReferralPromoCode();
                        $objReferralPromoCode->setPromocode($refPromocode);
                        $objReferralPromoCode->setReferrerUserId($objReferralInvitees->getUserId());
                        $objReferralPromoCode->setRefereeUserId($user);
                        $objReferralPromoCode->setIsRedeemed(0);
                        $em->persist($objReferralPromoCode);
                        $em->flush();
                        
                       $whitelabel = $this->get('session')->get('brand');
                        if($whitelabel){
                            $fromEmail = $whitelabel['fromEmail'];
                            $compnayname = $whitelabel['name'];
                            $compnaydomain = $whitelabel['domain'];
                        } else {
                            $fromEmail       = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                            $compnayname     = 'ExchangeVUE';
                            $compnaydomain   = 'exchangevue.com';
                        }
                        
                        $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:referral_promo_code.html.twig', array(
                            'promoCode' => $refPromocode, 
                            'refererEmail' => $user->getEmail(),
                            'invitedName' => $invitedFullName,
                            'companyname' => $compnayname));
                        
                        $send_email_refer = \Swift_Message::newInstance()
                                ->setSubject("Referral Promo Code")
                                ->setFrom($fromEmail)
                                ->setTo($invitedEmailId)
                                ->setBody($body->getContent())
                                ->setContentType('text/html');

                        $sentFlag = $this->container->get('mailer')->send($send_email_refer);
                        if($sentFlag){
                            $objReferralInvitees->setPromoCodeEmailSent(1);
                            $em->persist($objReferralInvitees);
                            $em->flush();
                        }
                            
                    }
                }
            }
        ## End Here
        return $this->render('DhiServiceBundle:Service:purchaseSuccess.html.twig', $view);
    }

    public function paymentCancelAction($id) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id

        $this->get('session')->getFlashBag()->add('failure', 'Your payment has been cancelled.');
        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
    }

    public function purchaseFailAction() {

        //Clear Session Data
        $this->get('PaymentProcess')->clearPaymentSession();

        $view = array();

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $orderNumber = $this->getRequest()->get('ord');
        $errNo = $this->getRequest()->get('err');

        $purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneByOrderNumber($orderNumber);

        if (!$purchaseOrder) {

            throw $this->createNotFoundException('Invalid Page Request');
        }

        $view['errNo'] = $errNo;
        $view['purchaseOrder'] = $purchaseOrder;

        if($this->get("session")->has('reCreateISP')){
            if($this->get("session")->get('reCreateISP') == true){
                $this->get("packageActivation")->reCreateIspUser($purchasedSummaryData['UserName']);
                $this->get('session')->remove('reCreateISP');
            }
        }

        return $this->render('DhiServiceBundle:Service:purchaseFail.html.twig', $view);
    }

    public function channelListAction($packageId) {
        $em         = $this->getDoctrine()->getManager();
        $operation  = $this->getRequest()->get('operation');
        $objSetting = $em->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'max_allow_to_show_channels'));
        $maxShowChannels = 0;
        if($objSetting){
            $maxShowChannels = $objSetting->getValue();
        }
        if ($maxShowChannels == 0 && $operation == 'Hide') {
            $channelList = array();
        }else{
            $channelList = $em->getRepository('DhiAdminBundle:Channel')->getChannelList($packageId, $maxShowChannels, $operation);
        }
        $arrChannelImage = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannelImage();

        $view = array();
        $view['channelList'] = $channelList;
        $view['packageId'] = $packageId;
        $view['arrChannelImage'] = $arrChannelImage;
        return $this->render('DhiServiceBundle:Service:channelList.html.twig', $view);
    }

    public function premiumChannelListAction($packageId) {

        $view = array();
        $view['channelList']['channel'][0] = 'Premium HBO';
        $view['channelList']['channel'][1] = 'Premium ZEE';
        $view['channelList']['channel'][2] = 'Premium STAR';

        return $this->render('DhiServiceBundle:Service:premiumChannelList.html.twig', $view);
    }

    public function creditCardYearMonth($type) {

        $data = array();

        if ($type == 'month') {

            for ($i = 1; $i <= 12; $i++) {

                $data[$i] = $i;
            }
        }

        if ($type == 'year') {

            $year = date('Y');
            for ($i = 0; $i <= 10; $i++) {

                $yy = $year + $i;
                $data[$yy] = $yy;
            }
        }

        return $data;
    }

    public function serviceRemoveAction(Request $request, $service) {

        $em = $this->getDoctrine()->getManager();
        $user = $this->get('security.context')->getToken()->getUser();
        $sessionId = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();

        $addon = $request->get('addon');
        $both = $request->get('both');
        $id = $request->get('id');

        $deleteServiceArr = array();
        if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {

            throw $this->createNotFoundException('Invalid Page Request');
        }
        $deleteServiceArr[] = strtoupper($service);

        /*if ($both) {

            if (!in_array('IPTV', $summaryData['AvailableServicesOnLocation']) && !in_array('ISP', $summaryData['AvailableServicesOnLocation'])) {

                throw $this->createNotFoundException('Invalid Page Request');
            }
            $deleteServiceArr = $summaryData['AvailableServicesOnLocation'];
        }*/

        $resultDelete = true;

        if (isset($deleteServiceArr) && !empty($deleteServiceArr)) {

            foreach ($deleteServiceArr as $deleteService) {

                $objService = $em->getRepository('DhiUserBundle:Service')->findOneBy(array('name' => strtoupper($deleteService)));

                $objDeletePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->deleteServicePackage($sessionId, $user, $objService, $id, $addon);

                if (!$objDeletePurchase) {

                    $resultDelete = false;
                }
            }
        }

        if ($resultDelete) {

            $descriptionLog = 'Removed ' . strtoupper($deleteService) . ' pack from cart';
            $activityTitle = 'Remove Cart Item';
            if ($addon) {

                $activityTitle = 'Remove Cart Item';
                $descriptionLog = 'User ' . $user->getUserName() . ' has removed ' . strtoupper($deleteService) . ' AddOns pack from cart.';
            }

            //Add Activity Log
            $activityLog = array();
            $activityLog['user'] = $user;
            $activityLog['activity'] = $activityTitle;
            $activityLog['description'] = $descriptionLog;

            $this->get('ActivityLog')->saveActivityLog($activityLog);
            //Activity Log end here
            //Update Discount data
            $discount = $this->get('BundleDiscount')->getBundleDiscount();

            $this->get('session')->getFlashBag()->add('success', 'Service package has been deleted successfully.');
        } else {

            $this->get('session')->getFlashBag()->add('success', 'Service package has not been deleted successfully.');
        }

        return $this->redirect($this->generateUrl('dhi_user_account'));
    }

    public function activateFreePlanAction(Request $request) {

        $em          = $this->getDoctrine()->getManager();
        $user        = $this->get('security.context')->getToken()->getUser();
        $sessionId   = $this->get('session')->get('sessionId'); //Get Session Id
        $summaryData = $this->get('DashboardSummary')->getUserServiceSummary();
        $orderNumber = $this->get('PaymentProcess')->generateOrderNumber();
        $isDeersAuthenticated  = $this->get('DeersAuthentication')->checkDeersAuthenticated();


        if ($request->getMethod() == "POST" && $summaryData) {

            $isDeersPackageExistInCart   = $this->get('paymentProcess')->checkDeersPackageExistInCart($summaryData);

            if($isDeersPackageExistInCart && $isDeersAuthenticated == 2) {

                $this->get('session')->getFlashBag()->add('failure', 'DEERS authentication is required for purchase package.');
                return $this->redirect($this->generateUrl('dhi_user_account'));
            }

            $paybleAmount = $request->get('paybleAmount');

            if ($paybleAmount == 0 || $summaryData['isEmployee'] == 1) {

                if ($summaryData['CartAvailable'] == 1) {

                    $this->get('session')->set('IsISPAvailabledInCart',$summaryData['IsISPAvailabledInCart']);
                    $this->get('session')->set('IsIPTVAvailabledInCart',$summaryData['IsIPTVAvailabledInCart']);

                    if (($summaryData['IsIPTVAvailabledInCart'] == 1 || $summaryData['IsAddOnAvailabledInCart'] == 1)) {

                        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
                        if($isSelevisionUser == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1002, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                        }
                    }

                    if ($summaryData['IsISPAvailabledInCart'] == 1) {
                        $isAradialUser = $this->get('aradial')->checkUserExistsInAradial($user->getUsername());

                        if (!empty($isAradialUser) && $isAradialUser['serviceAvailable'] == 0) {
                            $this->get('session')->getFlashBag()->add('failure', 'Error No: #1001, Something went wrong with your purchase. Please contact support if the issue persists.');
                            return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
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
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }

                    $objPurchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->findOneBy(array('sessionId' => $sessionId, 'paymentStatus' => 'InProcess'));

                    if (!$objPurchaseOrder) {

                        $objPurchaseOrder = new PurchaseOrder();
                    }
                    $objPurchaseOrder->setPaymentMethod($objPaymentMethod);
                    $objPurchaseOrder->setSessionId($sessionId);
                    $objPurchaseOrder->setOrderNumber($orderNumber);
                    $objPurchaseOrder->setUser($user);
                    $objPurchaseOrder->setPaymentBy('User');
                    $objPurchaseOrder->setPaymentByUser($user);
                    $objPurchaseOrder->setTotalAmount($cartTotalAmount);
                    $objPurchaseOrder->setPaymentStatus('Completed');

                    $em->persist($objPurchaseOrder);
                    $em->flush();
                    $insertIdPurchaseOrder = $objPurchaseOrder->getId();

                    if ($insertIdPurchaseOrder) {

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

                        /* $updateServicePurchase = $em->createQueryBuilder()->update('DhiServiceBundle:ServicePurchase', 'sp')
                                                    ->set('sp.purchaseOrder', $insertIdPurchaseOrder)
                                                    ->set('sp.paymentStatus', '\'Completed\'')
                                                    ->where('sp.sessionId =:sessionId')
                                                    ->setParameter('sessionId', $sessionId)
                                                    ->andWhere('sp.paymentStatus =:paymentStatus')
                                                    ->setParameter('paymentStatus', 'New')
                                                    ->andWhere('sp.user =:user')
                                                    ->setParameter('user', $user)
                                                    ->getQuery()->execute(); */

                        //Activate Purchase packages
                        $paymentRefundStatus = $this->get('packageActivation')->activateServicePack($user);

                        if ($paymentRefundStatus) {

                            $isPaymentRefund = $this->get('paymentProcess')->refundPayment();
                        }
                        $this->get('session')->set('sendPurchaseEmail',1);
                        return $this->redirect($this->generateUrl('dhi_service_purchase_order_confirm', array('ord' => $orderNumber)));
                    }else{

                        $this->get('session')->getFlashBag()->add('notice', 'Order could not procced, please check order amount.');
                        return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                    }
                } else {

                    $this->get('session')->getFlashBag()->add('notice', 'Data not found in cart.');
                    return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
                }
            } else {

                $this->get('session')->getFlashBag()->add('notice', 'Order could not procced, please check order amount.');
                return $this->redirect($this->generateUrl('dhi_service_purchaseverification'));
            }
        } else {

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }

//   public function promoCodeAction($promocode) {
//
//        //Clear Session Data
//
//
//        $em = $this->getDoctrine()->getManager();
//        $user = $this->get('security.context')->getToken()->getUser();
//
//
//        $promoCodeData = $em->getRepository('DhiUserBundle:PromoCode')->getServicePromoData($promocode);
//      $summaryData                 = $this->get('DashboardSummary')->getUserServiceSummary();
//
//      foreach($summaryData as $key => $val)
//      {
//          echo "<pre>";
//          print_r($summaryData[$key]['ISP']['RegularPack'][0]);
//           //print_r($summaryData['Cart']['ISP']['RegularPack'][0]) ;
//
//      }exit;
//
////        foreach($promoCodeData as $val)
////        {
////            //echo $val->getId();
////            foreach($val->getServiceLocations() as $val2)
////            {
////                echo $val2->getName();
////            }
////
////
////        }exit;
////
////        print_r($promoCodeData->getServiceL);exit;
//
//
//
//      $promoCodeData->getTotalTime();
//
//        if (!$purchaseOrder) {
//
//            throw $this->createNotFoundException('Invalid Page Request');
//        }
//
//        $view['errNo'] = $errNo;
//        $view['purchaseOrder'] = $purchaseOrder;
//
//        return $this->render('DhiServiceBundle:Service:purchaseFail.html.twig', $view);
//    }

     public function getPackageAction(Request $request, $service) {

        $service        = strtoupper($service);
        $em             = $this->getDoctrine()->getManager();
        $user           = $this->get('security.context')->getToken()->getUser();
        $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
        $summaryData    = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated = $this->get('DeersAuthentication')->checkDeersAuthenticated();

        //Check Email address verify for after first purchase
        if(!$this->get('paymentProcess')->emailVerifiedForNextPurchase()){

            $this->get('session')->getFlashBag()->add('notice', 'You need to verify your email address.');
            return $this->redirect($this->generateUrl('dhi_user_account'));
        }

        //Check IPTV/ISP service available on location
        if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {

            throw $this->createNotFoundException('Invalid Page Request');
        }


        if ($request->getMethod() == "POST") {

            $userPurchaseCredit = $request->get('userPurchaseCredit');
            $termsUse = $request->get('termsUse');
            $creditId = $request->get('creditId');

            if ($userPurchaseCredit != "" && $userPurchaseCredit == 1) {

                if($termsUse != 1) {

                    $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');

                }else {

                    if ($creditId != "") {

                        $flagCredit = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);

                        if($flagCredit) {

                            if($user->getUserCredit() && $user->getUserCredit()->getTotalCredits() > 0)
                            {
                                $this->get('session')->getFlashBag()->add('success', 'Credit has been updated successfully.');

                            } else {

                                $this->get('session')->getFlashBag()->add('success', 'Credit has been added successfully.');
                            }
                        }
                        else {

                            $this->get('session')->getFlashBag()->add('failure', 'Something went to wrong.');
                        }
                    }
                    else {

                         $this->get('session')->getFlashBag()->add('failure', 'Please select credit.');
                    }

                }

             return $this->redirect($this->generateUrl('dhi_user_account'));

            } else {

                $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName($service);

                if ($objService) {

                    $packageNameArr             = $request->get('packageName');
                    $packagePriceArr            = $request->get('price');
                    $bandwidthArr               = $request->get('bandwidth');
                    $validityArr                = $request->get('validity');
                    $premiumPackageNameArr      = $request->get('premiumPackageName');
                    $premiumPriceArr            = $request->get('premiumPrice');
                    $premiumPackageValidityArr  = $request->get('premiumPackageValidity');


                    $packageId = $request->get('packageId');
                    $premiumPackageIds = $request->get('premiumPackageId');
                    $termsUse = $request->get('termsUse');

                    $creditId = $request->get('creditId');

                    $flagError = 0;

                    if ($service == 'IPTV') {

                        if ($summaryData['IsIPTVAvailabledInPurchased'] == 0 && $summaryData['IsIPTVAvailabledInCart'] == 0) {

                            if ($packageId == '') {

                                $this->get('session')->getFlashBag()->add('failure', 'Please select valid ExchangeVUE package.');
                                $flagError = 1;
                            }
                        }
                    } else if ($service == 'ISP' && $packageId == '') {

                        $this->get('session')->getFlashBag()->add('failure', 'Please select valid ISP package.');
                        $flagError = 1;
                    } else if ($termsUse != 1) {

                        $this->get('session')->getFlashBag()->add('failure', 'Please accept terms and use.');
                        $flagError = 1;
                    }

                    if (!$flagError) {

                        $this->get('session')->set('termsUse', $termsUse);

                        if ($packageId) {

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
                            if (isset($validityArr[$packageId]) && !empty($validityArr[$packageId])) {

                                $validity = $validityArr[$packageId];
                            }

                            $servicePlanData = array();

                            $servicePlanData['Service'] = $objService;
                            $servicePlanData['PackageId'] = $packageId;
                            $servicePlanData['PackageName'] = $packageNameArr[$packageId];
                            $servicePlanData['ActualAmount'] = $price;
                            $servicePlanData['TermsUse'] = $termsUse;
                            $servicePlanData['Bandwidth'] = $bandwidth;
                            $servicePlanData['Validity'] = $validity;
                            $servicePlanData['SessionId'] = $sessionId;
                            $servicePlanData['IsUpgrade'] = 0;

                            $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService, 'user' => $user, 'isAddon' => 0);
                            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

                            if (!$objServicePurchase) {

                                $objServicePurchase = new ServicePurchase();
                            }

                            if ($summaryData['Is' . strtoupper($service) . 'AvailabledInPurchased'] == 1) {

                                $servicePlanData['IsUpgrade'] = 1;

                                if (strtoupper($service) == 'IPTV') {

                                    $savedResult = $this->get('DashboardSummary')->upgradeIPTVServicePlan($user,$objServicePurchase, $servicePlanData);
                                }

                                if (strtoupper($service) == 'ISP') {

                                    $savedResult = $this->get('DashboardSummary')->upgradeISPServicePlan($user,$objServicePurchase, $servicePlanData);
                                }
                            } else {

                                $savedResult = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData);
                            }
                        }

                        // add user credit purchase code.
                        if ($creditId != "") {

                            $savedResult = $this->get('DashboardSummary')->addUserCredit($user, $creditId, $sessionId);
                        }


                        $savedResult = $this->get('DashboardSummary')->addPremiumPackage($user,$objService, $premiumPackageIds, $premiumPackageNameArr, $premiumPriceArr, $premiumPackageValidityArr);

                        if ($savedResult) {

                            $this->get('session')->getFlashBag()->add('success', 'Package data has been saved successfully.');
                        }

                        //Update Discount data
                        $discount = $this->get('BundleDiscount')->getBundleDiscount();

                        echo json_encode($servicePlanData);
                        exit;
                    } else {

                        return $this->redirect($this->generateUrl('dhi_service_plan', array('service' => $service)));
                    }
                } else {

                    throw $this->createNotFoundException('Invalid Page Request');
                }
            }
        } else {

            throw $this->createNotFoundException('Invalid Page Request');
        }
    }

    public function ajaxAddToCartBundleAction(Request $request, $extendPlan) {

        $jsonResponse = array();
        $jsonResponse['result']     = 'success';
        $jsonResponse['errMsg']     = '';
        $jsonResponse['succMsg']    = '';
        $jsonResponse['lastTriggerId']  = '';

        $user           = $this->get('security.context')->getToken()->getUser();
        $em             = $this->getDoctrine()->getManager();
        $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
        $pid            = $request->get('pid');
        $service        = $request->get('service');
        $packageType    = $request->get('packageType');
        //$packageId      = $request->get('packageId');
        $isAddonsPack   = $request->get('isAddonsPack');
        //$ispValidity    = $request->get('ispValidity');

        $summaryData            = $this->get('DashboardSummary')->getUserServiceSummary();
        $isDeersAuthenticated   = $this->get('DeersAuthentication')->checkDeersAuthenticated();

        if ($this->get('session')->has('ChaseWPInfo')) {
            $this->get('session')->remove('ChaseWPInfo');
        }
        if ($this->get('session')->has('chaseUserErrorMsg')) {
            $this->get('session')->remove('chaseUserErrorMsg');
        }
        
        if(!$this->get('paymentProcess')->emailVerifiedForNextPurchase()){
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'You need to verify your email address.';
        }

		if ($user->getIsEmployee() == 1) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'Please contact administrator to purchase the plan.';
        }

        if (!in_array(strtoupper($service), $summaryData['AvailableServicesOnLocation'])) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = strtoupper($service). ' service not available on your location.';
        }

        if ($summaryData['isServiceLocationChanged'] == 1) {
            $jsonResponse['result'] = 'error';
            $jsonResponse['errMsg'] = 'You have loggedin in wrong Service Location.';
        }

        if ($jsonResponse['result'] == 'success') {

            if ($pid && in_array($packageType, array('IPTV', 'AddOns', 'BUNDLE', 'ISP')) && in_array($service, array('IPTV','ISP', 'BUNDLE'))) {

                $jsonResponse['lastTriggerId']  = 'bundleAddToCartBtn'.$pid;
                $bundle = $em->getRepository("DhiAdminBundle:Bundle")->findOneBy(array('bundle_id' => $pid));
                if($bundle){

                    $servicePlanData = array();
                    $servicePlanData['IsUpgrade']   = 0;

                    // Calculate amount
                    $discountPer = $bundle->getDiscount();

                    // IPTV Package
                    $objIptv = $bundle->getIptv();
                    $objIsp = $bundle->getIsp();
                    $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName('IPTV');

                    if($objIptv && $objIsp){
                        $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService->getId(), 'user' => $user->getId(), 'isAddon' => $isAddonsPack);
                        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

                        if (!$objServicePurchase) {
                            $objServicePurchase = new ServicePurchase();
                        }else{
                            if ($summaryData['IsIPTVAvailabledInPurchased'] == 1) {
                                $servicePlanData['IsUpgrade']   = 1;
                            }
                        }

                        $discountedAmo = ($objIptv->getAmount() * $discountPer)/100;

                        if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
                            $validity = $summaryData['employeeDefaultValidity'];
                        }else{
                            $validity = $objIsp->getValidity();
                        }

                        $servicePlanData['Service']         = $objService;
                        $servicePlanData['PackageId']       = $objIptv->getPackageId();
                        $servicePlanData['PackageName']     = $objIptv->getPackageName();
                        $servicePlanData['ActualAmount']    = $bundle->getIptvAmount();
                        $servicePlanData['PayableAmount']   = $objIptv->getAmount();
                        $servicePlanData['FinalCost']       = $bundle->getIptvAmount();
                        $servicePlanData['TermsUse']        = 1;
                        $servicePlanData['Bandwidth']       = $objIptv->getBandwidth();
                        $servicePlanData['Validity']        = $validity;
                        $servicePlanData['Discount']        = 0;
                        $servicePlanData['purchase_type']   = "BUNDLE";
                        $servicePlanData['bundle_id']       = $bundle->getBundleId();
                        $servicePlanData['bundleDiscount']  = $discountPer;
                        $servicePlanData['bundleName']      = $bundle->getbundleName();
                        $servicePlanData['displayBundleName'] = $bundle->getdisplayBundleName();;
                        $servicePlanData['paypalCredential']  = $summaryData['paypalCredentialKey'];
                        $servicePlanData['displayBundleDiscount']  = $bundle->getIptvAmount() - $objIptv->getAmount();
                        $servicePlanData['serviceLocation']  = ($objIptv->getServiceLocation() ? $objIptv->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                        
                        $jsonResponse['IPTV']               = $this->get('cartProcess')->addToCartIPTVPlan($objServicePurchase, $servicePlanData, $user, 'user', $extendPlan);

                        // Add to cart ISP
                        $objService = $em->getRepository('DhiUserBundle:Service')->findOneByName('ISP');
                        $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $objService->getId(), 'user' => $user->getId(), 'isAddon' => $isAddonsPack);
                        $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);

                        if (!$objServicePurchase) {
                            $objServicePurchase = new ServicePurchase();

                        }else{
                            if ($summaryData['IsIPTVAvailabledInPurchased'] == 1) {
                                $servicePlanData['IsUpgrade']   = 1;
                            }
                        }

                        $discountedAmo = ($objIsp->getAmount() * $discountPer)/100;
                        if ($summaryData['isEmployee'] == 1 && !empty($summaryData['employeeDefaultValidity'])) {
                            $validity = $summaryData['employeeDefaultValidity'];
                        }else{
                            $validity = $objIsp->getValidity();
                        }
                        $servicePlanData['Service']         = $objService;
                        $servicePlanData['PackageId']       = $objIsp->getPackageId();
                        $servicePlanData['PackageName']     = $objIsp->getPackageName();
                        $servicePlanData['ActualAmount']    = $bundle->getIspAmount();
                        $servicePlanData['PayableAmount']   = $objIsp->getAmount();
                        $servicePlanData['FinalCost']       = $bundle->getIspAmount();
                        $servicePlanData['TermsUse']        = 1;
                        $servicePlanData['Bandwidth']       = $objIsp->getBandwidth();
                        $servicePlanData['Validity']        = $validity;
                        $servicePlanData['Discount']        = 0;
                        $servicePlanData['purchase_type']   = "BUNDLE";
                        $servicePlanData['bundle_id']       = $bundle->getBundleId();
                        $servicePlanData['bundleDiscount']  = $discountPer;
                        $servicePlanData['bundleName']       = $bundle->getbundleName();
                        $servicePlanData['displayBundleDiscount']  = $bundle->getIspAmount() - $objIsp->getAmount();
                        $servicePlanData['serviceLocation']  = ($objIsp->getServiceLocation() ? $objIsp->getServiceLocation() : ($user->getUserServiceLocation() ? $user->getUserServiceLocation() : null));
                        
                        // $savedResult = $this->get('DashboardSummary')->addNewServicePlan($user,$objServicePurchase, $servicePlanData);
                        $jsonResponse['ISP'] = $this->get('cartProcess')->addISPServicePlan($objServicePurchase,$servicePlanData, $user, 'user' , $extendPlan);

                        if (!empty($jsonResponse['ISP']['response']['packageId'])) {
                            $jsonResponse['cartIspPackageId'] = $jsonResponse['ISP']['response']['packageId'];
                        }

                        if($jsonResponse['ISP']['result'] != "success" || $jsonResponse['IPTV']['result'] != "success"){
                            $jsonResponse['result'] = 'error';
                            $jsonResponse['errMsg'] = ($jsonResponse['ISP']['errMsg'] ? $jsonResponse['ISP']['errMsg'] : ($jsonResponse['IPTV']['errMsg'] ? $jsonResponse['IPTV']['errMsg'] : ""));
                        }else{
                            $discount = $this->get('BundleDiscount')->getBundleDiscount();
                            $jsonResponse['response']['service'] = 'BUNDLE';
                            $jsonResponse['succMsg'] = 'Bundle has been added successfully in cart.';
                        }

                    }else{
                        $jsonResponse['result'] = 'error';
                        $jsonResponse['errMsg'] = 'package not available.';
                    }

                }else{
                    $jsonResponse['result'] = 'error';
                    $jsonResponse['errMsg'] = 'Invalid request.';
                }


            }else{
                $jsonResponse['result'] = 'error';
                $jsonResponse['errMsg'] = 'Invalid package request.';
            }
        }

        $response = new Response(json_encode($jsonResponse));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function removeDiscountCouponAction($value='')
    {
        $em               = $this->getDoctrine()->getManager();
        $user             = $this->get('security.context')->getToken()->getUser();
        $response = array();

        $sessionId      = $this->get('paymentProcess')->generateCartSessionId();
        $summaryData    = $this->get('DashboardSummary')->getUserServiceSummary();

        $condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'user' => $user);
        if($summaryData['isDiscountCodeApplied'] == 1){
            $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findBy($condition);
            foreach ($objServicePurchase as $purchase) {
                $isApplied = $purchase->getDiscountCodeApplied();
                if($isApplied){
                    $discountPercentage = $purchase->getDiscountCodeRate();
                    $payableAmount = $purchase->getPayableAmount();
                    if($discountPercentage == null ||$discountPercentage == 0.00 ){
                        $discountPercentage = $purchase->getDiscountCodeAmount();
                        $discountCodeAmount =  $purchase->getDiscountCodeAmount();
                    }else{
                        if($discountPercentage < 100){
                            $discountCodeAmount = ((($payableAmount*100)/(100 - $discountPercentage)) - $payableAmount);
                        }else{
                            $discountCodeAmount =  $purchase->getDiscountCodeAmount();
                        }
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
                $this->get('session')->getFlashBag()->add('success', 'Discount code has been successfully removed.');
            }else{
                $response['status'] = "fail";
                $response['msg']    = "Discount code does not exists.";
            }

        }else{
            $response['status'] = "fail";
            $response['msg']    = "Discount code does not exists.";
        }

        $response = new Response(json_encode($response));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function generateReferralUniquePromoCode($length = 6, $type = '') {
        
        $em = $this->getDoctrine()->getManager();
        $chars = array_merge(range(0,9), range('A', 'Z'), range('a', 'z'));
        
        repeat:
        $key = '';
        for($i=0; $i < $length; $i++) {
            $key .= $chars[mt_rand(0, count($chars) - 1)];
        }
        
        if($type == 'ReferralPromoCode'){
            $checkReferralPromoCodeExists = $em->getRepository('DhiUserBundle:ReferralPromoCode')->findOneBy(array('promocode' => $key));
            if($checkReferralPromoCodeExists){
                goto repeat;
            }
        }
        return $key;
    }
}
