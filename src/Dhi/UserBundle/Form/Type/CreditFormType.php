<?php

namespace Dhi\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
//use Dhi\UserBundle\Repository\CountryRepository;
use Dhi\AdminBundle\Entity\Credit;
use Dhi\UserBundle\Entity\User;
//use Dhi\AdminBundle\Entity\IpAddressZone;

class CreditFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('credit', 'text', array('label' => 'credit', 'required' => true))
                ->add('amount', 'text', array('label' => 'amount', 'required' => true));
                  
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\Credit',
        ));
    }

    public function getName() {
        return 'dhi_credit';
    }

}
