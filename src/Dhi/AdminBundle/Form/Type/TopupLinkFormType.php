<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class TopupLinkFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('serviceLocations', 'entity', array(
                            'multiple' => true,
                            'expanded' => false,
                            'required' => true,
                            'property' => 'name',
                            'class' => 'Dhi\AdminBundle\Entity\ServiceLocation'
                ))
            ->add('linkName', 'text')
            ->add('url', 'text')
            ->add('status', 'choice', array('choices' => array('' => 'Select Status', 1 => 'Active', 0 => 'Inactive')));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\TopupLink'
        ));
    }

    /**
     * @return string
     */
    public function getName() {

        return 'dhi_topup_link';
    }

}
