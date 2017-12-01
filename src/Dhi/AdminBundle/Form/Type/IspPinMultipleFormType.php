<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class IspPinMultipleFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('package', 'entity', array(
		                'multiple' => false,
		                'expanded' => false,
		                'required' => true,
		                'empty_value' => 'Select Plan',
		                'property' => 'packageName',
		                'class' => 'Dhi\AdminBundle\Entity\Package',
                ))
                ->add('serviceLocation', 'entity', array(
                                'multiple' => false,
                                'expanded' => false,
                                'required' => true,
                				'empty_value' => 'Select Location',
                                'property' => 'name',
                                'class' => 'Dhi\AdminBundle\Entity\ServiceLocation'
                ))
				
				->add('isp_type', 'choice', array(
                    'choices' => array('' => 'Select Type', 'Individual' => 'Individual', 'Business' => 'Business'),
                ))
				
                ->add('name', 'text', array('label' => 'Name', 'required' => false))                
                ->add('email', 'text', array('label' => 'Email', 'required' => false)) 
				->add('validity', 'text',array('label'=> 'Validity', 'required' => true))
        		->add('noOfPin', 'text',array('label'=> 'No. Of Pin', 'required' => true, 'mapped'=>false));        
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
        		
            'data_class' => 'Dhi\AdminBundle\Entity\IspPin',
        ));
    }

    public function getName() {
    	
        return 'dhi_isp_pin';
    }
}
