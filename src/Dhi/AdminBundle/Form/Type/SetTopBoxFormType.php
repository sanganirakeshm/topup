<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Repository\UserRepository;

class SetTopBoxFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('macAddress', null, array('label' => 'Mac Address', 'required' => true))
				->add('givenAt', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                ))
				->add('user', 'entity', array(
                                'multiple' => false,
                                'expanded' => false,
					            'empty_value' => 'Select Customer',
                                'required' => true,
                                'property' => 'username',
                                'class' => 'Dhi\UserBundle\Entity\User',
                                'query_builder' => function(UserRepository $ur) {
                                    return $ur->createQueryBuilder('u')->setMaxResults(0)->setFirstResult(0);
                                },
                ));
                 $builder->get('user')->resetViewTransformers();
                //->add('givenAt', null, array('label' => 'Date', 'required' => true))
//                ->add('user', 'text', array('label' => 'user', 'required' => true));
//                ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\SetTopBox',
        ));
    }

    public function getName() {
        return 'dhi_set_top_box';
    }

}