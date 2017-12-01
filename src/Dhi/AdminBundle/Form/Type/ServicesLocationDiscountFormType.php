<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\AdminBundle\Repository\ServiceLocationRepository;


class ServicesLocationDiscountFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $mulitple = true;
        if(isset($options['data'])){
            if($options['data']->getId()){
                $mulitple = false;
            }
        }
        
        $builder->add('serviceLocation', 'entity', array(
                                'class' => 'DhiAdminBundle:ServiceLocation',
                                'property' => 'name',
                                'multiple' => $mulitple,
                                'query_builder' => function(ServiceLocationRepository $er) {
                                                return $er->createQueryBuilder('sl')
                                                            ->addOrderBy('sl.name', 'ASC');
                                                },
                                'required' => true,
                                'mapped' => false,
                                'expanded' => false
                                ))
                ->add('serviceLocationDiscounts', 'collection', array(
                                'type' => new DiscountFormType(),
                                'allow_add' => true,
                                'allow_delete' => true,
                                'prototype' => true,
                                'by_reference' => false,
                        ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
        ));
    }

    public function getName() {
        return 'dhi_service_location';
    }
}
