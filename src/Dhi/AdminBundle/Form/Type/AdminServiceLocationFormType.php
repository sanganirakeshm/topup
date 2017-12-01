<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class AdminServiceLocationFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('username', 'text', array('read_only' => true));
        
        $builder->add('serviceLocations', 'entity', array(
            'multiple' => true, // Multiple selection allowed
            'expanded' => false, // Render as checkboxes,
            'required' => true,
            'property' => 'name', // Assuming that the entity has a "name" property
            'class' => 'Dhi\AdminBundle\Entity\ServiceLocation'
        ));
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\User',
            'intention'  => 'admin_service_location',
            'validation_groups'  => 'AdminServiceLocation'
        ));
    }

    public function getName() {

        return 'dhi_admin_service_location';
    }
}
