<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\IspPin;
use Dhi\AdminBundle\Form\Type\IspPinFormType;
use Dhi\AdminBundle\Form\Type\IspPinMultipleFormType;
use Dhi\AdminBundle\Form\Type\ChangeIspPinPasswordFormType;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;


class IspPinController extends Controller {

    /**
     * List Services in Admin panel
     */
    public function indexAction(Request $request) {
		
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('isp_pin_list') )) {
        	
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view ISP pin list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
		
        return $this->render('DhiAdminBundle:IspPin:index.html.twig');
    }
    
    //Added For Grid List
    public function ispPinJsonAction($orderBy = "i.id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    	
        $ispPinColumns = array('serviceLocation','package','ispType','name','email','username','password','validity','id');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($ispPinColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'i.id';
            $sortOrder = 'DESC';
        } else {
        	
        	if ($gridData['order_by'] == 'id') {
        	
        		$orderBy = 'i.id';
        	}
        	
            if ($gridData['order_by'] == 'serviceLocation') {
                
                $orderBy = 'sl.name';
            }

            if ($gridData['order_by'] == 'package') {
                
                $orderBy = 'p.packageName';
            }
			
            if ($gridData['order_by'] == 'ispType') {
                
                $orderBy = 'i.ispType';
            }
            
			if ($gridData['order_by'] == 'username') {
                
                $orderBy = 'i.username';
            }

            if ($gridData['order_by'] == 'password') {
                
                $orderBy = 'i.password';
            }
            
            if ($gridData['order_by'] == 'validity') {
                
                $orderBy = 'i.validity';
            }                      
        }

        // Paging
        $per_page	= $gridData['per_page'];
        $offset 	= $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $adminServiceLocationPermission = '';
        if ($admin->getGroup() != 'Super Admin') {
            $adminServiceLocationPermission = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $adminServiceLocationPermission = empty($adminServiceLocationPermission) ? '0' : $adminServiceLocationPermission;
        }
        
        $data  = $em->getRepository('DhiAdminBundle:IspPin')->getIspPinGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $adminServiceLocationPermission);
      
        $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );
        
        if (isset($data) && !empty($data)) {
            
            if (isset($data['result']) && !empty($data['result'])) {
                
                $output = array(
                    "sEcho" => intval($_GET['sEcho']),
                    "iTotalRecords" => $data['totalRecord'],
                    "iTotalDisplayRecords" => $data['totalRecord'],
                    "aaData" => array()
                );
                
                
                foreach ($data['result'] AS $resultRow) {
                                      
                    $row = array();
					$row[] = ($resultRow->getServiceLocation())?$resultRow->getServiceLocation()->getName():'N/A';
                    $row[] = ($resultRow->getPackage())?$resultRow->getPackage()->getPackageName():'N/A';
                    $row[] = $resultRow->getIspType();
                    $row[] = $resultRow->getName() ? $resultRow->getName() : '-' ;
                    $row[] = $resultRow->getEmail() ? $resultRow->getEmail() : '-' ;
					$row[] = $resultRow->getUsername();
					$row[] = $resultRow->getPassword();
                    $row[] = $resultRow->getValidity();
                    $row[] = $resultRow->getId();
                    
                    $output['aaData'][] = $row;
                }                
            }
        }

        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }


    public function createSingleAction(Request $request) {
        
        //Check permission
        if(!$this->get('admin_permission')->checkPermission('isp_pin_create_single')) {
        	
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add ISP pin.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objIspPin = new IspPin();
                
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new IspPinFormType(), $objIspPin);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            
            if ($form->isValid()) {
                    
				$formData = $form->getData();
				
				// check user form aradial
				$aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($formData->getUserName());
                  
				if($aradialUserExist && !empty($aradialUserExist) && $aradialUserExist['status'] == 0) {
					
					 $userAradial = $this->get('aradial')->createUserIsp($formData->getUserName(), $formData->getPassword());
					 
					 if($userAradial) {
						 
						$em->persist($objIspPin);
						$em->flush();
						$this->get('session')->getFlashBag()->add('success', "ISP pin added successfully!");
						return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
				
					 } else {
						 
						 $this->get('session')->getFlashBag()->add('danger', "Something went to wrong to persist!");
						 return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
						
					 } 
					
					
				} else {
						
					$this->get('session')->getFlashBag()->add('danger', "User already exists!");
					return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
					
				}
				
            }
        }
        
        return $this->render('DhiAdminBundle:IspPin:createSingle.html.twig', array('form' => $form->createView()));        
    }
    
    public function createMultipleAction(Request $request) {
    
    	//Check permission
    	if(!$this->get('admin_permission')->checkPermission('isp_pin_create_multiple')) {
    		 
    		$this->get('session')->getFlashBag()->add('failure', "You are not allowed to add ISP pin.");
    		return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
    	}
    
    	$admin = $this->get('security.context')->getToken()->getUser();
    	$em = $this->getDoctrine()->getManager();
    	$isSavedSuccessfully = false;
    
    	$objIspPin = new IspPin();
    
    	$em = $this->getDoctrine()->getManager();
    	
    	$form = $this->createForm(new IspPinMultipleFormType(), $objIspPin);
    	
    
    	if ($request->getMethod() == "POST") {
    		
    		$postData = $request->get($form->getName());
    		$form->handleRequest($request);
    		
    		if ($form->isValid()) {
    			
    			$data = $form->getData();
    			
    			if ($postData['noOfPin'] > 0) {
    				
    				for ($i=0; $i<$postData['noOfPin']; $i++) {
    					
    					$randomString = $this->get('paymentProcess')->generateUniqueString(5);
    					
    					$userName = '';
    					if ($data->getPackage()) {
    						
    						$userName = strtolower(str_replace(' ', '-', $data->getPackage()->getPackageName()).'-'.$this->get('paymentProcess')->generateUniqueString(5));
    					}
						
						$password = $this->get('paymentProcess')->generateUniqueString(8);
						// create user in aradial
						$userAradial = $this->get('aradial')->createUserIsp($userName, $password);
						
						if($userAradial) {
							
							$objIspPin = new IspPin();

							$objIspPin->setServiceLocation($data->getServiceLocation());
							$objIspPin->setPackage($data->getPackage());
							$objIspPin->setIspType($data->getIspType());
							$objIspPin->setName($data->getName());
							$objIspPin->setEmail($data->getEmail());
							$objIspPin->setUsername($userName);
							$objIspPin->setPassword($password);
							$objIspPin->setValidity($data->getValidity());

							$em->persist($objIspPin);
							$em->flush();

							$isSavedSuccessfully = true;
							
						} else {
							
							$isSavedSuccessfully = false;
						}
						
    				}    	    				    				
    			}    			
    			
    			if ($isSavedSuccessfully) {
    			
    				$this->get('session')->getFlashBag()->add('success', "ISP pin added successfully!");
    			} else {
    				
    				$this->get('session')->getFlashBag()->add('error', "ISP pin adding failed!");
    			}
    
    			
    			return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
    		} else {
				
				echo $form->getErrorsAsString();
				exit;
			}
    	}
    
    	return $this->render('DhiAdminBundle:IspPin:createMultiple.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(!$this->get('admin_permission')->checkPermission('isp_pin_edit')) {
        	
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update ISP pin.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        $objIspPin = $em->getRepository('DhiAdminBundle:IspPin')->find($id);
        
        $form = $this->createForm(new IspPinFormType(), $objIspPin);
        $changePasswordForm = $this->createForm(new ChangeIspPinPasswordFormType(), $objIspPin);

        if ($request->getMethod() == "POST") {
        	
        	if ($request->request->has($form->getName())) {
        		
        		$form->handleRequest($request);        		
        		$objIspPin = $form->getData();
        		 
        		if ($form->isValid()) {
					
					$note = $request->request->get('isp-edit-pin-note');
					
					if($note) {
						
						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $objIspPin->getUsername();
						$activityLog['activity'] = 'Customer update information';
						$activityLog['description'] = $note;
						$this->get('ActivityLog')->saveActivityLog($activityLog);
						
					}
					
        			$em->persist($objIspPin);
        			$em->flush();
					
					
					
        			$this->get('session')->getFlashBag()->add('success', "ISP pin updated successfully!");
        			return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
        		}
        	}

        	if ($request->request->has($changePasswordForm->getName())) {
        		
        		$changePasswordForm->handleRequest($request);        		
        		$objIspPin = $changePasswordForm->getData();
        		
        		if ($changePasswordForm->isValid()) {
        		     
					$note = $request->request->get('change-password-pin-note');
					
					if($note) {
						
						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Customer update information';
						$activityLog['description'] = $note;
						$this->get('ActivityLog')->saveActivityLog($activityLog);
						
					}
					
					
        			$em->persist($objIspPin);
        			$em->flush();
        			 
        			$this->get('session')->getFlashBag()->add('success', "Password has been updated successfully!");
        			return $this->redirect($this->generateUrl('dhi_admin_isp_pin_list'));
        		}
        	}
        	
        }

        return $this->render('DhiAdminBundle:IspPin:edit.html.twig', array('form' => $form->createView(), 'changePasswordForm' => $changePasswordForm->createView(), 'id' => $id));        
    }

    public function deleteAction(Request $request) {
    	
        $id = $request->get('id');
        
        //Check permission
        if(!$this->get('admin_permission')->checkPermission('isp_pin_delete')) {
        	
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete ISP pin.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objIsp = $em->getRepository('DhiAdminBundle:IspPin')->find($id);

        if ($objIsp) {            
        	
			$aradialUserDelete = $this->get('aradial')->deleteUserFromAradial($objIsp->getUserName());
            
			if($aradialUserDelete) {
				
				$em->remove($objIsp);
				$em->flush();
				$result = array('type' => 'success', 'message' => 'ISP pin deleted successfully!');
				
			} else {
				
				$result = array('type' => 'danger', 'message' => 'You are not allowed to delete ISP pin!');
				
			}
			
        } else {
              
			$result = array('type' => 'danger', 'message' => 'You are not allowed to delete ISP pin!');
        }
        
        $response = new Response(json_encode($result));        
		$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }        
}
