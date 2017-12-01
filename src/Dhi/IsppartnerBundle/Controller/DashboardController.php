<?php

namespace Dhi\IsppartnerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\ServicePartner;
use Dhi\AdminBundle\Entity\PartnerPromoCodes;
use Dhi\UserBundle\Entity\UserService;
use Dhi\AdminBundle\Form\Type\ServicePartnerFormType;
use Dhi\IsppartnerBundle\Form\Type\PartnerPromoCodeFormType;
use Dhi\AdminBundle\Entity\PartnerPromoCodeBatch;
use Dhi\AdminBundle\Form\Type\PartnerPromoCodeBatchFormType;
use \DateTime;

class DashboardController extends Controller {

    public function indexAction() {
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        $user['id'] = $this->container->get('request')->getSession()->get('service_partner_id');
        $user['username'] = $this->container->get('request')->getSession()->get('service_partner_username');
        $user['name'] = $this->container->get('request')->getSession()->get('service_partner_name');

        //$em = $this->getDoctrine()->getManager();
        $objServicePartner = new ServicePartner();
        $form = $this->createForm(new ServicePartnerFormType($admin, null), $objServicePartner);
        if (!empty($user['islogin'])) {
            return $this->render('DhiIsppartnerBundle:Dashboard:index.html.twig', array("user" => $user, 'form' => $form->createView()));
        } else {
            return $this->redirect($this->generateUrl('isppartner_login'));
        }
    }

    public function searchAction(Request $request) {
        $user['id'] = $this->container->get('request')->getSession()->get('service_partner_id');
        $partner_promocode = $request->get('promocode');

        $em = $this->getDoctrine()->getManager();
        $data = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findOneBy(array('code' => $partner_promocode));
        
        $resp = array();
       
            if (count($data) > 0) {
                $codePartnerId = $data->getBatchId()->getPartner()->getId();
                
                 if($codePartnerId == $user['id']){
                    //search if redeemed
                    $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy(array('discountedPartnerPromocode' => $data->getId()));
                    if (count($objServicePurchase) > 0) {
                        $resp['discount_code_applied'] = $objServicePurchase->getDiscountCodeApplied();
                        $resp['promo_code_applied'] = $objServicePurchase->getPromoCodeApplied();
                    }
                    $resp['count'] = count($data);
                    $resp['name'] = $data->getCode();
                    $resp['promo_id'] = $data->getId();
                    $resp['batch_id'] = $data->getBatchId()->getId();
                    $resp['status'] = $data->getStatus();
                    $resp['creation_date'] = $data->getCreatedAt()->format("M-d-Y");
                    $resp['expiration_date'] = $data->getExpirydate()?$data->getExpirydate()->format("M-d-Y"):"N/A";
                    $resp['duration'] = $data->getDuration() . " Hour(s)";
                    $resp['isredeemed'] = $data->getIsRedeemed();
                    $resp['flag'] = true;
                }else{
                    $resp['flag'] = false;
                }
            } else {
                $resp['count'] = count($data);
                $resp['name'] = "no code found";
                $resp['flag'] = false;
            }
       
        $response = new Response(json_encode($resp));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    public function editCodeAction(Request $request, $batchId, $codeId) {
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        $user['id'] = $this->container->get('request')->getSession()->get('service_partner_id');
        $user['username'] = $this->container->get('request')->getSession()->get('service_partner_username');
        $user['name'] = $this->container->get('request')->getSession()->get('service_partner_name');

        if (empty($user['islogin'])) {
            return $this->redirect($this->generateUrl('isppartner_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $objPartnerPromoCodeBatch = $em->getRepository('DhiAdminBundle:PartnerPromoCodeBatch')->find($batchId);
        $objPartnerPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($codeId);

        
        //if not found 
        if (!$objPartnerPromoCode || !$objPartnerPromoCodeBatch) {
            $this->get('session')->getFlashBag()->add('danger', "No partner promo code found.");
            return $this->redirect($this->generateUrl('dhi_isppartner_dashboard'));
        }
        
        if ($objPartnerPromoCode->getIsRedeemed() == 'Yes') {
            $this->get('session')->getFlashBag()->add('failure', "Access denied!");
            return $this->redirect($this->generateUrl('isppartner_login'));
        }

        //service partner
        $objPartner = $objPartnerPromoCodeBatch->getPartner();
        if ($objPartner) {
            $serviceName = $objPartner->getService()->getName();
        }

        $packages = array();
        $objPartnerPromoCodeBatch->setReason('');
        $form_batch = $this->createForm(new PartnerPromoCodeBatchFormType(), $objPartnerPromoCodeBatch);
        $form_code = $this->createForm(new PartnerPromoCodeFormType($packages), $objPartnerPromoCode);

        $form_batch->remove('partner');
        $form_batch->remove('noOfCodes');
        $form_batch->remove('batchName');
        $form_code->remove('partnerValue');
        $form_code->remove('customerValue');
        $form_code->remove('serviceLocations');
        $form_code->remove('packageId');
        $form_code->remove('duration');
        $form_batch->remove('note');
        //TODO poth method

        if ($request->getMethod() == 'POST') {
            $form_batch->handleRequest($request);
            $form_code->handleRequest($request);
            if ($form_code->isValid()) {
                //update db
                $em->persist($objPartnerPromoCodeBatch);
                $em->flush();
                $em->persist($objPartnerPromoCode);
                $em->flush();

                $batchName = $objPartnerPromoCodeBatch->getBatchName();
                $code = $objPartnerPromoCode->getCode();
                $reason = $objPartnerPromoCodeBatch->getReason();
                $activityLog = array();
                $activityLog['ispuser'] = $user['username'];
                $activityLog['activity'] = 'Update partner Promo code';
//                $activityLog['description'] = "Admin " . $admin->getUsername() . " update partner promo code. Batch  Name: ".$batchName. ". Code: " .$code.". Reason: ".$reason;
                $activityLog['description'] = "ISP Partner  " . $user['name'] . " update partner promo code. Batch  Name: " . $batchName . ". Code: " . $code . ". Reason: " . $reason;
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                $this->get('session')->getFlashBag()->add('success', " Promo code <b>$code</b> updated successfully.");
                return $this->redirect($this->generateUrl('dhi_isppartner_dashboard'));
            }
        }

        $partnerId = $objPartnerPromoCodeBatch->getPartner()->getId();
        return $this->render('DhiIsppartnerBundle:Dashboard:editCode.html.twig', array(
                    'form_code' => $form_code->createView(),
                    'form_batch' => $form_batch->createView(),
                    'partnerId' => $partnerId,
                    'user' => $user,
        ));
    }

    public function deactivateAction(Request $request) {
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        $user['id'] = $this->container->get('request')->getSession()->get('service_partner_id');
        $user['username'] = $this->container->get('request')->getSession()->get('service_partner_username');
        $user['name'] = $this->container->get('request')->getSession()->get('service_partner_name');

        $promocodeid = $request->get('promocodeid');
        $candelete = $request->get('candelete');
        $isRedeemedValue = $request->get('isRedeemedValue');
        $em = $this->getDoctrine()->getManager();
        $objPartnerPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($promocodeid);
        $code = $objPartnerPromoCode->getCode();
        $resp = array();
        $code_apply_discount = $request->get('discodeapl');
        $code_apply_special_plan = $request->get('procodeapl');
        
        // if not redeemed yet 
        if($isRedeemedValue == 'No'){
            if($candelete == 'yes'){
                
                //Activity log 
                $activityLog = array();
                $activityLog['ispuser'] = $user['username'];
                $activityLog['activity'] = 'Deactivated partner promo code';
                $activityLog['description'] = "ISP Partner  " . $user['name'] . " Deactivated partner promo code.  Code: " . $code . ".";
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                // deactivate the  promo code which is no redeemed 
                $objPartnerPromoCode->setStatus('Inactive');
                $em->persist($objPartnerPromoCode);
                $em->flush();
                $this->get('session')->getFlashBag()->add('success', " Code <b>$code</b> has been deactivated and can not be used by a customer.");
                 $resp['type'] = 'success';
                    $resp['action'] = 'deactivated';
                    $response = new Response(json_encode($resp));
                    $response->headers->set('Content-Type', 'application/json');

                    return $response;
            }else{
                
                $this->get('session')->getFlashBag()->add('success', " You have chosen not to deactivate code <b>$code</b>. Please note this code is still available for use by a customer.");
                $resp['type'] = 'success';
                $resp['action'] = 'deactivated';
                $response = new Response(json_encode($resp));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
            
        }
        
        
        
        
        if ($code_apply_discount == 2) {
            $this->get('session')->getFlashBag()->add('success', " The customer has used code <b>$code</b> for a discount off of another IPTV purchase with ExchangeVUE. Please let the customer know they need to contact ExchangeVUE Support and submit a ticket to have the IPTV portion refunded.");
            $resp['type'] = 'success';
        } else {
            if ($candelete == 'yes') {
//                if ($code_apply_special_plan == 2) {
                    
                    // get service purchase details
                    $objServicePurchase = $em->getRepository('DhiServiceBundle:ServicePurchase')->findOneBy(array('discountedPartnerPromocode' => $promocodeid));
                    
                    if(!$objServicePurchase){
                        $this->get('session')->getFlashBag()->add('danger', "Purchase data not found.");
                        $resp['type'] = 'fail';
                        $resp['action'] = 'deactivated';
                        $response = new Response(json_encode($resp));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    
                    $servicePurchaseId = $objServicePurchase->getId();
                    
                    //Inactive IPTV Package from Selevision (unsetCustomerOffer)
                    $userName = $objServicePurchase->getUser()->getUserName();
                    $packageId = $objServicePurchase->getPackageId();
                    
                    $inActiveSuccess = false;
                    $wsParam = array();
                    $wsParam['cuLogin'] = $userName;
                    $wsParam['offer'] = $packageId;

                    $selevisionService = $this->get('selevisionService');
                    $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer', $wsParam);

                    if (isset($wsResponse['status']) && !empty($wsResponse['status'])) {

                        if ($wsResponse['status'] == 1) {

                            $inActiveSuccess = true;
                        }
                    }
                    
                    if(!$inActiveSuccess){
                        $this->get('session')->getFlashBag()->add('danger', "Chould not deactivate this service. Please try again.");
                        $resp['type'] = 'fail';
                        $resp['action'] = 'deactivated';
                        $response = new Response(json_encode($resp));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //get user uservice details
                    $objUserService = $objServicePurchase->getUserService();
                    
                    $objservicePartner = $em->getRepository('DhiAdminBundle:ServicePartner')->find($user['id']); 
                    
                    $temExpDate = new \DateTime();
                    $DeActivatedAt = $temExpDate->format('Y-m-d H:i:s');
                    $objUserService->setRefund(1);
                    $objUserService->setRefundAmount(0);
                    $objUserService->setStatus(0);
                    $objUserService->setDeActivatedAt($temExpDate);
                    $objUserService->setDeActivatedBy($objservicePartner);
                    $objUserService->setRefundedBy($objservicePartner);
                    $objUserService->setRefundedAt($temExpDate);
                    $em->persist($objUserService);
                    $em->flush();
                    
                    // service purchase
                    $objServicePurchase->setPaymentStatus('Refunded');
                    $em->persist($objServicePurchase);
                    $em->flush();

                    // purchase order
                    $objPurchaseOrder = $objServicePurchase->getPurchaseOrder();
                    $objPurchaseOrder->setRefundAmount(0);
                    $objPurchaseOrder->setPaymentStatus('Refunded');
                    $em->persist($objPurchaseOrder);
                    $em->flush();
                    
                    // update partner-promo-code 
                    $objPartnerPromoCode->setStatus('Inactive');
                    $em->persist($objPartnerPromoCode);
                    $em->flush();

                    $activityLog = array();
                    $activityLog['ispuser'] = $user['username'];
                    $activityLog['activity'] = 'Deactivated partner promo code';
                    $activityLog['description'] = "ISP Partner  " . $user['name'] . " Deactivated partner promo code.  Code: " . $code . ".";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                $this->get('session')->getFlashBag()->add('success', " Code <b>$code</b> and related Plan has been deactivated  and can not be used by a customer.");
                     
                    $resp['type'] = 'success';
                    $resp['action'] = 'deactivated';
                    $response = new Response(json_encode($servicePurchaseId));
                    $response->headers->set('Content-Type', 'application/json');

                    
                    return $response;
//                }else{
//                    $objPartnerPromoCode->setStatus('Inactive');
//                    $em->persist($objPartnerPromoCode);
//                    $em->flush();
//
//                    //Activity log 
//                    $activityLog = array();
//                    $activityLog['admin'] = 'N/A';
//                    $activityLog['user'] = $user['username'];
//                    $activityLog['activity'] = 'Deactivated partner promo code';
//                    $activityLog['description'] = "ISP Partner  " . $user['name'] . " Deactivated partner promo code.  Code: " . $code . ".";
//                    $this->get('ActivityLog')->saveActivityLog($activityLog);
//                    $this->get('session')->getFlashBag()->add('success', " Code <b>$code</b> has been deactivated and all access to the IPTV plan for this customer has been removed.");
//                    $resp['type'] = 'success';
//                    $resp['code'] = $code;
//                    $resp['action'] = 'deactivated';
//                    $resp['message'] = $activityLog['description'];
//                }
            } else {
                $this->get('session')->getFlashBag()->add('success', "You have chosen to leave the special plan active for the customer and they will continue working until it expires.");
                $resp['type'] = 'success';
                $resp['action'] = 'deactivated';
            }
        }
        $response = new Response(json_encode($resp));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

}
