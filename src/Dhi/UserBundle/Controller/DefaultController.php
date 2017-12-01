<?php

namespace Dhi\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('DhiUserBundle:Default:index.html.twig');
    }
}
