<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\UserBundle\Entity\Service;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Repository\UserRepository;
use Dhi\UserBundle\Repository\ServiceRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class UserCompensationFormType extends AbstractType {
    
    private $admin, $user;
    public function __construct($admin, $id, $em) {
        $this->admin = $admin;
        $this->user = $id;
        $this->em = $em;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $compensationType =  'Customer';
        $builder
            ->add('ispHours', 'text')
            ->add('reason', 'textarea', array(
                'attr' => array('required' => true)
            ))
            ->add('type', 'hidden', array('required' => true,'data' => $compensationType));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\Compensation',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Dhi\UserBundle\Entity\Compensation',
        );
    }
    
    public function getName() {
        return 'dhi_admin_compensation';
    }

}
