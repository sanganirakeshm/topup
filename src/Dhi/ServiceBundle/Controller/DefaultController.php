<?php

namespace Dhi\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use \DateTime;
use Symfony\Component\DependencyInjection\SimpleXMLElement;

use Symfony\Component\DependencyInjection\ContainerBuilder;  
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;  
use Symfony\Component\HttpKernel\DependencyInjection\Extension;  
use Symfony\Component\Config\FileLocator;

class DefaultController extends Controller
{
    public function indexAction(Request $request, $name)
    {
        
        $arrMilsatParam['requestId'] = '52943561';
        $arrMilsatParam['creditCardNumber'] = '6019444000728606';
        $arrMilsatParam['amount'] = '1';
        $arrMilsatParam['zipCode'] = '77024';
        $arrMilsatParam['cid'] = '30602341232';
        $arrMilsatParam['processingFacnbr'] = $this->container->getParameter('processingFacnbr');
        $arrMilsatParam['region'] = $this->container->getParameter('region');
        
        $objMilstar = $this->get('milstar')->msApproval($arrMilsatParam);
        
        return $this->render('DhiServiceBundle:Default:index.html.twig', array('name' => $name));
    }
    
    
    
    
}
