<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class UserWiseServiceController extends Controller
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
    
    public function getUserService($ipAddress, $user) {
        
        $country = $user->getCountry();
        
        $services = array('services' => '', 'location' => '');
        
        ################### START CODE FOR SERVICE LOCATION ###################
        $userIpAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($ipAddress);
        
        if($userIpAddressZone) {

            // get the services for IP range
            foreach($userIpAddressZone->getServices() as $service) {
                $services['services'][strtoupper($service->getName())] = strtoupper($service->getName());
            }

            $services['location'] = $userIpAddressZone->getServiceLocation()->getName();
        }
        
        ################### END CODE FOR SERVICE LOCATION ###################
        
        ################### START CODE FOR COUNTRY SERVICE ###################
        // now what if service location is not exist for current user IP
        // then provide the services based on the user's country id
        
        if(empty($services['services'])) {
            
            $countryServices = $this->em->getRepository('DhiUserBundle:CountrywiseService')->getAllActiveCountrywiseService($country->getId());
            
            foreach ($countryServices as $key => $service) {
                $services['services'][strtoupper($service['name'])] = strtoupper($service['name']);            
            }
        }
        ################### END CODE FOR COUNTRY SERVICE ###################
        
        ################### START CODE FOR DISABLED SERVICE ###################
        // now check if any service is disabled for user. If any, excempt that service
        $disabledServices = $this->em->getRepository('DhiUserBundle:UserServiceSetting')->getDisableServices($user->getId());
        
        if(!empty($disabledServices)) {
            foreach($disabledServices as $disableService) {
                
                if(isset($services['services'][$disableService])) {
                    
                    unset($services['services'][$disableService]);
                }
            }
        }
        ################### END CODE FOR DISABLED SERVICE ###################
        
        return $services;
        
    }
    
}
