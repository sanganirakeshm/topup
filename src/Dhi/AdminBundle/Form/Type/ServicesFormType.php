<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ServicesFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        //->add('status', 'checkbox', array('label' => 'Status','required' => false));
        $builder->add('name', 'text', array('label' => 'Service Name', 'required' => true))
                ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => false, 'empty_value' => 'Select Status'));
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'csrf_protection' => false
        ));
    }

    public function getName() {
        return 'dhi_service_add';
    }
}
