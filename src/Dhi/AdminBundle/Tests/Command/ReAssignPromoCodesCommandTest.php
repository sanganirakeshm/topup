<?php

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Dhi\AdminBundle\Command\ReAssignPromoCodesCommand;
use Symfony\Component\Console\Tester\CommandTester;

class ReAssignPromoCodesCommandTest extends WebTestCase {
    protected static $application;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->em        = static::$kernel->getContainer()->get('doctrine')->getManager();
        $this->container = static::$kernel->getContainer();
    }
    
    public function testExecute() {
        $totalCodes     = array('partner' => 0, 'business' => 0, 'customer' => 0);
        $objApplication = new Application(static::$kernel);
        $objApplication->add(new ReAssignPromoCodesCommand());
        $command = $objApplication->find('dhi:reassign-promo-codes');
     
        $allPackages            = array();
        $packages               = $this->em->getRepository("DhiAdminBundle:Package")->getPromoPackages();
        $allPackages['package'] = array_keys($packages);
        
        // Bundles
        $bundles                = $this->em->getRepository("DhiAdminBundle:Bundle")->getBundlePlan();
        $allPackages['bundle']  = array_keys($bundles);

        // Partner Promo Codes
        if (!empty($allPackages['package'])) {
            $partnerPromoCodeRepo = $this->em->getRepository("DhiAdminBundle:PartnerPromoCodes");
            $totalCodes['partner'] = $partnerPromoCodeRepo->createQueryBuilder('pc')
                ->select("COUNT(pc.id)")
                ->where('pc.packageId NOT IN (:packages)')
                ->setParameter('packages', $allPackages['package'])
                ->andWhere('pc.isRedeemed = :isRedeemed')
                ->setParameter('isRedeemed', 'No')
                ->andWhere('pc.isPlanExpired <> :isPlanExpired')
                ->setParameter('isPlanExpired', 'Yes')
                ->getQuery()->getSingleScalarResult();
        }

        if (!empty($allPackages)) {
            // Business Promo Codes
            $businessPromoCodeRepo = $this->em->getRepository("DhiAdminBundle:BusinessPromoCodes");
            $totalCodes['business'] = $businessPromoCodeRepo->createQueryBuilder('bpc')
                ->select("COUNT(bpc.id)")
                ->where('bpc.packageId NOT IN (:packages)')
                ->setParameter('packages', $allPackages['package'])
                ->andWhere('bpc.packageId NOT IN (:bundles)')
                ->setParameter('bundles', $allPackages['bundle'])
                ->andWhere('bpc.isRedeemed = :isRedeemed')
                ->setParameter('isRedeemed', 'No')
                ->andWhere('bpc.isPlanExpired <> :isPlanExpired')
                ->setParameter('isPlanExpired', 'Yes')
                ->getQuery()->getSingleScalarResult();

            // Customer Promo Codes
            $promoCodeRepo = $this->em->getRepository("DhiUserBundle:PromoCode");
            $totalCodes['customer'] = $promoCodeRepo->createQueryBuilder("pc")
                ->select("COUNT(pc.id)")
                ->where("pc.noOfRedemption IS NULL")
                ->andWhere('(pc.packageId NOT IN (:packages) AND pc.isBundle = :isPackageBundle) OR (pc.packageId NOT IN (:bundles) AND pc.isBundle = :isBundle)')
                ->setParameter('packages', $allPackages['package'])
                ->setParameter('bundles', $allPackages['bundle'])
                ->setParameter('isPackageBundle', 0)
                ->setParameter('isBundle', 1)
                ->andWhere('pc.isPlanExpired <> :isPlanExpired')
                ->setParameter('isPlanExpired', 'Yes')
                ->getQuery()->getSingleScalarResult();
        }

        $objcommandtest = new CommandTester($command);
        $objcommandtest->execute(array('command' => $command->getName()));
        $result = $objcommandtest->getDisplay();

        // Partner Promo Code
        $isPartner = strpos($result, "Updating ".$totalCodes['partner']." Partner Promo Codes: Completed");
        $this->assertFalse( $isPartner === false, "Could Not Update All Partner Promo Codes");

        $isBusiness = strpos($result, "Updating ".$totalCodes['business']." Business Promo Codes: Completed");
        $this->assertFalse( $isBusiness === false, "Could Not Update All Business Promo Codes");

        $isCustomer = strpos($result, "Updating ".$totalCodes['customer']." Customer Promo Codes: Completed");
        $this->assertFalse( $isCustomer === false, "Could Not Update All Customer Promo Codes");
    }
}