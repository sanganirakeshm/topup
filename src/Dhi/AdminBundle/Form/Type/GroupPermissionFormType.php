<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class GroupPermissionFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('permissions', 'entity', array(
            'multiple' => true, // Multiple selection allowed
            'expanded' => true, // Render as checkboxes
            'property' => 'name', // Assuming that the entity has a "name" property
            'class' => 'Dhi\UserBundle\Entity\Permission'
        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\Group'
        ));
    }

    public function getName() {

        return 'dhi_admin_group_permissions';
    }
}
