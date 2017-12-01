<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\UserActivityLog;

class ActivityLogServiceController extends Controller
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
    
    public function saveActivityLog($data)
    {

        $objActivityLog = new UserActivityLog();
        
        if (isset($data['admin']) && !empty($data['admin'])) {
            
            $objActivityLog->setAdmin($data['admin']);
            if (isset($data['user']) && !empty($data['user'])){
                $objActivityLog->setUser($data['user']);
            }
            $adminSessionId = $this->get('session')->get('adminSessionId');
            if(!$adminSessionId){
                
                $adminSessionId = $this->get('session')->getId();
            }
            $objActivityLog->setSessionId($adminSessionId);
        } else if (isset($data['user']) && !empty($data['user'])) {
        
            $objActivityLog->setUser($data['user']);
            $userSessionId = $this->get('session')->get('userSessionId');
            if(!$userSessionId){
                if($data['activity'] == 'Email Confirmation'){
                    $userSessionId = 'User Id: '.$data['user']->getId();
                }else{
                    $userSessionId = $this->get('session')->getId();
                }
            }
            $objActivityLog->setSessionId($userSessionId);
        } else if (isset($data['ispuser']) && !empty($data['ispuser'])) {
        
            $objActivityLog->setUser($data['ispuser']);
            $ispuserSessionId = $this->get('session')->get('ispPartnerSessionId');
            if(!$ispuserSessionId){
                $ispuserSessionId = $this->get('session')->getId();
            }
            $objActivityLog->setSessionId($ispuserSessionId);
        }
        
        $ipAddress = $this->get('session')->get('ipAddress');
        
        if(!$ipAddress) {
            $ipAddress = $this->get('GeoLocation')->getRealIpAddress();
        }
        
        if ($this->get('session')->has('brand')) {
            $objActivityLog->setWhiteLabelId($this->get('session')->get('brand')['id']);
        }
        
        if(isset($data['activity']) && isset($data['description']) && $ipAddress) {
            $objActivityLog->setActivity($data['activity']);
            $objActivityLog->setDescription($data['description']);
            $objActivityLog->setIp($ipAddress);
            $objActivityLog->setVisitedUrl($this->request->getUri());
            
            $this->em->persist($objActivityLog);
            $this->em->flush();
        }
    }
}
