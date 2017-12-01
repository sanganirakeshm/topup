<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Dhi\UserBundle\Repository\ServiceRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class PromotionFormType extends AbstractType {
    
    private $admin;
    public function __construct($admin) {
		$this->admin = $admin;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
        ->add('serviceLocations', 'entity', array(
            'multiple' => true,
            'expanded' => false,
            'required' => true,
            'empty_data' => '',
            'property' => 'name',
            'class'    => 'Dhi\AdminBundle\Entity\ServiceLocation'
        ))
        ->add('isActive', 'choice', array(
            'choices'  => array(
                1 => 'Active',
                0 =>'Inactive'),
            'required' => true))
        ->add('startDate', 'date', array(
            'required' => true,
            'widget'   => 'single_text',
            'format'   => 'MM-dd-yyyy'
        ))
        ->add('bannerImage','file',array(
            'label' => 'Banner Image:',
            'data_class' => null,
            'required' => false
        ))
        ->add('endDate', 'date', array(
            'required' => true,
            'widget'   => 'single_text',
            'format'   => 'MM-dd-yyyy'
        ))
        ->add('amount', 'text', array('required' => true))
        ->add('amountType', 'choice', array(
            'choices'  => array(
                'p' => 'Percentage off (%)',
                'a' =>'Amount Off ($)'),
            'required' => true));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\Promotion',
        ));
    }

    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\Promotion',
        );
    }
    public function getName() {
        return 'dhi_admin_promotion';
    }
}