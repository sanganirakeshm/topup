<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\EmailCampaign;
use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\EmailCampaignFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class EmailCampaignController extends Controller {

    public function indexAction(Request $request) {
        
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('email_campaign_list') || $this->get('admin_permission')->checkPermission('email_campaign_create') || $this->get('admin_permission')->checkPermission('email_campaign_update') || $this->get('admin_permission')->checkPermission('email_campaign_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view email campaign list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
     
        return $this->render('DhiAdminBundle:EmailCampaign:index.html.twig');
    }
    
    //added for grid
   public function emailCampaignListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $emailCompaignColumns = array('Subject','EmailType','Service', 'Status', 'SentAt', 'Id');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($emailCompaignColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'e.id';
            $sortOrder = 'DESC';
        } else {
            
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'e.id';
            }else if ($gridData['order_by'] == 'Subject') {
                $orderBy = 'e.subject';
            }else if ($gridData['order_by'] == 'SentAt') {
                $orderBy = 'e.sentAt';
            }else if ($gridData['order_by'] == 'EmailType') {
                $orderBy = 'e.emailType';
            }else if ($gridData['order_by'] == 'Service') {
                $orderBy = 'e.services';
            }else if ($gridData['order_by'] == 'Status') {
                $orderBy = 'e.emailStatus';
            }else{
                $orderBy = 'e.id';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiUserBundle:EmailCampaign')->getEmailCampaignGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
        
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
                   
                    $count = 1;
                    $servicesCount = count($resultRow->getServices());

                    $serviceName = '';
                    if($resultRow->getServices()){
                        foreach ($resultRow->getServices() as $service) {
                            
                            if ($count == $servicesCount) {
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                            } else {
                                
                                $serviceName .= '<span class="btn btn-success btn-sm service">'.$service->getName().'</span>';
                            }
                            $count++;
                        }
                    }
                  
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getSubject();
                    $row[] = $resultRow->getEmailType() == 'M' ? 'Marketing' : 'Support';
                    $row[] = $serviceName;
                    $row[] = '<span class="btn btn-success btn-sm service">'.$resultRow->getEmailStatus().'</span>';
                    $row[] = ($resultRow->getSentAt() ? $resultRow->getSentAt()->format('m/d/Y H:i:s') : 'N/A' );
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getEmailStatus();
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
        if(! $this->get('admin_permission')->checkPermission('email_campaign_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = new EmailCampaign();
        $form = $this->createForm(new EmailCampaignFormType(), $objEmailCampaign);
       
        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            if ($form->isValid()) {
                
                $formData = $form->getData();
                $objEmailCampaign->setSubject($formData->getSubject());
                $objEmailCampaign->setMessage($formData->getMessage());
                /* $objEmailCampaign->setStartDate($formData->getStartDate());
                $objEmailCampaign->setEndDate($formData->getEndDate()); */
                $objEmailCampaign->setEmailType($formData->getEmailType());

                $em->persist($objEmailCampaign);
                $em->flush();
                
                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Email Campaign';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added email campaign ".$formData->getSubject();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Email campaign added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
            }
        }
        return $this->render('DhiAdminBundle:EmailCampaign:new.html.twig', array(
                    'form' => $form->createView(),
        ));
        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = $em->getRepository('DhiUserBundle:EmailCampaign')->find($id);
        
        if (!$objEmailCampaign) {
            
            $this->get('session')->getFlashBag()->add('failure', "Unable to find email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        }
        
        $form = $this->createForm(new EmailCampaignFormType(), $objEmailCampaign);

        if ($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if ($form->isValid()) {
                
                $formData = $form->getData();

                $objEmailCampaign->setSubject($formData->getSubject());
                $objEmailCampaign->setMessage($formData->getMessage());
                /* $objEmailCampaign->setStartDate($formData->getStartDate());
                $objEmailCampaign->setEndDate($formData->getEndDate()); */
                $objEmailCampaign->setEmailType($formData->getEmailType());

                $em->persist($objEmailCampaign);
                $em->flush();
                
                // set audit log update email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Email Campaign';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated email campaign ".$formData->getSubject();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Email campaign updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
            }
        }

        return $this->render('DhiAdminBundle:EmailCampaign:edit.html.twig', array(
                    'form' => $form->createView(),
                    'email' => $objEmailCampaign
        ));        
    }

    public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to email campaign.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
            
        $objEmailCampaign = $em->getRepository('DhiUserBundle:EmailCampaign')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Email Campaign';
        if ($objEmailCampaign) {
            
            // set audit log delete email campagin
           
            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted email campaign ".$objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $em->remove($objEmailCampaign);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'Email compaign deleted successfully!');
        
        } else {
            
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete email campaign ".$objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete email campaign!');
        }        
         $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }

    public function sendEmailAction(Request $request, $id) {
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('email_campaign_list')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to send email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();

        $objEmailCampaign = $em->getRepository('DhiUserBundle:EmailCampaign')->find($id);
        
        if ($objEmailCampaign) {
            
            // set audit log send email campagin
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Send Email Campaign';
            $activityLog['description'] = "Admin ".$admin->getUsername()." has marked email campaign to send " . $objEmailCampaign->getSubject();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $objEmailCampaign->setEmailStatus('In Progress');
            $em->persist($objEmailCampaign);
            $em->flush();
            $this->get('session')->getFlashBag()->add('success', "Email campaign marked active to send");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        } else {
            
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to send an email!");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        }        
    }
    
    public function emailCampaignHistoryAction(Request $request, $campaignId){
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('email_campaign_history'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view email campaign history.");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        }        
        
        $objEmailCampaign = $em->getRepository('DhiUserBundle:EmailCampaign')->find($campaignId);
        
        if (!$objEmailCampaign) {
            
            $this->get('session')->getFlashBag()->add('failure', "Unable to find email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        }
        
        // Service locaiton
        $serviceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        $arrServiceLocations = array();

        if ($admin->getGroup() != 'Super Admin') {
            $lo = $admin->getServiceLocations();
            foreach ($lo as $key => $value) {
                $arrServiceLocations[] = $value->getName();
            }
        }else{
            foreach ($serviceLocations as $key => $serviceLocation){
                $arrServiceLocations[] = $serviceLocation['name'];
            }
        }
                
        return $this->render('DhiAdminBundle:EmailCampaign:emailCampaignHistory.html.twig',array(
            'campaignId' => $campaignId,
            'serviceLocations' => $arrServiceLocations
        ));
    }
   
    public function emailCampaignHistoryJsonAction(Request $request, $campaignId, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $emailCompaignColumns = array('EmailId', 'EmailType', 'ServiceLocation', 'SentAt');
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($emailCompaignColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'eh.id';
            $sortOrder = 'DESC';
        } else {
            
            if ($gridData['order_by'] == 'EmailId') {
                $orderBy = 'u.email';
            }
            if ($gridData['order_by'] == 'EmailType') {
                $orderBy = 'e.emailType';
            }
            if ($gridData['order_by'] == 'ServiceLocation') {
                $orderBy = 'u.userServiceLocation';
            }
            if ($gridData['order_by'] == 'SentAt') {
                $orderBy = 'eh.createdAt';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiUserBundle:EmailCampaignHistory')->getEmailCampaignHistoryGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $campaignId);
        
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
                    
                    $serviceLocation = 'N/A';
                    if($resultRow->getUser()){
                        $serviceLocation = $resultRow->getUser()->getUserServiceLocation() ? $resultRow->getUser()->getUserServiceLocation()->getName() : 'N/A';
                    }
                    
                    $row = array();
                    $row[] = $resultRow->getUser() ? $resultRow->getUser()->getEmail() : $resultRow->getBlastEmail();
                    $row[] = $resultRow->getEmailCampaign()->getEmailType() == 'M' ? 'Marketing' : 'Support';
                    $row[] = $serviceLocation;
                    $row[] = ($resultRow->getCreatedAt() ? $resultRow->getCreatedAt()->format('m/d/Y H:i:s') : 'N/A' );
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
