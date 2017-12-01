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

class ChaseMerchantIdsFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('merchantId', 'text',array('required' => false))
                ->add('merchantName', 'text',array('required' => false))
                ->add('isDefault','checkbox', array('required' => false));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ChaseMerchantIds',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\ChaseMerchantIds',
        );
    }
    
    public function getName() {
        
        return 'dhi_admin_chase_merchatids';
    }

}
