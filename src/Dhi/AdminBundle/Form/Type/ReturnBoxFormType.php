<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ReturnBoxFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('macAddress', null, array('label' => 'Mac Address', 'required' => true,'read_only' => true))
				->add('givenAt', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy',
					'read_only' => true
                ))
				->add('receivedAt', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                ));
                
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\SetTopBox',
        ));
    }

    public function getName() {
        return 'dhi_return_set_top_box';
    }

}