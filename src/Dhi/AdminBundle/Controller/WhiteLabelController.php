<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Form\Type\WhiteLabelFormType;
use Dhi\AdminBundle\Entity\WhiteLabel;

class WhiteLabelController extends Controller {
    
   public function indexAction(Request $request) {
       //Check Permission
        if (!($this->get('admin_permission')->checkPermission('admin_white_label_list') || $this->get('admin_permission')->checkPermission('admin_white_label_create') || $this->get('admin_permission')->checkPermission('admin_white_label_update') || $this->get('admin_permission')->checkPermission('admin_white_label_delete') || $this->get('admin_permission')->checkPermission('admin_white_label_status_change'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view White Label list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        return $this->render('DhiAdminBundle:Whitelabel:index.html.twig');
   }  
   
     //Added For Grid List
    public function whitelabelListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
        $WhiteLabelColumns = array('companyName','domain','fromEmail','Status'); 
        
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($WhiteLabelColumns);
       
        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'wl.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'Id') {

                $orderBy = 'wl.id';
            }

            if ($gridData['order_by'] == 'companyName') {

                $orderBy = 'wl.companyName';
            }

            if ($gridData['order_by'] == 'domain') {

                $orderBy = 'wl.domain';
            }
            
            if ($gridData['order_by'] == 'fromEmail') {

                $orderBy = 'wl.fromEmail';
            }
            if ($gridData['order_by'] == 'Status') {

                $orderBy = 'wl.status';
            }
        }
        
         // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiAdminBundle:WhiteLabel')->getBannerGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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

                    $row[] = $resultRow->getCompanyName();
                    $row[] = $resultRow->getDomain();
                    $row[] = $resultRow->getFromEmail();
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
        
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('admin_white_label_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add white label.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $whitelabel = new WhiteLabel();
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $form = $this->createForm(new WhiteLabelFormType(), $whitelabel);
        if ($request->getMethod() == "POST") {
            
            $formIsValid = true;
            $formData = $form->getData();
            $form->handleRequest($request);
            
            $hlogoimage = $whitelabel->getHeaderLogo();
            $flogoimage = $whitelabel->getFooterLogo();
            $bbimage    = $whitelabel->getBrandingBanner();
            $bckimage   = $whitelabel->getBackgroundimage();
            $bbipimage  = $whitelabel->getBrandingBannerInnerPage();
            $favimage   = $whitelabel->getFavicon();
            
            if(empty($hlogoimage) || empty($flogoimage) || empty($bbimage) || empty($bckimage) || empty($bbipimage) || empty($favimage)){
                $this->get('session')->getFlashBag()->add('danger', 'Please select required images.');
                $formIsValid = false;
            }
            
            $companynameexits = $em->getRepository('DhiAdminBundle:WhiteLabel')->findoneBy(array('companyName'=>$formData->getCompanyName(), 'isDeleted'=>0));
            if($companynameexits && $formIsValid){
                $this->get('session')->getFlashBag()->add('danger', 'Site name already exists.');
                $formIsValid = false;
            }
                
            $companydomainexits = $em->getRepository('DhiAdminBundle:WhiteLabel')->findoneBy(array('domain'=>$formData->getDomain(),'isDeleted'=>0));
            if($companydomainexits && $formIsValid){
                $this->get('session')->getFlashBag()->add('danger', 'Site domain already exists.');
                $formIsValid = false;
            }
            
            if ($form->isValid() &&  $formIsValid) {
                
                //Header Logo
                $headerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/headerlogo';
                if($whitelabel->getHeaderLogo()) {
                    $headerfile = $whitelabel->getHeaderLogo();
                    $headerfileName = md5(uniqid()).'.'.$headerfile->guessExtension();
                    
                    $headerfile->move($headerlogoDir, $headerfileName);
                    $whitelabel->setHeaderLogo($headerfileName);
                }
                
                //Footer Logo
                $footerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/footerlogo';
                if($whitelabel->getFooterLogo()) {
                    $footerfile = $whitelabel->getFooterLogo();
                    $footerfileName = md5(uniqid()).'.'.$footerfile->guessExtension();
                    $footerfile->move($footerlogoDir, $footerfileName);
                    $whitelabel->setFooterLogo($footerfileName);
                }
                
                //Branding Banner
                $brandingbannerDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbanner';
                if($whitelabel->getBrandingBanner()) {
                    $brandingbannerfile = $whitelabel->getBrandingBanner();
                    $brandingbannerfileName = md5(uniqid()).'.'.$brandingbannerfile->guessExtension();
                    $brandingbannerfile->move($brandingbannerDir, $brandingbannerfileName);
                    $whitelabel->setBrandingBanner($brandingbannerfileName);
                }
                
                //Branding banner support page
                /*
                $brandingbannersupportpageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbannersupportpage';
                if($whitelabel->getBrandingBannerSupportPage()) {
                    $brandingbannersupportpagefile = $whitelabel->getBrandingBannerSupportPage();
                    $brandingbannersupportpagefileName = md5(uniqid()).'.'.$brandingbannersupportpagefile->guessExtension();
                    $brandingbannersupportpagefile->move($brandingbannersupportpageDir, $brandingbannersupportpagefileName);
                    $whitelabel->setBrandingBannerSupportPage($brandingbannersupportpagefileName);
                }
                */
                
                //branding banner inner page
                $brandingbannerinnerpageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbannerinnerpage';
                if($whitelabel->getBrandingBannerInnerPage()) {
                    $brandingbannerinnerpagefile = $whitelabel->getBrandingBannerInnerPage();
                    $brandingbannerinnerpagefileName = md5(uniqid()).'.'.$brandingbannerinnerpagefile->guessExtension();
                    $brandingbannerinnerpagefile->move($brandingbannerinnerpageDir, $brandingbannerinnerpagefileName);
                    $whitelabel->setBrandingBannerInnerPage($brandingbannerinnerpagefileName);
                }
                
                
                //favicon
                $faviconDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/favicon';
                if($whitelabel->getFavicon()) {
                    $faviconfile = $whitelabel->getFavicon();
                    $faviconfileName = md5(uniqid()).'.'.$faviconfile->guessExtension();
                    $faviconfile->move($faviconDir, $faviconfileName);
                    $whitelabel->setFavicon($faviconfileName);
                }
                
                //Background Image
                $backgroundimageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/backgroundimage';
                if($whitelabel->getBackgroundimage()) {
                    $backgroundimagefile = $whitelabel->getBackgroundimage();
                    $backgroundimagefileName = md5(uniqid()).'.'.$backgroundimagefile->guessExtension();
                    $backgroundimagefile->move($backgroundimageDir, $backgroundimagefileName);
                    $whitelabel->setBackgroundimage($backgroundimagefileName);
                }
                
                $whitelabel->setCreatedBy($admin->getId());
                $em->persist($whitelabel);
                $em->flush();

                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add Site';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has added site: " . $whitelabel->getCompanyName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', 'Site added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:Whitelabel:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }
    
    
    public function editAction(Request $request, $id) {
        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('admin_white_label_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update site.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        
        $objWhiteLabel = $em->getRepository('DhiAdminBundle:WhiteLabel')->findOneBy(array('id' => $id, 'isDeleted' => 0));
        if (!$objWhiteLabel) {
            $this->get('session')->getFlashBag()->add('failure', "Unable to find site.");
            return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));
        }
        $companyname   = $objWhiteLabel->getCompanyName();
        $companydomain = $objWhiteLabel->getDomain();
        
        if($objWhiteLabel->getHeaderLogo()){
           $oldHeaderLogoPath = $objWhiteLabel->getHeaderLogo();
        }
        
        if($objWhiteLabel->getFooterLogo()){
           $oldFooterLogoPath = $objWhiteLabel->getFooterLogo();
        }
        
        if($objWhiteLabel->getBrandingBanner()){
           $oldBrandingBannerPath = $objWhiteLabel->getBrandingBanner();
        }

        if($objWhiteLabel->getBrandingBannerInnerPage()){
           $oldBrandingBannerInnerpagePath = $objWhiteLabel->getBrandingBannerInnerPage();
        }
        
        if($objWhiteLabel->getFavicon()){
           $oldfaviconPath = $objWhiteLabel->getFavicon();
        }
        
        if($objWhiteLabel->getBackgroundimage()){
           $oldbackgroundimage = $objWhiteLabel->getBackgroundimage();
        }
        
        
        
         $form = $this->createForm(new WhiteLabelFormType(), $objWhiteLabel);
         
         if ($request->getMethod() == "POST") {
             
            $form->handleRequest($request);
            $getfrmdata = $request->get('dhi_admin_white_label');
            $formData = $form->getData();
            
            $formIsValid = true;
            
            if(($formData->getHeaderLogo() == null && empty($oldHeaderLogoPath)) || ($formData->getFooterLogo() == null && empty($oldFooterLogoPath)) || ($formData->getBrandingBanner() == null && empty($oldBrandingBannerPath)) || ($formData->getBrandingBannerInnerPage() == null && empty($oldBrandingBannerInnerpagePath)) || ($formData->getFavicon() == null && empty($oldfaviconPath)) || ($formData->getBackgroundimage() == null && empty($oldbackgroundimage))){
                $this->get('session')->getFlashBag()->add('danger', 'Please select required image(s).');
                $formIsValid = false;
            }
            
            $objServiceLocationWiseSite = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findBy(array('whiteLabel' => $id, 'isDeleted' => 0));
            if ($objServiceLocationWiseSite && $objWhiteLabel->getStatus() == 0 && $formIsValid) {
                $this->get('session')->getFlashBag()->add('danger','You cannot deactivate the site as it is already assigned to one or more service location.');
                $formIsValid = false;
            }
            
            $companynameexits = $em->getRepository('DhiAdminBundle:WhiteLabel')->findoneBy(array('companyName'=> $formData->getCompanyName(), 'isDeleted'=> 0));
            if($companynameexits && $companyname != $getfrmdata['companyName'] && $formIsValid){
                $this->get('session')->getFlashBag()->add('danger', 'Site name already exists.');
                $formIsValid = false;
            }
            
            $companydomainexits = $em->getRepository('DhiAdminBundle:WhiteLabel')->findoneBy(array('domain' => $formData->getDomain(), 'isDeleted'=> 0));
            if($companydomainexits && $companydomain != $getfrmdata['domain'] && $formIsValid){
                $this->get('session')->getFlashBag()->add('danger', 'Site domain already exists.');
                $formIsValid = false;
                
            }
            if(!$formIsValid){
                
                return $this->redirect($this->generateUrl('dhi_admin_white_label_edit', array('id' => $id)));
            }
            
            if ($form->isValid() && $formIsValid) {
                
               //Header Logo
                $headerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/headerlogo';

                if($formData->getHeaderLogo() !== null && !empty($oldHeaderLogoPath) ){
                        $this->removeFile($oldHeaderLogoPath,'HeadeLogo'); // remove old file, see this at the bottom
                        $headerfile = $formData->getHeaderLogo();
                        $headerfileName = md5(uniqid()).'.'.$headerfile->guessExtension();
                        $headerfile->move($headerlogoDir, $headerfileName);
                        $objWhiteLabel->setHeaderLogo($headerfileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
                } else if(!empty($oldHeaderLogoPath)) {
                        $objWhiteLabel->setHeaderLogo($oldHeaderLogoPath);
                }else if($formData->getHeaderLogo() !== null){
                        $headerfile = $formData->getHeaderLogo();
                        $headerfileName = md5(uniqid()).'.'.$headerfile->guessExtension();
                        $headerfile->move($headerlogoDir, $headerfileName);
                        $objWhiteLabel->setHeaderLogo($headerfileName);
                }
                
                //Footer Logo
                $footerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/footerlogo';

                if($formData->getFooterLogo() !== null && !empty($oldFooterLogoPath) ){
                        $this->removeFile($oldFooterLogoPath,'FooterLogo'); // remove old file, see this at the bottom
                        $footerfile = $formData->getFooterLogo();
                        $footerfileName = md5(uniqid()).'.'.$footerfile->guessExtension();
                        $footerfile->move($footerlogoDir, $footerfileName);
                        $objWhiteLabel->setFooterLogo($footerfileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
                } else if(!empty($oldFooterLogoPath)) {
                        $objWhiteLabel->setFooterLogo($oldFooterLogoPath);
                }else if($formData->getFooterLogo() !== null){
                        $footerfile = $formData->getFooterLogo();
                        $footerfileName = md5(uniqid()).'.'.$footerfile->guessExtension();
                        $footerfile->move($footerlogoDir, $footerfileName);
                        $objWhiteLabel->setFooterLogo($footerfileName);
                }
                
                //Branding Banner
               $brandingbannerDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbanner';

                if($formData->getBrandingBanner() !== null && !empty($oldBrandingBannerPath) ){
                        $this->removeFile($oldBrandingBannerPath,'BrandingBanner'); // remove old file, see this at the bottom
                        $brandingbannerfile = $formData->getBrandingBanner();
                        $brandingbannersupportpagefileName = md5(uniqid()).'.'.$brandingbannerfile->guessExtension();
                        $brandingbannerfile->move($brandingbannerDir, $brandingbannersupportpagefileName);
                        $objWhiteLabel->setBrandingBanner($brandingbannersupportpagefileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
                } else if(!empty($oldBrandingBannerPath)) {
                        $objWhiteLabel->setBrandingBanner($oldBrandingBannerPath);
                }else if($formData->getBrandingBanner() !== null){
                        $brandingbannerfile = $formData->getBrandingBanner();
                        $brandingbannersupportpagefileName = md5(uniqid()).'.'.$brandingbannerfile->guessExtension();
                        $brandingbannerfile->move($brandingbannerDir, $brandingbannersupportpagefileName);
                        $objWhiteLabel->setBrandingBanner($brandingbannersupportpagefileName);
                }

                //Branding Banner Inner page
                $brandingbannerinnerpageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbannerinnerpage';

                if($formData->getBrandingBannerInnerPage() !== null && !empty($oldBrandingBannerInnerpagePath) ){
                    $this->removeFile($oldBrandingBannerInnerpagePath,'BrandingBannerInnerPage'); // remove old file, see this at the bottom
                    $brandingbannerinnerpagefile = $formData->getBrandingBannerInnerPage();
                    $brandingbannerinnerpagefileName = md5(uniqid()).'.'.$brandingbannerinnerpagefile->guessExtension();
                    $brandingbannerinnerpagefile->move($brandingbannerinnerpageDir, $brandingbannerinnerpagefileName);
                    $objWhiteLabel->setBrandingBannerInnerPage($brandingbannerinnerpagefileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.

                } else if(!empty($oldBrandingBannerInnerpagePath)) {
                    $objWhiteLabel->setBrandingBannerInnerPage($oldBrandingBannerInnerpagePath);

                } else if($formData->getBrandingBannerInnerPage() !== null){
                    $brandingbannerinnerpagefile = $formData->getBrandingBannerInnerPage();
                    $brandingbannerinnerpagefileName = md5(uniqid()).'.'.$brandingbannerinnerpagefile->guessExtension();
                    $brandingbannerinnerpagefile->move($brandingbannerinnerpageDir, $brandingbannerinnerpagefileName);
                    $objWhiteLabel->setBrandingBannerInnerPage($brandingbannerinnerpagefileName);

                }
                
                //Favicon
                $faviconDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/favicon';

                if($formData->getFavicon() !== null && !empty($oldfaviconPath) ){
                        $this->removeFile($oldfaviconPath,'Favicon'); // remove old file, see this at the bottom
                        $faviconfile = $formData->getFavicon();
                        $faviconfileName = md5(uniqid()).'.'.$faviconfile->guessExtension();
                        $faviconfile->move($faviconDir, $faviconfileName);
                        $objWhiteLabel->setFavicon($faviconfileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
                } else if(!empty($oldfaviconPath)) {
                        $objWhiteLabel->setFavicon($oldfaviconPath);
                }else if($formData->getFavicon() !== null){
                        $faviconfile = $formData->getFavicon();
                        $faviconfileName = md5(uniqid()).'.'.$faviconfile->guessExtension();
                        $faviconfile->move($faviconDir, $faviconfileName);
                        $objWhiteLabel->setFavicon($faviconfileName);
                }
                
                
                //Background Image
                $backgroundimageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/backgroundimage';

                if($formData->getBackgroundimage() !== null && !empty($oldbackgroundimage) ){
                        $this->removeFile($oldbackgroundimage,'BackgroundImage'); // remove old file, see this at the bottom
                        $backgroundimagefile = $formData->getBackgroundimage();
                        $backgroundimagefileName = md5(uniqid()).'.'.$backgroundimagefile->guessExtension();
                        $backgroundimagefile->move($backgroundimageDir, $backgroundimagefileName);
                        $objWhiteLabel->setBackgroundimage($backgroundimagefileName); // set Image Path because preUpload and upload method will not be called if any doctrine entity will not be changed. It tooks me long time to learn it too.
                } else if(!empty($oldbackgroundimage)) {
                        $objWhiteLabel->setBackgroundimage($oldbackgroundimage);
                }else if($formData->getBackgroundimage() !== null){
                        $backgroundimagefile = $formData->getBackgroundimage();
                        $backgroundimagefileName = md5(uniqid()).'.'.$backgroundimagefile->guessExtension();
                        $backgroundimagefile->move($backgroundimageDir, $backgroundimagefileName);
                        $objWhiteLabel->setBackgroundimage($backgroundimagefileName);
                }
                

                $objWhiteLabel->setUpdatedBy($admin->getId());
                $em->persist($objWhiteLabel);
                $em->flush();


                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Site';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated site: " . $objWhiteLabel->getCompanyName();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Site updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));
               
            } 
         }
         
         return $this->render('DhiAdminBundle:Whitelabel:edit.html.twig', array(
                    'form' => $form->createView(),
                    'whitelabel' => $objWhiteLabel,
                    'siteId'=>$id
        ));
    }
    
    public function removeFile($file, $type) {

        //Header Logo
        if ($type == 'HeadeLogo') {
            $headerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/headerlogo';
            $headerlogofile_path = $headerlogoDir . '/' . $file;
            if (file_exists($headerlogofile_path)) {
                unlink($headerlogofile_path);
            }
        }

        //Footer Logo
        if ($type == 'FooterLogo') {
            $footerlogoDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/footerlogo';
            $footerlogofile_path = $footerlogoDir . '/' . $file;
            if (file_exists($footerlogofile_path)) {
                unlink($footerlogofile_path);
            }
        }

        //Branding Banner
        if ($type == 'BrandingBanner') {
            $brandingbannerDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbanner';
            $brandingbannerfile_path = $brandingbannerDir . '/' . $file;
            if (file_exists($brandingbannerfile_path)) {
                unlink($brandingbannerfile_path);
            }
        }

        //Branding Banner Inner Page
        if ($type == 'BrandingBannerInnerPage') {
            $brandingbannerinnerpageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/brandingbannerinnerpage';
            $brandingbannerinnerpagefile_path = $brandingbannerinnerpageDir . '/' . $file;
            if (file_exists($brandingbannerinnerpagefile_path)) {
                unlink($brandingbannerinnerpagefile_path);
            }
        }
        
         //FavIcon
        if ($type == 'Favicon') {
            $faviconDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/favicon';
            $faviconfile_path = $faviconDir . '/' . $file;
            if (file_exists($faviconfile_path)) {
                unlink($faviconfile_path);
            }
        }
        
         //Background Image
        if ($type == 'BackgroundImage') {
            $backgroundimageDir = $this->container->getParameter('kernel.root_dir').'/../web/uploads/whitelabel/backgroundimage';
            $backgroundimagefile_path = $backgroundimageDir . '/' . $file;
            if (file_exists($backgroundimagefile_path)) {
                unlink($backgroundimagefile_path);
            }
        }
    }

    public function disableAction(Request $request, $id) {

        //Check Permission
        if(! $this->get('admin_permission')->checkPermission('admin_white_label_status_change')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to change status of site.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
         
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objWhiteLabel = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($id);
        
        
        if (!$objWhiteLabel) {

            $this->get('session')->getFlashBag()->add('failure', "Site does not exists.");
            return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));
        }
        
        $objServiceLocationWiseSite = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findBy(array('whiteLabel' => $id, 'isDeleted' => 0));
        if($objServiceLocationWiseSite && $objWhiteLabel->getStatus() == 1){
            $this->get('session')->getFlashBag()->add('danger','You cannot deactivate the site as it is already assigned to one or more service location.');
            return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));
        }
        
        if ($objWhiteLabel->getStatus() == 1) {
            $objWhiteLabel->setStatus(0);
            $changeStatus = 'InActive';
        } else {
            $objWhiteLabel->setStatus(1);
            $changeStatus = 'Active';
        }

        $em->persist($objWhiteLabel);
        $em->flush();


        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = $changeStatus.' Site';
        $activityLog['description'] = "Admin ".$admin->getUsername()." has ".$changeStatus." site: " . $objWhiteLabel->getCompanyName();
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $this->get('session')->getFlashBag()->add('success', 'Site '.$changeStatus.' successfully.');
        return $this->redirect($this->generateUrl('dhi_admin_white_label_list'));

    }
    
    public function deleteAction(Request $request) {
          
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('admin_white_label_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete a site.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $id = $request->get('id');
        
        $objWhiteLabel = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($id);
        
        $objServiceLocationWiseSite = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findBy(array('whiteLabel' => $id, 'isDeleted' => 0));
        if($objServiceLocationWiseSite){
            $result = array('type' => 'danger', 'message' => 'You cannot delete this site because it is assigned to service location.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete White Label';
        if ($objWhiteLabel) {

            $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted site: " . $objWhiteLabel->getCompanyName();
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            
            $objWhiteLabel->setIsDeleted(1);
            $em->persist($objWhiteLabel);
            $em->flush();
    	    $result = array('type' => 'success', 'message' => 'Site deleted successfully!');

        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete site";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete a site!');
        }

		
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    public function delteimagefileAction(Request $request){
        
        $imagefile = $request->get('imagefile');
        $ntype = $request->get('imagetype');
        $siteId = $request->get('siteid');
        
         $em = $this->getDoctrine()->getManager();
         $objWhiteLabel = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($siteId);
         if($objWhiteLabel){
             $this->removeFile($imagefile,$ntype);
             if($ntype=='HeadeLogo'){
                 $objWhiteLabel->setHeaderLogo('');
             }
             if($ntype=='FooterLogo'){
                 $objWhiteLabel->setFooterLogo('');
             }
             if($ntype=='BrandingBanner'){
                 $objWhiteLabel->setBrandingBanner('');
             }
             if($ntype=='BrandingBannerInnerPage'){
                 $objWhiteLabel->setBrandingBannerInnerPage('');
             }
             if($ntype=='Favicon'){
                 $objWhiteLabel->setFavicon('');
             }
             if($ntype=='BackgroundImage'){
                 $objWhiteLabel->setBackgroundimage('');
             }
             $em->persist($objWhiteLabel);
             $em->flush();

            $this->get('session')->getFlashBag()->add('success', "Image has been removed successfully!");
            $result = array('type' => 'success', 'message' => 'Image has been removed successfully!');
         }
        
        
        $response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
        
    }

    public function checkSiteAction(Request $request){
        $params   = $request->get('dhi_admin_white_label');
        $id       = $request->get('id');
        
        $em       = $this->getDoctrine()->getManager();
        $response = "true";
        if (!empty($params['companyName']) || !empty($params['domain'])) {

            $condition = array();
            if (!empty($params['companyName'])) {
                $condition["companyName"] = $params['companyName'];
            }else if (!empty($params['domain'])) {
                $condition["domain"] = $params['domain'];
            }

            $site = $em->getRepository("DhiAdminBundle:Whitelabel")->findoneBy($condition);
            if ($site) {
                if (!empty($id)) {
                    if ($site->getId() != $id) {
                        $response = "false";
                    }
                }else{
                    $response = "false";
                }
            }
        }

        $response = new Response($response);
        return $response;
    }

}