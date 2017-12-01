<?php

namespace Dhi\UserBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MaintenanceModeHandler {

    private $doctrine;
    private $router;
    private $container;

    public function __construct($doctrine, $router, $container) {
        $this->doctrine = $doctrine;
        $this->router = $router;
        $this->container = $container;
    }

    public function onCheckMaintenance(GetResponseEvent $event) {
        $request = $this->container->get('request');
        $session = $this->container->get('session');
        $requestHost = $request->getHost();
                
        $domainData = $this->doctrine->getRepository('DhiAdminBundle:WhiteLabel')->findOneBy(array('domain' => $requestHost, 'status' => true, 'isDeleted' => false));
        if($domainData)
        {
            $brand = array();
            $brandId = $domainData->getId();
            $brandName = $domainData->getCompanyName();
            $brandDomain = $domainData->getDomain();
            $brandFromEmail = $domainData->getFromEmail();
            $brandHeaderLogo = $domainData->getHeaderLogo();
            $brandFooterLogo = $domainData->getFooterLogo();
            $brandBanner = $domainData->getBrandingBanner();
            $brandInnerPageBanner = $domainData->getBrandingBannerInnerPage();
            $brandFavicon = $domainData->getFavicon();
            $supportpage = $domainData->getSupportpage();
            $backgroundImage = $domainData->getBackgroundimage();

            $brand['id'] = $brandId;
            $brand['name'] = $brandName;
            $brand['domain'] = $brandDomain;
            $brand['fromEmail'] = $brandFromEmail;
            $brand['headerLogo'] = $brandHeaderLogo;
            $brand['footerLogo'] = $brandFooterLogo;
            $brand['banner'] = $brandBanner;
            $brand['innerPageBanner'] = $brandInnerPageBanner;
            $brand['favicon'] = $brandFavicon;
            $brand['supportpage'] = $supportpage;
            $brand['backgroundImage'] = $backgroundImage;
            
            $session->set('brand', $brand);
            
            $availableServiceLocationOnSite = array();
            if($domainData->getServiceLocationWiseSite()){
                foreach ($domainData->getServiceLocationWiseSite() as $serviceLocation){
                    if($serviceLocation->getIsDeleted() == 0){
                        $locationId = $serviceLocation->getServiceLocation()->getId();
                        $availableServiceLocationOnSite[$locationId] = $locationId;
                    }
                }
            }
            $session->set('availableServiceLocationOnSite', $availableServiceLocationOnSite);
        }
        else
        {
            $session->remove('brand');
            $defaultSite = $this->doctrine->getRepository('DhiAdminBundle:Setting')->findOneByName('default_white_label_domain');
            if ($defaultSite) {
                $url = $defaultSite->getValue();
                $response = new RedirectResponse($url);
                $event->setResponse($response);
            }else{
                throw new NotFoundHttpException('Invalid Request');
            }
        }
        
        $objSetting = $this->doctrine->getRepository('DhiAdminBundle:Setting')->findOneBy(array('name' => 'friend_referral_enabled'));
        $referModuleStatus = 0;
        if($objSetting){
            $referModuleStatus  = $objSetting->getValue();
        }
        $session->set('isActiveReferModule', $referModuleStatus);
        
        $route = $this->container->get('request')->get('_route');
        $adminRoutes = array('dhi_service_plan','dhi_user_credit','dhi_user_account');
        $objMaintenance = $this->doctrine->getRepository('DhiAdminBundle:Setting')->findOneByName('maintenance_mode');
        
        if ($objMaintenance->getValue() == 'True') {

            if (in_array($route, $adminRoutes)) {

                $event->setResponse(new RedirectResponse($this->router->generate('dhi_user_plan_maintenance')));
            }
        }
    }

}
