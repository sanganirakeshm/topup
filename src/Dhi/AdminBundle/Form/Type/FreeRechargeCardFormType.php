<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FreeRechargeCardFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('userId', 'text', array('label' => 'Username or Email', 'required' => true));

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\FreeRechargeCard',
        ));
    }

    public function getName() {
        return 'dhi_admin_free_recharge_card';
    }

}
