<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\AdminBundle\Form\Type\SetTopBoxFormType;
use Dhi\AdminBundle\Form\Type\ReturnBoxFormType;
use Dhi\AdminBundle\Entity\SetTopBox;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\User;


class SetTopBoxController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('set_top_box_create') || $this->get('admin_permission')->checkPermission('set_top_box_list') || $this->get('admin_permission')->checkPermission('set_top_box_update') || $this->get('admin_permission')->checkPermission('set_top_box_delete') || $this->get('admin_permission')->checkPermission('set_top_box_return') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view set-top-box list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
     
        return $this->render('DhiAdminBundle:SetTopBox:index.html.twig');
    }
    
    //added for grid
   public function setTopBoxListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $setTopBoxColumns = array('Id','MacAddress','GivenAt','ReceivedAt','GivenBy','ReceivedBy','Status','Action');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($setTopBoxColumns);
        $admin = $this->get('security.context')->getToken()->getUser();
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'st.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'st.id';
            }
            
            if ($gridData['order_by'] == 'MacAddress') {
                
                $orderBy = 'st.macAddress';
            }
            if ($gridData['order_by'] == 'GivenAt') {
                
                $orderBy = 'st.givenAt';
            }
            if ($gridData['order_by'] == 'ReceivedAt') {
                
                $orderBy = 'st.receivedAt';
            }
          
            if ($gridData['order_by'] == 'GivenBy') {
                
                $orderBy = 'st.givenBy';
            }
			
			if ($gridData['order_by'] == 'ReceivedBy') {
                
                $orderBy = 'st.receivedBy';
            }
			if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'st.status';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiAdminBundle:SetTopBox')->getSetTopBoxGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
					                   
                    $flagDelete   = 1;
                    $row = array();
					$row[] = $resultRow['id'];
					$row[] = $resultRow['macAddress'];
					$row[] = $resultRow['givenAt'] ? $resultRow['givenAt']->format('M-d-Y') : '';
					$row[] = $resultRow['receivedAt'] ? $resultRow['receivedAt']->format('M-d-Y') : 'N/A';
					$row[] = $resultRow['givenBy'];
					$row[] = $resultRow['receivedBy'];
					$row[] = $resultRow['status'] == '1' ? 'active' : 'returned';
					$row[] = $resultRow['id'].'^'.$flagDelete;
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('set_top_box_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add set-top-box.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objSetTopBox = new SetTopBox();
        $form = $this->createForm(new SetTopBoxFormType(), $objSetTopBox);
        
		
        if ($request->getMethod() == "POST") {
			$settopBoxData = $request->get('dhi_set_top_box');
            if (!empty($settopBoxData['user'])) {
                $form->add('user', 'text',array('mapped' => false));

                $form->handleRequest($request);
                if ($form->isValid()) {
    				$formData = $form->getData();

    			    $user = $em->getRepository("DhiUserBundle:User")->find($settopBoxData['user']);	
                    if($user){
                        $objSetTopBox->setMacAddress($formData->getMacAddress());
        				$objSetTopBox->setGivenBy($admin->getUsername());
        				$objSetTopBox->setReceivedBy($user->getUsername());
                        $objSetTopBox->setGivenAt($formData->getGivenAt());
                        $objSetTopBox->setUser($user);
                        /* $objEmailCampaign->setStartDate($formData->getStartDate());
                        $objEmailCampaign->setEndDate($formData->getEndDate()); */
                        $objSetTopBox->setStatus(1);

                        $em->persist($objSetTopBox);
                        $em->flush();
                        
                        // set audit log add email campagin
                        $activityLog = array();
                        $activityLog['admin'] = $admin;
                        $activityLog['activity'] = 'Add Set Top Box';
                        $activityLog['description'] = "Admin ".$admin->getUsername()." has added set top box ";
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
                        
                        $this->get('session')->getFlashBag()->add('success', 'Set Top Box added successfully.');
                    }else{
                        $this->get('session')->getFlashBag()->add('danger', 'Customer does not exists.');
                    }
                    return $this->redirect($this->generateUrl('dhi_admin_set_top_box_list'));
                }
            }
        }
        return $this->render('DhiAdminBundle:SetTopBox:new.html.twig', array(
                    'form' => $form->createView(),
        ));
        
    }
	
	 public function editAction(Request $request, $id) {
		 
		  //Check permission
        if(! $this->get('admin_permission')->checkPermission('set_top_box_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update set-top-box.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
       
        $em = $this->getDoctrine()->getManager();
            
		$objSetTopBox = $em->getRepository('DhiAdminBundle:SetTopBox')->find($id);

		if (!$objSetTopBox) {

			$this->get('session')->getFlashBag()->add('failure', "Unable to find email campaign.");
			return $this->redirect($this->generateUrl('dhi_admin_set_top_box_list'));
		}

		$form = $this->createForm(new SetTopBoxFormType(), $objSetTopBox);

		if ($request->getMethod() == "POST") {

			$form->handleRequest($request);

			if ($form->isValid()) {
				
				

				$formData = $form->getData();
				
				$objSetTopBox->setMacAddress($formData->getMacAddress());
				$objSetTopBox->setGivenBy($admin->getUsername());
				$objSetTopBox->setReceivedBy($formData->getUser()->getUsername());
				$objSetTopBox->setGivenAt($formData->getGivenAt());
				$objSetTopBox->setUser($formData->getUser());
				/* $objEmailCampaign->setStartDate($formData->getStartDate());
				$objEmailCampaign->setEndDate($formData->getEndDate()); */
				//$objSetTopBox->setStatus(1);

				$em->persist($objSetTopBox);
				$em->flush();
				// add activity log
				$activityLog['activity'] = 'Edit Set-Top-Box';
				$activityLog['description'] = "Admin '".$admin->getUsername()."' has updated set-top-box";
				$this->get('ActivityLog')->saveActivityLog($activityLog);

				$this->get('session')->getFlashBag()->add('success', 'Set-top-box updated successfully.');
				return $this->redirect($this->generateUrl('dhi_admin_set_top_box_list'));
			}
		}
		
		return $this->render('DhiAdminBundle:SetTopBox:edit.html.twig', array(
						'form' => $form->createView(),
						'settopbox' => $objSetTopBox
			));
    }

	
	public function returnAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('set_top_box_return')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to return set-top-box.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
		
		$objSetTopBox = $em->getRepository('DhiAdminBundle:SetTopBox')->find($id);
		//$objCustomerInfo = $em->getRepository('DhiAdminBundle:SetTopBox')->getCustomerInfo($id);
		$name = $objSetTopBox->getUser()->getFirstName().''.$objSetTopBox->getUser()->getLastName();
		$email = $objSetTopBox->getUser()->getEmail();
            
            if (!$objSetTopBox) {
                
                $this->get('session')->getFlashBag()->add('failure', "Unable to find set top box.");
                return $this->redirect($this->generateUrl('dhi_admin_set_top_box_list'));
            }
            
        //$objSetTopBox = new SetTopBox();
        $form = $this->createForm(new ReturnBoxFormType(), $objSetTopBox);
       
        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                $formData = $form->getData();
				
				$objSetTopBox->setMacAddress($formData->getMacAddress());
				$objSetTopBox->setGivenBy($admin->getUsername());
				$objSetTopBox->setReceivedBy($formData->getUser()->getUsername());
                $objSetTopBox->setGivenAt($formData->getGivenAt());
                $objSetTopBox->setUser($formData->getUser());
                $objSetTopBox->setReceivedAt($formData->getReceivedAt());
				$objSetTopBox->setStatus(0);

                $em->persist($objSetTopBox);
                $em->flush();
                
                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Return Set Top Box';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has Return set top box ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Set Top Box Returned successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_set_top_box_list'));
            }
        }
        return $this->render('DhiAdminBundle:SetTopBox:return.html.twig', array(
                    'form' => $form->createView(),
					'return' => $objSetTopBox,
					'name'=>$name,
					'email'=>$email
        ));
        
    }
	
	
    public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('set_top_box_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete set-top-box.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objSetTopBox = $em->getRepository('DhiAdminBundle:SetTopBox')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete SetTopBox';
        if ($objSetTopBox) {
            
            // set audit log delete email campagin
           
            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted set-top-box";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objSetTopBox);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'set-top-box deleted successfully!');
        
        } else {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete set-top-box";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete set-top-box!');
        }        
         $response = new Response(json_encode($result));
        
		$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
	
	  public function stbMacAddressAction(Request $request) {
		 
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        if ($request->isXmlHttpRequest() && $request->getMethod() == "POST") {
            
            $settopBoxData = $request->get('dhi_set_top_box');

            $id = $request->get('id');
            if($id) {

                $objSetTopBox = $em->getRepository('DhiAdminBundle:SetTopBox')->find($id);
                $form = $this->createForm(new SetTopBoxFormType(), $objSetTopBox);                            
            }else {

                $objSetTopBox = new SetTopBox();
                $objSetTopBox->GetUser();

                $form = $this->createForm(new SetTopBoxFormType(), $objSetTopBox);
            }

            $user = $em->getRepository("DhiUserBundle:User")->find($settopBoxData['user']);
            if($user){

                $objSetTopBox->setMacAddress($settopBoxData['macAddress']);
                $objSetTopBox->setGivenBy($admin->getUsername());
                $objSetTopBox->setReceivedBy($user->getUsername());
                $objSetTopBox->setGivenAt($settopBoxData['givenAt']);
                $objSetTopBox->setUser($user);

    			$jsonResponse = array();
                $jsonResponse['status'] = 'success';
    			$jsonResponse['error']  = array();

    			$resultMacAddress = $em->getRepository('DhiAdminBundle:SetTopBox')->checkMacAddressExist($id);

    			foreach($resultMacAddress as $key => $macAddress) {
    				if($macAddress['macAddress'] == $objSetTopBox->getMacAddress()){
    					$jsonResponse['error'] = 'Mac Address Already Exist';
    				} 
    			}
            }else{
                $jsonResponse['error'] = "Customer does not exists.";
            }
            if(count($jsonResponse['error']) > 0) {

                $jsonResponse['status'] = 'error';
            }

            $response = new Response(json_encode($jsonResponse));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }else{
    		throw $this->createNotFoundException('Invalid Page Request');
    	}
    }
	
	public function searchCustomerAction(Request $request) {
		
		$em = $this->getDoctrine()->getManager();
		
		$searchedData = $request->get('q');
		
		
		$objSetTopBox1 = $em->getRepository('DhiUserBundle:User')->getByCustomerName($searchedData);
		
		$jsonResponse = array();
		//echo "<ul style='width: 552px; display: block; height: 240px; overflow: auto;'>";
		foreach($objSetTopBox1 as $key => $value) {
			
		
			
			$jsonResponse[$key]['id'] = $value['id'] ;
			$jsonResponse[$key]['text'] = $value['username'].' ('.$value['email'].')';

		}
		//echo "</ul>";
		
		
		
		echo json_encode($jsonResponse);
		exit;
		
	}

}
