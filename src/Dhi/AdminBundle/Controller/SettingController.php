<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\SettingFormType;
use Dhi\AdminBundle\Entity\Setting;
use Dhi\UserBundle\Entity\UserActivityLog;

class SettingController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('settings_list') || $this->get('admin_permission')->checkPermission('settings_create') || $this->get('admin_permission')->checkPermission('settings_update') || $this->get('admin_permission')->checkPermission('settings_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view settings list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $roles = $admin->getRoles();
        if ($admin) {

            return $this->render('DhiAdminBundle:Setting:index.html.twig', array('role' => $roles[0]));
        } else {

            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view settings!");
            return $this->redirect($this->generateUrl('dhi_admin_setting_list'));
        }
    }
    
    public function settingListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
     
        $settingColumns = array('Id', 'Name', 'Value');
        
           $admin = $this->get('security.context')->getToken()->getUser();
        
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($settingColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 's.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 's.id';
            }

            if ($gridData['order_by'] == 'Name') {
                
                $orderBy = 's.name';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
        
        $data  = $em->getRepository('DhiAdminBundle:Setting')->getSettingGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
       
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
                    
                    $flagEdit   = 1;
                    $flagDelete   = 1;
      
                    $name = '<a href="'.$this->generateUrl('dhi_admin_setting_edit', array('id' => $resultRow->getId())).'">'.$resultRow->getName().'</a>';
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $name;
                    $row[] = $resultRow->getValue();
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    
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
        if(! $this->get('admin_permission')->checkPermission('settings_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add settings.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $form = $this->createForm(new SettingFormType(), new Setting());

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objSetting = $form->getData();
                $em->persist($objSetting);
                $em->flush();
                
                // set audit log add setting
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add setting';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new setting successfully";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Setting added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_setting_list'));
            }
        }

        return $this->render('DhiAdminBundle:Setting:new.html.twig', array('form' => $form->createView()));
    }

    public function editAction(Request $request, $id) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('settings_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update settings.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $setting = $em->getRepository('DhiAdminBundle:Setting')->find($id);

        if (!$setting) {
            $this->get('session')->getFlashBag()->add('failure', "Setting does not exist.");
            return $this->redirect($this->generateUrl('dhi_admin_setting_list'));
        }

        $flag = false;
        if ($setting->getName() == 'maintenance_mode') {
            $flag = true;
        }

        $form = $this->createForm(new SettingFormType($flag), $setting);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $objSetting = $form->getData();
                $em->persist($objSetting);
                $em->flush();
                
                // set audit log edit setting
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit setting';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated setting ".$objSetting->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Setting updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_setting_list'));
            }
        }
        return $this->render('DhiAdminBundle:Setting:edit.html.twig', array('form' => $form->createView(), 'setting' => $setting));
    }

    public function deleteAction(Request $request) {
      
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('settings_delete')) {
            
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete settings.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        
        // set audit log delete setting
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete setting';
        
        $em = $this->getDoctrine()->getManager();

        $objSetting = $em->getRepository('DhiAdminBundle:Setting')->find($request->get('id'));

        if ($objSetting) {

            $setting_name = $objSetting->getName();
            $em->remove($objSetting);
            $em->flush();
      
            /* START: add user audit log for delete setting */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted setting ".$setting_name;
            /* END: add user audit log for delete setting */
            $result = array('type' => 'success', 'message' => 'Setting deleted successfully!');

        } else {

            /* START: add user audit log for delete setting */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has tried to delete setting";
            /* END: add user audit log for delete setting */
             
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete setting!');
        }   
          
        $this->get('ActivityLog')->saveActivityLog($activityLog);
        
        $response = new Response(json_encode($result));
        
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
       
    }
}
