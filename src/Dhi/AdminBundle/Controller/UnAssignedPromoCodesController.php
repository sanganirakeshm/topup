<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Dhi\UserBundle\Entity\PromoCode;
use Dhi\AdminBundle\Entity\BusinessPromoCodes;
// use Dhi\AdminBundle\Entity\PartnerPromoCodes;
use Dhi\UserBundle\Entity\Service;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Form\Type\UnAssignedPromoCodeFormType;
use Dhi\AdminBundle\Form\Type\UnAssignedPartnerPromoCodeFormType;
use Dhi\AdminBundle\Form\Type\UnAssignedBusinessPromoCodeFormType;

use Dhi\AdminBundle\Entity\PartnerPromoCodeBatch;
use Dhi\AdminBundle\Form\Type\PartnerPromoCodeBatchFormType;

use Dhi\AdminBundle\Entity\BusinessPromoCodeBatch;
use Dhi\AdminBundle\Form\Type\BusinessPromoCodeBatchFormType;

use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\AdminBundle\Entity\PartnerPromoCodes;

class UnAssignedPromoCodesController extends Controller {

	public function indexAction(Request $request) {
		$admin = $this->get('security.context')->getToken()->getUser();

		//Check permission
        if (!( $this->get('admin_permission')->checkPermission('unassigned_promo_codes_view'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view unassigned promo codes.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        return $this->render('DhiAdminBundle:UnAssignedPromoCodes:index.html.twig', array(
			'admin' => $admin
        ));
	}

	public function listJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
		$admin                  = $this->get('security.context')->getToken()->getUser();
		$em                     = $this->getDoctrine()->getManager();
		$helper                 = $this->get('grid_helper_function');
		$aColumns               = array('id', 'code', 'type', 'expiryDate', 'duration', 'note', 'serviceLocation');
		$gridData               = $helper->getSearchData($aColumns);
		$sortOrder              = $gridData['sort_order'];
		$orderBy                = $gridData['order_by'];
		$gridData['SearchType'] = 'ANDLIKE';
		$per_page               = $gridData['per_page'];
		$offset                 = $gridData['offset'];
		$resArray               = array();
		$data['business'] = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->getUnAssignedPromoCodesGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

		$data['partner'] = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->getUnAssignedPromoCodesGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

		$data['customer'] = $em->getRepository('DhiUserBundle:PromoCode')->getUnAssignedPromoCodesGrid($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);

		$output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => 0,
            "iTotalDisplayRecords" => 0,
            "aaData" => array()
        );

        if (!empty($data['business'])) {
        	$output = array(
				"sEcho"                => intval($_GET['sEcho']),
				"iTotalRecords"        => $data['business']['totalRecord'],
				"iTotalDisplayRecords" => $data['business']['totalRecord'],
				"aaData"               => array()
			);
        	$resArray = $data['business']['result'];
        }

        if (!empty($data['partner'])) {
        	$output = array(
				"sEcho"                => intval($_GET['sEcho']),
				"iTotalRecords"        => !empty($output['iTotalRecords']) ? $output['iTotalRecords'] + $data['partner']['totalRecord'] : $data['partner']['totalRecord'],
				"iTotalDisplayRecords" => !empty($output['iTotalDisplayRecords']) ? $output['iTotalDisplayRecords'] + $data['partner']['totalRecord'] : $data['partner']['totalRecord'],
				"aaData"               => array()
			);
			$resArray = array_merge($resArray, $data['partner']['result']);
        }

        if (!empty($data['customer'])) {
        	$output = array(
				"sEcho"                => intval($_GET['sEcho']),
				"iTotalRecords"        => !empty($output['iTotalRecords']) ? $output['iTotalRecords'] + $data['customer']['totalRecord'] : $data['customer']['totalRecord'],
				"iTotalDisplayRecords" => !empty($output['iTotalDisplayRecords']) ? $output['iTotalDisplayRecords'] + $data['customer']['totalRecord'] : $data['customer']['totalRecord'],
				"aaData"               => array()
			);
			$resArray = array_merge($resArray, $data['customer']['result']);
		}

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
			$orderBy   = 'code';
			$sortOrder = 'DESC';
        }

		$sortArray = array_map(function($arr) use($orderBy){ return $arr[$orderBy]; }, $resArray);
		array_multisort($sortArray, (strtolower($sortOrder) == "asc" ? SORT_ASC : SORT_DESC), $resArray);
		
		$resArray = array_slice($resArray, $offset, $per_page);
		foreach ($resArray as $key => $record) {
			$row = array();
			$row = array_values($record);
			
			$shortNote   = null;
			if (!empty(!empty($record['note']))) {
				if(strlen($record['note']) > 10){
	                $shortNote = substr($record['note'], 0, 10).'...';
	            }else{
	                $shortNote = $record['note'];
	            }
				$shortNote = '<a href="javascript:void(0);" onclick="showDetail('. $record['id'] .', \''.(strtolower($record['type']).'PromoCode').'\');">' . $shortNote. '</a>';
			}else{
				$shortNote = "N/A";
			}

			$row[3] = !empty($record['expiryDate']) ? $record['expiryDate']->format("m/d/Y") : "N/A";
			$row[5] = $shortNote;
			$row[6] = !empty($record['serviceLocation']) ? $record['serviceLocation'] : "N/A";
			$output['aaData'][] = $row;
		}

		$response = new Response(json_encode($output));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
	}

	public function deleteAction(Request $request){
		$admin  = $this->get('security.context')->getToken()->getUser();
		$result = array('type' => 'danger', 'message' => '');
		$ids    = $request->get('id');
		$em     = $this->getDoctrine()->getManager();

		//Check permission
        if(!$this->get('admin_permission')->checkPermission('unassigned_promo_codes_delete')) {
			$result['message'] = "You are not allowed to delete promo code";
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

		if (!empty($ids)) {
			$postArr = explode('^', $ids);
			if (!empty($postArr[0]) && !empty($postArr[1])) {
				$codeType = $postArr[0];
				$codeId   = $postArr[1];

				if ($codeType == 'business') {
					$obj = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->find($codeId);
					if ($obj) {
						$promoCode = $obj->getCode();
					}

				}else if ($codeType == 'partner') {
					$obj = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($codeId);
					if ($obj) {
						$promoCode = $obj->getCode();
					}

				}else if ($codeType == 'customer') {
					$obj = $em->getRepository('DhiUserBundle:PromoCode')->find($codeId);
					if ($obj) {
						if ($obj->getNoOfRedemption() == 0) {
							$promoCode = $obj->getPromoCode();
						}else{
							$result['message'] = "Promo Code Is Already Redeemed By Some Users";
						}
					}
				}else{
					$result['message'] = "Invalid Request";
				}

				if ($obj && !empty($promoCode)) {
					try{
						$em->remove($obj);
						$em->flush();
						$result['type']  = "success";
						$result['message'] = "Promo Code Deleted Successfully!";

						$activityLog = array();
				        $activityLog['admin'] = $admin;
				        $activityLog['activity'] = 'Delete UnAssigned Promo Code';
				        $activityLog['description'] = "Admin  ".$admin->getUsername()." has deleted unassigned promo code ".$promoCode;
            			$this->get('ActivityLog')->saveActivityLog($activityLog);

					}catch (\Exception $e) {
						$result['message'] = "Invalid Request";
					}
				}else{
					$result['message'] = !empty($result['message']) ? $result['message'] : "Invalid Request";
				}
			}else{
				$result['message'] = "Invalid Request";
			}
		}else{
			$result['message'] = "Invalid Request";
		}
		$response = new Response(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
	}

	public function reAssignAction(Request $request, $id, $type){
		
		//Check permission
        if (!( $this->get('admin_permission')->checkPermission('unassigned_promo_codes_reassign'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to reassign promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
		$id    = $request->get('id');
		$type  = $request->get('type');
		$em    = $this->getDoctrine()->getManager();

		if (!empty($id) && !empty($type)) {
			$packages    = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
			$bundle      = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
			$allPackages = $packages + $bundle;
			if ($type == 'business') {
				$oldPromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->find($id);
				if ($oldPromoCode) {
					$existingPromoCode['code']           = $oldPromoCode->getCode();
					$existingPromoCode['expiresAt']      = $oldPromoCode->getExpirydate();
					$existingPromoCode['duration']       = $oldPromoCode->getDuration();
					$existingPromoCode['note']           = $oldPromoCode->getNote();
					$existingPromoCode['customer_value'] = $oldPromoCode->getCustomerValue();
					$existingPromoCode['business_value'] = $oldPromoCode->getBusinessValue();
				}

			}else if ($type == 'partner') {
				$oldPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->find($id);
				if ($oldPromoCode) {
					$existingPromoCode['code']           = $oldPromoCode->getCode();
					$existingPromoCode['expiresAt']      = $oldPromoCode->getExpirydate();
					$existingPromoCode['duration']       = $oldPromoCode->getDuration();
					$existingPromoCode['note']           = $oldPromoCode->getNote();
					$existingPromoCode['customer_value'] = $oldPromoCode->getCustomerValue();
					$existingPromoCode['partner_value']  = $oldPromoCode->getPartnerValue();
				}

			}else if ($type == 'customer') {
				$oldPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->find($id);
				if ($oldPromoCode) {
					$existingPromoCode['code']      = $oldPromoCode->getPromoCode();
					$existingPromoCode['expiresAt'] = $oldPromoCode->getExpiredAt();
					$existingPromoCode['duration']  = $oldPromoCode->getDuration();
					$existingPromoCode['note']      = $oldPromoCode->getNote();
				}
			}

			if ($oldPromoCode) {

				$objBusinessPromoCodeBatch = new BusinessPromoCodeBatch();
				$objBusinessPromoCodeBatch->setNoOfCodes(1);
                $objBusinessPromoCodeBatch->setReason('');

				$objBusinessPromo = new BusinessPromoCodes();
				$objBusinessPromo->setCode($existingPromoCode['code']);
				$objBusinessPromo->setExpirydate($existingPromoCode['expiresAt']);
				$objBusinessPromo->setDuration($existingPromoCode['duration']);
				$objBusinessPromo->setNote($existingPromoCode['note']);
				
				if (!empty($existingPromoCode['business_value'])) {
					$objBusinessPromo->setBusinessValue($existingPromoCode['business_value']);
				}

				$objPartnerPromoCodeBatch = new PartnerPromoCodeBatch();
				$objPartnerPromoCodeBatch->setNoOfCodes(1);
                $objPartnerPromoCodeBatch->setReason('');
		                
				$objPartnerPromo = new PartnerPromoCodes();
				$objPartnerPromo->setCode($existingPromoCode['code']);
				$objPartnerPromo->setExpirydate($existingPromoCode['expiresAt']);
				$objPartnerPromo->setDuration($existingPromoCode['duration']);
				$objPartnerPromo->setNote($existingPromoCode['note']);
				
				if (!empty($existingPromoCode['partner_value'])) {
					$objPartnerPromo->setPartnerValue($existingPromoCode['partner_value']);
				}
				
				if (!empty($existingPromoCode['customer_value'])) {
					$objBusinessPromo->setCustomerValue($existingPromoCode['customer_value']);
					$objPartnerPromo->setCustomerValue($existingPromoCode['customer_value']);
				}

				$objPromoCode = new PromoCode();
				$objPromoCode->setPromoCode($existingPromoCode['code']);
				$objPromoCode->setExpiredAt($existingPromoCode['expiresAt']);
				$objPromoCode->setDuration($existingPromoCode['duration']);
				$objPromoCode->setNote($existingPromoCode['note']);

				$customerForm      = $this->createForm(new UnAssignedPromoCodeFormType($allPackages), $objPromoCode);
				$partnerBatchForm  = $this->createForm(new PartnerPromoCodeBatchFormType(), $objPartnerPromoCodeBatch);
				$partnerForm       = $this->createForm(new UnAssignedPartnerPromoCodeFormType($packages), $objPartnerPromo);
				$businessForm      = $this->createForm(new UnAssignedBusinessPromoCodeFormType($allPackages), $objBusinessPromo);
				$businessBatchForm = $this->createForm(new BusinessPromoCodeBatchFormType(), $objBusinessPromoCodeBatch);

				$partnerBatchForm->remove('noOfCodes');
				$partnerBatchForm->remove('batchName');
				$partnerForm->remove('note');

				$businessBatchForm->remove('noOfCodes');
				$businessBatchForm->remove('batchName');
				$businessForm->remove('note');

				$randBatchNameCode    = $this->getRandomCode(7);

				if ($request->getMethod() == "POST") {

					// Customer Promo Code
		            $customerForm->handleRequest($request);
		            if ($customerForm->isValid()) {
						$expirationDate      = $request->get('dhi_admin_customer_promo_code')['duration'];
						$expiresAt           = new \DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));
		                $objPromoCode->setCreatedBy($admin->getUsername())->setExpiredAt($expiresAt);
	                    if($objPromoCode->getService()->getName() == 'BUNDLE'){
	                        $objPromoCode->setIsBundle(1);
	                    } else {
	                        $objPromoCode->setIsBundle(0);
	                    }

	                    try{
		                    $em->persist($objPromoCode);
		                	$em->remove($oldPromoCode);
		                    $em->flush();

		                    // set audit log reassign promo code
		                    $activityLog = array();
		                    $activityLog['admin'] = $admin;
		                    $activityLog['activity'] = 'Promo Code Reassign';
		                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$existingPromoCode['code'];
		                    $this->get('ActivityLog')->saveActivityLog($activityLog);
			                
			                $this->get('session')->getFlashBag()->add('success', 'Promo Code Reassign Successfully.');
		                }catch (\Exception $e) {
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code! Please Try Again");
			            }
		                return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		          	}

		          	// Partner Promo Code
		            $partnerForm->handleRequest($request);
		            $partnerBatchForm->handleRequest($request);
		            if ($partnerForm->isValid() && $partnerBatchForm->isValid()) {
						$servicePrefix = $objPartnerPromo->getServiceLocations()->getName();
						$batchName     = strtoupper($servicePrefix.$randBatchNameCode);
						$noOfCodes     = 1;
						$isNeverExpire = $request->get('chkNeverExpire');
						
						$objPartnerPromoCodeBatch->setBatchName($batchName);
						$objPartnerPromoCodeBatch->setStatus($objPartnerPromo->getStatus());
						$em->persist($objPartnerPromoCodeBatch);

						$objPartnerPromo->setBatchId($objPartnerPromoCodeBatch);
	                    $objPartnerPromo->setCreatedBy($admin);
	                    
	                    if(!empty($isNeverExpire)){
	                        $objPartnerPromo->setExpirydate(null);
	                    }

	                    try{
		                    	$em->remove($oldPromoCode);
		                    	$em->flush();
		                    $em->persist($objPartnerPromo);
		                    $em->flush();

		                    // set audit log reassign promo code
		                    $activityLog = array();
		                    $activityLog['admin'] = $admin;
		                    $activityLog['activity'] = 'Promo Code Reassign';
		                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$existingPromoCode['code'];
		                    $this->get('ActivityLog')->saveActivityLog($activityLog);
			                
			                $this->get('session')->getFlashBag()->add('success', 'Promo Code Reassign Successfully.');
			            
			            }catch (\Exception $e) {
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code! Please Try Again");
			            }
        				return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		            }

		            // Business Promo Code
		            $businessForm->handleRequest($request);
		            $businessBatchForm->handleRequest($request);
		            if ($businessForm->isValid() && $businessBatchForm->isValid()) {
						$servicePrefix = $objBusinessPromo->getServiceLocations()->getName();
						$batchName     = strtoupper($servicePrefix.$randBatchNameCode);
						$noOfCodes     = 1;
						$isNeverExpire = $request->get('chkNeverExpire');
						
						$objBusinessPromoCodeBatch->setBatchName($batchName);
						$objBusinessPromoCodeBatch->setStatus($objBusinessPromo->getStatus());
						$em->persist($objBusinessPromoCodeBatch);

						$objBusinessPromo->setBatchId($objBusinessPromoCodeBatch);
	                    $objBusinessPromo->setCreatedBy($admin);

	                    if(!empty($isNeverExpire)){
	                        $objBusinessPromo->setExpirydate(null);
	                    }

	                    try{
	                    	$em->remove($oldPromoCode);
	                    	$em->flush();

		                    $em->persist($objBusinessPromo);
		                    $em->flush();

		                    // set audit log reassign promo code
		                    $activityLog = array();
		                    $activityLog['admin'] = $admin;
		                    $activityLog['activity'] = 'Promo Code Reassign';
		                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$existingPromoCode['code'];
		                    $this->get('ActivityLog')->saveActivityLog($activityLog);
			                
			                $this->get('session')->getFlashBag()->add('success', 'Promo Code Reassign Successfully.');
			            
			            }catch (\Exception $e) {
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code! Please Try Again");
			            }
        				return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		            }
		        }

				return $this->render('DhiAdminBundle:UnAssignedPromoCodes:reAssign.html.twig', array(
		          	'form' => $customerForm->createView(),
		          	'partnerForm' => $partnerForm->createView(),
		          	'partnerBatchForm' => $partnerBatchForm->createView(),
		          	'businessForm' => $businessForm->createView(),
		          	'businessBatchForm' => $businessBatchForm->createView(),
					'promoCodeId' => $id,
					'promoCodeType' => $type,
					'code' => $existingPromoCode['code']
		        ));
		    }else{
		    	$this->get('session')->getFlashBag()->add('failure', "Promo Code Does Not Exists.");
            	return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		    }
		}
	}

	public function bulkReAssignAction(Request $request){

		//Check permission
        if (!( $this->get('admin_permission')->checkPermission('unassigned_promo_codes_bulk_reassign'))) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to reassign promo code.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

		$arrPromoCodes = array();
		$promoCodes    = $request->get('promoIds');
		$strPromoCodes    = $request->get('dhi_admin_customer_promo_code[strPromoCodes]');

		if (!empty($promoCodes)) {
			$admin = $this->get('security.context')->getToken()->getUser();
			$em    = $this->getDoctrine()->getManager();
			$packages    = $em->getRepository('DhiAdminBundle:Package')->getPromoPackages();
			$bundle      = $em->getRepository('DhiAdminBundle:Bundle')->getBundlePlan();
			$allPackages = $packages + $bundle;

			foreach ($promoCodes as $key => $promoCode) {
				if (strpos($promoCode, "~") > 1) {
					$tmpStrPromo = explode("~", $promoCode);
					$arrPromoCodes[$tmpStrPromo[0]][] = $tmpStrPromo[1];
				}
			}

			if (!empty($arrPromoCodes['business'])) {
				$oldBusinessPromoCode = $em->getRepository('DhiAdminBundle:BusinessPromoCodes')->findBy(array("id" => $arrPromoCodes['business']));
			}

			if (!empty($arrPromoCodes['partner'])) {
				$oldPartnerPromoCode = $em->getRepository('DhiAdminBundle:PartnerPromoCodes')->findBy(array("id" => $arrPromoCodes['partner']));
			}

			if (!empty($arrPromoCodes['customer'])) {
				$oldCustomerPromoCode = $em->getRepository('DhiUserBundle:PromoCode')->findBy(array("id" => $arrPromoCodes['customer']));
			}

			if ((isset($oldCustomerPromoCode) && !empty($oldCustomerPromoCode)) || (isset($oldBusinessPromoCode) && !empty($oldBusinessPromoCode)) || (isset($oldPartnerPromoCode) && !empty($oldPartnerPromoCode))) {

				$objBusinessPromoCodeBatch = new BusinessPromoCodeBatch();
				$objBusinessPromoCodeBatch->setNoOfCodes(1);
	            $objBusinessPromoCodeBatch->setReason('');
	            $objBusinessPromo = new BusinessPromoCodes();

	            $objPartnerPromoCodeBatch = new PartnerPromoCodeBatch();
				$objPartnerPromoCodeBatch->setNoOfCodes(1);
	            $objPartnerPromoCodeBatch->setReason('');
				$objPartnerPromo   = new PartnerPromoCodes();
				$objPromoCode      = new PromoCode();
				$customerForm      = $this->createForm(new UnAssignedPromoCodeFormType($allPackages), $objPromoCode);
				$partnerBatchForm  = $this->createForm(new PartnerPromoCodeBatchFormType(), $objPartnerPromoCodeBatch);
				$partnerForm       = $this->createForm(new UnAssignedPartnerPromoCodeFormType($packages), $objPartnerPromo);
				$businessForm      = $this->createForm(new UnAssignedBusinessPromoCodeFormType($allPackages), $objBusinessPromo);
				$businessBatchForm = $this->createForm(new BusinessPromoCodeBatchFormType(), $objBusinessPromoCodeBatch);

				$partnerBatchForm->remove('noOfCodes');
				$partnerBatchForm->remove('batchName');
				$partnerForm->remove('note');

				$businessBatchForm->remove('noOfCodes');
				$businessBatchForm->remove('batchName');
				$businessForm->remove('note');
				$randBatchNameCode = $this->getRandomCode(7);

				if ($request->getMethod() == "POST") {
					$errorCount = $count = 0;

					// Customer Promo Code
		            $customerForm->handleRequest($request);
		            if ($customerForm->isValid()) {
						$expirationDate = $request->get('dhi_admin_customer_promo_code')['duration'];
						$expiresAt      = new \DateTime($objPromoCode->getExpiredAt()->format('Y-m-d 23:59:59'));
						$isBundle       = $objPromoCode->getIsBundle();
						$serviceName	= $objPromoCode->getService()->getName();

						if (!empty($oldBusinessPromoCode)) {
							foreach ($oldBusinessPromoCode as $businessPromoCode) {
								$newPromoCode = clone $objPromoCode;
								$newPromoCode->setCreatedBy($admin->getUsername())->setExpiredAt($expiresAt);
			                    if($serviceName == 'BUNDLE'){
			                        $newPromoCode->setIsBundle(1);
			                    } else {
			                        $newPromoCode->setIsBundle(0);
			                    }
			                    $newPromoCode->setPromoCode($businessPromoCode->getCode());

			                    try{
				                	$em->remove($businessPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$businessPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if (!empty($oldPartnerPromoCode)) {
							foreach ($oldPartnerPromoCode as $tmpPromoCode) {
								$newPromoCode = clone $objPromoCode;
								$newPromoCode->setCreatedBy($admin->getUsername())->setExpiredAt($expiresAt);
			                    if($serviceName == 'BUNDLE'){
			                        $newPromoCode->setIsBundle(1);
			                    } else {
			                        $newPromoCode->setIsBundle(0);
			                    }
			                    $newPromoCode->setPromoCode($tmpPromoCode->getCode());

			                    try{
				                	$em->remove($tmpPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$tmpPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
			                    }catch (\Exception $e) {
			                		$errorCount++;
				                }
							}
						}

						if (!empty($oldCustomerPromoCode)) {
							foreach ($oldCustomerPromoCode as $tmpPromoCode) {
								$newPromoCode = clone $objPromoCode;
								$newPromoCode->setCreatedBy($admin->getUsername())->setExpiredAt($expiresAt);
			                    if($serviceName == 'BUNDLE'){
			                        $newPromoCode->setIsBundle(1);
			                    } else {
			                        $newPromoCode->setIsBundle(0);
			                    }
			                    $newPromoCode->setPromoCode($tmpPromoCode->getPromoCode());

			                   	try{
				                	$em->remove($tmpPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$tmpPromoCode->getPromoCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
			                   	}catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

	                    if ($count > 0) {
	                    	$this->get('session')->getFlashBag()->add('success', $count.' Promo Code(s) Reassign Successfully.'. ($errorCount > 0 ? " Could Not Reassign $errorCount Promo Code(s)." : ""));
		                }else{
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code(s)! Please Try Again");
			            }
		                return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		          	}

		          	// Partner Promo Code
		            $partnerForm->handleRequest($request);
		            $partnerBatchForm->handleRequest($request);
		            if ($partnerForm->isValid() && $partnerBatchForm->isValid()) {

						$servicePrefix = $objPartnerPromo->getServiceLocations()->getName();
						$batchName     = strtoupper($servicePrefix.$randBatchNameCode);
						// $noOfCodes     = 1;
						$isNeverExpire = $request->get('chkNeverExpire');
						$objPartnerPromoCodeBatch->setBatchName($batchName);
						$objPartnerPromoCodeBatch->setStatus($objPartnerPromo->getStatus());
						$em->persist($objPartnerPromoCodeBatch);

						if (!empty($oldBusinessPromoCode)) {
							foreach ($oldBusinessPromoCode as $businessPromoCode) {
								$newPromoCode = clone $objPartnerPromo;
								$newPromoCode->setBatchId($objPartnerPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($businessPromoCode->getCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try{
				                	$em->remove($businessPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if (!empty($oldPartnerPromoCode)) {
							foreach ($oldPartnerPromoCode as $partnerPromoCode) {
								$newPromoCode = clone $objPartnerPromo;
								$newPromoCode->setBatchId($objPartnerPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($partnerPromoCode->getCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try{
				                	$em->remove($partnerPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if (!empty($oldCustomerPromoCode)) {
							foreach ($oldCustomerPromoCode as $customerPromoCode) {
								$newPromoCode = clone $objPartnerPromo;
								$newPromoCode->setBatchId($objPartnerPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($customerPromoCode->getPromoCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try{
				                	$em->remove($customerPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if ($count > 0) {
	                    	$this->get('session')->getFlashBag()->add('success', $count.' Promo Code(s) Reassign Successfully.'. ($errorCount > 0 ? " Could Not Reassign $errorCount Promo Code(s)." : ""));
		                }else{
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code(s)! Please Try Again");
			            }
		                return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		            }

		            // Business Promo Code
		            $businessForm->handleRequest($request);
		            $businessBatchForm->handleRequest($request);
		            if ($businessForm->isValid() && $businessBatchForm->isValid()) {
						
						$servicePrefix = $objBusinessPromo->getServiceLocations()->getName();
						$batchName     = strtoupper($servicePrefix.$randBatchNameCode);
						$isNeverExpire = $request->get('chkNeverExpire');
						$objBusinessPromoCodeBatch->setBatchName($batchName);
						$objBusinessPromoCodeBatch->setStatus($objBusinessPromo->getStatus());
						$em->persist($objBusinessPromoCodeBatch);

						if (!empty($oldBusinessPromoCode)) {
							foreach ($oldBusinessPromoCode as $businessPromoCode) {
								$newPromoCode = clone $objBusinessPromo;
								$newPromoCode->setBatchId($objBusinessPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($businessPromoCode->getCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try{
				                	$em->remove($businessPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if (!empty($oldPartnerPromoCode)) {
							foreach ($oldPartnerPromoCode as $partnerPromoCode) {
								$newPromoCode = clone $objBusinessPromo;
								$newPromoCode->setBatchId($objBusinessPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($partnerPromoCode->getCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try {
				                	$em->remove($partnerPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if (!empty($oldCustomerPromoCode)) {
							foreach ($oldCustomerPromoCode as $customerPromoCode) {
								$newPromoCode = clone $objBusinessPromo;
								$newPromoCode->setBatchId($objBusinessPromoCodeBatch);
								$newPromoCode->setCreatedBy($admin);
			                    $newPromoCode->setCode($customerPromoCode->getPromoCode());
			                    if(!empty($isNeverExpire)){
			                        $newPromoCode->setExpirydate(null);
			                    }
			                    try{
				                	$em->remove($customerPromoCode);
				                    $em->flush();
									$em->persist($newPromoCode);
				                    $em->flush();
				                    $count++;

				                    // set audit log reassign promo code
				                    $activityLog = array();
				                    $activityLog['admin'] = $admin;
				                    $activityLog['activity'] = 'Promo Code Reassign';
				                    $activityLog['description'] = "Admin ".$admin->getUsername()." has reassigned promo code ".$newPromoCode->getCode();
				                    $this->get('ActivityLog')->saveActivityLog($activityLog);
				                }catch (\Exception $e) {
				                	$errorCount++;
				                }
							}
						}

						if ($count > 0) {
	                    	$this->get('session')->getFlashBag()->add('success', $count.' Promo Code(s) Reassign Successfully.'. ($errorCount > 0 ? " Could Not Reassign $errorCount Promo Code(s)." : ""));
		                }else{
			            	$this->get('session')->getFlashBag()->add('failure', "Can Not Reassign Promo Code(s)! Please Try Again");
			            }
		                return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		            }
				}

				return $this->render('DhiAdminBundle:UnAssignedPromoCodes:bulkReAssign.html.twig', array(
					"oldBusinessPromoCode" => isset($oldBusinessPromoCode) ? $oldBusinessPromoCode : null,
					"oldPartnerPromoCode"  => isset($oldPartnerPromoCode) ? $oldPartnerPromoCode : null,
					"oldCustomerPromoCode" => isset($oldCustomerPromoCode) ? $oldCustomerPromoCode : null,
					'form'                 => $customerForm->createView(),
					'partnerForm'          => $partnerForm->createView(),
					'partnerBatchForm'     => $partnerBatchForm->createView(),
					'businessForm'         => $businessForm->createView(),
					'businessBatchForm'    => $businessBatchForm->createView(),
					'promoCodes'           => $promoCodes
				));
			}else{
				$this->get('session')->getFlashBag()->add('failure', "Promo Code(s) Does Not Exists!");
	        	return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
			}
		}else{
			$this->get('session')->getFlashBag()->add('failure', "Please Select Promo Code");
        	return $this->redirect($this->generateUrl('dhi_admin_unassigned_promo_codes_view'));
		}
	}

	private function getRandomCode($random_string_length = 7){
		$characters           = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$randBatchNameCode    = '';
        for ($i = 0; $i < $random_string_length; $i++) {
            $randBatchNameCode .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randBatchNameCode;
	}
}