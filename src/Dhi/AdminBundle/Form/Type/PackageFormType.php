<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class PackageFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('packageId', null, array('label' => 'Package Id', 'required' => true))
                ->add('name', null, array('label' => 'Name', 'required' => true))
                ->add('price', null, array('label' => 'Price', 'required' => true))
                ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\ServiceBundle\Entity\Package',
        ));
    }

    public function getName() {
        return 'dhi_user_package';
    }

}
