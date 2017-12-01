<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GeoLocationController extends Controller
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
        $this->request           = $container->isScopeActive('request') ? $container->get('request') : '';
    }

    public function getIPAddress($type = 'all', $ipAddress = '')
    {
        if (empty($ipAddress)) {
            $ipAddress = $this->getRealIpAddress();
        }
        $this->session->set('ipAddress',$ipAddress);

        $url = "http://pro.ip-api.com/php/".$ipAddress."?key=UFKzFOZdcXrDQmU";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if (empty($response)) {
            return false;
        }

        $result = unserialize($response);

        if($type != '' || $result){

            $this->session->set('ipAddress',$result['query']);
            $this->session->set('country',(isset($result['country']))?$result['country']:'');

            if($type == 'ip'){

                return $result['query'];
            }else{

                return array('ip' => $result['query'], 'country' => (isset($result['country']))?$result['country']:'');
            }
        }else{

            $this->session->set('ipAddress',$this->request->getClientIp());

            return $this->request->getClientIp();
        }

        return false;
    }

    public function getRealIpAddress() {
		$this->session->set('ipAddress',$this->request->getClientIp());
        return $this->request->getClientIp();
        /*
	    	if (isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR'] != '127.0.0.1')
	            $ipaddress = $_SERVER['REMOTE_ADDR'];
	        else if (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP'] != '127.0.0.1')
	            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
	        else if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '127.0.0.1')
	            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
	        else if (isset($_SERVER['HTTP_X_FORWARDED']) && $_SERVER['HTTP_X_FORWARDED'] != '127.0.0.1')
	            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
	        else if (isset($_SERVER['HTTP_FORWARDED_FOR']) && $_SERVER['HTTP_FORWARDED_FOR'] != '127.0.0.1')
	            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
	        else if (isset($_SERVER['HTTP_FORWARDED']) && $_SERVER['HTTP_FORWARDED'] != '127.0.0.1')
	            $ipaddress = $_SERVER['HTTP_FORWARDED'];
	        else 
	            $ipaddress = $_SERVER['REMOTE_ADDR'];
	
	        return $ipaddress;
        */
    }

    public function getStates(){
        $states = array(
            'AL'=>"Alabama",  
            'AK'=>"Alaska",  
            'AZ'=>"Arizona",  
            'AR'=>"Arkansas",  
            'CA'=>"California",  
            'CO'=>"Colorado",  
            'CT'=>"Connecticut",  
            'DE'=>"Delaware",  
            'DC'=>"District Of Columbia",  
            'FL'=>"Florida",  
            'GA'=>"Georgia",  
            'HI'=>"Hawaii",  
            'ID'=>"Idaho",  
            'IL'=>"Illinois",  
            'IN'=>"Indiana",  
            'IA'=>"Iowa",  
            'KS'=>"Kansas",  
            'KY'=>"Kentucky",  
            'LA'=>"Louisiana",  
            'ME'=>"Maine",  
            'MD'=>"Maryland",  
            'MA'=>"Massachusetts",  
            'MI'=>"Michigan",  
            'MN'=>"Minnesota",  
            'MS'=>"Mississippi",  
            'MO'=>"Missouri",  
            'MT'=>"Montana",
            'NE'=>"Nebraska",
            'NV'=>"Nevada",
            'NH'=>"New Hampshire",
            'NJ'=>"New Jersey",
            'NM'=>"New Mexico",
            'NY'=>"New York",
            'NC'=>"North Carolina",
            'ND'=>"North Dakota",
            'OH'=>"Ohio",  
            'OK'=>"Oklahoma",  
            'OR'=>"Oregon",  
            'PA'=>"Pennsylvania",  
            'RI'=>"Rhode Island",  
            'SC'=>"South Carolina",  
            'SD'=>"South Dakota",
            'TN'=>"Tennessee",  
            'TX'=>"Texas",  
            'UT'=>"Utah",  
            'VT'=>"Vermont",  
            'VA'=>"Virginia",  
            'WA'=>"Washington",  
            'WV'=>"West Virginia",  
            'WI'=>"Wisconsin",  
            'WY'=>"Wyoming"
            // ,'Others' => 'Others'
        );

        return $states;
    }
}
