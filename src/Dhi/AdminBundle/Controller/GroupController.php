<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\AdminFormType;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Entity\Group;
use Dhi\AdminBundle\Form\Type\ChangePasswordFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\CountrywiseService;
use Dhi\AdminBundle\Form\Type\CountrywiseServiceFormType;
use Dhi\AdminBundle\Form\Type\GroupFormType;
use Dhi\AdminBundle\Form\Type\GroupPermissionFormType;
use Doctrine\ORM\EntityRepository;
use \DateTime;

class GroupController extends Controller {

    public function indexAction(Request $request) {
        //Check Permission
        if (!($this->get('admin_permission')->checkPermission('admin_group_list') || $this->get('admin_permission')->checkPermission('admin_group_create') || $this->get('admin_permission')->checkPermission('admin_group_permission') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user group list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
            return $this->render('DhiAdminBundle:Group:index.html.twig');
    }
    
    //Added For Grid List
    public function groupListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $groupColumns = array('Id','Name');
        $admin = $this->get('security.context')->getToken()->getUser();  
        // common helper function for searching
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($groupColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            //$orderBy = 'g.id';
            $orderBy = 'g.name';
            $sortOrder = 'ASC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'g.id';
            }

            if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 'g.name';
            }
            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiUserBundle:Group')->getGroupGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getName();
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
        if(! $this->get('admin_permission')->checkPermission('admin_group_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add user group.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $activityLog = array(
                        'admin' => $admin,
                        'ip' => $request->getClientIp(),
                        'sessionId' => $request->getSession()->getId(),
                        'url' => $request->getUri()
        );
        
        $em = $this->getDoctrine()->getManager();
        $group = new Group();
        $form = $this->createForm(new GroupFormType(), $group);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $em->persist($group);
                $em->flush();
                
                // set audit log add group
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $admin;
                $activityLog['activity'] = 'Add Group';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new Group: " . $group->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Group added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_group_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:Group:new.html.twig', array(
                        'form' => $form->createView()
        ));
    }

    /**
     *
     * @param \Symfony\Component\HttpFoundation\Request $request            
     * @return type
     */
    public function editAction(Request $request, $id) {

        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('admin_group_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update user group.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('DhiUserBundle:Group')->find($id);
        
        $form = $this->createForm(new GroupFormType(), $group);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $em->persist($group);
                $em->flush();
                
                // set audit log edit group
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $admin;
                $activityLog['activity'] = 'Edit Group';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated Group: " . $group->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Group updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_group_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:Group:edit.html.twig', array(
                        'form' => $form->createView(),
                        'group' => $group
        ));
    }

    public function permissionAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        $group = $em->getRepository('DhiUserBundle:Group')->find($id);
        
        $form = $this->createForm(new GroupPermissionFormType(), $group);
        
        $categories = $em->getRepository('DhiUserBundle:PermissionCategory')->findBy(array(), array('name' => 'ASC'));
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
            
                $em->persist($group);
                $em->flush();
                
                // set audit log edit group permission
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $admin;
                $activityLog['activity'] = 'Edit group permission';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated group permission";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                
                $this->get('session')->getFlashBag()->add('success', "Group permissions updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_group_list'));
            }
            
        }
        
        return $this->render('DhiAdminBundle:Group:permission.html.twig', array(
                        'form' => $form->createView(),
                        'group' => $group,
                        'categories' => $categories,
        ));
    }
    public function deleteAction(Request $request){
        
        $id = $request->get('id');
        $em =  $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $objGroup = $em->getRepository('DhiUserBundle:Group')->find($id);
        
        if(!$objGroup){
            
            $result = array('type' => 'danger', 'message' => 'Group does not exist.');
        }else if(count($objGroup->getUsers()) > 0){ //check group assigned any user

            $result = array('type' => 'danger', 'message' => 'You can not delete group! One or more users are already added in this group.');
        }else {
            $em->remove($objGroup);
            $em->flush();

            // set audit log delete group
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['user'] = $admin;
            $activityLog['activity'] = 'Delete Group';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted Group: " . $objGroup->getName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'success', 'message' => 'Group has been deleted successfully!');
        }
        
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
