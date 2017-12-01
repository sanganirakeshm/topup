<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * 
 */
class TestController extends Controller {

    public function selevisionWsAction() {

        //Call Selevision webservice for changes customer password
        $wsParam = array();
        $wsParam['cuLogin']    = 'mahesh';
        $wsParam['cuPwd']      = 'mahesh1234';
        $wsParam['cuNewPwd1']  = 'mahesh';
        $wsParam['cuNewPwd2']  = 'mahesh';
        
        
        $selevisionService = $this->get('selevisionService');
        $wsResponse = $selevisionService->callWSAction('changeCustomerPwd',$wsParam);
        
        echo "<pre>";
        print_r($wsResponse);
        exit;
    }

}
