<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\InAppPromoCode;
use Dhi\UserBundle\Entity\Service;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Form\Type\InAppPromoCodeFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use \DateTime;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InAppPromoCodeController extends Controller {

    public function indexAction(Request $request) {

        //	Check permission
        if (!($this->get('admin_permission')->checkPermission('In_App_promo_code_list') || $this->get('admin_permission')->checkPermission('In_App_promo_code_create') || $this->get('admin_permission')->checkPermission('In_App_promo_code_update') || $this->get('admin_permission')->checkPermission('In_App_promo_code_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view promo code list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        

        $serviceLocation = array();
        $serviceLocation = $em->getRepository('DhiAdminBundle:ServiceLocation')->getAllServiceLocation();

        $serviceLocationLog = array();
        foreach ($serviceLocation as $activity) {
            $serviceLocationLog[] = $activity['name'];
        }
        
        $IspRedeemed = array('0'=>'Yes','1'=>'No');
        $IspActive = array('0'=>'Active','1'=>'InActive');
        
        // get all admin 
        $objAdmins = $em->getRepository('DhiUserBundle:User')->getAllAdmin();

        return $this->render('DhiAdminBundle:InAppPromoCode:index.html.twig', array(
                    'serviceLocations' => json_encode($serviceLocationLog),
                    'ispredeemed' => json_encode($IspRedeemed),
                    'ispactive' => json_encode($IspActive),
                    'admins' => $objAdmins
        ));
    }

    //added for grid
    public function promoCodeListJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $promoCodeColumns = array('ServiceLocation', 'PromoCode','Amount', 'CreatedBy', 'expiredAt','Status', 'IsReedemed','Redeemed Date','Redeemed By', 'Note', 'Action');
        $admin = $this->get('security.context')->getToken()->getUser();
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($promoCodeColumns);
        
               
        $createdBy = $request->get('createdBy');
        if (!empty($gridData['search_data'])) {
            $this->get('session')->set('promoSearchData', $gridData['search_data']);
        } else {
            $this->get('session')->remove('promoSearchData');
        }

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'pc.id';
            $sortOrder = 'DESC';
        } else {

            if ($gridData['order_by'] == 'ServiceLocation') {

                $orderBy = 'sl.name';
            }
            if ($gridData['order_by'] == 'PromoCode') {

                $orderBy = 'pc.promoCode';
            }
            if ($gridData['order_by'] == 'expiredAt') {

                $orderBy = 'pc.expiredAt';
            }
            if ($gridData['order_by'] == 'Note') {

                $orderBy = 'pc.note';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data = $em->getRepository('DhiUserBundle:InAppPromoCode')->getPromoCodeGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $createdBy);

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
                    //$resultRow = $resultRowArray[0];

                    $actualNotes = $resultRow->getNote();
                    $shortNote = null;
                    if (strlen($actualNotes) > 10) {
                        $shortNote = substr($actualNotes, 0, 10) . '...';
                    } else {
                        $shortNote = $resultRow->getNote();
                    }
                  
                  $username = 'N/A';
                  if($resultRow->getCustomer()){
                   $username = '<a href="' . $this->generateUrl('dhi_admin_view_customer', array('id' => $resultRow->getCustomer()->getId())) . '">' . $resultRow->getCustomer()->getUsername() . '</a>';
                  }
                    $row = array();
                    $row[] = $resultRow->getServiceLocations() ? $resultRow->getServiceLocations()->getName() : 'N/A';
                    $row[] = $resultRow->getPromoCode();
                    $row[] = $resultRow->getAmount();
                    $row[] = $resultRow->getCreatedBy();
                    $row[] = $resultRow->getExpiredAt() ? $resultRow->getExpiredAt()->format('M-d-Y') : 'N/A';
                    $row[] = $resultRow->getStatus();
                    $row[] = $resultRow->getIsRedeemed();
                    $row[] = $username;
                    $row[] = $resultRow->getRedeemDate() ? $resultRow->getRedeemDate()->format('M-d-Y') : 'N/A';
                    $row[] = $shortNote;
                    $row[] = $resultRow->getId().'^'.$resultRow->getIsRedeemed().'^'.$resultRow->getStatus();
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
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPromoCode = new InAppPromoCode();

        $form = $this->createForm(new InAppPromoCodeFormType(), $objPromoCode);
       
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $formData = $form->getData();
                $serviceLocationsId = $request->get('dhi_admin_in_app_promo_code')['serviceLocations'];
                $objServiceLocations = $em->getRepository('DhiAdminBundle:ServiceLocation')->findOneBy(array('id' => $serviceLocationsId));


                $expiresAt = new DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));

                // generating code
                $characters = 'abcdefghijklmnopqrstuvwxyz0123456789';
                $string = '';
                $random_string_length = 5;
                for ($i = 0; $i < $random_string_length; $i++) {
                    $string .= $characters[rand(0, strlen($characters) - 1)];
                }
                // add new code
                $objPromoCode = new InAppPromoCode();
                $objPromoCode->setCreatedBy($admin->getUsername())
                        ->setPromoCode($string)
                        ->setNote($request->get('dhi_admin_in_app_promo_code')['note'])
                        ->setStatus($request->get('dhi_admin_in_app_promo_code')['status'])
                        ->setServiceLocations($objServiceLocations)
                        ->setAmount($request->get('dhi_admin_in_app_promo_code')['amount'])
                        ->setExpiredAt($expiresAt);

                $em->persist($objPromoCode);
                $em->flush();

                // set audit log add promo code
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Add In App Promo Code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has added promo code " . $string;
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'In App Promo Code added successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_in_app_promo_code_list'));
            }
        }

        return $this->render('DhiAdminBundle:InAppPromoCode:new.html.twig', array(
                    'form' => $form->createView(),
        ));
    }

    public function editInAppPromocodeAction(Request $request, $id) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:InAppPromoCode')->find($id);

        if (!$objPromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_in_app_promo_code_list'));
        }
       
        $form = $this->createForm(new InAppPromoCodeFormType(), $objPromoCode);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();
                $objPromoCode->setCreatedBy($admin->getUsername());
                
                $expiresAt = new DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));


                $objPromoCode->setExpiredAt($expiresAt);
                $em->persist($objPromoCode);
                $em->flush();

                // set audit log add email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Update In App Promo Code';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated promo code " . $formData->getPromoCode();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Promo Code updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_in_app_promo_code_list'));
            }
        }

        return $this->render('DhiAdminBundle:InAppPromoCode:edit.html.twig', array(
                    'form' => $form->createView(),
                    'promo' => $objPromoCode
        ));
    }

    public function disableAction(Request $request, $id) {

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:InAppPromoCode')->find($id);

        if (!$objPromoCode) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_promo_code_list'));
        }
        
        if ($objPromoCode->getStatus() == 'Active') {
            $objPromoCode->setStatus('Inactive');
            $changeStatus = 'Disabled';
        } else {
            $objPromoCode->setStatus('Active');
            $changeStatus = 'Enabled';
        }

        $em->persist($objPromoCode);
        $em->flush();

        // set audit log add email campagin
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = $changeStatus . ' In App Promo Code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " has " . $changeStatus . " promo code " . $objPromoCode->getPromoCode();
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $this->get('session')->getFlashBag()->add('success', 'Promo Code ' . $changeStatus . ' successfully.');
        return $this->redirect($this->generateUrl('dhi_admin_in_app_promo_code_list'));
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        //Check permission
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_delete')) {
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete promo code.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        $admin = $this->get('security.context')->getToken()->getUser();

        $em = $this->getDoctrine()->getManager();

        $objPromoCode = $em->getRepository('DhiUserBundle:InAppPromoCode')->find($id);
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete In App Promo Code';
        if ($objPromoCode) {

            // set audit log delete email campagin

            $activityLog['description'] = "Admin  " . $admin->getUsername() . " has deleted promo code " . $objPromoCode->getPromoCode();
            $this->get('ActivityLog')->saveActivityLog($activityLog);

            $em->remove($objPromoCode);
            $em->flush();
            $result = array('type' => 'success', 'message' => 'Promo code deleted successfully!');
        } else {

            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete promo code.";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $result = array('type' => 'danger', 'message' => 'You are not allowed to delete promo code!');
        }
        $response = new Response(json_encode($result));

        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function previewPromoCodeAction(Request $request) {

        $promocode = new InAppPromoCode();
        $form = $this->createForm(new InAppPromoCodeFormType(), $promocode);

        $data = array();
       
        if ($request->getMethod() == "POST") {
            $form->handleRequest($request);
            $data = $form->getData();
        }
        return $this->render('DhiAdminBundle:InAppPromoCode:previewPromoCode.html.twig', array('data' => $data));
    }

    public function exportpdfAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_export_pdf')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export pdf.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');
        $logoImgDirPath = $this->getRequest()->server->get('DOCUMENT_ROOT') . '/bundles/dhiuser/images/logo.png';

        $file_name = 'customer_promocode_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download
        //Get Purchase History Data
        $searchData = array();
        if ($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }


        $promoData = $em->getRepository('DhiUserBundle:InAppPromoCode')->getPdfPromoData($searchData);


        // Set audit log for export pdf purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export pdf In App promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export user promo code";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
        $html = '<style>' . $stylesheet . '</style>';
        $html .= $this->renderView('DhiAdminBundle:InAppPromoCode:exportPdf.html.twig', array(
            'promoData' => $promoData
        ));

        unset($promoData);
        return new Response(
                $this->get('knp_snappy.pdf')->getOutputFromHtml($html), 200, array(
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $file_name . '"'
                )
        );
    }

    public function exportCsvAction(Request $request) {
         
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_export_csv')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to export csv.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        $searchData = array();
        if ($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }
       
        //$objPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
        $promoData = $em->getRepository('DhiUserBundle:InAppPromoCode')->getPdfPromoData($searchData);

        
        // Set audit log for export csv purchase history
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Export csv In App promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export csv promo code";

        $this->get('ActivityLog')->saveActivityLog($activityLog);

        //$result = $query->getQuery()->getResult();

        $response = new StreamedResponse();
        $response->setCallback(function() use($promoData) {

            $handle = fopen('php://output', 'w+');

            // Add a row with the names of the columns for the CSV file
            fputcsv($handle, array("Service Location", "Services",  "Promo Code","Amount", "Created By", "Note", "Expiration Date", "Status", "Note", "Is Redeemed?"), ',');
            
            foreach ($promoData as $key => $resultRow) {
                                

                $serviceLocation = $resultRow->getServiceLocations() ? $resultRow->getServiceLocations()->getName() : '';
                $createdBy = $resultRow->getCreatedBy();
                $promoCode = $resultRow->getPromoCode();
                $amount = $resultRow->getAmount();
                $expiredAt = $resultRow->getExpiredAt()->format('M-d-Y');
               
                $status = $resultRow->getStatus();
                $note = $resultRow->getNote();
                $IsReedemed = $resultRow->getIsRedeemed();

                fputcsv($handle, array(
                    $serviceLocation,
                    $promoCode,
                    $amount,
                    $createdBy,
                    $note,
                    $expiredAt,
                    $status,
                    $note,
                    $IsReedemed
                        ), ',');
            }

            fclose($handle);
        });

        // create filename
        $file_name = 'In_APP_promo_code_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.csv'; // Create pdf file name for download
        // set header
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $file_name . '"');
        
       //  echo "hello"; die;
        return $response;
    }

    //get service based on service location
    public function getPromoServiceAction(Request $request) {

        $locatoinId = $request->get('locationId');

        $em = $this->getDoctrine()->getManager();

        $services = $em->getRepository('DhiAdminBundle:IpAddressZone')->getServicesByLocation($locatoinId);

        $response = new Response(json_encode($services));
        $response->headers->set('Content-Type', 'application/json');

        return $response;

    }

    public function printAction(Request $request) {

        //Check permission
        if (!$this->get('admin_permission')->checkPermission('In_App_promo_code_print')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to print promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $isSecure = $request->isSecure() ? 'https://' : 'http://';
        $rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
        $dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

        $searchData = array();
        if ($this->get('session')->has('promoSearchData') && $this->get('session')->get('promoSearchData') != '') {
            $searchData = $this->get('session')->get('promoSearchData');
        }
        $promoData = $em->getRepository('DhiUserBundle:InAppPromoCode')->getPdfPromoData($searchData);

        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Print promo code';
        $activityLog['description'] = "Admin " . $admin->getUsername() . " export print promo code";
        $this->get('ActivityLog')->saveActivityLog($activityLog);

        //  Rendering view for printing data
        return $this->render('DhiAdminBundle:InAppPromoCode:print.html.twig', array(
                    'promoData' => $promoData,
                    'img' => $dhiLogoImg
        ));
    }

}
