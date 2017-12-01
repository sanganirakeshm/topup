<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardController extends Controller
{
    public function indexAction()
    {
		$em = $this->getDoctrine()->getManager();
		$month = 0;
		$prevmonth = 1 ;
        $previousMonthServiceLocation =  1;
        $paymentMethod =  1;
        $serviceLocation =  1;
		$previousMonthPaymentMethod = 1;
		
        return $this->render('DhiAdminBundle:Dashboard:index.html.twig');
    }

    public function getChartDataAction(){

        if (!($this->get('admin_permission')->checkPermission('dashboard_charts'))) {
            $resArray = array(
                'paymentMethod' => array(),
                'serviceLocation' => array(),
                'previousMonthPaymentMethod' => array(),
                'previousMonthServiceLocation' => array()
            );

        }else{

            $em = $this->getDoctrine()->getManager();
    		$month = 0;
    		$prevmonth = 1 ;
            $paymentMethod =  $this->get('chart')->getPaymentMethodWiseChartData($month);

    		$serviceLocation =  $this->get('chart')->getServiceLocationWiseChartData($month);
    		$previousMonthPaymentMethod =  $this->get('chart')->getPaymentMethodWiseChartData($prevmonth);
    		$previousMonthServiceLocation =  $this->get('chart')->getServiceLocationWiseChartData($prevmonth);
            $resArray = array(
                'paymentMethod' => $paymentMethod,
                'serviceLocation' => $serviceLocation,
                'previousMonthPaymentMethod' => $previousMonthPaymentMethod,
                'previousMonthServiceLocation' => $previousMonthServiceLocation
            );
        }
        $response = new Response(json_encode($resArray));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function getPreviousMonthPaymentMethodChartDataAction(){
        if ($this->get('admin_permission')->checkPermission('dashboard_charts')) {
            $previousMonthPaymentMethod =  $this->get('chart')->getPaymentMethodWiseChartData(1);
        }else{
            $previousMonthPaymentMethod = array();
        }
        $response = new Response(json_encode(array('previousMonthPaymentMethod' => $previousMonthPaymentMethod)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function getPaymentMethodChartDataAction(){
        if ($this->get('admin_permission')->checkPermission('dashboard_charts')) {
            $PaymentMethod =  $this->get('chart')->getPaymentMethodWiseChartData(0);
        }else{
            $PaymentMethod = array();
        }
        $response = new Response(json_encode(array('paymentMethod' => $PaymentMethod)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function getServiceLocationChartDataAction(){
        if ($this->get('admin_permission')->checkPermission('dashboard_charts')) {
            $serviceLocation =  $this->get('chart')->getServiceLocationWiseChartData(0);
        }else{
            $serviceLocation = array();
        }
        $response = new Response(json_encode(array('serviceLocation' => $serviceLocation)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    public function getPreviousMonthServiceLocationChartDataAction(){
        if ($this->get('admin_permission')->checkPermission('dashboard_charts')) {
            $previousMonthServiceLocation =  $this->get('chart')->getServiceLocationWiseChartData(1);
        }else{
            $previousMonthServiceLocation = array();
        }
        $response = new Response(json_encode(array('previousMonthServiceLocation' => $previousMonthServiceLocation)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
