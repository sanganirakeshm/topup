<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\AddonsMasterFormType;

class AddonsMasterController extends Controller
{
    public function indexAction(Request $request){
        
        if (!( $this->get('admin_permission')->checkPermission('addons_master_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view addons list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:AddonsMaster:index.html.twig');
    }
    
    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $adminColumns = array("name", "image");
        $admin        = $this->get('security.context')->getToken()->getUser();
        $helper       = $this->get('grid_helper_function');
        $gridData     = $helper->getSearchData($adminColumns);
        $sortOrder    = $gridData['sort_order'];
        $orderBy      = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'am.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'name') {
                $orderBy = 'am.name';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset   = $gridData['offset'];
        $em       = $this->getDoctrine()->getManager();
        $data     = $em->getRepository('DhiAdminBundle:AddonsMaster')->getAddonsGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                $isSecure    = $this->getRequest()->isSecure() ? 'https://' : 'http://';
                $imgUrl      = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl("uploads");

                foreach ($data['result'] AS $resultRow) {
                    $row = array();
                    $row[] = $resultRow['name'];
                    $row[] = !empty($resultRow['image']) ? $imgUrl.'/addons/'.$resultRow['image'] : "N/A";
                    $row[] = $resultRow['id'];
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function editAction(Request $request, $id){
        
        if(! $this->get('admin_permission')->checkPermission('addons_master_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update addons.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em    = $this->getDoctrine()->getManager();

        $objAddonsMaster = $em->getRepository('DhiAdminBundle:AddonsMaster')->find($id);
        if ($objAddonsMaster) {
            $uploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/addons';
            if($objAddonsMaster->getImage()){
                $oldImage = $objAddonsMaster->getImage();
            }

            $form = $this->createForm(new AddonsMasterFormType(), $objAddonsMaster);
            if ($request->getMethod() == "POST") {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $formData = $form->getData();
                    if($formData->getImage() !== null){
                        $image = $formData->getImage();
                        $fileName = md5(uniqid()).'.'.$image->guessExtension();
                        $image->move($uploadDir, $fileName);
                        $objAddonsMaster->setImage($fileName);
                        $em->persist($objAddonsMaster);
                        $em->flush();
                        
                        if (!empty($oldImage)) {
                            if(file_exists($uploadDir.'/'.$oldImage)) {   unlink($uploadDir.'/'.$oldImage); }
                        }
                    }

                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Edit Addons';
                    $activityLog['description'] = "Admin ".$admin->getUsername()." has updated Addons. Name: ".$objAddonsMaster->getName();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', 'Addons updated successfully.');
                    return $this->redirect($this->generateUrl('dhi_admin_addons_list'));
                }
            }

            return $this->render('DhiAdminBundle:AddonsMaster:edit.html.twig', array(
                'form'   => $form->createView(),
                'addons' => $objAddonsMaster,
                "oldImage" => !empty($oldImage) ? $oldImage : ''
            ));
        }else{
            $this->get('session')->getFlashBag()->add('failure', "Addons does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_addons_list'));
        }
    }
    
    public function removeImageAction($id){

        if(! $this->get('admin_permission')->checkPermission('addons_master_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update addons.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin      = $this->get('security.context')->getToken()->getUser();
        $em         = $this->getDoctrine()->getManager();
        $response   = array("status" => 'failure', "msg" => "");
        
        $objAddonsMaster = $em->getRepository('DhiAdminBundle:AddonsMaster')->find($id);
        if ($objAddonsMaster) {
            $uploadDir  = $this->container->getParameter('kernel.root_dir').'/../web/uploads/addons';
            $oldImage   = $objAddonsMaster->getImage();
            $isToRemove = false;
            if (!empty($oldImage)) {
                if(file_exists($uploadDir.'/'.$oldImage)) {
                    if (unlink($uploadDir.'/'.$oldImage)) {
                        $isToRemove = true;
                    }
                }
            }
            if ($isToRemove) {
                $objAddonsMaster->setImage("");
                $em->persist($objAddonsMaster);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Addons';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has remove Addons image. Name: ".$objAddonsMaster->getName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $response['msg']    = "Addons Image removed successfully";
                $response['status'] = 'success';
            }else{
                $response['msg'] = "Could not remove Addons Image";
            }

        }else{
            $response['msg'] = "Addons does not exists";
        }
        $response['url'] = $this->generateUrl('dhi_admin_addons_list');
        $this->get('session')->getFlashBag()->add($response['status'], $response['msg']);
        $finalResponse = new Response(json_encode($response));
        $finalResponse->headers->set('Content-Type', 'application/json');
        return $finalResponse;
    }
}
