<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Entity\PaypalCredentials;
use Dhi\AdminBundle\Form\Type\PaypalCredentialFormType;
use Doctrine\ORM\EntityRepository;
use \DateTime;

class CredentialController extends Controller {

    public function indexAction(Request $request) {
        //Check Permission
        if (!($this->get('admin_permission')->checkPermission('credential_list') || $this->get('admin_permission')->checkPermission('credential_create') || $this->get('admin_permission')->checkPermission('credential_update') || $this->get('admin_permission')->checkPermission('credential_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view compensation list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:Credential:index.html.twig');
    }
    
    public function credentialListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $credentialColumns = array('Id','Country','Service Locations', "Description", "Credential Id", 'Action');
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($credentialColumns);
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        $paypalAccounts = $this->container->getParameter("paypal_credentials");
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'c.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'c.id';
            }
            if ($gridData['order_by'] == 'Credential Id') {
                $orderBy = 'c.PaypalId';
            }
            if ($gridData['order_by'] == 'Country') {
                $orderBy = 'cu.name';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];
        $em = $this->getDoctrine()->getManager();
        
        $country = '';
        if ($admin->getGroup() != 'Super Admin') {
            $country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
            $country = empty($country) ? '0' : $country;
        }
        
        $data  = $em->getRepository('DhiAdminBundle:PaypalCredentials')->getCredentialGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $country);
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
                    $locationName = '';
                    if($resultRow->getServiceLocations()){
                        $locationName = $resultRow->getServiceLocations()->getName();
                    }

                    $country = '';
                    if($resultRow->getCountry()){
                        $country = $resultRow->getCountry()->getName();
                    }

                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $country;
                    $row[] = $locationName;
                    $row[] = (isset($paypalAccounts[$resultRow->getPaypalId()]) ? $paypalAccounts[$resultRow->getPaypalId()]['credential_name'] : "N/A");
                    $row[] = $resultRow->getPaypalId();
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    $output['aaData'][] = $row;
                }
            }
        }
        $response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     * @return type
     */
    public function newAction(Request $request) {
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('credential_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add paypal credential.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array('admin' => $admin);
        $em = $this->getDoctrine()->getManager();

        // Get countries
        $countries = $em->getRepository("DhiUserBundle:Country")->getCreditCardCountry();

        // Get Paypal Accounts
        $paypalAccounts = $this->container->getParameter("paypal_credentials");
        $credentials = array();
        foreach ($paypalAccounts as $key => $account) {
            $credentials[$key] = $account['credential_name'];
        }
        
        $credential = new PaypalCredentials();
        $credential->setCreatedAt(new \DateTime(date('Y-m-d H:i:s')));
        $credential->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));
        $form = $this->createForm(new PaypalCredentialFormType($credentials), $credential);
        if($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if($form->isValid()){

                $checkExists = $em->getRepository("DhiAdminBundle:PaypalCredentials")->findOneBy(array('serviceLocations' => $credential->getServiceLocations(), 'country' => $credential->getCountry()));
                if (!$checkExists) {

                    $em->persist($credential);
                    $em->flush();

                    // Generate Log
                    $activityLog['activity']    = 'Add Credential for service location';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new paypal credential for service location.";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Credential added successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_credential_list'));
                }else{
                    $this->get('session')->getFlashBag()->add('failure', "Credential already added for service location.");
                }
            }
        }
        return $this->render('DhiAdminBundle:Credential:new.html.twig', array(
            'form' => $form->createView(),
            'countries' => $countries
        ));
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     * @return type
     */
    public function editAction(Request $request, $id) {

        $em = $this->getDoctrine()->getManager();
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('credential_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update paypal credential.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $activityLog = array(
            'admin' => $admin,
        );

        // Get countries
        $countries = $em->getRepository("DhiUserBundle:Country")->getCreditCardCountry();

        // Get Paypal Accounts
        $paypalAccounts = $this->container->getParameter("paypal_credentials");
        $credentials = array();
        foreach ($paypalAccounts as $key => $account) {
            $credentials[$key] = $account['credential_name'];
        }
        
        $credential = $em->getRepository('DhiAdminBundle:PaypalCredentials')->find($id);
        $form = $this->createForm(new PaypalCredentialFormType($credentials), $credential);
        
        if($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if($form->isValid()) {

                $checkExists = $em->getRepository("DhiAdminBundle:PaypalCredentials")->findOneBy(array('serviceLocations' => $credential->getServiceLocations(), 'country' => $credential->getCountry(), 'id' => $credential->getId()));
                if (!$checkExists) {

                    $credential->setUpdatedAt(new \DateTime(date('Y-m-d H:i:s')));
                    $em->persist($credential);
                    $em->flush();

                    $activityLog = array();
                    $activityLog['admin']       = $admin;
                    $activityLog['activity']    = 'Edit Credential for service location';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has edited paypal credential for service location.";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Credential updated successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_credential_list'));
                }else{
                    $this->get('session')->getFlashBag()->add('failure', "Credential already added for service location.");
                }
            }
        }
        
        return $this->render('DhiAdminBundle:Credential:edit.html.twig', array(
            'form'         => $form->createView(),
            'credential' => $credential,
            'countries' => $countries
        ));
    }
    
    public function deleteAction(Request $request) {
        $id = $request->get('id');

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('credential_delete')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete credential.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $credential = $em->getRepository('DhiAdminBundle:PaypalCredentials')->find($id);
        if ($credential) {
            $em->remove($credential);
            $em->flush();

            // set audit log delete user
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['activity'] = 'Delete credential';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted paypal credential. Id: " . $credential->getId();
            $result = array('type' => 'success', 'message' => 'Paypal credential deleted successfully!');
        } else {
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete paypal credential";
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete paypal credential!');
        }
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function searchServiceLocationAction(Request $request) {
        $countryId =  $request->get('countryId');
        $id        = $request->get('id');
        $em = $this->getDoctrine()->getManager();
        $serviceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->getCountryServiceLocation($countryId, $id);
        $response = new Response(json_encode($serviceLocations));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
