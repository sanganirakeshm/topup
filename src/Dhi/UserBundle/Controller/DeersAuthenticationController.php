<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DeersAuthenticationController extends Controller {
    protected $container;
    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;

    public function __construct($container) {

        $this->container = $container;
        
        $this->em = $container->get('doctrine')->getManager();
        $this->session = $container->get('session');
        $this->securitycontext = $container->get('security.context');
        $this->request = $container->get('request');
    }

    public function checkDeersAuthenticated($type = '', $userId = 0) {
        
        if($userId > 0){
            $user              = $this->em->getRepository("DhiUserBundle:User")->findOneById($userId);
            $userLocationServices  = $this->get('UserLocationWiseService')->getUserServiceLocationFromAdmin($user);
        }else{
            $user              = $this->securitycontext->getToken()->getUser();    
            $userLocationServices  = $this->get('UserLocationWiseService')->getUserLocationService();
        }

        
        $countDeersPackage     = $this->em->getRepository('DhiAdminBundle:Package')->countDeersIPTVPackage($this->session->get('serviceLocationId'));
        
        $ischeckDeers = false;
        
        if($type == 'Milstar') {
            
            if($userLocationServices['IsMilstarEnabled'] == 1) {
        
                $ischeckDeers = true;
            }
        }else{
            
            if($countDeersPackage > 0) {
            
                $ischeckDeers = true;
            }    
        }
        
        $isDeersAuthenticated = 0;
        if($ischeckDeers){
            
            $isDeersAuthenticated = 2;
            
            if($user->getIsDeersAuthenticated() == '1') {
            
                $objSetting     = $this->em->getRepository('DhiAdminBundle:Setting')->findOneByName('deers_timeframe');
                 
                if($objSetting){
                     
                    $settingDays    = $objSetting->getValue();
                    $objDays        = date_diff($user->getDeersAuthenticatedAt(), new \DateTime());
                    $days           = $objDays->days;
                     
                    if($settingDays < $days) {
                         
                        $user->setIsDeersAuthenticated(0);
                        $user->setIsLastLogin(0);
                        $this->em->persist($user);
                        $this->em->flush();
                                                                         
                    }else{
                                                            
                        $isDeersAuthenticated = 1;
                    }
                }
            }                        
        }            

        return $isDeersAuthenticated;        
    }
}
