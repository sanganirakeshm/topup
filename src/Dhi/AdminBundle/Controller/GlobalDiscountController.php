<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Dhi\AdminBundle\Entity\GlobalDiscount;
use Dhi\AdminBundle\Form\Type\GlobalDiscountFormType;
use Dhi\UserBundle\Entity\UserActivityLog;

class GlobalDiscountController extends Controller {

    /**
     * List Services in Admin panel
     */
    public function indexAction(Request $request) {

        
        //Check permission
        if (!($this->get('admin_permission')->checkPermission('global_discount_list') || $this->get('admin_permission')->checkPermission('global_discount_create') || $this->get('admin_permission')->checkPermission('global_discount_update') || $this->get('admin_permission')->checkPermission('global_discount_delete') )) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to view global discount list.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }        
        $em = $this->getDoctrine()->getManager();
        $objCountries = $em->getRepository('DhiAdminBundle:GlobalDiscount')->getAllDiscountedCountry();
        $countries = array_map(function($country){ return $country['name']; }, $objCountries);
        return $this->render('DhiAdminBundle:GlobalDiscount:index.html.twig', array('countries' => $countries));
    }
    
    //Added For Grid List
    public function globalDiscountListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
        
    
        $globaldiscountColumns = array('Id','Country','MinimumAmount','MaximumAmount','DiscountPurchase');
        $admin = $this->get('security.context')->getToken()->getUser();  
        $helper = $this->get('grid_helper_function');
        $gridData = $helper->getSearchData($globaldiscountColumns);

        $sortOrder = $gridData['sort_order'];
        $orderBy = $gridData['order_by'];
        
        if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
            
            $orderBy = 'gd.id';
            $sortOrder = 'DESC';
        } else {
            
             if ($gridData['order_by'] == 'Id') {
                
                $orderBy = 'gd.id';
            }

            if ($gridData['order_by'] == 'Country') {
                
                $orderBy = 'c.name';
            }
            
               if ($gridData['order_by'] == 'MinimumAmount') {
                
                $orderBy = 'gd.minAmount';
            }

            if ($gridData['order_by'] == 'MaximumAmount') {
                
                $orderBy = 'gd.maxAmount';
            }
             if ($gridData['order_by'] == 'DiscountPurchase') {
                
                $orderBy = 'gd.percentage';
            }
           
           
        }

        // Paging
        $per_page = $gridData['per_page'];
        $offset = $gridData['offset'];

        $em = $this->getDoctrine()->getManager();
      
        $data  = $em->getRepository('DhiAdminBundle:GlobalDiscount')->getGlobalGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper);
      
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
                   
                   
                    $flagDelete   = 1;
                    $row = array();
                    $row[] = $resultRow->getId();
                    $row[] = ($resultRow->getCountry())?$resultRow->getCountry()->getName():'Global';
                    $row[] = $resultRow->getMinAmount();
                    $row[] = $resultRow->getMaxAmount();
                    $row[] = $resultRow->getPercentage();
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
        if(!$this->get('admin_permission')->checkPermission('global_discount_create')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to add global location discount.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }
        
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $isValidDiscountRangeSlot = true;
        
        $objGlobalDiscount = new GlobalDiscount();
        
        
        $em = $this->getDoctrine()->getManager();
        $form = $this->createForm(new GlobalDiscountFormType(), $objGlobalDiscount);
        
        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);
            
            $objGlobalDiscount = $form->getData();
            $isValidDiscountRangeSlot = $em->getRepository('DhiAdminBundle:GlobalDiscount')->checkDiscount($objGlobalDiscount, false);
                    
            if ($form->isValid() && $isValidDiscountRangeSlot) {
                    
                    $em->persist($objGlobalDiscount);
                    $em->flush();
                    
                    // set audit log add global discount
                    $activityLog = array();
                    $activityLog['admin'] = $admin;
                    $activityLog['activity'] = 'Add Global Discount';
                    $activityLog['description'] = "Admin " . $admin->getUsername() . " has added new global discount  ".$objGlobalDiscount->getPercentage()." successfully";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                    
                $this->get('session')->getFlashBag()->add('success', "Global discount added successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_global_discount_list'));
            }
        }
        
        return $this->render('DhiAdminBundle:GlobalDiscount:new.html.twig', array('form' => $form->createView(), 'isValidDiscountRangeSlot' => $isValidDiscountRangeSlot));        
    }

    public function editAction(Request $request, $id) {
        
        //Check permission
        if(!$this->get('admin_permission')->checkPermission('global_discount_update')) {
            $this->get('session')->getFlashBag()->add('failure', "You are not allowed to update global discount.");
            return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
        }

        $admin = $this->get('security.context')->getToken()->getUser();
        
        $em = $this->getDoctrine()->getManager();
        $isValidDiscountRangeSlot = true;
        
        $objGlobalDiscount = $em->getRepository('DhiAdminBundle:GlobalDiscount')->find($id);
        
        if (!$objGlobalDiscount) {
            $this->get('session')->getFlashBag()->add('failure', "Global discount does not exist.");
            return $this->redirect($request->headers->get('referer'));
        }
        
        $minAmount = $objGlobalDiscount->getMinAmount();
        $maxAmount = $objGlobalDiscount->getMaxAmount();
        $countryId = $objGlobalDiscount->getCountry() ? $objGlobalDiscount->getCountry()->getId() : '';
        
        $form = $this->createForm(new GlobalDiscountFormType(), $objGlobalDiscount);

        if ($request->getMethod() == "POST") {

                $form->handleRequest($request);
            
                $objGlobalDiscount = $form->getData();
                 $country = '';
                 if($objGlobalDiscount->getCountry()) {
                     
                     $country = $objGlobalDiscount->getCountry()->getId();
                 }
                 
                 if($objGlobalDiscount->getMinAmount() != $minAmount || $objGlobalDiscount->getMaxAmount() != $maxAmount || $country != $countryId) {
                    
                    $maxAmountFlag = false;
                    
                    if($objGlobalDiscount->getMaxAmount() >= $maxAmount || $objGlobalDiscount->getMaxAmount() <= $maxAmount) {
                        
                        $maxAmountFlag = $objGlobalDiscount->getMaxAmount();
                        
                    }
                    
                    // check global discount amount
                    $isValidDiscountRangeSlot = $em->getRepository('DhiAdminBundle:GlobalDiscount')->checkDiscount($objGlobalDiscount,$maxAmountFlag);
                }
            
            if ($form->isValid() && $isValidDiscountRangeSlot) {
                
                $em->persist($objGlobalDiscount);
                $em->flush();
                 
                // set audit log update global discount
                $activityLog = array();
                $activityLog['admin'] = $admin;
                $activityLog['activity'] = 'Edit Global Discount';
                $activityLog['description'] = "Admin " . $admin->getUsername() . " has updated global discount ". $objGlobalDiscount->getPercentage();
                $this->get('ActivityLog')->saveActivityLog($activityLog);
                
                $this->get('session')->getFlashBag()->add('success', "Global discount updated successfully!");
                return $this->redirect($this->generateUrl('dhi_admin_global_discount_list'));
            }
        }

        return $this->render('DhiAdminBundle:GlobalDiscount:edit.html.twig', array('form' => $form->createView(), 'id' => $id, 'isValidDiscountRangeSlot' => $isValidDiscountRangeSlot));        
    }

    public function deleteAction(Request $request) {
        $id = $request->get('id');
        //Check permission
        if(!$this->get('admin_permission')->checkPermission('global_discount_delete')) {
             $result = array('type' => 'danger', 'message' => 'You are not allowed to global discount.');
            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        $admin = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        // set audit log delete global discount
        $activityLog = array();
        $activityLog['admin'] = $admin;
        $activityLog['activity'] = 'Delete Global discount';
        
        $objGlobalDiscount = $em->getRepository('DhiAdminBundle:GlobalDiscount')->find($id);

        if ($objGlobalDiscount) {            
            $globalDiscountId = $objGlobalDiscount->getId();
            $em->remove($objGlobalDiscount);
            $em->flush();
            /* START: add user audit log for Delete global-discount-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted global discount ". $globalDiscountId;
            $this->get('ActivityLog')->saveActivityLog($activityLog);
             $result = array('type' => 'success', 'message' => 'Global discount deleted successfully!');
            /* END: add user audit log for delete global-discount-list */

        } else {
            /* START: add user audit log for Delete global-discount-list */
            $activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete Global dicount ".$id;
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            /* END: add user audit log for delete global-discount-list */       
             $result = array('type' => 'danger', 'message' => 'You are not allowed to delete Global discount!');
        }
        
        $response = new Response(json_encode($result));
        
	$response->headers->set('Content-Type', 'application/json');
     
        return $response;
    }
    
    
    public function checkGlobalDiscount($objGlobalDiscount, $update = false) {
        
           $flag = false; 
           
           $em = $this->getDoctrine()->getManager();
           
           $arrDiscountCountry = $em->getRepository('DhiAdminBundle:GlobalDiscount')->getAllDiscountCountry($objGlobalDiscount->getCountry() ? $objGlobalDiscount->getCountry()->getId() : '');
           
           
           if(!empty($arrDiscountCountry)) {
                        
                        foreach($arrDiscountCountry as $discount) {
                               
                               if($objGlobalDiscount->getMinAmount() > $discount['minAmount'] && $objGlobalDiscount->getMinAmount() >= $discount['maxAmount'])
                               {
                                   if($objGlobalDiscount->getMinAmount() >= $discount['maxAmount']) {
                                       
                                        if($objGlobalDiscount->getMinAmount() > $discount['maxAmount'] && $objGlobalDiscount->getMaxAmount() > $discount['maxAmount']) {
                                           
                                            $flag = true;
                                            break;
                                        }
                                        else {
                                            
                                            $flag  = false;
                                            break;
                                        }
                                       
                                   }
                                   
                               }   
                               
                               else if($objGlobalDiscount->getMinAmount() <= $discount['minAmount'] && $objGlobalDiscount->getMinAmount() <= $discount['maxAmount'])
                               {
                                   if($objGlobalDiscount->getMinAmount() <= $discount['minAmount'] && $objGlobalDiscount->getMaxAmount() <= $discount['minAmount'])
                                   {
                                       
                                       $flag  = true;
                                       
                                   }
                                   else {
                                       
                                       if($update) {
                                           
                                           if($discount['maxAmount'] < $objGlobalDiscount->getMaxAmount()) {
                                               
                                              $flag = true;
                                               
                                           }
                                           
                                       }
                                       
                                       $flag  = false;
                                       break;
                                   }
                                   
                               }
                               
                               else {
                                       
                                  $flag  = false;
                                  break;
                                  
                               }
                               
                                
                        }
                        
                }
                
                else {
                    
                    return true;
                }
                 
               
               return $flag;    
                   
    }
}
