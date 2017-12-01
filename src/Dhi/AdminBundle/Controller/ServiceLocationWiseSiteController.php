<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\ServiceLocationWiseSite;
use Dhi\AdminBundle\Form\Type\ServiceLocationWiseSiteFormType;

class ServiceLocationWiseSiteController extends Controller {

    public function indexAction(Request $request) {

        if (!($this->get('admin_permission')->checkPermission('service_location_wise_sites_list') || $this->get('admin_permission')->checkPermission('assign_service_location_to_sites') || $this->get('admin_permission')->checkPermission('service_location_to_sites_edit') || $this->get('admin_permission')->checkPermission('service_location_to_sites_delete'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view service location wise site.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em                 = $this->getDoctrine()->getManager();
        $admin              = $this->get('security.context')->getToken()->getUser();
        $serviceLocation    = array();
        $objServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();

        if ($admin->getGroup() != 'Super Admin') {
            $location = $admin->getServiceLocations();
            foreach ($location as $key => $value) {
                $serviceLocation[] = $value->getName();
            }

        }else{
            foreach ($objServiceLocation as $activity) {
                $serviceLocation[] = $activity['name'];
            }
        }

        $allSites = $em->getRepository("DhiAdminBundle:WhiteLabel")->getWhiteLabelSites();
        return $this->render('DhiAdminBundle:ServiceLocatioWiseSite:index.html.twig', array('serviceLocations' => $serviceLocation, 'allSites' => $allSites));
    }

    public function listJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $WhiteLabelColumns = array('ServiceLocation', 'CompanyName', 'CompanyDomainName', 'CreatedAt', 'UpdatedAt');

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($WhiteLabelColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
       $companyName = $request->get('CompanyName');
        
        if(!empty($companyName))
        {
            $gridData['search_data']['CompanyName'] = $companyName;
            $gridData['SearchType'] = 'ANDLIKE';
        }
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'ls.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'CompanyName') {

                $orderBy = 'wl.companyName';
            }
            if ($gridData['order_by'] == 'CompanyDomainName') {

                $orderBy = 'wl.domain';
            }
            if ($gridData['order_by'] == 'CreatedAt') {

                $orderBy = 'ls.createdAt';
            }
            if ($gridData['order_by'] == 'UpdatedAt') {

                $orderBy = 'ls.updatedAt';
            }
            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->getLocationWiseSiteGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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

                    $row[] = $resultRow['serviceLocationName'];
                    $row[] = $resultRow['companyName'];
                    $row[] = $resultRow['domain'];
                    $row[] = $resultRow['createdAt'] ? $resultRow['createdAt']->format('Y-m-d h:i:s') : 'N/A';
                    $row[] = $resultRow['updatedAt'] ? $resultRow['updatedAt']->format('Y-m-d h:i:s') : 'N/A';
                    $row[] = $resultRow['id'];
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function newAction(Request $request) {

        if (!$this->get('admin_permission')->checkPermission('assign_service_location_to_sites')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add service location wise site.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_to_sites_list'));
        }

        $objServiceLocationWiseSite = new ServiceLocationWiseSite();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new ServiceLocationWiseSiteFormType(array("admin" => $admin)), $objServiceLocationWiseSite);
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();

                $isAlreadyAssignSiteToServiceLocation = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findBy(array('serviceLocation' => $formData->getServiceLocation(), 'isDeleted' => 0));
                if ($isAlreadyAssignSiteToServiceLocation) {
                    $this->get('session')->getFlashBag()->add('danger', 'This service location is already assigned to other site.');
                    return $this->render('DhiAdminBundle:ServiceLocatioWiseSite:new.html.twig', array(
                                'form' => $form->createView(),
                    ));
                }

                $objServiceLocationWiseSite->setCreatedBy($admin);
                $objServiceLocationWiseSite->setUpdatedBy($admin);
                $em->persist($objServiceLocationWiseSite);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Service Location Wise Site';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has assign domain :'".$objServiceLocationWiseSite->getWhiteLabel()->getDomain()."' to service location :'".$objServiceLocationWiseSite->getServiceLocation()->getName()."' ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Service location wise site assigned successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_service_location_to_sites_list'));
            }
        }

        return $this->render('DhiAdminBundle:ServiceLocatioWiseSite:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    public function editAction(Request $request, $id) {
        
        if (!$this->get('admin_permission')->checkPermission('service_location_to_sites_edit')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update service location wise site.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_to_sites_list'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objServiceLocationWiseSite = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->find($id);
        if (!$objServiceLocationWiseSite) {
            $this->get('session')->getFlashBag()->add('failure', "Unable to find record.");
            return $this->redirect($this->generateUrl('dhi_admin_service_location_to_sites_list'));
        }

        $form = $this->createForm(new ServiceLocationWiseSiteFormType(array('admin' => $admin)), $objServiceLocationWiseSite);
        $form->remove('serviceLocation');
        if ($request->getMethod() == "POST") {
            $oldDomain = $objServiceLocationWiseSite->getWhiteLabel()->getDomain();
            $form->handleRequest($request);
            if ($form->isValid()) {

                $formData = $form->getData();
                $objServiceLocationWiseSite->setUpdatedBy($admin);
                $em->persist($objServiceLocationWiseSite);
                $em->flush();

                $newDomain = $objServiceLocationWiseSite->getWhiteLabel()->getDomain();
                $serviceLocation = $objServiceLocationWiseSite->getServiceLocation()->getName();
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Service Location Wise Site';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated domain '".$oldDomain."' to '".$newDomain."' for service location :'".$serviceLocation."'";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Service location wise site updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_service_location_to_sites_list'));
            }
        }

        return $this->render('DhiAdminBundle:ServiceLocatioWiseSite:edit.html.twig', array(
                    'form' => $form->createView(),
                    'objServiceLocationWiseSite' => $objServiceLocationWiseSite
        ));
    }
    
    public function deleteAction(Request $request) {

        if (!$this->get('admin_permission')->checkPermission('service_location_to_sites_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete service location wise site.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $id = $request->get('id');

        $objServiceLocationWiseSite = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->find($id);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Service Location Wise Site';
        if ($objServiceLocationWiseSite) {
            
            $domain = $objServiceLocationWiseSite->getWhiteLabel()->getDomain();
            $serviceLocation = $objServiceLocationWiseSite->getServiceLocation()->getName();
            $activityLog['description'] = "Admin  " . $admin->getUsername() . " has deleted assigned service location :'".$serviceLocation."' from domain : '".$domain."'";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $objServiceLocationWiseSite->setIsDeleted(true);
            $objServiceLocationWiseSite->setUpdatedBy($admin);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Service location wise site deleted successfully.');
        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete service location wise site ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'Record does not exists.');
        }
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
