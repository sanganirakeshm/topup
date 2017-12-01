<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\AdminFormType;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Form\Type\ChangePasswordFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\CountrywiseService;
use Dhi\AdminBundle\Form\Type\CountrywiseServiceFormType;
use \DateTime;

class AdminController extends Controller {

    public function indexAction(Request $request) {
        
        if (!($this->get('admin_permission')->checkPermission('admin_list') || $this->get('admin_permission')->checkPermission('admin_create') || $this->get('admin_permission')->checkPermission('admin_update') || $this->get('admin_permission')->checkPermission('admin_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view admin list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();              
       
        return $this->render('DhiAdminBundle:Admin:index.html.twig', array('admin' => $admin));
    }
    
    //added for grid
    public function adminListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
       
        $adminColumns = array('Id','Username','Email','Status','LastLogin','LoginStatus','Role');
         
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($adminColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'u.id';
            $sortOrder = 'DESC';
        } else {
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'u.id';
            }
            
          if ($gridData['order_by'] == 'Username') {
                
                $orderBy = 'u.username';
            }
            if ($gridData['order_by'] == 'Email') {
                
                $orderBy = 'u.email';
            }
              if ($gridData['order_by'] == 'Status') {
                
                $orderBy = 'u.enabled';
            }
            if ($gridData['order_by'] == 'LastLogin') {
                
                $orderBy = 'u.lastLogin';
            } 
            if ($gridData['order_by'] == 'LoginStatus') {
                
                $orderBy = 'u.isloggedin';
            } 
            
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiUserBundle:User')->getAdminGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper,$admin);
        
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
                   
                    $flagDelete = 0;             
                    $flagLocation = 0; 
                   
                    $username = '<a href="'.$this->generateUrl('dhi_admin_edit', array('id' => $resultRow['id'])).'">'.$resultRow['username'].'</a>';                        
                    $delete = '';
                    if($admin->getGroup() == 'Super Admin' && $resultRow['groupName'] !='Super Admin')
                    {
                        $flagDelete = 1; 
                        $delete = $resultRow['id'].'^'.$flagDelete;
                    }  
                    $location = '';
                    if($resultRow['groupName'] != 'Super Admin' && $resultRow['groupName'] != null ) 
                    {
                        $flagLocation = 1; 
                        $location = $resultRow['id'].'^'.$flagLocation;
                    }  
                    
                    
                    $row = array();
                     $row[] = $resultRow['id'];
                    $row[] = $username;
                    $row[] = $resultRow['email'];
                    $row[] = $resultRow['enabled'] ? 'Active':'';
                    $row[] = $resultRow['lastLogin'] != null ? $resultRow['lastLogin']->format('M-d-Y H:i:s') : '';                 
                    $row[] = $resultRow['isloggedin'] ? 'LoggedIn' : '';
                    $row[] = $resultRow['groupName'] != null ? $resultRow['groupName'] : '';  
                    $row[] = $delete.'^'.$location ; 
                    
                    
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
        
        if(! $this->get('admin_permission')->checkPermission('admin_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new admin.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new AdminFormType($admin, null), new User());

        if ($request->getMethod() == "POST") {


            $form->handleRequest($request);
//             $formValues = $request->request->get('dhi_admin_registration');

            if ($form->isValid()) {

                $objAdmin = $form->getData();
                $objAdmin->setRoles(array('ROLE_ADMIN'));
                $em->persist($objAdmin);
                $em->flush();
                
                // Set activity log add user
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['user'] = $objAdmin;
                $activityLog['activity'] = 'Add admin';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new admin as Role: " . $objAdmin->getSingleRole() . ", Email: " . $objAdmin->getEmail() . " and Username: " . $objAdmin->getUsername();
                
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                
                $this->get('session')->getFlashBag()->add('success', "Admin added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_list'));
            }
        }

        return $this->render('DhiAdminBundle:Admin:new.html.twig', array('form' => $form->createView()));
        
    }

    public function editAction(Request $request, $id) {
        
        if(! $this->get('admin_permission')->checkPermission('admin_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update admin.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();        
        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        
        if($admin->getGroup() != 'Super Admin' && $admin->getId() != $user->getId()) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update this admin.");
            return $this->redirect($this->generateUrl('dhi_admin_list'));
        }
        
        if (!$user) {
            $this->get('session')->getFlashBag()->add('failure', "Admin user does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_list'));
        }
 
        $form = $this->createForm(new AdminFormType($admin, $user), $user);
        $changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);

        if ($request->getMethod() == "POST") {

            if ($request->request->has($form->getName())) {

                $username = $user->getUsername();
                $form->handleRequest($request);

                if ($form->isValid()) {

                    $objAdmin = $form->getData();
                    $objAdmin->setUsername($username);
                    $em->persist($objAdmin);
                    $em->flush();
                    
                    // Set activity log update admin user
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $objAdmin;
                    $activityLog['activity'] = 'Edit admin';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated admin " . $user->getUsername();

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Admin updated successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_list'));
                }
            }

            if ($request->request->has($changePasswordForm->getName())) {

                $changePasswordForm->handleRequest($request);

                if ($changePasswordForm->isValid()) {

                    $userManager = $this->get('fos_user.user_manager');
                    $userManager->updateUser($user);
                    
                    // Set activity log change password
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['user'] = $user;
                    $activityLog['activity'] = 'Admin change password';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has changed password for admin " . $user->getUsername();

                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                    $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");
                    return $this->redirect($this->generateUrl('dhi_admin_list'));
                }
            }
        }

        return $this->render('DhiAdminBundle:Admin:edit.html.twig', array(
                    'form' => $form->createView(),
                    'user' => $user,
                    'changePasswordForm' => $changePasswordForm->createView(),
        ));
        
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
          
        if(! $this->get('admin_permission')->checkPermission('admin_delete')) {
            
             $result = array('type' => 'danger', 'message' => 'You are not allowed to admin.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $em = $this->getDoctrine()->getManager();
        $admin = $this->get('security.context')->getToken()->getUser();
        $user = $em->getRepository('DhiUserBundle:User')->find($id);
        
        if($user->getGroup() == $admin->getGroup() or $user->getGroup() == 'Super Admin') {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to delele ".$admin->getGroup());
            return $this->redirect($this->generateUrl('dhi_admin_list'));
        }
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['ip'] = $request->getClientIp();
        $activityLog['sessionId'] = $request->getSession()->getId();
        $activityLog['url'] = $request->getUri();

        if ($user) {
            
            // Set activity log change password
            $activityLog = array();
            $activityLog['admin'] = $admin;
            $activityLog['user'] = $user;
            $activityLog['activity'] = 'Delete admin';
            $activityLog['description'] = "Admin " . $admin->getUsername() . " deleted admin " . $user->getUsername();

           
            
            $user->setIsDeleted(1);
            $user->setExpired(1);
            $user->setExpiresAt(new DateTime());
            $em->persist($user);
            $em->flush();
               $result = array('type' => 'success', 'message' => 'Admin deleted successfully!');
            
        } else {
             $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete admin ".$user->getUsername();
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete admin!');
        }
         $this->get('ActivityLog')->saveActivityLog($activityLog);
         
         $response = new Response(json_encode($result));
        
	 $response->headers->set('Content-Type', 'application/json');
     
         return $response;
    }

    public function changePasswordAction(Request $request) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $changePasswordForm = $this->createForm(new \Dhi\UserBundle\Form\Type\ChangePasswordFormType, $admin);

        if ($request->getMethod() == "POST") {

            $changePasswordForm->handleRequest($request);

            if ($changePasswordForm->isValid()) {
                
                // set audit log admin user change password
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Change password';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " change password ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $userManager = $this->get('fos_user.user_manager');
                $userManager->updateUser($admin);
                $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");
            }
        }

        return $this->render('DhiAdminBundle:Admin:changePassword.html.twig', array(
                    'changePasswordForm' => $changePasswordForm->createView()
        ));
    }
    
    public function checkEmailAction(Request $request, $id) {
        
        $user = $this->get('security.context')->getToken()->getUser();
        //$objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($request->get('email'), $user->getId());
        $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->findOneBy(array('email' => $request->get('email')));

        if ($objUser && $objUser->getId() != $id) {
            
            $result = 'error';
            
        } else {
            
            $result = 'success';
        }
        
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
}
