<?php

namespace Dhi\AdminBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use \DateTime;
use Doctrine\ORM\Query\ResultSetMapping;

class ServiceAPIFailedRequestCommand extends ContainerAwareCommand {

    private $output;

    protected function configure() {

        $this->setName('dhi:service-api-failed-request')->setDescription('Call aradial/selevision service API log request');
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        $output->writeln("\n####### Start Cron at " . date('M j H:i') . " #######\n");

        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $serviceApiErrorLogs = $em->getRepository('DhiServiceBundle:ServiceApiErrorLog')->findBy(array('status' => 0));
        
        if ($serviceApiErrorLogs) {

            foreach ($serviceApiErrorLogs as $apiLog) {
                
                if($apiLog->getUser()) {
                    
                    $isUpdated = false;
                    $user = $apiLog->getUser();
                    
                    $username    = $user->getUsername();
                    $password    = base64_decode($user->getEncryPwd());
                    $firstName   = $user->getFirstName();
                    $lastName    = $user->getLastName();
                    $email       = $user->getEmail();
                    $address     = $user->getAddress();
                    $city        = $user->getCity();
                    $state       = $user->getState();
                    $country     = ($user->getCountry())?$user->getCountry()->getName():"";
                    $zip         = $user->getZip();
                    
                    ############################### Selevision API Call ####################################
                    if ($apiLog->getApiType() == 'Selevision') {
                        
                        $wsSelevisionResponse = array();
                        $wsSelevisionResponse['status'] = 0;
                        
                        $selevisionUserExist = $this->getContainer()->get('selevisionService')->checkUserExistInSelevision($user);
                        
                        if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {
                            
                            $seleVisionCurrrentPwd = $selevisionUserExist['password']; // get plain password for selevisions
                        
                            if ($apiLog->getAction() == 'ProfileUpdate') {
                                
                                // call selevisions service to update account
                                $wsParam = array();
                                $wsParam['cuLogin']        = $username;
                                $wsParam['cuNewFirstName'] = $firstName;
                                $wsParam['cuNewLastName']  = $lastName;
                                $wsParam['cuNewEmail']     = $email;
                                
                                $wsSelevisionResponse = $this->getContainer()->get('selevisionService')->callWSAction('updateCustomer', $wsParam);
                            }
        
                            if ($apiLog->getAction() == 'ChangePassword') {
                            
                                // call selevisions service to update password
                                if($seleVisionCurrrentPwd != $password) {
                                    
                                    $wsParam = array();
                                    $wsParam['cuLogin']   = $username;
                                    $wsParam['cuPwd']     = ($user->getNewSelevisionUser())?$seleVisionCurrrentPwd:$password;
                                    $wsParam['cuNewPwd1'] = $password;
                                    $wsParam['cuNewPwd2'] = $password;
                                    
                                    $wsSelevisionResponse = $this->getContainer()->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);
                                } else {
                                    
                                    $wsSelevisionResponse['status'] = 1;
                                }                                
                            }
                            
                            if ($wsSelevisionResponse['status'] == 1) {
                                
                                $isUpdated = true;
                            }
                        } else {
                            
                            if($selevisionUserExist['serviceAvailable'] == 0) {
                            
                                $output->writeln("\n####### Log Id# ".$apiLog->getId()." ,Selevision service unavailable. #######\n");
                            
                            } else if($selevisionUserExist['status'] == 0) {
                               
                                $em->remove($apiLog);
                                $em->flush();
                            
                                $output->writeln("\n####### User:- ".$username." does not exists in selevision service. #######\n");
                            }                            
                        }                                    
                    }
                    ################################# END Selevision API ##################################

                    ################################## Aradial API Call ###################################
                    if ($apiLog->getApiType() == 'Aradial') {
                        
                        $wsAradialResponse = array();
                        $wsAradialResponse['status'] = 0;
                        
                        $aradialUserExist = $this->getContainer()->get('aradial')->checkUserExistsInAradial($username);
                    
                        if($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1) {
                            
                            if ($apiLog->getAction() == 'ProfileUpdate') {
                            
                                $wsParam = array();
                                $wsParam['Page']                       =  "UserEdit";
                                $wsParam['Modify']                     = 1;
                                $wsParam['UserID']                     = $username;
                                $wsParam['db_UserDetails.FirstName']   = $firstName;
                                $wsParam['db_UserDetails.LastName']    = $lastName;
                                $wsParam['db_UserDetails.Email']       = $email;
                                $wsParam['db_UserDetails.Address1']    = $address;
                                $wsParam['db_UserDetails.City']        = $city;
                                $wsParam['db_$GS$UserDetails.State']   = $state;
                                $wsParam['db_$GS$UserDetails.Country'] = $country;
                                $wsParam['db_UserDetails.Zip']         = $zip;
                                
                                $wsAradialResponse = $this->getContainer()->get('aradial')->callWSAction('updateUser',$wsParam);                                                               
                            }
                        
                            if ($apiLog->getAction() == 'ChangePassword') {
                            
                                //Update password in aradial
                                $wsParam = array();
                                $wsParam['Page']      =  "UserEdit";
                                $wsParam['Modify']    = 1;
                                $wsParam['UserID']    = $username;
                                $wsParam['Password']  = $password;
                                
                                $wsAradialResponse = $this->getContainer()->get('aradial')->callWSAction('updateUser',$wsParam);                                                               
                            }
                            
                            if ($wsAradialResponse['status'] == 1) {
                            
                                $isUpdated = true;
                            }
                        } else {
                            
                            if($aradialUserExist['serviceAvailable'] == 0) {
                                
                                $output->writeln("\n####### Log Id# ".$apiLog->getId()." ,Aradail service unavailable. #######\n");
                                
                            } else if($aradialUserExist['status'] == 0) {
                                
                                $em->remove($apiLog);
                                $em->flush();
                                
                                $output->writeln("\n####### User:- ".$username." does not exists in aradial service. #######\n");
                            }                            
                        }
                    }
                    ################################# END Aradial API ##################################
                    
                    if($isUpdated) {
                        
                        $apiLog->setStatus(1);
                        $em->persist($apiLog);
                        $em->flush();
                        
                        $output->writeln("\n####### Log Id# ".$apiLog->getId()." has been updated successfully! #######\n");
                    }
                }                                
            }            
        }              
          
        $output->writeln("\n####### End Cron #######\n");
    }

}


