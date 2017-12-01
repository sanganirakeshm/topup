<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Dhi\AdminBundle\Entity\ChaseMerchantIdAuditLog;

class ChaseMerchantIdAuditLogServiceController extends Controller {

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

    public function saveChaseMerchantIdAuditLog($data) {

        $objChaseMerchantIdAuditLog = new ChaseMerchantIdAuditLog();

        if (isset($data)) {

            $objChaseMerchantIdAuditLog->setServiceLocationWiseChaseMerchantId(isset($data['ServiceLocationWiseChaseMerchantId']) ? $data['ServiceLocationWiseChaseMerchantId'] : '');
            $objChaseMerchantIdAuditLog->setServiceLocation(isset($data['ServiceLocation']) ? $data['ServiceLocation'] : 'N/A');
            $objChaseMerchantIdAuditLog->setOldChaseMerchantId(isset($data['OldChaseMerchantId']) ? $data['OldChaseMerchantId'] : NULL);
            $objChaseMerchantIdAuditLog->setNewUpdatedAt(isset($data['NewUpdatedAt']) ? $data['NewUpdatedAt'] : NULL);
            $objChaseMerchantIdAuditLog->setOldUpdatedBy(isset($data['OldUpdatedBy']) ? $data['OldUpdatedBy'] : NULL);
            $objChaseMerchantIdAuditLog->setNewUpdatedBy(isset($data['NewUpdatedBy']) ? $data['NewUpdatedBy'] : NULL);
            
            if(isset($data['OperationType'])){
                $objChaseMerchantIdAuditLog->setOperationType($data['OperationType']);
            }
            if(isset($data['OldUpdatedAt'])){
                $objChaseMerchantIdAuditLog->setOldUpdatedAt($data['OldUpdatedAt']);
            }
            if(isset($data['NewChaseMerchantId'])){
                $objChaseMerchantIdAuditLog->setNewChaseMerchantId($data['NewChaseMerchantId']);
            }
            $this->em->persist($objChaseMerchantIdAuditLog);
            $this->em->flush();
        }
    }

}
