<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Dhi\AdminBundle\Repository\TikilivePromoCodeRepository;

class PackageWiseTikiLivePlanFormType extends AbstractType {
    
    private $tikiLivePlan;
    public function __construct($tikiLivePlan) {
	   $this->tikiLivePlan = $tikiLivePlan;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('tikiLivePlanName', 'choice', array('required' => true, 'choices'  => $this->tikiLivePlan));
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\PackageWiseTikiLivePlan',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
            'data_class' => 'Dhi\AdminBundle\Entity\PackageWiseTikiLivePlan',
        );
    }
    
    public function getName() {
        return 'dhi_admin_package_wise_tikilive_plan';
    }

}
