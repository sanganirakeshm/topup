<?php

namespace Dhi\UserBundle\EventListener;

use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\Mailer\MailerInterface;
use FOS\UserBundle\Util\TokenGeneratorInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\DependencyInjection\Container;

class EmailConfirmationListener implements EventSubscriberInterface
{
    private $mailer;
    private $tokenGenerator;
    private $router;
    private $session;
    private $container;

    public function __construct(MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator, UrlGeneratorInterface $router, SessionInterface $session, Container $container)
    {
        $this->mailer         = $mailer;
        $this->tokenGenerator = $tokenGenerator;
        $this->router         = $router;
        $this->session        = $session;
        $this->container      = $container;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationSuccess',
        );
    }

    public function onRegistrationSuccess(FormEvent $event)
    {
        /** @var $user \FOS\UserBundle\Model\UserInterface */
        $user = $event->getForm()->getData();

        $user->setEnabled(false);
        if (null === $user->getConfirmationToken()) {
            $token = $this->tokenGenerator->generateToken();

            while (null === $token) {
                $token = $this->tokenGenerator->generateToken();
            }

            $user->setConfirmationToken($token);
        }

        $whiteLabel = $this->session->get('brand');
        if(!empty($whiteLabel))
        {
            $subject       = "Welcome ".$user->getFirstname() . ' ' . $user->getLastname() ." to ".$whiteLabel['name']."!";
            $fromName      = $whiteLabel['name'];
            $fromEmail     = $whiteLabel['fromEmail'];
            $compnayname   = $whiteLabel['name'];
            $compnaydomain = $whiteLabel['domain'];
            $supportpage   = $whiteLabel['supportpage'];
        } else {
            $fromName      = $whiteLabel['name'];
            $fromEmail     = $this->container->getParameter('fos_user.registration.confirmation.from_email');
            $compnayname   = 'ExchangeVUE';
            $compnaydomain = 'exchangevue.com';
            $subject       = "Welcome ".$user->getFirstname() . ' ' . $user->getLastname() ." to ExchangeVUE!";
            $supportpage   = 'https://www.facebook.com/dhitelecom';
        }

        $template         = $this->container->getParameter('fos_user.registration.confirmation.template');
        $url              = $this->router->generate('fos_user_registration_confirm', array('token' => $user->getConfirmationToken()), true);
        $renderedTemplate = $this->container->get('templating')->renderResponse($template, array(
            'user'            => $user,
            'companyname'     => $compnayname,
            'companydomain'   => $compnaydomain,
            'supportpage'     => $supportpage,
            'confirmationUrl' => $url
        ));

        $template      = $renderedTemplate->getContent();
        $renderedLines = explode("\n", trim($template));
        $body          = implode("\n", array_slice($renderedLines, 1));

        $welcomeEmail  = \Swift_Message::newInstance()
            ->setSubject($subject)
            ->setFrom(array($fromEmail => $fromName))
            ->setTo($user->getEmail())
            ->setBody($body);

        $this->container->get('mailer')->send($welcomeEmail);

        $this->session->set('fos_user_send_confirmation_email/email', $user->getEmail());

        $url = $this->router->generate('fos_user_registration_check_email');
        $event->setResponse(new RedirectResponse($url));
    }
}
