<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\FrontHome;
use Dhi\AdminBundle\Form\Type\FrontHomeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class FrontHomeController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('front_home_list') || $this->get('admin_permission')->checkPermission('front_home_create') || $this->get('admin_permission')->checkPermission('front_home_update') || $this->get('admin_permission')->checkPermission('front_home_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view home content list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();

        $objCountries = $em->getRepository('DhiUserBundle:Country')->getAllCountry();
        $countries = array_map(function($country){ return $country['name']; } , $objCountries);

        return $this->render('DhiAdminBundle:FrontHome:index.html.twig', array('countries' => $countries));
    }

    //added for grid
	public function frontHomeListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $frontHomeColumns = array('Country','Column1','Column2','Column3','Column4');

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($frontHomeColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'fh.id';
            $sortOrder = 'DESC';
        } else {

             if ($gridData['order_by'] == 'Id') {

                $orderBy = 'fh.id';
            }

			  if ($gridData['order_by'] == 'Country') {

                $orderBy = 'c.name';
            }

            if ($gridData['order_by'] == 'Column1') {

                $orderBy = 'fh.column1';
            }

            if ($gridData['order_by'] == 'Column2') {

                $orderBy = 'fh.column2';
            }
            if ($gridData['order_by'] == 'Column3') {

                $orderBy = 'fh.column3';
            }

            if ($gridData['order_by'] == 'Column4') {

                $orderBy = 'fh.column4';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiUserBundle:FrontHome')->getFrontHomeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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

                    $row[] = $resultRow->getCountry()->getName();
                    $row[] = $resultRow->getColumn1();
                    $row[] = $resultRow->getColumn2();
                    $row[] = $resultRow->getColumn3();
                    $row[] = $resultRow->getColumn4();
                    $row[] = $resultRow->getId();

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
        if(! $this->get('admin_permission')->checkPermission('front_home_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add home content.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $frontHome = new FrontHome();
	//	 $image = new Image();

        $form = $this->createForm(new FrontHomeFormType(), $frontHome);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $formData = $form->getData();

                $em->persist($frontHome);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Home Content';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added home content ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Home Content added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_front_home_list'));
            }
        }
        return $this->render('DhiAdminBundle:FrontHome:new.html.twig', array(
                    'form' => $form->createView(),
        ));

    }

	  public function editAction(Request $request, $id) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('front_home_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update home content.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objFrontHome = $em->getRepository('DhiUserBundle:FrontHome')->find($id);
        if (!$objFrontHome) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find Home Content.");
            return $this->redirect($this->generateUrl('dhi_admin_front_home_list'));
        }

        $form = $this->createForm(new FrontHomeFormType(), $objFrontHome);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();
                $em->persist($objFrontHome);
                $em->flush();


                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Home Content';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated home content ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Home Content updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_front_home_list'));
            }
        }

        return $this->render('DhiAdminBundle:FrontHome:edit.html.twig', array(
                    'form' => $form->createView(),
                    'home' => $objFrontHome
        ));
    }

	public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('front_home_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete banner.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objFrontHome = $em->getRepository('DhiUserBundle:FrontHome')->find($id);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Home Content';

		if ($objFrontHome) {

            

            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted home content";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objFrontHome);
            $em->flush();
             $result = array('type' => 'success', 'message' => 'Home Content deleted successfully!');

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete home content ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete home content!');
        }
         $response = new Response(json_encode($result));

	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }




}
