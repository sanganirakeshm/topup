<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use FOS\UserBundle\Model\UserInterface;
use \DateTime;
use FOS\UserBundle\Mailer\MailerInterface;
use Dhi\UserBundle\Entity\Support;
use Dhi\UserBundle\Entity\SupportCategory;
use Dhi\UserBundle\Form\Type\SupportFormType;
use Symfony\Component\Validator\Constraints\NotBlank;

class SupportController extends Controller {

    public function supportAction(Request $request) {
        $em = $this->getDoctrine()->getManager();

        $user = $this->get('security.context')->getToken()->getUser();

        $objSupport = new Support();
        $form = $this->createForm(new SupportFormType($this->container), $objSupport);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $category = $objSupport->getCategory();
                $location = $objSupport->getLocation();

                if (!$location) {
                    $this->get('session')->getFlashBag()->add('failure', 'Please select location.');
                }else{
                    $servicename = $objSupport->getSupportService()->getServiceName();
                    $body = $this->container->get('templating')->renderResponse('DhiUserBundle:Emails:support_team_email.html.twig', array('data' => $objSupport, "location" => $location->getName(), "category" => $category, "service" => $servicename));
                    $whiteLabelBrand = $this->get('session')->get('brand');
                    
                    $objsolarwindsrequesttype = $em->getRepository("DhiUserBundle:SolarWindsSupportLocation")->findOneby(array('supportLocation'=>$location->getId()));
                   
                    $solarwindId = $objsolarwindsrequesttype->getSolarWindsRequestType()->getSolarWindId();
                    
                    
                    $solarwinds               = $this->container->get('Solarwinds');
                    $sWRequest                = array();
                    $sWRequest['email']       = $objSupport->getEmail();
                    $sWRequest['firstName']   = $objSupport->getFirstName();
                    $sWRequest['lastName']    = $objSupport->getLastName();
                    $sWRequest['phone']       = $objSupport->getNumber();
                    $sWRequest['location']    = $location->getName();
                    $sWRequest['time']        = $objSupport->getTime();
                    $sWRequest['service']     = $objSupport->getSupportService()->getServiceName();
                    $sWRequest['roomNumber']  = $objSupport->getRoomNumber();
                    $sWRequest['building']    = $objSupport->getBuilding();
                    $sWRequest['message']     = $objSupport->getMessage();
                    $sWRequest['category']    = $category->getName();
                    $sWRequest['country']     = $objSupport->getCountry()->getName();
                    $sWRequest['siteName']    = (!empty($whiteLabelBrand['name']) ? $whiteLabelBrand['name'] : 'ExchangeVUE');
                    $sWRequest['domain']      = (!empty($whiteLabelBrand['domain']) ? $whiteLabelBrand['domain'] : 'N/A');
                    $sWRequest['isSecure']    = (($request->isSecure()) ? 'https://' : 'http://');
                    $sWRequest['solarwindId'] = $solarwindId;

                    $response                 = $solarwinds->generateTicket($sWRequest);

                    if ($response['status'] == 'success') {
                        $id = $response['data']->id;

                        $whiteLabelBrandId = $whiteLabelBrandName = $whiteLabelBrandObj = '';
                        $sendmail = false;
                        if(!empty($whiteLabelBrand))
                        {
                            $whiteLabelBrandId = $whiteLabelBrand['id'];
                            $whiteLabelBrandObj = $em->getRepository('DhiAdminBundle:WhiteLabel')->find($whiteLabelBrand['id']);
                            if($whiteLabelBrandObj)
                            {
                                $objSupport->setWhiteLabel($whiteLabelBrandObj);
                            }

                            if($whiteLabelBrandObj->getSupportEmail()){
                                $supportemails = explode(',', $whiteLabelBrandObj->getSupportEmail());
                            } else {
                                $supportemails = array($this->container->getParameter('support_email_recipient'));   
                            }
                            $whiteLabelBrandName = $whiteLabelBrand['name'];
                        }

                        $issend = false;
                        if(count($supportemails) > 0){
                            foreach($supportemails as $supportemail){
                                $toemail = trim($supportemail);
                                if(!empty($toemail) && !!filter_var($toemail, FILTER_VALIDATE_EMAIL)){
                                    $supportsendEmail = \Swift_Message::newInstance()
                                        ->setSubject($whiteLabelBrandName . ': ' . $category->getName())
                                        ->setFrom($objSupport->getEmail())
                                        ->setTo($toemail)
                                        ->setBody($body->getContent())
                                        ->setContentType('text/html');

                                    $this->container->get('mailer')->send($supportsendEmail);
                                    $issend = true;
                                }
                            }
                        }

                        if ($issend) {

                            $objSupport->setTicketId($id);
                            $objSupport->setIsSent(1);
                            $em->persist($objSupport);
                            $em->flush();

                            $this->get('session')->getFlashBag()->add('success', 'Your support request has been sent successfully');

                        }else{

                            $this->get('session')->getFlashBag()->add('success', 'Your support request has been failed');
                        }

                        return $this->redirect($this->generateUrl('dhi_user_support'));

                    }else{
                        $this->get('session')->getFlashBag()->add('failure', $response['message']);
                    }
                }
            }
        }

        return $this->render('DhiUserBundle:Support:support.html.twig', array(
            'form' => $form->createView()
        ));
    }


    public function indexAction(Request $request) {
        $user = $this->get('security.context')->getToken()->getUser();
        $em = $this->getDoctrine()->getManager();
        $objSupport = new Support();
        $form = $this->createForm(new SupportFormType($user), $objSupport);

        if ($request->getMethod() == "POST") {

            $form->handleRequest($request);

            if ($form->isValid()) {
                $formData = $form->getData();
                $formData = $objSupport->setUser($user);
                $em->persist($formData);
                $em->flush();

                // sent user mail to support team
                $bodySupportEmail = $this->container->get('templating')->renderResponse('DhiUserBundle:Support:email_user_support.html.twig', array('username' => $user, 'message' => $objSupport->getDescription()));

                $support_email_verification = \Swift_Message::newInstance()
                        ->setSubject($formData->getSubject())
                        ->setFrom($user->getEmail())
                        ->setTo($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                        ->setBody($bodySupportEmail->getContent())
                        ->setContentType('text/html');

                $this->container->get('mailer')->send($support_email_verification);

                // sent mail support team to user
                $bodyUserEmail = $this->container->get('templating')->renderResponse('DhiUserBundle:Support:email_support.html.twig', array('username' => $user, 'ticket' => $objSupport->getId()));

                $user_email_verification = \Swift_Message::newInstance()
                        ->setSubject("Ticket EV" . $objSupport->getId() . ' ' . $formData->getSubject())
                        ->setFrom($this->container->getParameter('fos_user.registration.confirmation.from_email'))
                        ->setTo($user->getEmail())
                        ->setBody($bodyUserEmail->getContent())
                        ->setContentType('text/html');

                $this->container->get('mailer')->send($user_email_verification);

                $this->get('session')->getFlashBag()->add('success', 'Your message was sent successfully');
                return $this->redirect($this->generateUrl('dhi_user_support'));
            }
        }

        return $this->render('DhiUserBundle:Support:index.html.twig', array(
            'form' => $form->createView(),
            'user' => $user
        ));
    }

}
