<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\AdminFormType;
use Dhi\UserBundle\Entity\Permission;
use Dhi\AdminBundle\Form\Type\PermissionFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Doctrine\ORM\EntityRepository;
use \DateTime;

class PermissionController extends Controller {

    public function indexAction(Request $request) {
        
        //Check Permission
        if (!($this->get('admin_permission')->checkPermission('admin_permission_list') || $this->get('admin_permission')->checkPermission('admin_permission_update') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view permission list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        return $this->render('DhiAdminBundle:Permission:index.html.twig');
    }
    
    //Added For Grid List
    public function permissionListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $permissionColumns = array('Id','Name');
        $admin = $this->get('security.context')->getToken()->getUser();  
        // common helper function for searching
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($permissionColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'p.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'p.id';
            }

            if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 'p.name';
            }
            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiUserBundle:Permission')->getPermissionGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
        if(! $this->get('admin_permission')->checkPermission('admin_permission_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add permission.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        $permission = new Permission();
        $form = $this->createForm(new PermissionFormType(), $permission);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $em->persist($permission);
                $em->flush();
                
                // set audit log add permission
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Permission';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new Permission: " . $permission->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Permission added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_permission_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:Permission:new.html.twig', array(
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
        if(! $this->get('admin_permission')->checkPermission('admin_permission_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update permission.");
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
        
        $permission = $em->getRepository('DhiUserBundle:Permission')->find($id);
        
        $form = $this->createForm(new PermissionFormType(), $permission);
        
        if($request->getMethod() == "POST") {
            
            $form->handleRequest($request);
            
            if($form->isValid()) {
                
                $em->persist($permission);
                $em->flush();
                
                // set audit log edit permission
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Permission';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated Permission: " . $permission->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Permission updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_permission_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:Permission:edit.html.twig', array(
                        'form' => $form->createView(),
                        'permission' => $permission
        ));
    }
}
