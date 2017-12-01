<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class LoginHistoryTrimCommand extends ContainerAwareCommand{
    
    private $output;
    protected function configure(){

        $this->setName('dhi:login-history-trim')->setDescription('Removed login history after 90 days');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {

        $output->writeln("\n####### Start Trim Login History Cron at ".date('M j H:i')." #######\n");
        
        $em = $this->getContainer()->get('doctrine')->getManager();

        $objUserLoginLog = $em->getRepository('DhiUserBundle:UserLoginLog')->getAfterNinetyLoginLog();
        
        if($objUserLoginLog){
            
            foreach ($objUserLoginLog as $userLoginLog){
                
            	$em->remove($userLoginLog);
            	$em->flush();                                                
            }
            
            $output->writeln("\n####### Login log has been deleted successfully. #######\n");
        }else{
            
            $output->writeln("\n####### Login log not found. #######\n");
        }
        
        $output->writeln("\n####### End Cron #######\n");                        
    }
}
