<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\AdminBundle\Repository\ServiceLocationRepository;

class DiscountFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('minAmount', null, array('label' => 'Minimum Amount', 'required' => true))
                ->add('maxAmount', null, array('label' => 'Maximum Amount', 'required' => true))
                ->add('percentage', null, array('label' => 'Discount', 'required' => true))
				->add('collectionIndex', 'hidden', array('mapped' => false));   
		
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationDiscount'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationDiscount',
        );
    }

    public function getName() {
        return 'dhi_service_discount';
    }
}
