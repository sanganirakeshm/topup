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
use Dhi\AdminBundle\Repository\ServiceLocationRepository;

class ServicePartnerFormType extends AbstractType {

     public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', 'text',array('required' => true))
            ->add('description', 'textarea',array('required' => false))
            ->add('pocName', 'text',array('required' => false))
            ->add('pocEmail', 'text',array('required' => false))
            ->add('pocPhone', 'text',array('required' => false))
            ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'))
            ->add('username', 'text', array('required' => false))
            ->add('password', 'password', array('required' => false));
    }

    public function getName() {
        return 'dhi_admin_service_partner';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServicePartner'
        ));
    }
}