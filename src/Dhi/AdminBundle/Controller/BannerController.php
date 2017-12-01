<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\Banner;
use Dhi\AdminBundle\Form\Type\BannerFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class BannerController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('banner_list') || $this->get('admin_permission')->checkPermission('banner_create') || $this->get('admin_permission')->checkPermission('banner_update') || $this->get('admin_permission')->checkPermission('banner_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view banner list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        $em = $this->getDoctrine()->getManager();
        $objCountries = $em->getRepository('DhiUserBundle:Country')->getAllCountry();
        $countries = array_map(function($country){ return $country['name']; } , $objCountries);
        return $this->render('DhiAdminBundle:Banner:index.html.twig', array('countries' => $countries,));
    }

    //added for grid
	public function bannerListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $frontHomeColumns = array('Country','OrderNo','Status');

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($frontHomeColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'b.id';
            $sortOrder = 'DESC';
        } else {

             if ($gridData['order_by'] == 'Id') {

                $orderBy = 'b.id';
            }

			if ($gridData['order_by'] == 'Country') {

                $orderBy = 'c.name';
            }

			if ($gridData['order_by'] == 'OrderNo') {

                $orderBy = 'b.orderNo';
            }
			if ($gridData['order_by'] == 'Status') {

                $orderBy = 'b.status';
            }

        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiUserBundle:Banner')->getBannerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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
                    $row[] = $resultRow->getOrderNo();
                    $row[] = $resultRow->getStatus() == 'true' ? 'Active' : 'InActive' ;
                    $row[] = $resultRow->getId().'^'.$resultRow->getStatus();

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
        if(! $this->get('admin_permission')->checkPermission('banner_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add banner.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $banner = new Banner();
	//	 $image = new Image();

        $form = $this->createForm(new BannerFormType(), $banner);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            if ($form->isValid()) {

                $formData = $form->getData();

				$brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';

				if($banner->getBannerImages()) {
				$file1 = $banner->getBannerImages();
				$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
				$file1->move($brochuresDir, $fileName1);
				$banner->setBannerImages($fileName1);
				}

				$orderByData = $em->getRepository('DhiUserBundle:Banner')->getOrderByData($banner->getCountry()->getId(),$banner->getOrderNo());

				if($orderByData){
					foreach($orderByData as $data){
						$nextOrderNo = $data['orderNo']+1;
						$objOrderData = $em->getRepository('DhiUserBundle:Banner')->find($data['id']);
						$objOrderData->setOrderNo($nextOrderNo);
						$em->persist($objOrderData);
					}
				}


                $em->persist($banner);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add banner images';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added home content ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Banner images added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_banner_list'));
            }

        }
        return $this->render('DhiAdminBundle:Banner:new.html.twig', array(
                    'form' => $form->createView(),
        ));

    }

	  public function editAction(Request $request, $id) {

        //Check permission
        if(! $this->get('admin_permission')->checkPermission('banner_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update banner.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objBanner = $em->getRepository('DhiUserBundle:Banner')->find($id);

		if($objBanner->getBannerImages()){
		$oldImagePath1 = $objBanner->getBannerImages();
		}

		if($objBanner->getOrderNo()){
		$oldOrderNo = $objBanner->getOrderNo();
		}



        if (!$objBanner) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find Banner list.");
            return $this->redirect($this->generateUrl('dhi_admin_banner_list'));
        }

        $form = $this->createForm(new BannerFormType(), $objBanner);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();
				$brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';

				if($formData->getBannerImages() !== null && !empty($oldImagePath1) ){
					$this->removeFile($oldImagePath1); // remove old file, see this at the bottom
					$file1 = $formData->getBannerImages();
					$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
					$file1->move($brochuresDir, $fileName1);
					$objBanner->setBannerImages($fileName1); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
				} else if(!empty($oldImagePath1)) {
					$objBanner->setBannerImages($oldImagePath1);
				}else if($formData->getBannerImages() !== null){
					$file1 = $formData->getBannerImages();
					$fileName1 = md5(uniqid()).'.'.$file1->guessExtension();
					$file1->move($brochuresDir, $fileName1);
					$objBanner->setBannerImages($fileName1);
				}




				$oldOrderData = $em->getRepository('DhiUserBundle:Banner')->getByOldOrderData($objBanner->getCountry()->getId(),$oldOrderNo);
				foreach($oldOrderData as $data){

					$objOrderData = $em->getRepository('DhiUserBundle:Banner')->find($data->getId());
					$objOrderData->setOrderNo($formData->getOrderNo());
					$em->persist($objOrderData);

				}

				$oldOrderData1 = $em->getRepository('DhiUserBundle:Banner')->getByOldOrderData($objBanner->getCountry()->getId(),$formData->getOrderNo());
				foreach($oldOrderData1 as $data){

					$objOrderData1 = $em->getRepository('DhiUserBundle:Banner')->find($data->getId());
					$objOrderData1->setOrderNo($oldOrderNo);
					$em->persist($objOrderData1);

				}

                $em->persist($objBanner);
                $em->flush();


                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Banner';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated Banner ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Banner updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_banner_list'));
            }
        }

        return $this->render('DhiAdminBundle:Banner:edit.html.twig', array(
                    'form' => $form->createView(),
                    'banner' => $objBanner
        ));
    }

	public function removeFile($file)
	{

		$brochuresDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads';
		$file_path = $brochuresDir.'/'.$file;

		if(file_exists($file_path)) {   unlink($file_path); }
	}

	public function deleteAction(Request $request) {
          $id = $request->get('id');
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('banner_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to banner.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

		 $em = $this->getDoctrine()->getManager();

		$objBanner = $em->getRepository('DhiUserBundle:Banner')->find($id);

		if($objBanner && $objBanner->getBannerImages()){
                    $bannerPath = $objBanner->getBannerImages();
		}

		$this->removeFile($bannerPath);

        $admin = $this->get('security.context')->getToken()->getUser();

		$orderByData = $em->getRepository('DhiUserBundle:Banner')->getDeleteOrderByData($objBanner->getCountry()->getId(),$objBanner->getOrderNo());


        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Banners';
        if ($objBanner) {

            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted banner";
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objBanner);
            $em->flush();
			$result = array('type' => 'success', 'message' => 'Banner deleted successfully!');

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete banner ";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete banner!');
        }

		if($orderByData){
			foreach($orderByData as $data){
				$nextOrderNo = $data['orderNo']-1;
				$objOrderData = $em->getRepository('DhiUserBundle:Banner')->find($data['id']);
				$objOrderData->setOrderNo($nextOrderNo);
				$em->persist($objOrderData);
			}
		}
		 $em->flush();
		$response = new Response(json_encode($result));

		$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

	function checkOrderNoAction(Request $request){

		$em = $this->getDoctrine()->getManager();

		$orderNo =  $request->get('orderno');
		$country =  $request->get('country');

		$getBannerData = $em->getRepository('DhiUserBundle:Banner')->checkExistOrderNo($country,$orderNo);
                
		$jsonResponse = Array();
		if($getBannerData){
                    $jsonResponse['status'] = 'error';
		} else {
                    $jsonResponse['status'] = 'success';
		}

		echo json_encode($jsonResponse);
		exit;

	}

	public function getOrderNoAction(Request $request){

		$id = $request->get('id');

		$em = $this->getDoctrine()->getManager();

		$country =  $request->get('country');


			$getBannerData = $em->getRepository('DhiUserBundle:Banner')->getOrderNo($country,$id);

		$response = new Response(json_encode($getBannerData));
		$response->headers->set('Content-Type', 'application/json');

        return $response;
	}

	  public function disableAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objBanner = $em->getRepository('DhiUserBundle:Banner')->find($id);

        if (!$objBanner) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find banner.");
            return $this->redirect($this->generateUrl('dhi_admin_banner_list'));
        }
		if($objBanner->getStatus() == 1){
			    $objBanner->setStatus(0);
				$changeStatus = 'Disabled';
		}	else{
				$objBanner->setStatus(1);
				$changeStatus = 'Enabled';

		}

                $em->persist($objBanner);
                $em->flush();


                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = $changeStatus.' Banner';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has ".$changeStatus." banner ";
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Banner '.$changeStatus.' successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_banner_list'));



    }


}
