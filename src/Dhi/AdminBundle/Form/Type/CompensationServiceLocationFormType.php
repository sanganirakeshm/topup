<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Repository\ServiceLocationRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class CompensationServiceLocationFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('serviceLocations', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
                ));                            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocation'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
        );
    }

    public function getName() {
        
        return 'compensation_service_location';
    }

}
