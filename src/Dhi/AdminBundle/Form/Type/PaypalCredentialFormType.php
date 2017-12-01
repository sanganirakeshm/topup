<?php

namespace Dhi\AdminBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\ServiceLocationRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class PaypalCredentialFormType extends AbstractType {
    
    private $credentials;
    public function __construct($credentials) {
	   $this->credentials = $credentials;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('PaypalId', 'choice', array('required' => true, 'choices'  => $this->credentials))
                ->add('country', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\UserBundle\Entity\Country',
                    'empty_value' => 'Select'
                ))
                ->add('serviceLocations', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
                    'empty_value' => 'Select'
                ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\PaypalCredentials',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\PaypalCredentials',
        );
    }
    
    public function getName() {
        
        return 'dhi_admin_credential';
    }

}
