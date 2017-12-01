<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserMacAddress;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dhi\UserBundle\Form\Type\UserMacAddressFormType;

/**
 * 
 */
class UserMacAddressController extends Controller {

    // add and edit user mac address
    public function macAddressAction(Request $request, $id) {

        $user = $this->get('security.context')->getToken()->getUser();
        
        // create customer
        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
        
        $em = $this->getDoctrine()->getManager();
        $macAddressType = '';
        $mac_address_seqno = '';
        $sequenceNumber = '';
        
        // Set activtiy log user mac address
        $activityLog = array();
        $activityLog['user'] = $user;
        
        if($id != 0) {
            
            $macAddressType = 'Edit';
            $objUserMacAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
            $userMacAddress = $objUserMacAddress->getMacAddress();
            
        } else {
            
            $macAddressType = 'Add';
            $objUserMacAddress = new UserMacAddress();
        }
        
        $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objUserMacAddress);

        $response = array('status' => 'failure');
        
        if ($request->isXmlHttpRequest()) {
            
            if ($request->getMethod() == "POST") {

                $userMacAddressForm->handleRequest($request);
                
                if ($userMacAddressForm->isValid()) {
                    
                        $formData =  $userMacAddressForm->getData();
                        
                        $macAddress = substr(str_replace(":", "", $formData->getMacAddress()), -6);
                        $serialNumber = "DHI-".$macAddress;
                        
                        if($id == 0 && $macAddressType == 'Add') {
                            
                            // Check user login in selevision
                            $wsResponseGetUserMacAddress = $this->get('selevisionService')->userLoginSelevision($user->getUsername());
                            
                            if(!empty($wsResponseGetUserMacAddress) && $wsResponseGetUserMacAddress['status'] == 1) {
                                
                                foreach($wsResponseGetUserMacAddress as $key => $value) {
                                    
                                    if(empty($value))
                                    {
                                        
                                        if($key == 'mac') {
                                            
                                          $sequenceNumber = 0;
                                            
                                        } else {
                                            
                                           $keySeqno =  explode('_', $key);
                                           $sequenceNumber = $keySeqno[2];
                                           
                                        }
                                        break;
                                        
                                    }
                                    
                                    
                                }
                                
                            }
                             
                            // add mac address in selevision
                            
                            $wsResponse = $this->get('selevisionService')->registerMacAddressSelevision($formData->getMacAddress(),$serialNumber);
                           
//							if($wsResponse['detail'] == 'mac already exists'){
//								
//								
//							}
							
                            // check mac-address has successfully registerted or not
                            $this->get('selevisionService')->checkSelevisionResponse($wsResponse);
                            
                            if($sequenceNumber != "") {
                                
                                $mac_address_seqno = $sequenceNumber;
                            } 
                            else {
                                
                                $mac_address_seqno = $this->get('selevisionService')->getMacAddressSequenceNumber($user);
                                
                            }
                            
                            $wsResponseSetMacAddress = $this->get('selevisionService')->setMacAddressSelevision($formData->getMacAddress(),$serialNumber, 'set', $user->getUsername(), $mac_address_seqno);
                            
                            // check mac-address has successfully mapped with user or not
                            $this->get('selevisionService')->checkSelevisionResponse($wsResponseSetMacAddress);
                            
                            $activityLog['activity'] = 'Add mac address';
                            $activityLog['description'] = 'User ' . $user->getUsername() . ' has added mac address '.$formData->getMacAddress();
                            
                            
                        } else {
                            
                               $mac_address_seqno = $objUserMacAddress->getSequenceNumber();
                               
                               if($formData->getMacAddress() != $userMacAddress) {
                                   
                                   // add mac address in selevision
                            
                                   $wsResponse = $this->get('selevisionService')->registerMacAddressSelevision($formData->getMacAddress(),$serialNumber);
                                   
                                   // check mac-address has successfully registerted or not
                                   $this->get('selevisionService')->checkSelevisionResponse($wsResponse);
                             
                                   $wsResponseSetMacAddress = $this->get('selevisionService')->setMacAddressSelevision($formData->getMacAddress(),$serialNumber, 'set', $user->getUsername(), $mac_address_seqno);
                            
                                   // check mac-address has successfully mapped with user or not
                                   $this->get('selevisionService')->checkSelevisionResponse($wsResponseSetMacAddress);
                                   
                                   $activityLog['activity'] = 'Edit mac address';
                                   $activityLog['description'] = 'User ' . $user->getUsername() . ' has updated mac address'.$id;
                         
                                    
                               } 
                                
                        }
                    
                    // set activity log    
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $objUserMacAddress->setUser($user);
                    $objUserMacAddress->setSequenceNumber($mac_address_seqno);
                    
                    $em->persist($objUserMacAddress);
                    $em->flush();
                    $response['status'] = 'success';
                    
                    
                } else {
                    
                    if($id == 0) {
					
					$token = md5(uniqid($user->getEmail(), true));


						$receiverName = '';
						$receiverEmail = '';
						$receiverid = '';
						$formData =  $userMacAddressForm->getData();

						$customerName =  $em->getRepository('DhiUserBundle:UserMacAddress')->getCustomerNameByMacAddress($formData->getMacAddress());

						foreach($customerName as $key => $val){

							$receiverName = $val->getUser()->getName();
							$receiverEmail = $val->getUser()->getEmail();
							$receiverId = $val->getId();
							$mac_address_seqno = $val->getSequenceNumber();
							$objectMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->find($val->getId());
						}

						$datetime = new DateTime();

						//$pastDate = $datetime->getTimestamp();
						$pastDate = $datetime->format('Y-m-d H:i:s');

						//$objectMacAddress->setUser($user);
						$objectMacAddress->setSequenceNumber($mac_address_seqno);
						$objectMacAddress->setToken($token);
						$objectMacAddress->setMacAlreadyExistDate($pastDate);
						$em->persist($objectMacAddress);
						$em->flush();
                                                
                                                $session = $this->container->get('session');
                                                $whitelabel = $session->get('brand');
                                                if($whitelabel){
                                                    $compnayname = $whitelabel['name'];
                                                    $compnaydomain = $whitelabel['domain'];
                                                } else {
                                                    $compnayname     = 'ExchangeVUE';
                                                    $compnaydomain   = 'exchangevue.com';
                                                }

						$body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:mac_address_verfication.html.twig', array('senderName' => $user->getName(),'token'=>$token,'receiverName'=>$receiverName,'senderId'=>$user->getId(),'companyname'=>$compnayname,'companydomain'=>$compnaydomain));	

						$message = \Swift_Message::newInstance()
										->setSubject('Authorization Of MacAddress')
										->setFrom($user->getEmail())
										->setTo($receiverEmail)
										->setBody($body->getContent())
										->setContentType('text/html');

						//$this->container->get('mailer')->send($message);

						if (!$this->container->get('mailer')->send($message))
						{
						  echo "Failures:";	  
						}				
	
                        
                     $macAddressform = $this->render('DhiUserBundle:UserMacAddress:new.html.twig', array('form' => $userMacAddressForm->createView()));
                     $response['error'] =  $macAddressform->getContent();
                     
                    } else {
                        
                     $userFormdata = $this->render('DhiUserBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objUserMacAddress));
                     $response['error'] =  $userFormdata->getContent();
                        
                    }
                    
                }
            }
        }
        
        echo json_encode($response);
        exit;
    }
    // list of user mac address
    public function listMacAddressAction(Request $request, $id) {

        $user = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        $response = array('status' => 'failure', 'totalMacAddress' => 0);
       
         if($user) {
             
            $objMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->findBy(array('user' => $user));
            
            $macAddressList = array();
            
            if($objMacAddress) {
                
                $response['totalMacAddress'] = count($objMacAddress);
                
                foreach($objMacAddress as $key => $macAddress) {
                    
                    $macAddressList[$key]['id'] = $macAddress->getId();
                    $macAddressList[$key]['macAddress'] = $macAddress->getMacAddress();
                    
                }
                
                $data = $this->render('DhiUserBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                
                $response['status'] = $data->getContent();
                echo json_encode($response);
                exit;
                
            }  
               
         } 
         
         echo json_encode($response);
         exit;
        
    }
    
    // delete mac address
    public function deleteMacAddressAction(Request $request, $id) {

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $response = array('status' => 'failure', 'totalMacAddress' => 0);
        
         if($user) {
             
            // remove record 
            if($id && $id != 0) {
                 
                  $macAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
                    
                    if($macAddress) {
                        
                            $wsResponseUnsetMacAddress = $this->get('selevisionService')->unsetMacAddressSelevision($macAddress->getMacAddress(), 'unset', $user->getUsername(), $macAddress->getSequenceNumber());
                            
                            if(!empty($wsResponseUnsetMacAddress) && $wsResponseUnsetMacAddress['status'] == 1) {
                                
                                // Set activtiy log delete user mac address
                                $activityLog = array();
                                $activityLog['user'] = $user;
                                $activityLog['activity'] = 'Delete mac address';
                                $activityLog['description'] = 'User ' . $user->getUsername() . ' has delete mac address '.$id;
                                $this->get('ActivityLog')->saveActivityLog($activityLog);

                                $em->remove($macAddress);
                                $em->flush();
                                
                            } else {
                                
                                $response['error'] = 'Something went wrong with delete mac. Please contact support if the issue persists';
                                echo json_encode($response);
                                exit;
                                
                            } 
                            
                    }
                
                 $objMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->findBy(array('user' => $user));
                 
                 $macAddressList = array();
                 
                 if($objMacAddress) {
                     
                     $response['totalMacAddress'] = count($objMacAddress);
                
                        foreach($objMacAddress as $key => $macAddress) {

                            $macAddressList[$key]['id'] = $macAddress->getId();
                            $macAddressList[$key]['macAddress'] = $macAddress->getMacAddress();

                        }
                
                    $data = $this->render('DhiUserBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
                    $response['status'] = $data->getContent();
                    echo json_encode($response);
                    exit;
                    
                  
                }
                
                else {
                    
                    $data = $this->render('DhiUserBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
                    $response['status'] = $data->getContent();
                    echo json_encode($response);
                    exit;
                    
                }
             
            }  
               
         } 
         
         echo json_encode($response);
         exit;
        
    }
    
    // edit mac address
    public function addMacAddressAction(Request $request) {

        $user = $this->get('security.context')->getToken()->getUser();
        
        $form = $this->createForm(new UserMacAddressFormType());
                     
        $macAddressform = $this->render('DhiUserBundle:UserMacAddress:new.html.twig', array('form' => $form->createView()));
        
        echo $macAddressform->getContent();
        exit;
        
    }
    
    // edit mac address
    public function editMacAddressAction(Request $request, $id) {

        $user = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        $response = array();
        
         if($user) {
                
                 $objMacAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
                    
                 if($objMacAddress) {
                     
                     $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objMacAddress);
                     
                     $userFormdata = $this->render('DhiUserBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objMacAddress));
                     
                     echo $userFormdata->getContent();
                     exit;
                     
                 }
                 else {
                     
                     echo false;
                     exit;
                     
                 }
         }
         
        echo false;
        exit;
        
    }
	
	
	/**
     * Receive the confirmation token from user email provider, login the user
     */
    public function confirmAction(Request $request)
    {
		$token = $request->get('token');
		$senderId = $request->get('senderId');
		$em = $this->getDoctrine()->getManager();
		
		$confirmMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->findBy(array('token' => $token));
		if($confirmMacAddress) {
			
			foreach($confirmMacAddress as $key => $val){
				$receiverName = $val->getUser()->getName();
				$receiverEmail = $val->getUser()->getEmail();
				$receiverId = $val->getId();
				$pastDayTime = $val->getMacAlreadyExistDate();
				}
		
		}else {
	        
           // session_destroy();
            //$this->get('session')->invalidate();
            return $this->redirect($this->generateUrl('dhi_user_mac_address_invalidate_token'));
		}
		if(!empty($senderId)) {
		$user = $em->getRepository('DhiUserBundle:User')->find($senderId);
		} else {
			$user = '';
		}
		if(!empty($user)){
			
		}else{
			return $this->redirect($this->generateUrl('dhi_user_mac_address_invalidate_token'));
		}
		$confirmObjMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->find($receiverId);
		
		//$pastDayTime = $request->get('pastDate');
        $today = new DateTime();
		//$pastDayTime = date('Y-m-d H:i:s',$pastDayTime);
		$currentDayTime = $today->format('Y-m-d H:i:s');
		$diff = strtotime($currentDayTime) - strtotime($pastDayTime);
		
		$diff_in_hrs = $diff/60/60;
		
		
		 //Add Activity Log
        $activityLog = array();
        $activityLog['user']         = $user->getName();
        $activityLog['activity']     = 'MacAddress Email Confirmation';
		
        
        if ($diff_in_hrs > 24) {
            /* START: add user audit log for email confirmation */
            $em = $this->getDoctrine()->getManager();
            
            $activityLog['description'] = 'User '.$user->getName().' has tried to confirm email after 24 hours';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            return $this->redirect($this->generateUrl('dhi_user_mac_address_invalidate_token'));
        } else {
			 

			$confirmObjMacAddress->setUser($user);
			
			$em->persist($confirmObjMacAddress);
			$em->flush();
			
            $em = $this->getDoctrine()->getManager();
           
            $activityLog['description'] = 'User '.$user->getName().' has confirmed email';
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for email confirmation */

            //return $response;
			return $this->redirect($this->generateUrl('dhi_user_mac_address_verification_success'));
        }
		
		
        
    }
	
	
	
	public function emailVerificationSuccessAction() {
        
        return $this->render('DhiUserBundle:UserMacAddress:verification_success.html.twig');
    }
    
    public function invalidTokenAction() {
		
        //$message = '';
        return $this->render('DhiUserBundle:UserMacAddress:confirm_token.html.twig');
    }
    
    
}
