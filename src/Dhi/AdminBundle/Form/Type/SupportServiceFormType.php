<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SupportServiceFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
            ->add('serviceName', null, array('label' => 'Support Service Name', 'required' => true))
            ->add('isActive', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => false, 'empty_value' => 'Select Status'));
            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\SupportService',
        ));
    }

    public function getName() {
        return 'dhi_user_support_service';
    }

}
