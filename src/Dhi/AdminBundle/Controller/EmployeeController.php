<?php

namespace Dhi\AdminBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Dhi\UserBundle\Entity\User;
use Dhi\AdminBundle\Form\Type\UserFormType;
use Dhi\AdminBundle\Form\Type\UserSettingFormType;
use Dhi\AdminBundle\Form\Type\ChangePasswordFormType;
use Dhi\AdminBundle\Form\Type\LoginLogSearchFormType;
use \DateTime;
use Dhi\UserBundle\Entity\UserActivityLog;
use Dhi\UserBundle\Entity\UserService;
use Dhi\UserBundle\Entity\UserServiceSetting;
use Dhi\UserBundle\Entity\UserServiceSettingLog;
use Dhi\UserBundle\Entity\UserSetting;
use Dhi\UserBundle\Entity\ServiceLocation;
use Dhi\ServiceBundle\Entity\PurchaseOrder;
use Dhi\ServiceBundle\Entity\ServicePurchase;
use Dhi\UserBundle\Entity\UserCreditLog;
use Dhi\UserBundle\Entity\UserCredit;
use Dhi\AdminBundle\Entity\InformationLog;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dhi\ServiceBundle\Model\ExpressCheckout;
use Dhi\AdminBundle\Entity\UserSessionHistory;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class EmployeeController extends Controller {

	public function indexAction(Request $request) {

		//Check permission
		if (!($this->get('admin_permission')->checkPermission('employee_list') || $this->get('admin_permission')->checkPermission('employee_create') || $this->get('admin_permission')->checkPermission('employee_delete') || $this->get('admin_permission')->checkPermission('employee_update') || $this->get('admin_permission')->checkPermission('view_employee') )) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to view employee list.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}
		$em = $this->getDoctrine()->getManager();
		$admin = $this->get('security.context')->getToken()->getUser();
		$this->get('session')->remove('serviceLocationSelection');
		
		// Service locaiton
		$arrServiceLocations = array();
		
		if ($admin->getGroup() != 'Super Admin') {
			$lo = $admin->getServiceLocations();
			foreach ($lo as $key => $value) {
				$arrServiceLocations[] = $value->getName();
			}
			sort($arrServiceLocations);
		}else{
			$serviceLocations = $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
			foreach ($serviceLocations as $key => $serviceLocation){
				$arrServiceLocations[] = $serviceLocation['name'];
			}
		}
		return $this->render('DhiAdminBundle:Employee:index.html.twig', array(
			'admin' => $admin,
			"serviceLocations" => $arrServiceLocations
		));
	}

	public function listJsonAction(Request $request, $orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0) {
		$userColumns = array('Id', 'Name', 'Username', 'Email', 'ActiveServices', 'ServiceLocation', 'ActivationDate','ExpiryDate', 'ServiceSettings');
		$admin = $this->get('security.context')->getToken()->getUser();
		$helper = $this->get('grid_helper_function');
		$gridData = $helper->getSearchData($userColumns);
                
                $firstname = trim($request->get('firstname')); 
                $lastname  = trim($request->get('lastname')); 
                
		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
			$orderBy = 'u.id';
			$sortOrder = 'DESC';
		} else {
			if ($gridData['order_by'] == 'Id') {
				$orderBy = 'u.id';
			}
			if ($gridData['order_by'] == 'Name') {
				$orderBy = 'u.firstname';
			}
			if ($gridData['order_by'] == 'Username') {
				$orderBy = 'u.username';
			}
			if ($gridData['order_by'] == 'Email') {
				$orderBy = 'u.email';
			}
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];
		$em = $this->getDoctrine()->getManager();

		$country = '';
		if ($admin->getGroup() != 'Super Admin') {
			$country = $em->getRepository('DhiAdminBundle:ServiceLocation')->getValidLocation($admin);
			$country = empty($country) ? '0' : $country;
		}
		$data = $em->getRepository('DhiUserBundle:User')->getUserGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $country, $firstname, $lastname, 1);

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
					$flagDelete = 1;
					$flagSetting = 0;

					$username = '<a href="' . $this->generateUrl('dhi_admin_view_employee', array('id' => $resultRow->getId())) . '">' . $resultRow->getUsername() . '</a>';

					$count = 1;
                                        $activeServices = $resultRow->getActiveServices();
					$servicesCount = count($activeServices);

					$serviceName = '';
					if ($activeServices) {
						foreach ($activeServices as $service) {
							if($service['name'] != "TVOD") {
								if ($count == $servicesCount) {
									$serviceName .= '<span class="btn btn-success btn-sm service">' . $service['name'] . '</span>';
								} else {
									$serviceName .= '<span class="btn btn-success btn-sm service">' . $service['name'] . '</span>';
								}
								$count++;
							}
						}
					}

					$activationDate = '';
					$expiryDate = '';
                                        $purchasedServiceLocation = 'N/A';
                                        $userServices = $resultRow->getUserServices() ? $resultRow->getUserServices() : false;
                                        
					if($userServices) {
						$counttext=0;
						$pretext = array();
						$activationArray = array();
						$expiryArray = array();

						foreach ($userServices as $user_service) {
                                                    
                                                        $purchasedServiceLocation = $user_service->getServicePurchase()->getServiceLocationId() ? $user_service->getServicePurchase()->getServiceLocationId()->getName() : 'N/A';
							if ($user_service->getService()->getName() == 'TVOD') {
								continue;
							}

							if ($user_service->getIsAddon() == 0) {
								if ($user_service->getStatus() == 1) {
									$counttext++;
									$pretext[$counttext] =  $user_service->getService()->getName();
									$activationArray[$counttext] =  $user_service->getActivationDate()->format('m/d/Y H:i:s') ;
                                    if($user_service->getIsPlanActive() == 0 && $user_service->getServicePurchase()->getPurchaseType() == ''){
                                        $expiryArray[$counttext] =  'Not Logged in';
                                    } else {
                                        $expiryArray[$counttext] =  $user_service->getExpiryDate()->format('m/d/Y H:i:s');
                                    }
								}
							}
						}

						foreach($pretext as $key => $value) {
							if($counttext > 1 ) {
								$activationDate .= $activationArray[$key] . ' (' . $pretext[$key] . ')<br>';
								$expiryDate .= $expiryArray[$key] . ' (' . $pretext[$key] . ')<br>';
							} else {
								$activationDate .= $activationArray[$key] . '<br>';
								$expiryDate .= $expiryArray[$key] . '<br>';
							}
						}

						$scount = 1;
                                                $serviceSettings = $resultRow->getServiceSettings();
						$settingCount = count($serviceSettings);
						$settingName = '';
						if ($serviceSettings) {
							$flagSetting = 1;
							foreach ($serviceSettings as $setting) {
								if ($setting->getServiceStatus() == 'Disabled') {
									$settingName .= '<a href="' . $this->generateUrl('dhi_admin_employee_service_status', array('userId' => $resultRow->getId(), 'serviceSettingId' => $setting->getId())) . '" title = "Reactivate' . $setting->getService()->getName() . ' for user ' . $resultRow->getUsername() . '"> Reactivate ' . $setting->getService()->getName() . '</a><br/>';
								} else {
									$settingName .= '<a href="' . $this->generateUrl('dhi_admin_employee_service_status', array('userId' => $resultRow->getId(), 'serviceSettingId' => $setting->getId())) . '" title = "Deactivate' . $setting->getService()->getName() . ' for user ' . $resultRow->getUsername() . '"> Deactivate ' . $setting->getService()->getName() . '</a><br/>';
								}
								$scount++;
							}
						}

						$row = array();
						$row[] = $resultRow->getId();
						$row[] = $resultRow->getFirstname() . ' ' . $resultRow->getLastname();
						$row[] = $username;
						$row[] = $resultRow->getEmail();
						$row[] = $activeServices ? $serviceName : 'N/A';
						$row[] = $purchasedServiceLocation;
						$row[] = $activationDate ? $activationDate : 'N/A' ;
						$row[] = $expiryDate ? $expiryDate : 'N/A' ;
						$row[] = $settingName;
						$row[] = $resultRow->getId() . '^' . $flagDelete . '^' . $flagSetting . '^' . $resultRow->getUsername();
						$output['aaData'][] = $row;
					}
				}
			}
		}
		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}

	public function newAction(Request $request) {
		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_create')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to add new employee.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$ipAddress = $this->get('session')->get('ipAddress');
		$countryObj = '';

		$form = $this->createForm(new UserFormType($admin, null), new User());
		if ($request->getMethod() == "POST") {
			$form->handleRequest($request);
			$formValues = $request->request->get('dhi_admin_registration');
                        
                        $isFormValid = true;
                        $email = $formValues['email'];
                        $firstName = $formValues['firstname'];
                        $lastName = $formValues['lastname'];


                        if(!preg_match('/^[^\'"]*$/', $email)){
                            $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                            $isFormValid = false;
                        }

                        if(!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)){
                            $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                            $isFormValid = false;
                        }

                        if(!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)){
                            $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                            $isFormValid = false;
                        }
                        
			if ($form->isValid() && $isFormValid) {
				$registrationArr = $request->request->get('dhi_admin_registration');
                                
                                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($registrationArr['email']);
                                $objUsername = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkUsernameEmail($registrationArr['username']);

                                if ($objUser || $objUsername) {
                                    $this->get('session')->getFlashBag()->add('danger', "Email Or Username Already in used!");
                                    return $this->render('DhiAdminBundle:Employee:new.html.twig', array(
                                            'form' => $form->createView()
                                    ));
                                }
                                
				$objUser = $form->getData();
				if ($objUser->getUserServiceLocation()) {
					$countryObj = $objUser->getUserServiceLocation()->getCountry();
				}
				$tokenGenerator = $this->get('fos_user.util.token_generator');
				$objUser->setConfirmationToken($tokenGenerator->generateToken());
				$objUser->setEncryPwd(base64_encode($registrationArr['plainPassword']['first']));
				$objUser->setGeoLocationCountry($countryObj);
				$objUser->setIpAddress($ipAddress);
				$objUser->setIpAddressLong(ip2long($ipAddress));
				$objUser->setIsEmployee(1);
				$em->persist($objUser);
				$em->flush();

				// set audit log add user
				$activityLog = array();
				$activityLog['admin'] = $admin;
				$activityLog['user'] = $objUser;
				$activityLog['activity'] = 'Add employee';
				$activityLog['description'] = "Admin " . $admin->getUsername() . " has added new employee Email: " . $objUser->getEmail() . " and Username: " . $objUser->getUsername();
				$this->get('ActivityLog')->saveActivityLog($activityLog);
                                
                                $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$registrationArr['userServiceLocation']));
               
                                if($getdomain){
                                    $subject = "Welcome " . $objUser->getName() . " to ".$getdomain->getWhiteLabel()->getCompanyName()."!";;
                                    $fromemail = $getdomain->getWhiteLabel()->getFromEmail();
                                    $compnayname = $getdomain->getWhiteLabel()->getCompanyName();
                                    $compnaydomain = $getdomain->getWhiteLabel()->getDomain();
                                    $supportpage   = $getdomain->getWhiteLabel()->getSupportpage();
                                } else {
                                    $subject = "Welcome " . $objUser->getName() . " to ExchangeVUE!";
                                    $fromemail = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                                    $compnayname     = 'ExchangeVUE';
                                    $compnaydomain   = 'exchangevue.com';
                                    $supportpage    = 'facebook.com/dhitelecom';
                                }
                                $confimationurl = $compnaydomain.'/confirm/'.$objUser->getConfirmationToken();
                                if($request->isSecure()){
                                    $confimationurl = 'https://'.$confimationurl;
                                } else {
                                   $confimationurl  = 'http://'.$confimationurl;
                                }
				$body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:register_email.html.twig', array(
					'user' => $objUser,
					'token' => $objUser->getConfirmationToken(),
                    'companyname'=>$compnayname,
                    'confirmationurl'=>$confimationurl,
                    'companydomain'=>$compnaydomain,
                    'supportpage' =>$supportpage,
                    'httpProtocol' => ($request->isSecure() ? 'https://' : 'http://')
				));

				$resend_email_verification = \Swift_Message::newInstance()->setSubject($subject)
                                        ->setFrom($fromemail)
                                        ->setTo($objUser->getEmail())->setBody($body->getContent())->setContentType('text/html');

				$this->container->get('mailer')->send($resend_email_verification);
				$this->get('session')->getFlashBag()->add('success', "Employee added successfully!");
				return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
			}
		}
		return $this->render('DhiAdminBundle:Employee:new.html.twig', array(
			'form' => $form->createView()
		));
	}
	/* END */

	/* START - Employee Delete Action */
	public function deleteAction(Request $request) {
		$id = $request->get('id');
		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_delete')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to delete employee.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($id);
		if ($user) {
			
                        $objUserService = $user->getUserServices();
                        
                        $totalUserActiveServices = $em->getRepository('DhiUserBundle:UserService')->countTotalActiveServiceList($user);
                        
                        $successInactiveServiceTotal = 0;
                        
                        if($objUserService && $totalUserActiveServices != 0){
                        
                            foreach ($objUserService as $activeService){
                        
                                if($activeService->getService() && $activeService->getStatus() == 1) {
                                    
                                    $serviceName   = $activeService->getService()->getName();
                                    $userName      = $activeService->getUser()->getUserName();
                                    $packageId 	   = $activeService->getPackageId();
                                    
                                    //Inactive IPTV package
                                    if(strtoupper($serviceName) == 'IPTV') {
	            			
			                $wsParam = array();
			                $wsParam['cuLogin'] = $userName;
			                $wsParam['offer']   = $packageId;
			                
			                $selevisionService = $this->get('selevisionService');
			                $wsResponse = $selevisionService->callWSAction('unsetCustomerOffer',$wsParam);

			                if(isset($wsResponse['status']) && !empty($wsResponse['status'])){
			                
			                    if($wsResponse['status'] == 1){
			                    	
                                                $activeService->setStatus(0);
                                                $em->persist($activeService);
                                                $em->flush();
                                                $successInactiveServiceTotal++;
			                    }
			                }
                                    }
                                    //Inactive ISP package
                                    if(strtoupper($serviceName) == 'ISP') {

                                        $cancelUserResponse = $this->get('aradial')->deleteUserFromAradial($userName);

                                        if($cancelUserResponse['status'] == 1) {
                                            
                                            $activeService->setStatus(0);
                                            $em->persist($activeService);
                                            $em->flush();
                                            $successInactiveServiceTotal++;
                                        }
                                    }
                                }
                            }
                        }    
                        
                        if($totalUserActiveServices == $successInactiveServiceTotal){ // check all services is inactive
                            $user->setIsDeleted(1);
                            $user->setExpired(1);
                            $user->setExpiresAt(new DateTime());
                            $em->persist($user);
                            $em->flush();
                            // set audit log delete user
                            $activityLog = array();
                            $activityLog['admin'] = $admin;
                            $activityLog['user'] = $user;
                            $activityLog['activity'] = 'Delete employee';
                            $activityLog['description'] = "Admin " . $admin->getUsername() . " has deleted employee " . $user->getUsername();
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                            $result = array('type' => 'success', 'message' => 'Employee deleted successfully!');
                        }else{
                            $result = array('type' => 'danger', 'message' => 'Some problem for delete employee please try again.');
                        }
			
		} else {
			$activityLog['description'] = "Admin " . $admin->getUsername() . " tried to delete employee";
                        $this->get('ActivityLog')->saveActivityLog($activityLog);
			$result = array('type' => 'danger', 'message' => 'You are not allowed to delete employee!');
		}
		
		$response = new Response(json_encode($result));
		$response->headers->set('Content-Type', 'application/json');
		return $response;
	}
	/* END */

	/* START - Employee Edit Action */
	public function editAction(Request $request, $id) {
		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_update')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to update employee.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($id);
                $email = $user->getEmail();
                $empemail = $user->getEmail();
		if (!$user) {
			$this->get('session')->getFlashBag()->add('failure', "Employee does not exist.");
			return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
		}

		// SET parameters for user audit log for edit Employee
		$activityLog = array(
			'admin' => $admin,
			'ip' => $request->getClientIp(),
			'sessionId' => $request->getSession()->getId(),
			'url' => $request->getUri()
		);
		
		$roles = $user->getRoles();
		$form = $this->createForm(new UserFormType($admin, $user), $user);
		$changePasswordForm = $this->createForm(new ChangePasswordFormType(), $user);
		$userSetting = $em->getRepository('DhiUserBundle:UserSetting')->getUserMaxSetting($id);
		if ($userSetting == null) {
			$userSetting = new UserSetting();
			$userSetting->setUser($user);
			$userSetting->setAdminId($admin->getId());
		}
		$userSettingForm = $this->createForm(new UserSettingFormType(), $userSetting);

		// set default employee settings from Global settings
		if (!$userSetting->getMacAddress()) {
			$userSettingForm->get('mac_address')->setData($this->get('session')->get('mac_address'));
		}
		if (!$userSetting->getMaxDailyTransaction()) {
			$userSettingForm->get('max_daily_transaction')->setData($this->get('session')->get('max_daily_transaction'));
		}

		if ($request->getMethod() == "POST") {

			if ($request->request->has($form->getName())) {
                            
                            
                                $getuserdata = $request->get('dhi_admin_registration');
                              if($email!=$getuserdata['email']){
                                $objUser = $this->getDoctrine()->getManager()->getRepository('DhiUserBundle:User')->checkEmail($getuserdata['email']);

                                if ($objUser) {
                                    $this->get('session')->getFlashBag()->add('danger', "Email Already in used!");

                                    return $this->render('DhiAdminBundle:Employee:edit.html.twig', array(
                                            'form'               => $form->createView(),
                                            'user'               => $user,
                                            'admin'              => $admin,
                                            'role'               => $roles[0],
                                            'changePasswordForm' => $changePasswordForm->createView(),
                                            'userSettingform'    => $userSettingForm->createView(),
                                            'id'                 => $id
                                    ));
                                }
                              }
                            
				$username = $user->getUsername();
				$form->handleRequest($request);
				$formValues = $request->request->get('dhi_admin_registration');
                                
                                $isFormValid = true;
                                $email = $formValues['email'];
                                $firstName = $formValues['firstname'];
                                $lastName = $formValues['lastname'];


                                if(!preg_match('/^[^\'"]*$/', $email)){
                                    $this->get('session')->getFlashBag()->add('failure', "Please enter valid email.");
                                    $isFormValid = false;
                                }

                                if(!preg_match('/^[A-Za-z0-9 _-]+$/', $firstName)){
                                    $this->get('session')->getFlashBag()->add('failure', "Your first name contain characters, numbers and these special characters only - _");
                                    $isFormValid = false;
                                }

                                if(!preg_match('/^[A-Za-z0-9 _-]+$/', $lastName)){
                                    $this->get('session')->getFlashBag()->add('failure', "Your last name contain characters, numbers and these special characters only - _");
                                    $isFormValid = false;
                                }
                                 
				if ($form->isValid() && $isFormValid) {
					$note = $request->request->get('profile-note');
					if ($note) {
						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Employee update information';
						$activityLog['description'] = $note;
						$this->get('ActivityLog')->saveActivityLog($activityLog);
                                                
						if ($getuserdata['email'] != $empemail) {
                                                      
                                                 
                                                 
							$userManager = $this->get('fos_user.user_manager');
							$tokenGenerator = $this->get('fos_user.util.token_generator');
							$token = $tokenGenerator->generateToken();
							$user->setConfirmationToken($token);
							$user->setIsEmailVerified(0);
							$user->setEmailVerificationDate(new DateTime());
                                                            
							$userManager->updateUser($user);
                                                        
                                                        $servicelocationId =  $user->getUserServiceLocation()->getId();
                                                        $getdomain = $em->getRepository('DhiAdminBundle:ServiceLocationWiseSite')->findoneBy(array('serviceLocation'=>$servicelocationId));
               
                                                        if($getdomain){
                                                            $subject = "Welcome " . $user->getUsername() . " to ".$getdomain->getWhiteLabel()->getCompanyName()."!";;
                                                            $fromemail = $getdomain->getWhiteLabel()->getFromEmail();
                                                            $compnayname = $getdomain->getWhiteLabel()->getCompanyName();
                                                            $compnaydomain = $getdomain->getWhiteLabel()->getDomain();
                                                            $supportpage   = $getdomain->getWhiteLabel()->getSupportpage();
                                                        } else {
                                                            $subject = "Welcome " . $user->getUsername() . " to ExchangeVUE!";
                                                            $fromemail = $this->container->getParameter('fos_user.registration.confirmation.from_email');
                                                            $compnayname     = 'ExchangeVUE';
                                                            $compnaydomain   = 'exchangevue.com';
                                                            $supportpage     = 'https://www.facebook.com/dhitelecom';
                                                        }


							$body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:resend_email_verification.html.twig', array(
								'user' => $user,
								'token' => $token,
                                                                'companyname'=>$compnayname
							));

							$resend_email_verification = \Swift_Message::newInstance()
                                                                             ->setSubject($subject)
                                                                             ->setFrom($fromemail)
                                                                             ->setTo($user->getEmail())
                                                                             ->setBody($body->getContent())
                                                                             ->setContentType('text/html');

							$this->container->get('mailer')->send($resend_email_verification);
						}
						$objUser = $form->getData();
						$em->persist($objUser);
						$em->flush();

						// get account details for selevisions account
						$accountArr = $request->request->get('dhi_admin_registration');

						############################ START Selevision API ######################################
						// check selevision api to check whether employee exist in system
						$selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

						// if employee exists, update the details
						if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

							// call selevisions service to update account
							$wsParam = array();
							$wsParam['cuLogin'] = $objUser->getUsername();
							$wsParam['cuNewFirstName'] = $accountArr['firstname'];
							$wsParam['cuNewLastName'] = $accountArr['lastname'];
							$wsParam['cuNewEmail'] = $user->getEmail();

							$wsResponse = $this->get('selevisionService')->callWSAction('updateCustomer', $wsParam);
						} else {

							if ($selevisionUserExist['serviceAvailable'] == 0) {

								//Store API fail log
								$this->get('paymentProcess')->serviceAPIErrorLog($user, 'ProfileUpdate', 'Selevision');
							}
						}
						########################## END Selevision API #################################
						############################# START Aradial API #################################
						//Update profile detail in aradial
						$aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());
						if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1) {
							$wsParam = array();
							$wsParam['Page']                       = "UserEdit";
							$wsParam['Modify']                     = 1;
							$wsParam['UserID']                     = $user->getUsername();
							$wsParam['db_UserDetails.FirstName']   = $accountArr['firstname'];
							$wsParam['db_UserDetails.LastName']    = $accountArr['lastname'];
							$wsParam['db_UserDetails.Address1']    = $accountArr['address'];
							$wsParam['db_UserDetails.City']        = $accountArr['city'];
							$wsParam['db_$GS$UserDetails.State']   = $accountArr['state'];
							$wsParam['db_$GS$UserDetails.Country'] = ($objUser->getCountry()) ? $objUser->getCountry()->getName() : "";
							$wsParam['db_UserDetails.Zip']         = $accountArr['zip'];
							$wsParam['db_UserDetails.Email']       = $user->getEmail();

							$aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
						} else {

							if ($aradialUserExist['serviceAvailable'] == 0) {
								//Store API fail log
								$this->get('paymentProcess')->serviceAPIErrorLog($user, 'ProfileUpdate', 'Aradial');
							}
						}
						########################### END Aradial API ###################################

						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Edit employee';
						$activityLog['description'] = "Admin " . $admin->getUsername() . " has updated employee " . $user->getUsername();
						$this->get('ActivityLog')->saveActivityLog($activityLog);

						$this->get('session')->getFlashBag()->add('success', "Employee updated successfully!");
						return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
					} else {

						$this->get('session')->getFlashBag()->add('failure', "Please enter employee information note!");
						return $this->redirect($request->headers->get('referer'));
					}
				}
			}

			if ($request->request->has($changePasswordForm->getName())) {
				$changePasswordForm->handleRequest($request);
				if ($changePasswordForm->isValid()) {
					$note = $request->request->get('change-password-note');
					if ($note) {
						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Employee update information';
						$activityLog['description'] = $note;
						$this->get('ActivityLog')->saveActivityLog($activityLog);
						//////////////////////////
						// ====================== //

						$changePasswordArr = $request->request->get('dhi_admin_changepassword');
                        $newPassword       = $changePasswordArr['plainPassword']['first'];
                        $userPassword      = $user->getEncryPwd();
                        $isToUpdate = true;
                        if ($changePasswordArr['plainPassword']['first'] == "" || $changePasswordArr['plainPassword']['second'] == "") {
                            $isToUpdate = false;
                        }

                        $isIPTVPwdUdated = false;
						############################### START Selevision API #################################
                        // Check user exits in selevision
                        $selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);
                        if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1 && $isToUpdate == true) {
                            $currentPassword = $selevisionUserExist['password']; // get plain password for selevisions
                            $seleVisionCurrrentPwd = $selevisionUserExist['password']; // get plain password for selevisions

                            if (($user->getNewSelevisionUser() || $currentPassword != $newPassword)) {

                                // call selevisions service to update password
                                $wsParam = array();
                                $wsParam['cuLogin'] = $user->getUsername();
                                $wsParam['cuPwd'] = ($user->getNewSelevisionUser()) ? $seleVisionCurrrentPwd : $currentPassword;
                                $wsParam['cuNewPwd1'] = $newPassword;
                                $wsParam['cuNewPwd2'] = $newPassword;

                                $wsResPwd = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);

                                if ($wsResPwd['status'] == 1) {
                                    $isIPTVPwdUdated = true;
                                    $user->setNewSelevisionUser(0);
                                    $em->persist($user);
                                    $em->flush();
                                }else{
                                    $isToUpdate = false;
                                }
                            }else{
                                if ($currentPassword == $newPassword) {
                                    $isIPTVPwdUdated = true;
                                }
                            }
                        } else {
                            if ($selevisionUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ChangePassword', 'Selevision');
                            }
                        }
                        ############################### END Selevisoin API #########################################
                        ################################ START Aradial API #########################################
                        //Update password in aradial
                        $aradialUserExist = $this->get('aradial')->checkUserExistsInAradial($user->getUserName());

                        if ($aradialUserExist['status'] == 1 && $aradialUserExist['serviceAvailable'] == 1 && $newPassword && $isToUpdate) {

                            $wsParam = array();
                            $wsParam['Page'] = "UserEdit";
                            $wsParam['Modify'] = 1;
                            $wsParam['UserID'] = $user->getUsername();
                            $wsParam['Password'] = $newPassword;

                            $aradialResponse = $this->get('aradial')->callWSAction('updateUser', $wsParam);
                            if (!empty($aradialResponse['status']) && $aradialResponse['status'] == 1) {
                                $isToUpdate = true;
                            }else{
                                $isToUpdate = false;

                                if ($isIPTVPwdUdated == true) {
                                    $wsParam = array();
                                    $wsParam['cuLogin']   = $user->getUsername();
                                    $wsParam['cuPwd']     = $newPassword;
                                    $wsParam['cuNewPwd2'] = $wsParam['cuNewPwd1'] = base64_decode($userPassword);
                                    $wsResetPassword = $this->get('selevisionService')->callWSAction('changeCustomerPwd', $wsParam);
                                    if (empty($wsResetPassword['status']) || (!empty($wsResetPassword['status']) && $wsResetPassword['status'] != 1)) {
                                        
                                        // Send Notification to admin
                                        $setting = $em->getRepository("DhiAdminBundle:Setting")->findOneByName('service_failed_notification_email');
                                        if ($setting) {
                                            $email = $setting->getValue();
                                            $servicetype = "IPTV";
                                            $type        = 'ChangePassword';
                                            $this->get("PackageActivation")->sendNotification($servicetype,$type,$user,$email);
                                        }
                                    }
                                }
                            }
                        } else {
                            if ($aradialUserExist['serviceAvailable'] == 0) {

                                //Store API fail log
                                $this->get('paymentProcess')->serviceAPIErrorLog($user, 'ChangePassword', 'Aradial');
                            }
                        }
                        ################################## END Aradial API ########################################

                        if ($isToUpdate) {
                            $userManager = $this->get('fos_user.user_manager');

                            // password strore in base64
                            $user->setEncryPwd(base64_encode($changePasswordArr['plainPassword']['first']));
                            $userManager->updateUser($user);
                            $this->get('session')->getFlashBag()->add('success', "Password updated successfully!");

                            // set audit log edit Employee
							$activityLog = array();
							$activityLog['admin'] = $admin;
							$activityLog['user'] = $user;
							$activityLog['activity'] = 'Change employee password';
							$activityLog['description'] = "Admin " . $admin->getUsername() . " has changed password for employee " . $user->getUsername();
                            $this->get('ActivityLog')->saveActivityLog($activityLog);
                        }else{
                            $this->get('session')->getFlashBag()->add('failure', "Sorry! Can not change the password.");
                        }

						
						return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
					} else {

						$this->get('session')->getFlashBag()->add('failure', "Please enter employee information note!");
						return $this->redirect($request->headers->get('referer'));
					}
				}
			}


			if ($request->request->has($userSettingForm->getName())) {
				$userSettingForm->handleRequest($request);
				if ($userSettingForm->isValid()) {
					$note = $request->request->get('user-setting-note');
					if ($note) {
						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Employee update information';
						$activityLog['description'] = $note;
						$this->get('ActivityLog')->saveActivityLog($activityLog);
						// ====================== //


						$objSetting = $userSettingForm->getData();
						$em->persist($objSetting);
						$em->flush();

						// set audit log edit user
						$activityLog = array();
						$activityLog['admin'] = $admin;
						$activityLog['user'] = $user;
						$activityLog['activity'] = 'Employee settings';
						$activityLog['description'] = "Admin '" . $admin->getUsername() . "' has updated employee settings for user '" . $user->getUsername() . "'";
						$this->get('ActivityLog')->saveActivityLog($activityLog);

						$this->get('session')->getFlashBag()->add('success', "Settings saved successfully!");
						return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
					} else {

						$this->get('session')->getFlashBag()->add('failure', "Please enter employee information note!");
						return $this->redirect($request->headers->get('referer'));
					}
				}
			}
		}

		//$roles = $user->getRoles();
		return $this->render('DhiAdminBundle:Employee:edit.html.twig', array(
			'form'               => $form->createView(),
			'user'               => $user,
			'admin'              => $admin,
			'role'               => $roles[0],
			'changePasswordForm' => $changePasswordForm->createView(),
			'userSettingform'    => $userSettingForm->createView(),
			'id'                 => $id
		));
	}

	/**
	 * user login log list
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 */
	public function loginLogAction(Request $request, $id) {

		//Check permission

		if (!($this->get('admin_permission')->checkPermission('employee_login_log') || $this->get('admin_permission')->checkPermission('employee_login_log_export') || $this->get('admin_permission')->checkPermission('employee_login_log_print') )) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user log detail.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$user = null;
		if ($id) {
			$user = $em->getRepository('DhiUserBundle:User')->find($id);
		}
		/*

			$searchParams = $request->query->all();

			$form = $this->createForm(new LoginLogSearchFormType(), array(
			'searchParams' => isset($searchParams['search']) ? $searchParams['search'] : ''
			));

			if (isset($searchParams['search']) && !empty($searchParams['search'])) {
			$search = $searchParams['search'];

			// set audit log search user log
			$activityLog = array();
			$activityLog['admin'] = $admin;
			$activityLog['activity'] = 'Search user login log';
			$activityLog['description'] = "Admin " . $admin->getUsername() . " searched criteria name:'{$searchParams['search']['search']}', startDate:'{$searchParams['search']['startDate']}' and endDate:'{$searchParams['search']['endDate']}'";

			if ($user) {

			$activityLog['user'] = $user;
			}

			$this->get('ActivityLog')->saveActivityLog($activityLog);
			$query = $em->getRepository('DhiUserBundle:UserLoginLog')->getUserLoginLogSearch($query, $searchParams['search']);
			}
		 */

		return $this->render('DhiAdminBundle:Employee:loginLog.html.twig', array(
					'admin' => $admin,
					// 'form' => $form->createView(),
					'id' => $id
		));
	}

	public function loginLogListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $id) {

		$loginLogColumns = array('Name', 'IpAddress', 'ServiceLocation', 'ActiveServices', 'AvailableServices', 'Country', 'Logintime');
		$admin = $this->get('security.context')->getToken()->getUser();
		$helper = $this->get('grid_helper_function');
		$gridData = $helper->getSearchData($loginLogColumns);

		if (!empty($gridData['search_data'])) {

			$this->get('session')->set('loginLogSearchData', $gridData['search_data']);
		} else {

			$this->get('session')->remove('loginLogSearchData');
		}

		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {

			$orderBy = 'l.id';
			$sortOrder = 'DESC';
		} else {

			if ($gridData['order_by'] == 'Name') {

				$orderBy = 'u.firstname';
			}
			if ($gridData['order_by'] == 'IpAddress') {

				$orderBy = 'l.ipAddress';
			}
			if ($gridData['order_by'] == 'ServiceLocation') {

				$orderBy = 'sl.name';
			}

			if ($gridData['order_by'] == 'ActiveServices') {

				$orderBy = 'u.activeServices';
			}
			if ($gridData['order_by'] == 'Country') {

				$orderBy = 'c.name';
			}
			if ($gridData['order_by'] == 'Logintime') {

				$orderBy = 'l.createdAt';
			}
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];

		$em = $this->getDoctrine()->getManager();

		$data = $em->getRepository('DhiUserBundle:UserLoginLog')->getUserLoginLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $id);

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

					$em = $this->getDoctrine()->getManager();
					$user = $em->getRepository('DhiUserBundle:User')->find($resultRow->getUser()->getID());
					$userService = $this->get('UserWiseService');
					$data = $userService->getUserService($resultRow->getIpAddress(), $user);
					$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow->getUser()->getID());

					$count = 1;
					$servicesCount = count($activeServices);

					$activeService = '';
					if ($activeServices) {
						foreach ($activeServices as $service) {

							if ($count == $servicesCount) {

								$activeService .= '<span class="btn btn-success btn-sm service">' . $service . '</span>';
							} else {

								$activeService .= '<span class="btn btn-success btn-sm service">' . $service . '</span>';
							}
							$count++;
						}
					}

					$acount = 1;
					$aservicesCount = count($data['services']);

					$availableServices = '';
					if ($data['services']) {
						foreach ($data['services'] as $availableservice) {

							if ($acount == $aservicesCount) {

								$availableServices .= '<span class="btn btn-success btn-sm service">' . $availableservice . '</span>';
							} else {

								$availableServices .= '<span class="btn btn-success btn-sm service">' . $availableservice . '</span>';
							}
							$acount++;
						}
					}



					$row = array();
					$row[] = $resultRow->getUser()->getName();
					$row[] = $resultRow->getIpAddress();
					$row[] = $resultRow->getUser()->getUserServiceLocation() ? $resultRow->getUser()->getUserServiceLocation()->getName() : '';
					$row[] = $activeService;
					$row[] = $availableServices;
					$row[] = $resultRow->getCountry() ? $resultRow->getCountry()->getName() : '';
					$row[] = $resultRow->getCreatedAt()->format('M-d-Y H:i:s');


					$output['aaData'][] = $row;
				}
			}
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}

	public function loginLogExportAction(Request $request, $id) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_login_log_export')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$isSecure = $request->isSecure() ? 'https://' : 'http://';
		$rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
		$dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

		$file_name = 'user_login_log_' . $admin->getUserName() . '_' . date('m-d-Y', time()) . '.pdf'; // Create pdf file name for download

		$searchData = array();

		if ($this->get('session')->has('loginLogSearchData') && $this->get('session')->get('loginLogSearchData') != '') {

			$searchData = $this->get('session')->get('loginLogSearchData');
		}

		// set audit log search user log
		$activityLog = array();
		$activityLog['admin'] = $admin;

		$activityLog['activity'] = 'Export user login log pdf';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " has exported Login Log pdf";
		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$resultUserLoginLog = $em->getRepository('DhiUserBundle:UserLoginLog')->getAllUserLoginLogQuery($searchData);


		$userLoginLog = array();

		if ($resultUserLoginLog) {

			foreach ($resultUserLoginLog as $key => $resultRow) {

				$em = $this->getDoctrine()->getManager();
				$user = $em->getRepository('DhiUserBundle:User')->find($resultRow->getUser()->getID());
				$userService = $this->get('UserWiseService');
				$data = $userService->getUserService($resultRow->getIpAddress(), $user);
				$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow->getUser()->getID());

				$count = 1;
				$servicesCount = count($activeServices);

				$activeService = '';
				if ($activeServices) {
					foreach ($activeServices as $service) {

						if ($count == $servicesCount) {

							$activeService .= $service . '<br>';
						} else {

							$activeService .= $service . '<br>';
						}
						$count++;
					}
				}

				$acount = 1;
				$aservicesCount = count($data['services']);

				$availableServices = '';
				if ($data['services']) {
					foreach ($data['services'] as $availableservice) {

						if ($acount == $aservicesCount) {

							$availableServices .= $availableservice . '<br>';
						} else {

							$availableServices .= $availableservice . '<br>';
						}
						$acount++;
					}
				}


				$userLoginLog[$key]['id'] = $resultRow->getId();
				$userLoginLog[$key]['name'] = $resultRow->getUser()->getName();
				$userLoginLog[$key]['ip'] = $resultRow->getIpAddress();
				$userLoginLog[$key]['location'] = $resultRow->getUser()->getUserServiceLocation() ? $resultRow->getUser()->getUserServiceLocation()->getName() : '';
				$userLoginLog[$key]['activeService'] = $activeService;
				$userLoginLog[$key]['availableServices'] = $availableServices;
				$userLoginLog[$key]['country'] = $resultRow->getCountry() ? $resultRow->getCountry()->getName() : '';
				$userLoginLog[$key]['loginDate'] = $resultRow->getCreatedAt()->format('M-d-Y H:i:s');
			}
		}

                $stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');
                $html = '<style>' . $stylesheet . '</style>';
                $html .= $this->renderView('DhiAdminBundle:Employee:loginLogExport.html.twig', array(
			'userLoginLog' => $userLoginLog
		));

                unset($userLoginLog);

                return new Response(
                    $this->get('knp_snappy.pdf')->getOutputFromHtml($html),
                    200,
                    array(
                        'Content-Type'          => 'application/pdf',
                        'Content-Disposition'   => 'attachment; filename="'.$file_name.'"'
                    )
                );
		// create html to pdf
		/*$pdf = $this->get("white_october.tcpdf")->create();

		// set document information
		$pdf->SetCreator('ExchangeVUE');
		$pdf->SetAuthor('ExchangeVUE');
		$pdf->SetTitle('ExchangeVUE');
		$pdf->SetSubject('Purchase History');

		// set default header data
		$pdf->SetHeaderData('', 0, 'ExchangeVUE', '<img src="' . $dhiLogoImg . '" />');

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, 35, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// set font
		$pdf->SetFont('helvetica', '', 9);

		// add a page
		$pdf->AddPage();

		// Load a stylesheet and render html
		$stylesheet = file_get_contents($rootDirPath . '/../web/bundles/dhiuser/css/pdf.css');

		$html = '<style>' . $stylesheet . '</style>';
		$html .= $this->renderView('DhiAdminBundle:Employee:loginLogExport.html.twig', array(
			'userLoginLog' => $userLoginLog
		));

		// output the HTML content
		$pdf->writeHTML($html);

		// reset pointer to the last page
		$pdf->lastPage();

		// Close and output PDF document
		$pdf->Output($file_name, 'D');
		exit();*/
	}

	public function loginLogExportPrintAction(Request $request, $id) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_login_log_print')) {

			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$isSecure = $request->isSecure() ? 'https://' : 'http://';
		$rootDirPath = $this->container->get('kernel')->getRootDir(); // Get Application Root DIR path
		$dhiLogoImg = $isSecure . $this->getRequest()->getHost() . $this->container->get('templating.helper.assets')->getUrl('bundles/dhiuser/images/logo.png');

		$searchData = array();

		if ($this->get('session')->has('loginLogSearchData') && $this->get('session')->get('loginLogSearchData') != '') {

			$searchData = $this->get('session')->get('loginLogSearchData');
		}

		// set audit log search user log
		$activityLog = array();
		$activityLog['admin'] = $admin;

		$activityLog['activity'] = 'Export user login log print';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " has exported Login Log print";
		$this->get('ActivityLog')->saveActivityLog($activityLog);

		$resultUserLoginLog = $em->getRepository('DhiUserBundle:UserLoginLog')->getAllUserLoginLogQuery($searchData);


		$userLoginLog = array();

		if ($resultUserLoginLog) {

			foreach ($resultUserLoginLog as $key => $resultRow) {

				$em = $this->getDoctrine()->getManager();
				$user = $em->getRepository('DhiUserBundle:User')->find($resultRow->getUser()->getID());
				$userService = $this->get('UserWiseService');
				$data = $userService->getUserService($resultRow->getIpAddress(), $user);
				$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($resultRow->getUser()->getID());

				$count = 1;
				$servicesCount = count($activeServices);

				$activeService = '';
				if ($activeServices) {
					foreach ($activeServices as $service) {

						if ($count == $servicesCount) {

							$activeService .= $service . '<br>';
						} else {

							$activeService .= $service . '<br>';
						}
						$count++;
					}
				}

				$acount = 1;
				$aservicesCount = count($data['services']);

				$availableServices = '';
				if ($data['services']) {
					foreach ($data['services'] as $availableservice) {

						if ($acount == $aservicesCount) {

							$availableServices .= $availableservice . '<br>';
						} else {

							$availableServices .= $availableservice . '<br>';
						}
						$acount++;
					}
				}


				$userLoginLog[$key]['id'] = $resultRow->getId();
				$userLoginLog[$key]['name'] = $resultRow->getUser()->getName();
				$userLoginLog[$key]['ip'] = $resultRow->getIpAddress();
				$userLoginLog[$key]['location'] = $resultRow->getUser()->getUserServiceLocation() ? $resultRow->getUser()->getUserServiceLocation()->getName() : '';
				$userLoginLog[$key]['activeService'] = $activeService;
				$userLoginLog[$key]['availableServices'] = $availableServices;
				$userLoginLog[$key]['country'] = $resultRow->getCountry() ? $resultRow->getCountry()->getName() : '';
				$userLoginLog[$key]['loginDate'] = $resultRow->getCreatedAt()->format('M-d-Y H:i:s');
			}
		}
		return $this->render('DhiAdminBundle:Employee:loginLogPrint.html.twig', array(
					'userLoginLog' => $userLoginLog,
					'img' => $dhiLogoImg
		));
	}

	public function loginLogPrintAction(Request $request, $id) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_login_log_print')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to export user login log.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$user = null;
		if ($id) {
			$user = $em->getRepository('DhiUserBundle:User')->find($id);
		}

		$activityLog = array();
		$activityLog['admin'] = $admin;

		$desc = "";
		if ($user) {
			$activityLog['user'] = $user;
			$desc = " of user " . $user->getUsername();
		}

		$activityLog['activity'] = 'Print user login log';
		$activityLog['description'] = "Admin " . $admin->getUsername() . " has print Login Log " . $desc;
		$this->get('ActivityLog')->saveActivityLog($activityLog);

		/* END: add user audit log for user-login-log export */
	}

	public function changeServiceStatusAction($userId, $serviceSettingId) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_service_setting')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to change user services.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$admin = $this->get('security.context')->getToken()->getUser();

		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($userId);
		$setting = $em->getRepository('DhiUserBundle:UserServiceSetting')->find($serviceSettingId);
		if ($setting) {
			if ($setting->getService()->getName() == 'IPTV') {

				$setting->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Enabled' : 'Disabled');
				$em->persist($setting);

				// Add User's service log
				$objUserServiceSettingLog = new UserServiceSettingLog();
				$objUserServiceSettingLog->setService($setting->getService());
				$objUserServiceSettingLog->setUser($user);
				$objUserServiceSettingLog->setServiceStatus(($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated');
				$em->persist($objUserServiceSettingLog);

				$em->flush();

				// Set log user service status change
				$activityLog = array();
				$activityLog['admin'] = $admin;
				$activityLog['user'] = $user;
				$activityLog['activity'] = 'User service status change';
				$activityLog['description'] = "Admin " . $admin->getUsername() . " has change status of " . $user;
				$this->get('ActivityLog')->saveActivityLog($activityLog);

				############################### START Selevision API #################################
				$wsService = ($setting->getServiceStatus() == 'Disabled') ? 'deactivateCustomer' : 'reactivateCustomer';

				// check selevision api to check whether Employee exist in system
				$selevisionUserExist = $this->get('selevisionService')->checkUserExistInSelevision($user);

				// if Employee exists, Disable/Reactivate Employee
				if ($selevisionUserExist['status'] == 1 && $selevisionUserExist['serviceAvailable'] == 1) {

					$wsParam = array();
					$wsParam['cuLogin'] = $user->getUsername();
					$wsResponse = $this->get('selevisionService')->callWSAction($wsService, $wsParam);
				} else {

					if ($selevisionUserExist['serviceAvailable'] == 0) {

						//Store API fail log
						$this->get('paymentProcess')->serviceAPIErrorLog($user, ucfirst($wsService), 'Aradial');
					}
				}
				######################### END Selevision API #################################

				$serviceStatus = ($setting->getServiceStatus() == 'Disabled') ? 'Disabled' : 'Reactivated';
				$this->get('session')->getFlashBag()->add('success', "Service " . $setting->getService()->getName() . " " . $serviceStatus . " for user " . $user->getUsername());
			}
		}
		return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
	}

	public function serviceDetailAction(Request $request, $id) {

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_purchase_detail')) {

			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to purchase for employee.");
			return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
		}

		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($id);
		$admin = $this->get('security.context')->getToken()->getUser();
		$selectedServiceLocation = $this->get('session')->get('selectedServiceLocation');
		$adminIp = $this->get('session')->get('ipAddress');

		$isOnlySingleLocation = 0;

		if (!$user) {

			throw $this->createNotFoundException('Invalid Page Request');
		}

		if (count($admin->getServiceLocations()) == 1) {

			$isOnlySingleLocation = 1;

			$serviceLocation = $admin->getServiceLocations();
			if ($serviceLocation) {

				$country = ($serviceLocation[0]->getCountry()) ? $serviceLocation[0]->getCountry() : NULL;

				$user->setGeoLocationCountry($country);
				$user->setUserServiceLocation($serviceLocation[0]);
				$user->setIpAddress($adminIp);
				$user->setIpAddressLong(ip2long($adminIp));

				$em->persist($user);
				$em->flush();
			}
		}

		$serviceLocations = array();
        if ($admin->getGroup() != 'Super Admin') {
            $adminLocation = $admin->getServiceLocations();
            if (!empty($adminLocation)) {
                foreach ($adminLocation as $key => $value) {
                    $serviceLocations[] = array('id' => $value->getId(), 'name' => $value->getName());
                }
            }
        } else {
            $serviceLocations= $em->getRepository("DhiAdminBundle:ServiceLocation")->getAllServiceLocation();
        }

		$summaryData = $this->get('DashboardSummary')->getUserServiceSummary('admin', $user);

		$locationId = '';
		if ($user->getUserServiceLocation()) {
			$locationId = $user->getUserServiceLocation()->getId();
		}

		return $this->render('DhiAdminBundle:Employee:userPurchaseDetail.html.twig', array(
			'user' => $user,
			'summaryData' => $summaryData,
			'isOnlySingleLocation' => $isOnlySingleLocation,
			'serviceLocations' => $serviceLocations,
			'userLocationId' => $locationId
		));
	}

	public function addIptvPackageAction($userId) {

		$admin = $this->get('security.context')->getToken()->getUser();

		//Check permission
		if (!$this->get('admin_permission')->checkPermission('employee_service_setting')) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to add package.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($userId);

		$wsParam = array();
		$wsRespose = $this->get('selevisionService')->callWSAction('getAllOffers', $wsParam);

		$packages = array();
		if ($wsRespose['status'] == 1) {

			$packages = $wsRespose;
		}

		$view = array();
		$view['user'] = $user;
		$view['packages'] = $packages;

		return $this->render('DhiAdminBundle:Employee:addIptvPackage.html.twig', $view);
	}

	public function viewAction(Request $request, $id) {

		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('DhiUserBundle:User')->find($id);

		if (!$user) {

			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to view user detail.");
			return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
		}

		// check user mac address how many add maximum
		$userSetting = $em->getRepository('DhiUserBundle:UserSetting')->findOneBy(array('user' => $user));

		if (isset($userSetting) && $userSetting && $userSetting->getMacAddress()) {

			$request->getSession()->set('mac_address', $userSetting->getMacAddress());
		}

		if (isset($userSetting) && $userSetting && $userSetting->getMacAddress()) {

			$request->getSession()->set('max_daily_transaction', $userSetting->getMacAddress());
		}


		// get user mac address
		$objMacAddress = $em->getRepository("DhiUserBundle:UserMacAddress")->findBy(array('user' => $user));

		$activeServiceList = $em->getRepository("DhiUserBundle:UserService")->activeServiceList($user->getUserServices());


		return $this->render('DhiAdminBundle:Employee:view.html.twig', array('user' => $user, 'userMacAddress' => $objMacAddress, 'activeServiceList' => $activeServiceList));
	}

	public function refundAction(Request $request) {

		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		$defaultRefundType = array('ISP', 'IPTV', 'IPTVPremium','BUNDLE');

		$userId = $request->get('userId');
		$userServiceId = $request->get('userServiceId');
		$packageType = $request->get('packageType');
		$confirmPage = $request->get('confirmPage');
		$submitRefundPayment = $request->get('submitRefundPayment');
		$refundAmount = $request->get('processAmount');
		$finalRefundAmount = $request->get('finalRefundAmount');

		if (!$userId && !in_array($packageType, $defaultRefundType)) {

			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to refund amount.");
			return $this->redirect($this->generateUrl('dhi_admin_employee_list'));
		}

		$isAddOn = 0;
		$serviceTypeArr = array();
		if ($packageType == 'ISP' || $packageType == 'BUNDLE') {

			$serviceTypeArr = array('ISP', 'IPTV');
		}

		if ($packageType == 'IPTV') {

			$serviceTypeArr = array('IPTV');
		}

		if ($packageType == 'IPTVPremium') {

			$serviceTypeArr = array('IPTV');
			$isAddOn = 1;
		}

		$refundSummary = $em->getRepository("DhiUserBundle:UserService")->getRefundUserServiceData($userId, $serviceTypeArr, $isAddOn, $userServiceId);

		$user = $em->getRepository("DhiUserBundle:User")->find($userId);
		//echo "<pre>";print_r($refundSummary);exit;
		//Get user's active package from selevision
		$selevisionService = $this->get('selevisionService');
		$selevisionIPTVActivePackageIds = $selevisionService->getActivePackageIds($user->getUsername()); //$user->getUsername()

		if ($request->isXmlHttpRequest() && ($submitRefundPayment == 1 || $confirmPage == 1)) {

			$displayJsonResponse = false;

			$jsonResponse = array();
			$jsonResponse['status'] = 'failed';
			$jsonResponse['msg'] = 'Error No: #1003, Something went wrong in ajax request. please again';

			if ($refundAmount >= 0) {

				if ($refundAmount > $refundSummary['TotalOriginalAmt']) {

					$jsonResponse['msg'] = 'Refund amount $' . $refundAmount . ' is not valid.';

					$displayJsonResponse = true;
				}
			} else {

				$jsonResponse['msg'] = 'Please check refund amount.';
				$displayJsonResponse = true;
			}

			//Check refund package available in user's selevision account
			$IPTVPackageId = $request->get('IPTVPackageId');
			$IPTVPackageName = $request->get('IPTVPackageName');
			$ISPPackageId = $request->get('ISPPackageId');
			$ISPPackageName = $request->get('ISPPackageName');
			$refundServiceId = $request->get('refundServiceId');

			if ($IPTVPackageId && in_array('IPTV', $serviceTypeArr)) {
				$i = 0;
				foreach ($IPTVPackageId as $packageId) {

					if (!in_array($packageId, $selevisionIPTVActivePackageIds)) {

						if ($i == 0) {

							$jsonResponse['msg'] = '';
						}

						$jsonResponse['msg'] .= $IPTVPackageName[$packageId] . " package not exist in user's selevision account.<br/>";
						$displayJsonResponse = true;

						$i++;
					}
				}
			}
			//End here


			if ($submitRefundPayment == 1) {

				$displayJsonResponse = true;
				$isPaymentRefundedSuccess = false;

				if ($refundAmount == 0 && $refundSummary['TotalRefundAmt'] == 0) {

					$isPaymentRefundedSuccess = true;
				} else {

					$isPaymentRefundedSuccess = true;
				}
				//After success refund payment update status into database and remove package from selevision account
				if ($isPaymentRefundedSuccess) {

					$packRefundAmount = 0;
					$totalRefundAmount = 0;
					$totalRefundedAmount = 0;
					$isRefundedAnyOnePack = false;
					$purchaseOrderDetail = array();
					$i = 0;

					$userServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServiceFromIds($refundServiceId);

					if ($userServices) {

						foreach ($userServices as $userService) {


							if ($userService->getPurchaseOrder() && $i == 0) {

								$purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] = 0;
							}
							$doProcessRefund = false;
							$servicePurchase = $userService->getServicePurchase();

							//Calculate package validity remain days
							$todayDateTime = new \DateTime();
							$expiredDate = $userService->getExpiryDate();

							$intervalRemainDays = $todayDateTime->diff($expiredDate);
							$remainingDays = $intervalRemainDays->format('%a');
							//End here

							$finalCost = $userService->getFinalCost();
							// $prevCredit = $userService->getUnusedCredit();
							// if($prevCredit > 0){
							// 	$finalCost += $prevCredit;
							// }

							$perDayAmount = $finalCost / $userService->getValidity(); //Per day package price
							$packRefundAmount = $perDayAmount * $remainingDays; //Remaining days package amount
							$totalRefundAmount += $packRefundAmount;

							if ($userService->getService() && $userService->getIsAddOn() == $isAddOn) {

								$serviceName = strtoupper($userService->getService()->getName());

								if (in_array($serviceName, $serviceTypeArr)) {

									if ($isAddOn == 1) {

										if ($userService->getIsAddOn() == 1 && $userService->getId() == $userServiceId) {

											$doProcessRefund = true;
										}
									} else {

										$doProcessRefund = true;
									}

									if ($doProcessRefund) {

										$isPackageRefunded = false;
										if ($serviceName == 'IPTV') {

											//Call Selevision webservice for unset package
											$wsOfferParam = array();
											$wsOfferParam['cuLogin'] = $userService->getUser()->getUserName();
											$wsOfferParam['offer'] = $userService->getPackageId();

											$selevisionService = $this->get('selevisionService');
											$wsRes = $selevisionService->callWSAction('unsetCustomerOffer', $wsOfferParam);

											if ($wsRes['status'] == 1) {

												$isPackageRefunded = true;
											}
										}

										if ($serviceName == 'ISP') {

											######## Update Password in Aradial ####################
											if ($this->get('aradial')->checkUserExistsInAradial($userService->getUser()->getUserName())) {
												$wsParam                           = array();
												$wsParam['Page']                   = 'UserSessions';
												$wsParam['SessionsMode']           = 'UsrAllSessions';
												$wsParam['qdb_Users.UserID']       = $userService->getUser()->getUserName();
												$wsParam['op_$D$AcctSessionTime']  = "<>";
												$wsParam['qdb_$D$AcctSessionTime'] = " ";

												$aradial = $this->get('aradial');
												$wsResponse = $aradial->callWSAction('getUserSessionHistory', $wsParam);

												if (!empty($wsResponse['userSession'])) {

													foreach ($wsResponse['userSession'] as $user) {

														$userName = $user['UserID'];
														$nasName = $user['NASName'];
														$startTime = $user['InTime'];
														$stopTime = $user['TimeOnline'];
														$framedAddress = $user['FramedAddress'];
														$callerId = $user['CallerId'];
														$calledId = $user['CalledId'];
														$acctSessionTime = $user['AcctSessionTime'];
														$isRefunded = 1;
														if(!empty($acctSessionTime)) {

															if (!empty($userName)) {
																$userData = $em->getRepository('DhiUserBundle:User')->getEmailForAradialUser($userName);
															}

															if (!empty($userData)) {
																$email = $userData[0]['email'];
															} else {
																$email = '';
															}

															$Param = array();
															$Param['Page'] = 'UserHit';
															$Param['qdb_Users.UserID'] = $userName;

															if (empty($email)) {
																$wsResponse1 = $aradial->callWSAction('getUserList', $Param);
																if (!empty($wsResponse1['userList'])) {
																	$email = empty($wsResponse1['userList'][0]['UserDetails.Email']) ? '' : $wsResponse1['userList'][0]['UserDetails.Email'];
																}
															}

															$objUserSession = new UserSessionHistory();
															$objUserSession->setUserName($userName);
															$objUserSession->setEmail($email);
															$objUserSession->setNasName($nasName);
															$objUserSession->setStartTime($startTime);
															$objUserSession->setStopTime($stopTime);
															$objUserSession->setCallerId($callerId);
															$objUserSession->setCalledId($calledId);
															$objUserSession->setFramedAddress($framedAddress);
															$objUserSession->setIsRefunded($isRefunded);
                                                            if (!empty($startTime)) {
																$objUserSession->setStartDateTime(new \DateTime($startTime));
															}
															if (!empty($stopTime) && !empty($startTime)) {
																list($hours, $minutes, $seconds) = sscanf($stopTime, '%d:%d:%d');
                                                                $sTime = new \DateTime($startTime);
                                                                $objStopTime = clone $sTime;
                                                                $seconds = ($hours * 3600) + ($minutes * 60) + $seconds;
                                                                $objStopTime->modify('+'.$seconds.' seconds');
                                                                $objUserSession->setStopDateTime($objStopTime);
															}
															$em->persist($objUserSession);
															$em->flush();
														}
													}
												}

												// Delete existing user from aradial
												$cancelingUsrParam = array();
												$cancelingUsrParam['Page'] = "UserEdit";
												$cancelingUsrParam['UserId'] = $userService->getUser()->getUserName();
												$cancelingUsrParam['ConfirmDelete'] = 1;

												$cancelUsrResponse = $this->get('aradial')->callWSAction('cancelUser', $cancelingUsrParam);
											}
											############ END Here ##################################

											$isPackageRefunded = true;
										}

										if ($isPackageRefunded) {

											//Update user service data
											$userService->setRefund(1);
											$userService->setRefundAmount($refundAmount);
											$userService->setStatus(0);
											$userService->setRefundedBy($admin);
											$userService->setRefundedAt(new \DateTime());
											$em->persist($userService);

											//Update service purchase data
											if ($servicePurchase) {

												$servicePurchase->setPaymentStatus('Refunded');
												$servicePurchase->setRechargeStatus(2);
												$em->persist($servicePurchase);
											}

											if ($userService->getPurchaseOrder()) {

												$purchaseOrderDetail[$userService->getPurchaseOrder()->getId()]['totalRefundAmount'] += $packRefundAmount;
											}

											$totalRefundedAmount += $packRefundAmount;
											$isRefundedAnyOnePack = true;
										}
									}
								}
							}
							$i++;
						}


						if ($isRefundedAnyOnePack) {

							$paymentStatus = 'Refunded';
							if ($purchaseOrderDetail) {

								foreach ($purchaseOrderDetail as $key => $val) {

									$totalRefundAmount = $val['totalRefundAmount'];
									$purchaseOrder = $em->getRepository('DhiServiceBundle:PurchaseOrder')->find($key);

									if ($purchaseOrder) {

										if ($purchaseOrder->getUserService()) {

											foreach ($purchaseOrder->getUserService() as $service) {

												if ($service->getStatus() == 1) {

													$paymentStatus = 'Completed';
												}
											}
										}

										$purchaseOrder->setPaymentStatus($paymentStatus);
                                        $purchaseOrder->setRefundAmount($refundAmount);

										$em->persist($purchaseOrder);
									}
								}

								$jsonResponse['status'] = 'success';
								$jsonResponse['msg'] = "$" . $refundAmount . " has been refunded successfully.";
							}
						}
						$em->flush();
					} else {

						$jsonResponse['msg'] = 'Data not exist for refund payment.';
					}
				}
			}

			if ($displayJsonResponse) {

				echo json_encode($jsonResponse);
				exit;
			}
		}

		$view = array();
		$view['refundSummary'] = $refundSummary;
		$view['packageType'] = $packageType;
		$view['userId'] = $userId;
		$view['userServiceId'] = $userServiceId;
		$view['refundAmount'] = $refundAmount;
		$view['finalRefundAmount'] = $finalRefundAmount;

		if ($confirmPage) {

			return $this->render('DhiAdminBundle:Employee:confirmRefundPayment.html.twig', $view);
		} else {

			return $this->render('DhiAdminBundle:Employee:refundPayment.html.twig', $view);
		}
	}

	public function getUserServiceDetailAction($userId, $ipAddress) {
												$em = $this->getDoctrine()->getManager();

												$user = $em->getRepository('DhiUserBundle:User')->find($userId);

												$userService = $this->get('UserWiseService');
												$data = $userService->getUserService($ipAddress, $user);

												$activeServices = $em->getRepository('DhiUserBundle:UserService')->getActiveServices($userId);

												return $this->render('DhiAdminBundle:Employee:userServiceLocation.html.twig', array('services' => $data['services'], 'location' => $data['location'], 'activeServices' => $activeServices));
	}

	public function logAction(Request $request, $id) {
		//Check permission
		$admin = $this->get('security.context')->getToken()->getUser();
		$em = $this->getDoctrine()->getManager();

		if (!($this->get('admin_permission')->checkPermission('employee_login_log') || $this->get('admin_permission')->checkPermission('employee_login_log_export') || $this->get('admin_permission')->checkPermission('employee_login_log_print') )) {
			$this->get('session')->getFlashBag()->add('failure', "You are not allowed to view employee log detail.");
			return $this->redirect($this->generateUrl('dhi_admin_dashboard'));
		}

		$user = null;
		if ($id) {
			$user = $em->getRepository('DhiUserBundle:User')->find($id);
			if ($user) {
				$userName = $user->getUserName();
				$query = $em->getRepository('DhiUserBundle:UserActivityLog')->getAllActivityLogs();
			}
		}

		return $this->render('DhiAdminBundle:Employee:log.html.twig', array(
					'admin' => $admin,
					'id' => $id,
		));
	}

	public function activityLogListJsonAction($orderBy = "id", $sortOrder = "asc", $search = "all", $offset = 0, $id) {

		$em = $this->getDoctrine()->getManager();
		$aColumns = array('Admin', 'User', 'Activity', 'Description', 'IP', 'Date Time');
		$admin = $this->get('security.context')->getToken()->getUser();
		$helper = $this->get('grid_helper_function');
		$gridData = $helper->getSearchData($aColumns);

		$sortOrder = $gridData['sort_order'];
		$orderBy = $gridData['order_by'];

		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => 0,
			"iTotalDisplayRecords" => 0,
			"aaData" => array()
		);

		$user = $em->getRepository('DhiUserBundle:User')->find($id);
		if ($user) {
			$userName = $user->getUserName();
		} else {
			$response = new Response(json_encode($output));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}

		if ($gridData['sort_order'] == '' && $gridData['order_by'] == '') {
			$orderBy = 'al.id';
			$sortOrder = 'DESC';
		} else {
			if ($gridData['order_by'] == 'Admin') {

				$orderBy = 'al.admin';
			}

			if ($gridData['order_by'] == 'User') {

				$orderBy = 'al.user';
			}

			if ($gridData['order_by'] == 'Activity') {

				$orderBy = 'al.activity';
			}

			if ($gridData['order_by'] == 'IP') {

				$orderBy = 'al.ip';
			}
			if ($gridData['order_by'] == 'Date Time') {

				$orderBy = 'al.timestamp';
			}
		}

		// Paging
		$per_page = $gridData['per_page'];
		$offset = $gridData['offset'];
		$data = $em->getRepository('DhiUserBundle:UserActivityLog')->getActivityLogGridList($per_page, $offset, $orderBy, $sortOrder, $gridData['search_data'], $gridData['SearchType'], $helper, $admin, $userName);


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
					$row[] = $resultRow->getAdmin() ? $resultRow->getAdmin() : 'N/A';
					$row[] = (($resultRow->getUser() && $resultRow->getAdmin() == "") ? $resultRow->getUser() : 'N/A');
					$row[] = $resultRow->getActivity();
					$row[] = $resultRow->getDescription();
					$row[] = $resultRow->getIp();
					$row[] = $resultRow->getTimestamp()->format('m/d/Y H:i:s');
					$output['aaData'][] = $row;
				}
			}
		}

		$response = new Response(json_encode($output));
		$response->headers->set('Content-Type', 'application/json');

		return $response;
	}
}
