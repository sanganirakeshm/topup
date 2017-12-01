<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\UserBundle\Entity\EmailCampaign;
use Dhi\AdminBundle\Form\Type\EmailCampaignSearchFormType;
use Dhi\AdminBundle\Form\Type\EmailCampaignFormType;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Form\Type\SurveyMonkeyMailFormType;
class SurveyMonkeyMailController extends Controller {

    public function indexAction(Request $request) {

        //Check permission
        if (!($this->get('admin_permission')->checkPermission('survey_monkey_mail_list') ||  $this->get('admin_permission')->checkPermission('survey_monkey_mail_update')  )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view survey monkey mail list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        return $this->render('DhiAdminBundle:SurveyMonkeyMail:index.html.twig');
    }

    public function surveyMonkeyMailListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {

        $emailCompaignColumns = array('Id','Subject','Status');

        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($emailCompaignColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];

        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

            $orderBy = 'smm.id';
            $sortOrder = 'DESC';
        } else {

             if ($gridData['order_by'] == 'Id') {

                $orderBy = 'smm.id';
            }

            if ($gridData['order_by'] == 'Subject') {

                $orderBy = 'smm.subject';
            }
            if ($gridData['order_by'] == 'Status') {

                $orderBy = 'smm.emailStatus';
            }
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();

        $data  = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->getEmailCampaignGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

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

                    $count = 1;
                    
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = $resultRow->getSubject();
                    $row[] = '<span class="btn btn-success btn-sm service">'.$resultRow->getEmailStatus().'</span>';
                    $row[] = $resultRow->getId().'^'.$flagDelete;
                    $output['aaData'][] = $row;
                }
            }
        }

        $response = new Response(json_encode($output));
	$response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(! $this->get('admin_permission')->checkPermission('survey_monkey_mail_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update survey monkey mail.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();

        
        $objSurveyMonkeyMail = $em->getRepository('DhiUserBundle:SurveyMonkeyMail')->find($id);

        if (!$objSurveyMonkeyMail) {

            $this->get('session')->getFlashBag()->add('failure', "Unable to find email campaign.");
            return $this->redirect($this->generateUrl('dhi_admin_email_campaign_list'));
        }

        $form = $this->createForm(new SurveyMonkeyMailFormType(), $objSurveyMonkeyMail);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {

                $formData = $form->getData();

                $objSurveyMonkeyMail->setSubject($formData->getSubject());
                $objSurveyMonkeyMail->setMessage($formData->getMessage());
                $em->persist($objSurveyMonkeyMail);
                $em->flush();

                // set audit log update email campagin
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Survey Monkey Email';
                $activityLog['description'] = "Admin ".$admin->getUsername()." has updated Survey Monkey Mail".$formData->getSubject();
                $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', 'Survey Monkey Mail updated successfully.');
                return $this->redirect($this->generateUrl('dhi_admin_survey_monkey_mail_list'));
            }
        }

        return $this->render('DhiAdminBundle:SurveyMonkeyMail:edit.html.twig', array(
                    'form' => $form->createView(),
                    'email' => $objSurveyMonkeyMail
        ));
    }

}
