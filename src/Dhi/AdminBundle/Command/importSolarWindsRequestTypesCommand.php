<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Dhi\UserBundle\Entity\SolarWindsRequestType;
use Dhi\UserBundle\Entity\UserActivityLog;

class ImportSolarWindsRequestTypesCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {
        $this->setName('dhi:import-solar-winds-request-types')
                ->setDescription('import data from solar winds.');
    }
    
    public function execute(InputInterface $input, OutputInterface $output) {
        
        $output->writeln("\n####### Start Import Solar winds data Cron at " . date('Y M j H:i') . " #######\n");
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $solarwindsdata = $this->callsolarwindService();
       
        if(!$solarwindsdata){
           $output->writeln("\n####### Cron Aborted: Something went wrong. Please check API configuration #######\n"); 
        }
       
        if($solarwindsdata){
            $isflush = false;
            $solarwindIds = array();
            foreach ($solarwindsdata as $resultRow){
                
             if($resultRow){
               $solarwindsid              = $resultRow->id;
               $solarwindsproblemtypename = $resultRow->problemTypeName;
               $solarwindIds[]            = $solarwindsid;
               
               $isdataexits = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->findOneBy(array('solarWindId'=>$solarwindsid));
               if(!$isdataexits){
                $objsolarwinds = new SolarWindsRequestType();
                $objsolarwinds->setSolarWindId($solarwindsid);
                $objsolarwinds->setRequestTypeName($solarwindsproblemtypename);
                $em->persist($objsolarwinds);
                
                $output->writeln("\n####### ".$solarwindsproblemtypename." has been imported successfully #######\n");
                $isflush = true;
               } else {
                    
                 if($isdataexits)  {
                     $oldname = $isdataexits->getRequestTypeName();
                   if(trim($oldname) != trim($solarwindsproblemtypename))  {
                     $objsolarwinds = $isdataexits;
                     $objsolarwinds->setRequestTypeName($solarwindsproblemtypename); 
                     $em->persist($objsolarwinds);
                     
                     $output->writeln("\n####### ".$solarwindsproblemtypename." has been updated successfully #######\n");
                     $isflush = true;
                   }
                 }
               }
             } else {
                $output->writeln("\n####### record not available #######\n"); 
             }
          } 
          
          if(count($solarwindIds)>0){
            /* Delete Mapping if data not found in Solar winds*/
            $notavailabledata = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->getdeletedSolarWindsdata($solarwindIds);
           
            if($notavailabledata){
                $deletedArr = array();
                foreach ($notavailabledata as $mapdata){
                   
                    $objsolarwindrequesttype = $em->getRepository('DhiUserBundle:SolarWindsRequestType')->find($mapdata['requesttypeid']); 
                    if($objsolarwindrequesttype){
                        $em->remove($objsolarwindrequesttype);
                    }

                    if(!empty($mapdata['userId'])){ 
                        $username = $mapdata['username'];
                    } else {
                        $adminuser =  $em->getRepository('DhiUserBundle:User')->getsuperadminuser();
                        $username = $adminuser[0]['username']; 
                    }
                   
                    $objActivityLog = new UserActivityLog();
                    $objActivityLog->SetAdmin("Cron");
                    $objActivityLog->SetUser($username);
                    $objActivityLog->setActivity('Delete Solar Winds request type');
                    $objActivityLog->setDescription("Cron  " . $username . " has deleted solar winds request type : '".$mapdata['requestTypeName']."'");
                    $objActivityLog->setIp("N/A");
                    $objActivityLog->setSessionId("N/A");
                    $objActivityLog->setVisitedUrl("N/A");
                    $em->persist($objActivityLog);
                    $em->flush();


                    $isflush = true;
                    if (!in_array($mapdata['requesttypeid'], $deletedArr)) {
                        $output->writeln("\n####### ".$mapdata['requestTypeName']." has been deleted #######\n");
                    }
                    array_push($deletedArr, $mapdata['requesttypeid']);
                }
            }
          }
            /*---------------------------End of code------------------------------*/
            if($isflush==true) { 
               $em->flush();
               $em->clear(); 
            }
          
       }
       
       $output->writeln("\n####### End Import Solar winds data Cron at " . date('Y M j H:i') . " #######\n");
    }
    
    public function callsolarwindService(){
        
        $Solar_winds_API_URL  = $this->getContainer()->getParameter('solarwinds_api_url');
    	$Solar_winds_key     = $this->getContainer()->getParameter('solarwinds_api_key');
        
     if($Solar_winds_key && $Solar_winds_API_URL){
         
        // Set the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $Solar_winds_API_URL.'RequestTypes?apiKey='.$Solar_winds_key);
        
        // Turn off the server and peer verification (TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        // Get response from the server.
        $httpResponse = curl_exec($ch);
        
        $info = curl_getinfo($ch);
        $result = json_decode($httpResponse);
    	
        if($info['http_code'] == '200'){
              return $result;
        }
     }
    
     return false;   
  }
            
    
}
