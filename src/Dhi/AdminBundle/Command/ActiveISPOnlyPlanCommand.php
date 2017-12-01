<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class ActiveISPOnlyPlanCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this->setName('dhi:activate-isp-plan')->setDescription('Activate ISP only plans.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln("\n####### Start Activate ISP only plans Cron at " . date('M j H:i') . " #######\n");
        $em = $this->getContainer()->get('doctrine')->getManager();
        $aradial = $this->getContainer()->get('aradial');

        $inactivePackages = $em->getRepository("DhiUserBundle:UserService")->getInactiveIspPlans();
        $toFlush = false;
        
        foreach ($inactivePackages as $ispPackage) {
            $userId = $ispPackage->getUser()->getUsername();
            $aradialUser = $aradial->getUserDetails($userId);
            
            if (!empty($aradialUser)) {
                $planExpiryDate = trim($aradialUser['expiryDate']);
                $planActivationDate = trim($aradialUser['activationDate']);

                if (!empty($planExpiryDate)) {
                    $output->writeln("Setting Expiry date for user: $userId");
                    $isPromocodeUsed = false;
                    $servicePurchase = $ispPackage->getServicePurchase();
                    
                    if ($servicePurchase) {
                        $promocodeUsed = $servicePurchase->getPromoCodeApplied();

                        if (in_array($promocodeUsed, array(1, 2, 3))) {
                            $isPromocodeUsed = true;
                        }
                    }
                    
                    $validity 							= $ispPackage->getValidity();
                    $validityType 					= $servicePurchase->getValidityType();
                    $expiryDate 						= new \DateTime($planExpiryDate);
                    $hours 									= $expiryDate->format("H:i:s");
                    $aradialActivationDate 	= new \DateTime($planActivationDate); //\DateTime::createFromFormat("m/d/y H:i:s",$planActivationDate." ".$hours);
                    $user 									= $ispPackage->getUser();
                    
                    if (empty($validityType)) {
                        $validityType = 'DAYS';
                    }


                    if($ispPackage->getSuspendedStatus() == 2){
                        $user           = $ispPackage->getUser();
                        $activationDate = $ispPackage->getActivationDate();
                        $expiryDate     = $ispPackage->getExpiryDate();
                        $isUserUpdated  = $aradial->updateUserIsp($user, $activationDate, $expiryDate);
                        $ispPackage->setSuspendedStatus(3);

                    } else if ($isPromocodeUsed == true) {
                        $activationDate = $aradialActivationDate;
                        $expiryDate     = clone $activationDate;
                        $user           = $ispPackage->getUser();
                        $expiryDate->modify('+' . $validity . ' ' . $validityType);
                        $isUserUpdated  = $aradial->updateUserIsp($user, $activationDate, $expiryDate);

                    } else if ($expiryDate < $ispPackage->getExpiryDate() || $aradialActivationDate < $ispPackage->getActivationDate()) {
                        $isUserUpdated = $aradial->updateUserIsp($user, $ispPackage->getActivationDate(), $ispPackage->getExpiryDate());
                        $activationDate = $ispPackage->getActivationDate();
                        $expiryDate = $ispPackage->getExpiryDate();

                    } else {
                        $activationDate = clone $expiryDate;
                        $activationDate->modify('-' . $validity . ' ' . $validityType);
                        $isUserUpdated = true;
                    }
                    
                    if ($isUserUpdated) {
                        $ispPackage->setActivationDate($activationDate);
                        $ispPackage->setExpiryDate($expiryDate);
                        $ispPackage->setIsPlanActive(true);
                        $em->persist($ispPackage);
                        $toFlush = true;
                    }
                }
            }
        }

        if ($toFlush) {
            $em->flush();
        }

        $output->writeln("\n####### End Cron #######\n");
    }
}
