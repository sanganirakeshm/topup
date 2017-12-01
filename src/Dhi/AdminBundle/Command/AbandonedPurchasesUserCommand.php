<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;

class AbandonedPurchasesUserCommand extends ContainerAwareCommand {
    private $output;

    protected function configure() {
        $this->setName('dhi:abandoned-purchase-user')->setDescription('Abandoned Purchase of user.');
    }
    
    public function execute(InputInterface $input,OutputInterface $output) {
        
        $output->writeln("\n####### Start Abandoned Purchase User Cron at " . date('Y M j H:i') . " #######\n");
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->con         = $em->getConnection();
        $abandedthreshold    = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('abandoned_threshold');
        $abandedthresholdval = trim($abandedthreshold->getValue());
        
        if(!is_numeric($abandedthresholdval) || $abandedthresholdval < 0){
            $output->writeln("\n####### Cron Aborted (Error): Abandoned purchase threshold shoud be integer value. #######\n");
            exit();
        }
        
        $userpurchase = $em->getRepository("DhiUserBundle:User")->getAbandonedPurchase($abandedthresholdval);
         
        if($userpurchase){
            
            $istoflush = false;
            foreach($userpurchase as $resultRow){
               
              if(!empty($resultRow['lastpurchasedate']) && !empty($resultRow['lastLogin'])){  
                    
                     $timestamp = 'abn_'.date('YmdHis');
                     $objuser = $em->getRepository("DhiUserBundle:User")->find($resultRow['id']);
                     if($objuser){
                         $usernameforlog       = $resultRow["username"];
                         $newusername          = $timestamp.'_'.$resultRow['username'];
                         $newusernamecanoical  = $timestamp.'_'.$resultRow['usernameCanonical'];
                         $newemail             = $timestamp.'_'.$resultRow['email'];
                         $newemailcanoical     = $timestamp.'_'.$resultRow['emailCanonical'];
                            
                         $objuser->setUsername($newusername);
                         $objuser->setUsernameCanonical($newusernamecanoical);
                         $objuser->setEmail($newemail);
                         $objuser->setEmailCanonical($newemailcanoical);
                         $objuser->setIsAbandoned(true);
                         $objuser->setIsDeleted(true);
                         $em->persist($objuser);
                         
                        $sql = "UPDATE user_activity_log SET user = '$newusername' Where user = '$usernameforlog'";
                        $this->con->exec($sql);
                        $istoflush = true;
                        $output->writeln("\n####### User: ".$resultRow["username"]." has been Renamed successfully #######\n");
                     } 
                } 
            }
           if($istoflush==true){ 
            $em->flush();
            $em->clear();
           }
                         
        } else {
             $output->writeln("\n####### No User Found #######\n");
        }
         $output->writeln("\n####### End Abandoned Purchase User Cron at " . date('Y M j H:i') . " #######\n");
    }
}
