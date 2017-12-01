<?php

namespace Dhi\TvodBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('DhiTvodBundle:Default:index.html.twig', array('name' => $name));
    }
}
