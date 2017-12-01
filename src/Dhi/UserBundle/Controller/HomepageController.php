<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Dhi\UserBundle\Entity\Country;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;

class HomepageController extends Controller {

		public function indexAction(Request $request) {

			$em = $this->getDoctrine()->getManager();
			$allCountry = $em->getRepository('DhiAdminBundle:Package')->getCountryByIPTVService();

			if ($this->getRequest()->get('param') != '') {
				$countryValue = $this->getRequest()->get('param');
				$this->get('session')->set('Country', $this->getRequest()->get('param'));
			} else {
				$countryValue = $this->getRequest()->cookies->get('mycookie');
			}

			$cookieGuest = array(
				'name' => 'mycookie',
				'value' => $countryValue,
				'path' => '',
				'time' => time() + 24*60*60
			);
			$country = $em->getRepository('DhiUserBundle:Country')->findBy(array('isoCode'=>$countryValue));
			if($country){
				$countryId = $country[0]->getId();
			} else {
				$countryId = null;
			}

			$banner      = $em->getRepository('DhiUserBundle:Banner')->findBy(array('country'=>$countryId,'status' => 1), array('orderNo' => 'ASC'));
			$isSecure    = $request->isSecure() ? 'https://' : 'http://';
			$imgUrl      = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl("uploads");				
			$locationIds = null;
			$ipAddress   = $this->get("GeoLocation")->getRealIpAddress();
			if($ipAddress){
	            $ipAddressZone = $em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($ipAddress);
	            if($ipAddressZone){
	                if($ipAddressZone->getServiceLocation()){
	                    $locationIds = $ipAddressZone->getServiceLocation()->getId();
	                }
	            }
	        }

			$imageArray  = array();
			if(!empty($locationIds) ){
	    	/*$objpromotionBanner = $em->getRepository('DhiAdminBundle:Promotion')->getActiveCodeData($locationIds);
      	foreach($objpromotionBanner as $promotionBanner){
      		$imageArray[] = array('bannerImange' => $promotionBanner['bannerImage'] ,'bannerAltText' => 'Promotion Banner Image', 'isPromotion' => 1);
      	}*/

        $objDiscountImage = $em->getRepository('DhiAdminBundle:DiscountCode')->getActiveCodeData($locationIds);
	      foreach($objDiscountImage as $discountImage){
	      	if (!empty($discountImage['discountImage']) && file_exists($this->getRequest()->server->get('DOCUMENT_ROOT').$this->getRequest()->getBasePath().'/uploads/'.$discountImage['discountImage'])) {
	        	$imageArray[] = array('bannerImange' => $imgUrl.'/'.$discountImage['discountImage'] ,'bannerAltText' => 'Promocode Banner Image');
	       	}
		    }
			}

	    foreach($banner as $bannerdetail){
	    	if ($bannerdetail->getBannerImages() && file_exists($this->getRequest()->server->get('DOCUMENT_ROOT').$this->getRequest()->getBasePath().'/uploads/'.$bannerdetail->getBannerImages())) {
	        $imageArray[] = array('bannerImange' => $imgUrl.'/'.$bannerdetail->getBannerImages(),'bannerAltText' => $bannerdetail->getCountry()->getName());
	      }
	    }

	    $homeContent = $em->getRepository('DhiUserBundle:FrontHome')->findBy(array('country'=>$countryId));

			$cookie = new Cookie($cookieGuest['name'], $cookieGuest['value'], $cookieGuest['time'], $cookieGuest['path']);
			$response = new Response();
			$response->headers->setCookie($cookie);
			$response->sendHeaders();
                        $hostname = $request->getHost();

			return $this->render('DhiUserBundle:Homepage:index.html.twig',array('banners'=> $imageArray,'homeContent' => $homeContent, 'imageUrl' => $imgUrl,'hostname'=>$hostname));
		}

   	public function setTopupLinkAction(Request $request){
            $user = $this->get('security.context')->getToken()->getUser();
            $isRoleuser = $this->get('security.context')->isGranted('ROLE_USER');
            $topUpLinkName = '';
            $topUpLink = '';
            if($isRoleuser){
            
                $userServiceLocation = '';
                if($user){
                    $userServiceLocation = $user->getUserServiceLocation() ? $user->getUserServiceLocation()->getName() : '';
                }
                $em = $this->getDoctrine()->getManager();
                $objTopupLink = $em->getRepository('DhiAdminBundle:TopupLink')->checkLinkAvailableForUser($userServiceLocation);
                if($objTopupLink){
                    foreach ($objTopupLink as $result){
                        $topUpLinkName = $result->getLinkName();
                        $topUpLink = $result->getUrl();
                    }
                }
            }
            return $this->render('DhiUserBundle:Homepage:setTopupLink.html.twig',array('topupLinkName' => $topUpLinkName, 'topupLink' => $topUpLink));
    }

    public function landingAction() {	
			$em = $this->getDoctrine()->getManager();
			//$allCountry = $em->getRepository('DhiAdminBundle:Package')->getCountryByIPTVService();
			$allCountry = $em->getRepository('DhiUserBundle:Country')->getCountyForShowOnLanding();
			if ($this->getRequest()->get('location') == 'home') {
				$this->get('session')->remove('Country');
				$response = new Response();
				$response->headers->clearCookie('mycookie');
				$response->sendHeaders();
				return $this->render('DhiUserBundle:Homepage:landing.html.twig', array('allCountry' => $allCountry));
			}

			if ($this->getRequest()->cookies->get('mycookie')) {
				return $this->redirect($this->generateUrl('dhi_user_homepage'));
			}

			$this->get('session')->remove('Country');
			return $this->render('DhiUserBundle:Homepage:landing.html.twig', array('allCountry' => $allCountry));
	}

	public function errorAction(Request $request) {
		return $this->render('DhiUserBundle:Homepage:error.html.twig', array('status_code' => 404));
	}

	public function aboutAction(Request $request) {
                $hostname = $request->getHost();
		return $this->render('DhiUserBundle:Homepage:about.html.twig',array('hostname'=>$hostname));
	}

	public function faqAction(Request $request) {
                $hostname = $request->getHost();
		return $this->render('DhiUserBundle:Homepage:faq.html.twig',array('hostname'=>$hostname));
	}
}
