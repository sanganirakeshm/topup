<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class GlobalDiscountFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('country', 'entity', array(
                'multiple' => false,
                'expanded' => false,
                'required' => false,
                'empty_value' => 'Select Country',
                'property' => 'name',
                'class' => 'Dhi\UserBundle\Entity\Country',
                ))
                ->add('minAmount', 'text', array('label' => 'Minimum Amount', 'required' => true))
                ->add('maxAmount', 'text', array('label' => 'Maximum Amount', 'required' => true))
                ->add('percentage', 'text',array('label'=> 'Percentage', 'required' => true));        
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\GlobalDiscount',
        ));
    }

    public function getName() {
        return 'dhi_global_discount';
    }
}
