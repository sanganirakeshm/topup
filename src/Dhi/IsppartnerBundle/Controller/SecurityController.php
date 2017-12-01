<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Dhi\IsppartnerBundle\Controller;

//use FOS\UserBundle\Controller\SecurityController as Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dhi\AdminBundle\Entity\ServicePartner;
use Symfony\Component\HttpFoundation\Session\Session;
use Dhi\IsppartnerBundle\Form\Type\ResetPartnerPasswordType;

//use Symfony\Component\BrowserKit\Request;

class SecurityController extends Controller {

//    protected function renderLogin(array $data) {
//        $user = $this->get('security.context')->getToken()->getUser();
//        if (is_object($user)) {
//           
//            $request = $this->get('request');
//            $roles = $user->getRoles();
//            if (strpos($request->getPathInfo(), '/isp-partner/') !== false && $roles['0'] != 'ROLE_USER') {
//                $url = $this->generateUrl('dhi_admin_dashboard');
//                return new RedirectResponse($url);
//            }
//        }
//        return $this->render('DhiIsppartnerBundle:Security:login.html.twig', $data);
//    }
    public function loginAction(Request $request) {
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        if (!empty($user['islogin'])) {
            return $this->redirect($this->generateUrl('dhi_isppartner_dashboard'));
        } else {
            return $this->render('DhiIsppartnerBundle:Security:login.html.twig');
        }
    }

    public function checkAction(Request $request) {

        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        if (!empty($user['islogin'])) {
            return $this->redirect($this->generateUrl('dhi_isppartner_dashboard'));
        } else {
            $login = FALSE;
            if (isset($_COOKIE['userInfo'])) {
                $userInfo = json_decode($_COOKIE['userInfo']);
                $username = $userInfo->_username;
                $password = $userInfo->_password;
                $remember_me = 1;
                $login = FALSE;
            }

            if ($request->getMethod() === 'POST') {
                $username = $request->get('_username');
                $password = $request->get('_password');

                $remember_me = $request->get('_remember_me');
                $login = TRUE;
            }

            if ($login) {
                if (!empty($username) && !empty($password)) {

                    $flag = FALSE;
                    $em = $this->getDoctrine()->getManager();
                    $ServicePartnerData = $em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array('username' => $username));
                    if (!empty($ServicePartnerData)) {
                        if ($ServicePartnerData->getStatus() == TRUE) {
                            if ($ServicePartnerData != NULL) {
                                $flag = TRUE;
                            }
                            if ($flag) {
                                if (password_verify($password, $ServicePartnerData->getPassword())) {
                                    $userInfo = array(
                                        '_username' => $username,
                                        '_password' => $password
                                    );
                                    $userInfo = json_encode($userInfo);
                                    if ($remember_me == 1) {
                                        setcookie("userInfo", $userInfo, time() + 60 * 60 * 24 * 100);
                                        $data = json_decode($userInfo, TRUE);
                                    } else {
                                        setcookie("userInfo", $userInfo, time() - 60 * 60);
                                    }
                                    $session = $request->getSession();
                                    $session->set('service_partner_islogin', TRUE);
                                    $session->set('service_partner_id', $ServicePartnerData->getId());
                                    $session->set('service_partner_username', $ServicePartnerData->getUsername());
                                    $session->set('service_partner_name', $ServicePartnerData->getName());
                                    
                                    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwyz0123456789';
                                    $randSessionId = time();
                                    $random_string_length = $this->container->getParameter('ispPartner_sessionId_max_length');
                                    for ($i = 0; $i < $random_string_length; $i++) {
                                        $randSessionId .= $characters[rand(0, strlen($characters) - 1)];
                                    }
                                    $session->set('ispPartnerSessionId', $randSessionId);
                                    
                                    //Activity log
                                    $activityLog = array();
                                    //$activityLog['admin'] = 'N/A';
                                    $activityLog['ispuser'] = $ServicePartnerData->getUsername();
                                    $activityLog['activity'] = 'Login';
                                    $activityLog['description'] = "ISP Partner " . $ServicePartnerData->getUsername() . " has logged in.";
                                    $this->get('ActivityLog')->saveActivityLog($activityLog);
                                    return $this->redirect($this->generateUrl('dhi_isppartner_dashboard'));
                                } else {
                                    $this->get('session')->getFlashBag()->add('danger', "Invalid credentials.");
                                    $error = "Invalid Passoword !!";
                                }
                            } else {
                                $error = "Invalid Username !!";
                            }
                        } else {
                            if ($ServicePartnerData->getStatus() == FALSE) {
                                $this->get('session')->getFlashBag()->add('danger', "Your account is Inactive, Please contact Site Admin.");
                                $error = "Your account is Inactive, Please contact Site Admin.";
                            } else {
                                $this->get('session')->getFlashBag()->add('danger', "Your account is Deleted, Please contact Site Admin.");
                                $error = "Your account is Deleted, Please contact Site Admin.";
                            }
                        }
                    } else {
                        $this->get('session')->getFlashBag()->add('danger', "Invalid credentials.");
                        $error = "Invalid credentials.";
                    }
                } else {
                    $this->get('session')->getFlashBag()->add('danger', "Username/Password can not be blank !!");
                    $error = "Username/Password can not be blank !!";
                }
            }
            return $this->render('DhiIsppartnerBundle:Security:login.html.twig');
        }
    }

    public function resetpasswordAction(Request $request) {
        $user = array();
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        $user['id'] = $this->container->get('request')->getSession()->get('service_partner_id');
        $user['username'] = $this->container->get('request')->getSession()->get('service_partner_username');
        $user['name'] = $this->container->get('request')->getSession()->get('service_partner_name');
        if (empty($user['islogin'])) {
            return $this->redirect($this->generateUrl('isppartner_login'));
        }
        $em = $this->getDoctrine()->getManager();
        $servicePartner = $em->getRepository('DhiAdminBundle:ServicePartner')->findOneBy(array('id' => $user['id']));
        
        if (null === $servicePartner) {
            $this->get('session')->getFlashBag()->add('error', "This link is expired.");
            return $this->render('DhiIsppartnerBundle:Security:login.html.twig');
        }

        $form = $this->createForm(new ResetPartnerPasswordType(), $servicePartner);
        
        if ($request->getMethod() == "POST") {
            $formData = $request->get('dhi_isppartner_user');
            $dbPass = $servicePartner->getPassword();

            $form->handleRequest($request);
            if ($form->isValid()) {

                // check on current password 
                $current_password = $formData['current_password'];
                if (password_verify($current_password, $dbPass)) {
                    $pass = $formData['password']['first'];
                    $servicePartner->setPassword(password_hash($pass, PASSWORD_BCRYPT));
                    $em->persist($servicePartner);
                    $em->flush();

                    //Activity log
                    $activityLog = array();
                    //$activityLog['admin'] = 'N/A';
                    $activityLog['ispuser'] = $user['username'];
                    $activityLog['activity'] = 'Change password';
                    $activityLog['description'] = "ISP Partner " . $user['username'] . " has Changed pasword.";
                    $this->get('ActivityLog')->saveActivityLog($activityLog);

                    $this->get('session')->getFlashBag()->add('success', "Password has been changed successfully.");
                    return $this->redirect($this->generateUrl('isppartner_login'));
                } else {
                    $this->get('session')->getFlashBag()->add('danger', "Current password in not matched with system.");
                    return $this->redirect($this->generateUrl('dhi_isppartner_resetpassword'));
                }
            }
        }

        return $this->render('DhiIsppartnerBundle:Security:resetpassword.html.twig', array(
                    'form' => $form->createView(),
                    'id' => $user['id'],
                    'user' => $user,
        ));
    }

    public function logoutAction() {
        $user['islogin'] = $this->container->get('request')->getSession()->get('service_partner_islogin');
        if (empty($user['islogin'])) {
            return $this->redirect($this->generateUrl('isppartner_login'));
        } else {
            $user['username'] = $this->container->get('request')->getSession()->get('service_partner_username');
            $this->container->get('request')->getSession()->remove('service_partner_islogin');
            $activityLog = array();
            //$activityLog['admin'] = 'N/A';
            $activityLog['ispuser'] = $user['username'];
            $activityLog['activity'] = 'Logout';
            $activityLog['description'] = "ISP Partner " . $user['username'] . " has logged out.";
            $this->get('ActivityLog')->saveActivityLog($activityLog);
            $this->container->get('request')->getSession()->remove('ispPartnerSessionId');
            return $this->redirect($this->generateUrl('isppartner_login'));
        }
    }

    public function editAction(Request $request, $id) {
        
    }

}
