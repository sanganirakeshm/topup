<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\UserBundle\Entity\UserService;

class ChartController extends Controller
{
    protected $container;
    
    protected $em;
    protected $session;
    protected $securitycontext;
    
    public function __construct($container) {
        
        $this->container = $container;
        
        $this->em                = $container->get('doctrine')->getManager();
        $this->session           = $container->get('session');
        $this->securitycontext   = $container->get('security.context');
    }
	
	public function getServiceLocationWiseChartData($month)
    {
		$saleReportData = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getServiceLocationForChart();

		$serviceLocationData = array();
		if(!empty($saleReportData)){
			foreach($saleReportData as $salesData) {
				$monthlyData = $this->saleReportChartArray($salesData['ipAddressZones'],$month);
				if (!empty($monthlyData)) {
					$totalAmount = 0;
					foreach ($monthlyData as $paymentData) {
						$totalAmount = $totalAmount + $paymentData['totalAmount'];	
						$serviceLocationData[$salesData['name']]['label'] 	= $salesData['name'];
					}
					$serviceLocationData[$salesData['name']]['data']	= $totalAmount;
				}
			}
		}

		$serviceLocationData = array_values($serviceLocationData);
		if($serviceLocationData) {
			return json_encode($serviceLocationData);
		}
		return false;
    }

    public function getPaymentMethodWiseChartData($month)
    {
		$saleReportData = $this->em->getRepository('DhiAdminBundle:ServiceLocation')->getServiceLocationForChart();
		$paymentData = array();
		if($saleReportData){
			foreach($saleReportData as $salesData) {
				$paymentData[] = $this->saleReportChartArray($salesData['ipAddressZones'],$month);
			}
		}

		$data = array();
		if(!empty($paymentData)) {
			$totalAmount = 0;
			$tempArray = array();
			$dataTemp = array();
			foreach ($paymentData as $payment) {
				foreach($payment as $pay){
					$tempArray['paymentMethod'] = $pay['paymentMethod'];
					$tempArray['totalAmount'] = $pay['totalAmount'];
					$dataTemp[] = $tempArray; 
				}
			}

			$res  = array();
			foreach($dataTemp as $paymentwise)
			{
				if(array_key_exists($paymentwise['paymentMethod'],$res)){
					$res[$paymentwise['paymentMethod']]['totalAmount']   += $paymentwise['totalAmount'];
				}else{
					$res[$paymentwise['paymentMethod']]  = $paymentwise;
				}
			}

			foreach($res as $key => $value){
				$temp = array();
				$temp['label'] = $value['paymentMethod'];
				$temp['data']  = $value['totalAmount'];
				$data[] = $temp;
			}
		}

		if($data) {
			return json_encode($data);
		}
		return false;
    }

	public function saleReportChartArray($resultRow,$month){
            
            $summaryArr = array();
		if ($resultRow) {
			foreach($resultRow as $ipAddressZone) {
				$fromIpAddress	= $ipAddressZone['fromIpAddressLong'];
				$toIpAddress	= $ipAddressZone['toIpAddressLong'];

				$objUserService = $this->em->getRepository('DhiUserBundle:UserService')->getPaymentChartData($fromIpAddress,$toIpAddress,$month);		
				if ($objUserService) {
					foreach ($objUserService as $userService) {
						$summaryArr[] = $userService;
					}
				}
			}
		}
		return $summaryArr;
	}
}
