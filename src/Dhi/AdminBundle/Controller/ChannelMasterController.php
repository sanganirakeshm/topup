<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\ChannelMasterFormType;
use \DateTime;

class ChannelMasterController extends Controller {

    public function indexAction(Request $request) {
        if (!( $this->get('admin_permission')->checkPermission('channel_master_list'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view channel list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $admin = $this->get('security.context')->getToken()->getUser();              
        return $this->render(
            'DhiAdminBundle:ChannelMaster:index.html.twig', array()
        );
    }

    public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        $adminColumns = array("name", "image");
        $admin        = $this->get('security.context')->getToken()->getUser();
        $helper       = $this->get('grid_helper_function');
        $gridData     = $helper->getSearchData($adminColumns);
        $sortOrder    = $gridData['sort_order'];
        $orderBy      = $gridData['order_by'];
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            $orderBy = 'cm.id';
            $sortOrder = 'DESC';
        } else {
            if ($gridData['order_by'] == 'Id') {
                $orderBy = 'b.id';
            }
            if ($gridData['order_by'] == 'name') {
                $orderBy = 'cm.name';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset   = $gridData['offset'];
        $em       = $this->getDoctrine()->getManager();
        $data     = $em->getRepository('DhiAdminBundle:ChannelMaster')->getChannelGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    $row[] = !empty($resultRow['image']) ? $imgUrl.'/channels/'.$resultRow['image'] : "N/A";
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
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('channel_master_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update channe.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em    = $this->getDoctrine()->getManager();

        $objChannel = $em->getRepository('DhiAdminBundle:ChannelMaster')->find($id);
        if ($objChannel) {
            $uploadDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/channels';
            if($objChannel->getImage()){
                $oldImage = $objChannel->getImage();
            }

            $form = $this->createForm(new ChannelMasterFormType(), $objChannel);
            if ($request->getMethod() == "POST") {
                $form->handleRequest($request);
                if ($form->isValid()) {
                    $formData = $form->getData();
                    if($formData->getImage() !== null){
                        $image = $formData->getImage();
                        $fileName = md5(uniqid()).'.'.$image->guessExtension();
                        $image->move($uploadDir, $fileName);
                        $objChannel->setImage($fileName);
                        $em->persist($objChannel);
                        $em->flush();
                        
                        if (!empty($oldImage)) {
                            if(file_exists($uploadDir.'/'.$oldImage)) {   unlink($uploadDir.'/'.$oldImage); }
                        }
                    }

                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Edit Channel';
                    $activityLog['description'] = "Admin ".$admin->getUsername()." has updated Channel. Name: ".$objChannel->getName();
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', 'Channel updated successfully.');
                    return $this->redirect($this->generateUrl('dhi_admin_channel_list'));
                }
            }

            return $this->render('DhiAdminBundle:ChannelMaster:edit.html.twig', array(
                'form'   => $form->createView(),
                'channel' => $objChannel,
                "oldImage" => !empty($oldImage) ? $oldImage : ''
            ));
        }else{
            $this->get('session')->getFlashBag()->add('failure', "Channel does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_channel_list'));
        }
    }

    public function removeImageAction($id){
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('channel_master_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update channel.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $admin      = $this->get('security.context')->getToken()->getUser();
        $em         = $this->getDoctrine()->getManager();
        $response   = array("status" => 'failure', "msg" => "");
        $objChannel = $em->getRepository('DhiAdminBundle:ChannelMaster')->find($id);
        if ($objChannel) {
            $uploadDir  = $this->container->getParameter('kernel.root_dir').'/../web/uploads/channels';
            $oldImage   = $objChannel->getImage();
            $isToRemove = false;
            if (!empty($oldImage)) {
                if(file_exists($uploadDir.'/'.$oldImage)) {
                    if (unlink($uploadDir.'/'.$oldImage)) {
                        $isToRemove = true;
                    }
                }
            }
            if ($isToRemove) {
                $objChannel->setImage("");
                $em->persist($objChannel);
                $em->flush();
                $response['msg']    = "Channel Image removed successfully";
                $response['status'] = 'success';
            }else{
                $response['msg'] = "Could not remove Channel Image";
            }

        }else{
            $response['msg'] = "Channel does not exists";
        }
        $response['url'] = $this->generateUrl('dhi_admin_channel_list');
        $this->get('session')->getFlashBag()->add($response['status'], $response['msg']);
        $finalResponse = new Response(json_encode($response));
        $finalResponse->headers->set('Content-Type', 'application/json');
        return $finalResponse;
    }
}