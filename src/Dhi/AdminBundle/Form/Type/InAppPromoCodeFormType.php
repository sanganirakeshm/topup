<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\ServiceLocationRepository;

use Dhi\UserBundle\Repository\ServiceRepository;

class InAppPromoCodeFormType extends AbstractType {

    public function __construct() {
        
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('serviceLocations', 'entity', array(
                    'class' => 'DhiAdminBundle:ServiceLocation',
                    'property' => 'name',
                    'multiple' => false,
                    'query_builder' => function(ServiceLocationRepository $sr) {
                        return $sr->createQueryBuilder('sl');
                    },
                    'required' => true,
                    'empty_value' => 'Select'))
                ->add('promocode', 'text')
                ->add('amount', 'text', array('required' => true))
                ->add('expiredAt', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                ))
                ->add('note', 'textarea', array('required' => true))
                ->add('status', 'choice', array('choices' => array('Active' => 'Active', 'Inactive' => 'Inactive'), 'required' => true, 'empty_value' => 'Select'));
    }

    public function getName() {
        return 'dhi_admin_in_app_promo_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\InAppPromoCode'
        ));
    }

}
