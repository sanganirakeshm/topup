<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class IspPinFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
		
		
		$builder->add('package', 'entity', array(
		                'multiple' => false,
		                'expanded' => false,
		                'required' => true,
		                'empty_value' => 'Select Plan',
		                'property' => 'packageName',
		                'class' => 'Dhi\AdminBundle\Entity\Package',
						'read_only' => $builder->getData()->getId() ? true : false,
						"disabled" => $builder->getData()->getId() ? "disabled" : false,
                ))
                ->add('serviceLocation', 'entity', array(
                                'multiple' => false,
                                'expanded' => false,
                                'required' => true,
                				'empty_value' => 'Select Location',
                                'property' => 'name',
                                'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
								'read_only' => $builder->getData()->getId() ? true : false,
								"disabled" => $builder->getData()->getId() ? "disabled" : false,
					
                ))
				
				->add('isp_type', 'choice', array("required" => true,
					'read_only' => $builder->getData()->getId() ? true : false,
					"disabled" => $builder->getData()->getId() ? "disabled" : false,
					'choices' => array('' => 'Select Type', 'Individual' => 'Individual', 'Business' => 'Business'),
                ))
				
                ->add('name', 'text', array('label' => 'Name', 'required' => false, 'read_only' => $builder->getData()->getId() ? true : false,
						"disabled" => $builder->getData()->getId() ? "disabled" : false))                
                ->add('email', 'text', array('label' => 'Email', 'required' => false, 'read_only' => $builder->getData()->getId() ? true : false,
						"disabled" => $builder->getData()->getId() ? "disabled" : false))                
                ->add('username', 'text', array('label' => 'Username', 'required' => true, 'read_only' => $builder->getData()->getId() ? true : false,
						"disabled" => $builder->getData()->getId() ? "disabled" : false))                
                ->add('validity', 'text',array('label'=> 'Validity', 'required' => true));    
        
		if (!$builder->getData()->getId()) {
			
			$builder->add('password', 'password', array('label' => 'Password', 'required' => true, 'read_only' => $builder->getData()->getId() ? true : false,
						"disabled" => $builder->getData()->getId() ? "disabled" : false));
		}
        
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
