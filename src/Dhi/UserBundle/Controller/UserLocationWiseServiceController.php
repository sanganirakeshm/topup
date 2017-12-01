<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserLocationWiseServiceController extends Controller
{
	protected $container;
	protected $em;
	protected $session;
	protected $securitycontext;
	protected $request;

	public function __construct($container) {

		$this->container = $container;

		$this->em                = $container->get('doctrine')->getManager();
		$this->session           = $container->get('session');
		$this->securitycontext   = $container->get('security.context');
		$this->request           = $container->get('request');
	}

	public function getUserLocationService($userId = 0) {

		if($userId > 0){
            $user = $this->em->getRepository("DhiUserBundle:User")->findOneById($userId);
        }else{
            $user = $this->securitycontext->getToken()->getUser();
        }

		if($this->session->has('UserLocationWiseService')) {
			return $this->session->get('UserLocationWiseService');
		}

		$clientIp = $this->session->get('ipAddress');

		if(!$this->session->has('country') && is_object($user)) {
			$this->session->set('country', $user->getCountry()->getName());
			$country = $user->getCountry()->getName();
		}else{
			
			$country  = $this->session->get('country');
		}

		$services = $this->getServiceByLocation($user,$clientIp,$country);

		// set services to session, so response will be quick
		$this->session->set('UserLocationWiseService', $services);
		
		return $services;

	}
	
    public function getUserServiceLocationFromAdmin($user){
	
		$clientIp = ($user->getIpAddress())?$user->getIpAddress():$this->session->get('ipAddress');
		$country  = ($user->getGeoLocationCountry())?$user->getGeoLocationCountry()->getName():$this->session->get('country');
		
		$services = $this->getServiceByLocation($user, $clientIp, $country, 'admin');

		return $services;
	}

	public function getServiceByLocation($user, $clientIp, $country, $callBy = 'user'){

		$services = array();
		$services['IsMilstarEnabled'] = 0;
		$services['FacNumber'] 		  = '';
		$services['serviceLocationId']= '';
		$isServiceExist = 0;
		 
		################### START CODE FOR SERVICE LOCATION ###################
		$userIpAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($clientIp);
		if($userIpAddressZone) {

			if ($callBy == 'user') {
				$this->session->set('serviceLocationId', $userIpAddressZone->getServiceLocation()->getId());
				$this->session->set('serviceLocationName', $userIpAddressZone->getServiceLocation()->getName());
			}
			$services['serviceLocationId'] = $userIpAddressZone->getServiceLocation()->getId();
                         
			if($userIpAddressZone->getServices()){

				// get the services for IP range
				foreach($userIpAddressZone->getServices() as $service) {
					 
					$services[strtoupper($service->getName())]['ServiceId'] = $service->getId();
					$services[strtoupper($service->getName())]['ServiceName'] = strtoupper($service->getName());
				}

				$isServiceExist = 1;
			}

			if(!empty($services)) {
				 
				// check milstar facility for IP range
				if($userIpAddressZone->getIsMilstarEnabled() && array_key_exists('IPTV',$services)) {
					 
					$services['IsMilstarEnabled'] = 1;
					$services['FacNumber'] = $userIpAddressZone->getMilstarFacNumber();
				}
			}
			 
		}
		 
		################### END CODE FOR SERVICE LOCATION ###################
		 
		################### START CODE FOR COUNTRY SERVICE ###################
		// now what if service location is not exist for current user IP
		// then provide the services based on the user's country id
		 
		if(!$isServiceExist) {
			 
			//$services = array('IsMilstarEnabled' => 0);
			// get country based on user IP
			 
			$country = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('name' => $country));
			 
			if($country) {

				$countryServices = $this->em->getRepository('DhiUserBundle:CountrywiseService')->getAllActiveCountrywiseService($country->getId());

				if($countryServices) {
					foreach ($countryServices as $service) {
	
						$services[strtoupper($service['name'])]['ServiceId']   = $service['id'];
						$services[strtoupper($service['name'])]['ServiceName'] = strtoupper($service['name']);
					}
					$isServiceExist = 1;
				}
			}
		}
		################### END CODE FOR COUNTRY SERVICE ###################
		################### START CODE FOR GLOBAL SERVICE ###################
                
		if(!$isServiceExist) {
			
        	$mainServices = $this->em->getRepository('DhiUserBundle:Service')->getAllActiveService();
                    
            foreach ($mainServices as $service) {

            	$services[strtoupper($service['name'])]['ServiceId']   = $service['id'];
                $services[strtoupper($service['name'])]['ServiceName'] = strtoupper($service['name']);
			}
		}
		
        ################### START CODE FOR GLOBAL SERVICE ###################
                
		################### START CODE FOR DISABLED SERVICE ###################
		// now check if any service is disabled for user. If any, excempt that service
		
		if(is_object($user)) {
		
		$disabledServices = $this->em->getRepository('DhiUserBundle:UserServiceSetting')->getDisableServices($user->getId());
		 
		if(!empty($disabledServices)) {
			 
			foreach($disabledServices as $disableService) {
				 
				if(array_key_exists($disableService, $services)) {
					 
					unset($services[$disableService]);
				}
			}
		}
		
		}
		################### END CODE FOR DISABLED SERVICE ###################
		 
		################### START CODE IF MAIN IS SERVICE DISABLED ###################
		$disabledServices = $this->em->getRepository('DhiUserBundle:Service')->getDisableServices();
		 
		if(!empty($disabledServices)) {
			 
			foreach($disabledServices as $disableService) {
				 
				if(array_key_exists($disableService, $services)) {
					 
					unset($services[$disableService]);
				}
			}
		}
		################### END CODE IF MAIN IS SERVICE DISABLED ###################

		################### START CODE BUNDLES #########################

		if(isset($services['IPTV']) && isset($services['ISP'])){
			$services['BUNDLE'] = 1;
		}

		################### END CODE BUNDLES #########################

		
		return $services;
	}

	
}
