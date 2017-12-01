<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Component\Validator\Constraints\Count;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Event\GetResponseUserEvent;
use FOS\UserBundle\Event\FilterUserResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\Service;

class BundleDiscountController extends Controller
{

    protected $container;

    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;

    protected $DashboardSummary;

    public function __construct($container) {

        $this->container = $container;

        $this->DashboardSummary  = $container->get('DashboardSummary');
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->request           = $container->get('request');
    }
    
    public function getBundleDiscount($type = 'user',$user = NULL, $isTvod = false) {

        $clientIp          = $this->session->get('ipAddress');
        $serviceLocationId = $this->session->get('serviceLocationId');

        //Get Summary Data
        $summaryCartData = $this->DashboardSummary->getUserServiceSummary($type,$user,$isTvod);
        
        if($type == 'admin'){

        	$country 			= ($user->getGeoLocationCountry())?$user->getGeoLocationCountry()->getName():$user->getCountry()->getName();
        	$serviceLocationId 	= ($user->getUserServiceLocation())?$user->getUserServiceLocation()->getId():$summaryCartData['serviceLocationId'];
        	$sessionId          = $this->session->get('adminSessionId');
        }else{

        	$user		= $this->securitycontext->getToken()->getUser();
        	$country	= $this->session->get('country');
        	$sessionId  = $this->session->get('sessionId');
        }

        //Get Country
        $country         = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('name' => $country));

        $discountArr     = array();
        $totalISPAmount  = 0;

        if(in_array('IPTV',$summaryCartData['AvailableServicesOnLocation']) && in_array('ISP',$summaryCartData['AvailableServicesOnLocation'])) {

            $cartData = $summaryCartData['Cart'];

            if($cartData) {

                if(array_key_exists('ISP',$cartData)) {

                    if(isset($cartData['ISP']['RegularPack']) && !empty($cartData['ISP']['RegularPack'])) {

                        foreach ($cartData['ISP']['RegularPack'] as $ispPackage) {

                            $totalISPAmount += $ispPackage['amount'];
                        }
                    }
                }
            }


            ########## START CODE FOR SERVICE LOCATION WISE DISCOUNT ##########
            $serviceLocationDiscount = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getUserLocationWiseDiscount(ip2long($clientIp),$serviceLocationId);

            if(!empty($serviceLocationDiscount)) {

                foreach ($serviceLocationDiscount->getServiceLocationDiscounts() as $discount) {

                    $minAmt     = $discount->getMinAmount();
                    $maxAmt     = $discount->getMaxAmount();
                    $percentage = $discount->getPercentage();

                    if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {

                        $discountArr['Precentage'] = $percentage;
                    }
                }

            }

            ##########  END  CODE FOR SERVICE LOCATION WISE DISCOUNT ##########


            ########## START CODE FOR COUNTRY WISE DISCOUNT ##########
            if(empty($discountArr) && $country) {

                $countryDiscounts = $this->em->getRepository('DhiAdminBundle:GlobalDiscount')->getAllDiscountCountry($country->getId());

                if($countryDiscounts) {

                    foreach($countryDiscounts as $discount) {

                        $minAmt     = $discount->getMinAmount();
                        $maxAmt     = $discount->getMaxAmount();
                        $percentage = $discount->getPercentage();

                        if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {

                            $discountArr['Precentage'] = $percentage;
                        }
                    }

                } else {

                    $countryDiscounts = $this->em->getRepository('DhiAdminBundle:GlobalDiscount')->getAllDiscountCountry(null);

                    if($countryDiscounts) {

                        foreach($countryDiscounts as $discount) {

                            $minAmt = $discount->getMinAmount();
                            $maxAmt = $discount->getMaxAmount();
                            $percentage = $discount->getPercentage();

                            if($totalISPAmount >= $minAmt && $totalISPAmount <= $maxAmt) {

                                $discountArr['Precentage'] = $percentage;
                            }
                        }

                    }
                }
            }
            ##########  END  CODE FOR COUNTRY WISE DISCOUNT ##########

            $discountPrecentage = NULL;

            if(isset($discountArr['Precentage']) && !empty($discountArr['Precentage'])) {

                $discountPrecentage = $discountArr['Precentage'];
            }

            //Apply discount on current cart IPTV package
            $userCartItems = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New');
        
            if($userCartItems) {
                foreach ($userCartItems as $item) {
                    
                    if($item->getPurchaseType() != "BUNDLE"){
                        if($discountPrecentage) {
                            
                            if(!$item->getDiscountRate() && strtoupper($item->getService()->getName()) == 'IPTV') {
                                
                                $discountAmount = ($item->getActualAmount() * $discountPrecentage) / 100;
                                
                                $item->setTotalDiscount($discountAmount);
                                $item->setDiscountRate($discountPrecentage);
                                $item->setPayableAmount($item->getPayableAmount() - $discountAmount);
                                $item->setFinalCost($item->getFinalCost() - $discountAmount);
                                $this->em->persist($item);
                            }
                        } else {
                            
                            $paybleAmount = ($item->getPayableAmount() + $item->getTotalDiscount());
                            $finalCost = ($item->getFinalCost() + $item->getTotalDiscount());
                            
                            $item->setTotalDiscount(NULL);
                            $item->setDiscountRate(NULL);
                            $item->setPayableAmount($paybleAmount);
                            $item->setFinalCost($finalCost);
                            $this->em->persist($item);
                        }

                    }else{
                        $discountAmount = 0;
                        $bundleCartItems = $this->em->getRepository('DhiServiceBundle:ServicePurchase')->getUserCartItems($user,$sessionId,'New',true);
                        if(count($bundleCartItems) > 1){

                            $isApplied = $item->getBundleApplied();
                            if($isApplied == 0){
                                // $discountAmount = ($item->getActualAmount() * $item->getBundleDiscount()) / 100;
                                // $item->setPayableAmount($item->getPayableAmount() - $discountAmount);
                                $item->setFinalCost($item->getPayableAmount()); //($item->getPayableAmount() - $discountAmount);    
                                $item->setBundleApplied(1);
                            }
                            
                            // if($type == 'admin'){
                            //     $item->setFinalCost($item->getPayableAmount() - $discountAmount);
                            // }


                        }else{

                            $paybleAmount = ($item->getPayableAmount() + $item->getTotalDiscount());
                            $finalCost = ($item->getFinalCost() + $item->getTotalDiscount());

                            $item->setPayableAmount($paybleAmount);
                            $item->setFinalCost($finalCost);
                            $item->setBundleId(null);
                            $item->setPurchaseType(null);
                            $item->setBundleDiscount(null);
                        }
                        $item->setTotalDiscount(NULL);
                        $item->setDiscountRate(NULL);
                        $this->em->persist($item);

                    }
                }
                $this->em->flush();
            }

            if(!empty($discountArr)){

                return $discountArr;
            }
        }

        return false;

    }
}
