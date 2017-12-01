<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserMacAddress;
use Symfony\Component\HttpFoundation\JsonResponse;
use Dhi\UserBundle\Form\Type\UserMacAddressFormType;
use Dhi\UserBundle\Form\Type\TransferMacAddressFormType;
use FOS\UserBundle\Mailer\MailerInterface;
/**
 * 
 */
class UserMacAddressController extends Controller {

    // add and edit user mac address
    public function macAddressAction(Request $request, $id, $type, $userId) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $macAddressType = '';
        $mac_address_seqno = '';
        $sequenceNumber = '';
        
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        
        // create customer
        $isSelevisionUser = $this->get('selevisionService')->createNewUser($user);
		
        
        if($type != 'add' && $id != 0) {
            
            $objUserMacAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
            $userMacAddress = $objUserMacAddress->getMacAddress();
            
        } else {
            
            $objUserMacAddress = new UserMacAddress();
        }
		
        
        $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objUserMacAddress);

        $response = array('status' => 'failure');
        
        $objMacAddress =  $em->getRepository('DhiUserBundle:UserMacAddress')->findBy(array('user' => $user));
        
        if ($request->isXmlHttpRequest()) {
           
            if($type == 'add') {
        
                if($objMacAddress && count($objMacAddress) > $request->getSession()->get('mac_address') || count($objMacAddress) == $request->getSession()->get('mac_address')) {
                    
                    $response['failure'] = 'You can add maximum '.$request->getSession()->get('mac_address').' mac addresses.';
                    echo json_encode($response);
                    exit;

                }
            }
            
            if ($request->getMethod() == "POST") {

                $userMacAddressForm->handleRequest($request);

				
                if ($userMacAddressForm->isValid()) {
					
						
                        // Set audit log for redund amount
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['user'] = $user;
                        
                        $formData =  $userMacAddressForm->getData();
                        
                        $macAddress = substr(str_replace(":", "", $formData->getMacAddress()), -6);
                        $serialNumber = "DHI-".$macAddress;
                        
                        if($id == 0 && $type == 'add') {
                           
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
                           
                            // check mac-address has successfully registerted or not
                            //$this->get('selevisionService')->checkSelevisionResponse($wsResponse);
                            
                            if($sequenceNumber != "") {
                                
                                $mac_address_seqno = $sequenceNumber;
                            } 
                            else {
                                
                                $mac_address_seqno = $this->get('selevisionService')->getMacAddressSequenceNumber($user);
                                
                            }
							
                            
                            $wsResponseSetMacAddress = $this->get('selevisionService')->setMacAddressSelevision($formData->getMacAddress(),$serialNumber, 'set', $user->getUsername(), $mac_address_seqno);
                            
							
                            // check mac-address has successfully mapped with user or not
                            $this->get('selevisionService')->checkSelevisionResponse($wsResponseSetMacAddress);
                            
                            $activityLog['activity'] = 'Add user mac address';
                            $activityLog['description'] = "Admin " . $admin->getUsername() . " has add mac address " . $formData->getMacAddress();
                            
                        }  else {
                            
                               $mac_address_seqno = $objUserMacAddress->getSequenceNumber();
                               
                               if($formData->getMacAddress() != $userMacAddress) {
                                   
                                   // add mac address in selevision
                            
                                   $wsResponse = $this->get('selevisionService')->registerMacAddressSelevision($formData->getMacAddress(),$serialNumber);
                                  
                                   // check mac-address has successfully registerted or not
                                   $this->get('selevisionService')->checkSelevisionResponse($wsResponse);
                             
                                   $wsResponseSetMacAddress = $this->get('selevisionService')->setMacAddressSelevision($formData->getMacAddress(),$serialNumber, 'set', $user->getUsername(), $mac_address_seqno);
                            	   
                                   // check mac-address has successfully mapped with user or not
                                   $this->get('selevisionService')->checkSelevisionResponse($wsResponseSetMacAddress);
                                   
                                   $activityLog['activity'] = 'Edit user mac address';
                                   $activityLog['description'] = "Admin " . $admin->getUsername() . " has update mac address " . $formData->getMacAddress();
                         
                                    
                               } 
                                
                        }
						
						
                        
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $objUserMacAddress->setUser($user);
                    $objUserMacAddress->setSequenceNumber($mac_address_seqno);
                    $em->persist($objUserMacAddress);
                    $em->flush();
                    
                    $response['status'] = 'success';
                    
                    
                } else {
					
                    
                    if($type == 'add') {

						 $macAddressform = $this->render('DhiAdminBundle:UserMacAddress:new.html.twig', array('form' => $userMacAddressForm->createView()));
						 $response['error'] =  $macAddressform->getContent();
                     
                    } else {

						
					   $userFormdata = $this->render('DhiAdminBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objUserMacAddress));
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

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        
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
                
                $data = $this->render('DhiAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                
                $response['status'] = $data->getContent();
                echo json_encode($response);
                exit;
                
            }  
               
         } 
         
         echo json_encode($response);
         exit;
        
    }
    
    // delete mac address
    public function deleteMacAddressAction(Request $request, $id, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        
        $response = array('status' => 'failure', 'totalMacAddress' => 0);
        
         if($user) {
             
            // remove record 
            if($id && $id != 0) {
                 
                  $macAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
                    
                    if($macAddress) {
						
                        
                            $wsResponseUnsetMacAddress = $this->get('selevisionService')->unsetMacAddressSelevision($macAddress->getMacAddress(), 'unset', $user->getUsername(), $macAddress->getSequenceNumber());
                          
							$this->get('selevisionService')->checkSelevisionResponse($wsResponseUnsetMacAddress);	
                            if(!empty($wsResponseUnsetMacAddress) && $wsResponseUnsetMacAddress['status'] == 1) {
                                
                                // Set activtiy log delete user mac address
                                $activityLog = array();
                                $activityLog['admin'] = $admin;
                                $activityLog['user'] = $user;
                                $activityLog['activity'] = 'Delete user mac address';
                                $activityLog['description'] = "Admin " . $admin->getUsername() . " has delete mac address ".$id;
                                $this->get('ActivityLog')->saveActivityLog($activityLog);

                                $em->remove($macAddress);
                                $em->flush();
                                
                            }else {
                                
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
                
                    $data = $this->render('DhiAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
                    $response['status'] = $data->getContent();
                    echo json_encode($response);
                    exit;
                    
                  
                }
                
                else {
                    
                    $data = $this->render('DhiAdminBundle:UserMacAddress:list.html.twig', array('userMacAddress' => $macAddressList));
                    
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

        $admin = $this->get('security.context')->getToken()->getUser();
        $userId = $request->get('userId');
        
        $form = $this->createForm(new UserMacAddressFormType());
                     
        $macAddressform = $this->render('DhiAdminBundle:UserMacAddress:new.html.twig', array('form' => $form->createView()));
        
        echo $macAddressform->getContent();
        exit;
        
    }
    
    // edit mac address
    public function editMacAddressAction(Request $request, $id, $userId) {

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();
        
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
        
        $response = array();
        
         if($user) {
                
                 $objMacAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
                    
                 if($objMacAddress) {
                     
                     $userMacAddressForm = $this->createForm(new UserMacAddressFormType(), $objMacAddress);
                     
                     $userFormdata = $this->render('DhiAdminBundle:UserMacAddress:edit.html.twig', array('form' => $userMacAddressForm->createView(), 'macAddress' => $objMacAddress));
                     
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
	
	
	// transfer mac address
    public function transferDeviceAction(Request $request, $id, $userId) {
		
		$admin = $this->get('security.context')->getToken()->getUser();
		
		$em = $this->getDoctrine()->getManager();
		
        $user = $em->getRepository('DhiUserBundle:User')->find($userId);
		
		$objMacAddress = $em->getRepository('DhiUserBundle:UserMacAddress')->find($id);
			
		$form = $this->createForm(new TransferMacAddressFormType(), $objMacAddress);
		
		if($user) {
		
			

			if ($request->getMethod() == "POST") {
				
				$form->handleRequest($request);

				if ($form->isValid()) {
					
					$formData = $form->getData();
					
					if($formData->getUser() != $user->getUsername()) {
						
						    
						
						$wsResponseUnsetMacAddress = $this->get('selevisionService')->unsetMacAddressSelevision($objMacAddress->getMacAddress(), 'unset', $user->getUsername(), $objMacAddress->getSequenceNumber());

						$this->get('selevisionService')->checkSelevisionResponse($wsResponseUnsetMacAddress);	
					//	print_r($wsResponseUnsetMacAddress);
						
						$objMacAddressByUser =  $em->getRepository('DhiUserBundle:UserMacAddress')->find($formData->getId());
						//$formData =  $userMacAddressForm->getData();

						$macAddress = substr(str_replace(":", "", $objMacAddressByUser->getMacAddress()), -6);
						$serialNumber = "DHI-".$macAddress;
						// check mac-address has successfully registerted or not
						//$this->get('selevisionService')->checkSelevisionResponse($wsResponseGetUserMacAddress);

						$checkCustomer = $em->getRepository('DhiUserBundle:UserMacAddress')->checkCustomerMacAddress($formData->getUser()->getId());

						if($checkCustomer < 5) {
						
						//echo $formData->getMacAddress();
						//echo $serialNumber;
						//echo $formData->getUser();
						//echo $formData->getSequenceNumber();exit;
							
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
							
						$mac_address_seqno = $this->get('selevisionService')->getMacAddressSequenceNumber($user);
						

						$wsResponseSetMacAddress = $this->get('selevisionService')->setMacAddressSelevision($formData->getMacAddress(),$serialNumber, 'set', $formData->getUser(), $mac_address_seqno);

						//$this->get('selevisionService')->checkSelevisionResponse($wsResponseSetMacAddress);

						$activityLog['activity'] = 'Transfer MacAddress';
						$activityLog['description'] = "Admin '".$admin->getUsername()."' has Transfered MacAddress";
						$this->get('ActivityLog')->saveActivityLog($activityLog);
					
						$this->get('session')->getFlashBag()->add('success', 'Mac-Address Transferred successfully.');

						return $this->redirect($this->generateUrl('dhi_admin_view_customer', array('id' => $userId)));
					} else {

						$this->get('session')->getFlashBag()->add('failure', 'This customer has already assigned maximum-mac address.');

						return $this->redirect($this->generateUrl('dhi_admin_mac_address_transfer', array('id' => $id,'userId' =>$userId)));
					}

				} else{
					
					$this->get('session')->getFlashBag()->add('failure', 'Please select another customer to transfer mac-address');

					return $this->redirect($this->generateUrl('dhi_admin_mac_address_transfer', array('id' => $id,'userId' =>$userId)));
					
				}
			}
		
		}
		}

		return $this->render('DhiAdminBundle:UserMacAddress:transfer.html.twig', array('form' => $form->createView(), 'macAddress' => $objMacAddress,'userId'=>$userId));
    }
	
	
	
	

    
    
}
