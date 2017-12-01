<?php

namespace Dhi\IsppartnerBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('DhiIsppartnerBundle:Default:index.html.twig', array('name' => $name));
    }
}
