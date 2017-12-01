<?php

namespace Dhi\UserBundle\Controller;

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


class PromoCodeServiceController extends Controller {

    protected $container;

    protected $em;
    protected $session;
    protected $securitycontext;
    protected $request;
    protected $geoLocation;
    protected $countries;

    public function __construct($container) {

        $this->container = $container;
		$this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
        $this->con               = $this->em->getConnection();
        $tikiliveEm              = $container->get('doctrine')->getManager('tikilive');
        $this->tikiliveCon       = $tikiliveEm->getConnection();
        $this->geoLocation       = $container->get('GeoLocation');
        $this->countries         = array();
    }

   public function ajaxRedeemPromoCodeAction(Request $request)	{

		$promocode = $request->get('promocode');
		$jsonResponse = array();
		$objPromoCode = $this->em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array('promoCode' => $promocode));
		if($objPromoCode){

		$jsonResponse['packageName']	=	$objPromoCode->getPackage()->getPackageName();
		$jsonResponse['description']	=	$objPromoCode->getPackage()->getDescription();
		$jsonResponse['validity']	=	$objPromoCode->getPackage()->getValidity();
		$jsonResponse['result']   = 'success';
    	$jsonResponse['succMsg']  = 'promo code'.$promocode.' has been applied successfully in cart.';
		}
		else {
		$jsonResponse['result']   = 'error';
    	$jsonResponse['errMsg']  = 'please enter valid promo-code';

		}

		echo json_encode($jsonResponse);exit;
		
	}

	public function ajaxApplyPromoCodeAction(Request $request)	{
		$totalValidaity = '';
		$jsonResponse = array();
		$jsonResponse['result']   = '';
    	$jsonResponse['succMsg']  = '';
    	$jsonResponse['errMsg']  = '';
    	$jsonResponse['response'] = '';

		$promocode = $request->get('promocode');
		$packageId = $request->get('dataid');
		$type = $request->get('type');

		$em 			= $this->getDoctrine()->getManager();
		$user 			= $this->get('security.context')->getToken()->getUser();
		$sessionId		= $this->get('paymentProcess')->generateCartSessionId();

		$condition = array('sessionId' => $sessionId, 'paymentStatus' => 'New', 'service' => $type, 'user' => $user->getId());

		$objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->findOneBy(array('promoCode' => $promocode));
		if($objPromoCode){

		$objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy($condition);
		$totalValidaity = $objServicePurchase->getValidity() + $objPromoCode->getTotalTime();
    	$objServicePurchase->setValidity($totalValidaity);
    	$em->persist($objServicePurchase);
    	$em->flush();
		$jsonResponse['result']   = 'success';
    	$jsonResponse['succMsg']  = 'promo code'.$promocode.' has been applied successfully in cart.';
		}
		else {
		$jsonResponse['result']   = 'error';
    	$jsonResponse['errMsg']  = 'please enter valid promo-code';

		}

		echo json_encode($jsonResponse);exit;
	}
        
    public function tikiliveActiveUser($arrParams = array()){

        $returnFlag = false;
        if($arrParams){

            $date = new \DateTime();
            $currdate = $date->format('Y-m-d H:i:s');
            $values = array(
                'isActive' => 0,
                'createdAt' => $currdate,
                'updatedAt' => $currdate,
                'tikiliveUserId' => $arrParams['tikilive_user_id']
            );

            if(!empty($arrParams['userId'])){

                $date               = new \DateTime();
                $currdate           = $date->format('Y-m-d H:i:s');
                $values['isActive'] = 1;
                $lastLogin          = (!empty($arrParams['lastLoginTime']) ? date("Y-m-d h:i:s", $arrParams['lastLoginTime']) : NULL);

                if (!empty($arrParams['redemptionDate'])) {
                    $expiryDate = $arrParams['redemptionDate'];
                    $expiryDate->modify("+30 DAYS");
                    $expiryDate = $expiryDate->format('Y-m-d H:i:s');
                } else {
                    $expiryDate = NULL;
                }

                $values['tikiliveLastLogin']  = $lastLogin;
                $values['tikiliveLastIp']     = (!empty($arrParams['lastLoginIP']) ? long2ip($arrParams['lastLoginIP']) : NULL);
                $values['tikiliveLastIpLong'] = (!empty($arrParams['lastLoginIP']) ? $arrParams['lastLoginIP'] : NULL);
                $values['username']           = $arrParams['userUsername'];
                $values['expiryDate']         = $expiryDate;

                if(!empty($arrParams['operation']) && $arrParams['operation'] == 'insert'){

                    $tikilivePromoCodeCondition = array(
                        'promo_code' => $arrParams['coupon_code']
                    );
                    $arrSlAndCountry                      = $this->getServiceLocationFromIPAddress($values['tikiliveLastIp']);
                    $values['promo_code']                 = $arrParams['coupon_code'];
                    $values['tikilive_user_country_code'] = $arrParams['userLogCountry'];
                    $values['service_location']           = $arrSlAndCountry['serviceLocation'];
                    $values['country']                    = $arrSlAndCountry['country'];

                    $sql = "INSERT INTO tikilive_active_user(tikilive_user_id, tikilive_user_name, tikilive_last_login, tikilive_last_ip, tikilive_last_ip_long, is_active, created_at, updated_at, promo_code_expiry_date, promo_code, tikilive_user_country_code, service_location, actual_country) VALUES (:tikiliveUserId, :username, :tikiliveLastLogin, :tikiliveLastIp, :tikiliveLastIpLong, :isActive, :createdAt, :updatedAt, :expiryDate, :promo_code, :tikilive_user_country_code, :service_location, :country)";

                }else if(!empty($arrParams['operation']) && $arrParams['operation'] == 'update'){

                    unset($values['isActive']);
                    unset($values['createdAt']);
                    unset($values['expiryDate']);
                    unset($values['username']);

                    $sql = "UPDATE tikilive_active_user SET tikilive_last_login = :tikiliveLastLogin, tikilive_last_ip = :tikiliveLastIp, tikilive_last_ip_long = :tikiliveLastIpLong, updated_at = :updatedAt WHERE tikilive_user_id = :tikiliveUserId";
                }
                
            }else if(empty($arrParams['userId']) && !empty($arrParams['operation']) && $arrParams['operation'] == 'update'){

                unset($values['createdAt']);
                unset($values['expiryDate']);
                $sql = "UPDATE tikilive_active_user SET is_active = :isActive, updated_at = :updatedAt WHERE tikilive_user_id = :tikiliveUserId ";
            }
            if(!empty($sql)){
                $statement = $this->con->prepare($sql);
                $statement->execute($values);
                $returnFlag = true;
            }
        }
        return $returnFlag;
    }

    public function unsetTikiliveConnection(){
        if (isset($this->tikiliveCon)) {
            $this->tikiliveCon = null;
        }
    }

    private function getServiceLocationFromIPAddress($ipAddress){
        $response = array('serviceLocation' => NULL, 'country' => NULL);
        if (!empty($ipAddress)) {

            // Get country
            if (!empty($this->countries[$ipAddress])) {
                $response['country'] = $this->countries[$ipAddress];

            } else {
                $geoLocation = $this->geoLocation->getIPAddress('all', $ipAddress);
                if(!empty($geoLocation['country'])) {
                    $country = ucwords($geoLocation['country']);
                    $response['country'] = $country;
                    $this->countries[$ipAddress] = $country;
                }
            }

            // Get service location
            $ipAddressZone = $this->em->getRepository('DhiAdminBundle:IpAddressZone')->getUserZone($ipAddress);
            if($ipAddressZone){
                if($ipAddressZone->getServiceLocation()){
                    $response['serviceLocation'] = $ipAddressZone->getServiceLocation()->getId();
                }
            }
        }
        return $response;
    }
}
