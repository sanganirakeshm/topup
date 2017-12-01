<?php

namespace Dhi\UserBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Core\SecurityContext;
use Doctrine\Bundle\DoctrineBundle\Registry as Doctrine;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Dhi\UserBundle\Entity\UserLoginLog;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\Country;
use Dhi\UserBundle\Entity\User;
use \DateTime;
/**
 * Listener responsible to change the redirection at the end of the password resetting
 */

class LoginListener {

    /** @var \Symfony\Component\Security\Core\SecurityContext */
    private $securityContext;

    /** @var \Doctrine\ORM\EntityManager */
    private $em;
    
    /** @var \Symfony\Component\HttpFoundation\Session\Session */
    private $session;
    
    private $router;
    
    protected $container;
    


    /**
     * Constructor
     * 
     * @param SecurityContext $securityContext
     * @param Doctrine        $doctrine
     */
    public function __construct(Router $router, SecurityContext $securityContext, Doctrine $doctrine, Session $session, $container) {
        $this->securityContext = $securityContext;
        $this->em = $doctrine->getEntityManager();
        $this->session = $session;
        $this->router = $router;
        $this->router = $router;
        $this->container = $container;
        
        $this->ActivityLog = $container->get('ActivityLog');
        $this->GeoLocation = $container->get('GeoLocation');
                
    }

    /**
     * Do the magic.
     * 
     * @param InteractiveLoginEvent $event
     */
    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event) {
        $request = $event->getRequest();
        
        //$response = new RedirectResponse($this->router->generate('fos_user_security_login'));
        $objActivityLog = new UserActivityLog();
        
        if(strpos($request->getPathInfo(), '/admin/') !== false) {
            
            if ($this->securityContext->isGranted('ROLE_ADMIN')) {
                
                $admin = $event->getAuthenticationToken()->getUser();
            
                $admin->setIsloggedin(true);
                $this->em->persist($admin);
                $this->em->flush();
                
                
                /* START: add admin audit log for login activity */
                $activityLog = array();
                $activityLog['admin'] 	 = $admin;
                $activityLog['activity'] = 'Admin Logged In';
                $activityLog['description'] = 'Admin '.$admin->getUsername().' has logged in.';
                $this->ActivityLog->saveActivityLog($activityLog);
                /* END: add admin audit log for login activity */                
                $this->GeoLocation->getRealIpAddress();
                $response = new RedirectResponse($this->router->generate('dhi_admin_dashboard'));
            } else {
                //$this->session->getFlashBag()->add('failure', 'You are not authrize to login.');
                $response = new RedirectResponse($this->router->generate('admin_login'));
            }
        } else { 
            if ($this->securityContext->isGranted('ROLE_ADMIN')) {
                //$this->session->getFlashBag()->add('failure', 'You are not authrize to login.');
                $response = new RedirectResponse($this->router->generate('fos_user_security_logout'));
                //die('admin from frontend');
            } else {
                $response = new RedirectResponse($this->router->generate('dhi_user_account'));
                
                $user      = $event->getAuthenticationToken()->getUser();
                $ipAddress = $this->GeoLocation->getRealIpAddress();
                $geoLocationCountry = NULL;
                $userServiceLocation = NULL;
                $country = NULL;
                
                if($user->getIsSuspended() == FALSE ){
                    //Get service location country from ipAddress
                    if($ipAddress){

                        $ipAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($ipAddress);
                        if($ipAddressZone){

                            if($ipAddressZone->getServiceLocation()){

                                if($ipAddressZone->getServiceLocation()->getCountry()){

                                    $geoLocationCountry = $ipAddressZone->getServiceLocation()->getCountry();
                                }

                                $userServiceLocation = $ipAddressZone->getServiceLocation();
                            }
                        }
                    }

    //                 $start = microtime(true);//Start Debug
                    $geoLocation = $this->GeoLocation->getIPAddress('all');
    //                 $end = microtime(true);//End debug
    //                 $this->container->get('optimizeLogger')->info('Debug#1 load time: '.sprintf("%.3f seconds", $end - $start)); //Write paypal log

                    if(!empty($geoLocation['country'])) {

                        if(isset($geoLocation) && array_key_exists('country',$geoLocation) && array_key_exists('ip',$geoLocation)){

                            $countryCode = ucwords($geoLocation['country']);
                            $country     = $this->em->getRepository('DhiUserBundle:Country')->findOneBy(array('name' => $countryCode));                                                                                                             
                        }
                    }

                    //Update User IpAddress and ServiceLocation
                    $user->setIpAddress($ipAddress);
                    $user->setIpAddressLong(sprintf("%u", ip2long($ipAddress)));
                    $user->setGeoLocationCountry(($geoLocationCountry)?$geoLocationCountry:$country);
                    $user->setUserServiceLocation($userServiceLocation);

                    $this->em->persist($user);
                    $this->em->flush();

                    /* START: add user login log for login */

                    $whiteLabelBrand = $this->session->get('brand');
                    $whiteLabelBrandObj = false;
                    if($whiteLabelBrand)
                    {
                        $whiteLabelBrandId = $whiteLabelBrand['id'];
                        $whiteLabelBrandDomain = $whiteLabelBrand['domain'];
                        $whiteLabelBrandObj = $this->em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrandId);
                    }

                    $userLoginLog = new UserLoginLog();
                    $userLoginLog->setIpAddress($ipAddress);
                    $userLoginLog->setCreatedAt(new DateTime());
                    $userLoginLog->setCountry(($geoLocationCountry)?$geoLocationCountry:$country);
                    $userLoginLog->setUser($user);
                    if($whiteLabelBrandObj)
                    {
                        $userLoginLog->setWhiteLabel($whiteLabelBrandObj);
                    }

                    $this->em->persist($userLoginLog);
                    $this->em->flush();
                    /* END: add user login log for login */

                    /* START: add admin audit log for login activity */
                    $activityLog = array();
                    $activityLog['user'] 	    = $user;
                    $activityLog['activity']    = 'Customer Logged In';
                    $activityLog['description'] = 'User '.$user->getUsername().' has logged in.';
                    $this->ActivityLog->saveActivityLog($activityLog);
                    /* END: add admin audit log for login activity */
                }
            }
        }
        
        return $response;
    }
}

?>
