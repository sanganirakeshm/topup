<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SelevisionPackageController extends Controller
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
        $this->deersAuthentication = $container->get('DeersAuthentication');
    }
    
    // get all packages for service locations
    public function userLocationPackages($service = '', $isPurchased = true) {

    		$user = $this->securitycontext->getToken()->getUser();
    		$isDeersAuthenticated 	= $this->deersAuthentication->checkDeersAuthenticated();
    		$activeService = $user->getActivePackages();
        $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary();

        $availableServiceLocationOnSite = $this->session->get('availableServiceLocationOnSite');
        $packageArr = array();
        if(!in_array($this->session->get('serviceLocationId'), $availableServiceLocationOnSite)){
            $packageArr['BUNDLE'] = array('isp'=>array(), 'iptv'=>array(), 'maxIptvPlan' => 0, 'bundle' => array());
            return $packageArr;
        }

        $condition = array(
            'isDeersAuthenticated'          => $isDeersAuthenticated,
            'service'                       => $service,
            'isEmployee'                    => 0,
            'IsBundleAvailabledInPurchased' => $summaryData['IsBundleAvailabledInPurchased'],
            'IsIPTPVAvailabledInPurchased'  => $summaryData['IsIPTVAvailabledInPurchased'],
            'isPurchased'                   => $isPurchased
        );

    		$packageArr = $this->em->getRepository('DhiAdminBundle:Package')->getAllPackage($this->session->get('serviceLocationId'),$activeService, $condition);

        if ($service != "PREMIUM") {
            $packageArr['BUNDLE'] = $this->em->getRepository('DhiAdminBundle:Bundle')->getAllBundle($this->session->get('serviceLocationId'),$activeService,$isDeersAuthenticated, $summaryData['Cart']);

            $condition = array(
                'serviceLocationId' => $this->session->get('serviceLocationId'),
                'isAutoBundle'      => true,
                'activeService'     => $activeService
            );
            $packageArr['ISPAUTOBUNDLE'] = $this->em->getRepository('DhiAdminBundle:Package')->getBundleFromPlan($condition);
        }

       	return $packageArr;
    }
    
    public function getAdminPackage($user,$serviceLocationId, $service){
    	 
        $summaryData  = $this->get('DashboardSummary')->getUserServiceSummary('admin', $user, false);
    	$packages = array();

    	if($user && $serviceLocationId){

    		$activeService 	 = $user->getActivePackages();    		
                if($service == 'BUNDLE'){
                    $packages['BUNDLE']            = $this->em->getRepository('DhiAdminBundle:Bundle')->getAllBundleAdmin($serviceLocationId,$activeService, 0, $summaryData['Cart'], $user->getIsEmployee());
                    $packages['PROMOTIONALBUNDLE'] = $this->em->getRepository('DhiAdminBundle:Bundle')->getAllpromotionalBundleAdmin($serviceLocationId,$activeService, 0, $summaryData['Cart'], $user->getIsEmployee());
                }else{
                    $condition = array(
                        'isDeersAuthenticated' => 0,
                        'service'              => $service,
                        'isEmployee'           => $user->getIsEmployee()
                    );
                    $packages = $this->em->getRepository('DhiAdminBundle:Package')->getAllPackage($serviceLocationId, $activeService, $condition);
                }
    	}
    	
	    return $packages;
    }
} 
