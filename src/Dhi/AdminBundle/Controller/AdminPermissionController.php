<?php

namespace Dhi\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class AdminPermissionController extends Controller
{
    protected $container;
    
    public function __construct($container) {
    
        $this->container = $container;
    }
    
    public function checkPermission($permission)
    {
        $permissions = $this->container->get('session')->get('permissions');
        
        if(in_array($permission, $permissions))
            return true;
        else
            return false;
    }

    
}
