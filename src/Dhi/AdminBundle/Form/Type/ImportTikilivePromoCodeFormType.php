<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Repository\ServiceLocationRepository;

use Dhi\UserBundle\Repository\TikilivePromoCodeRepository;
use Dhi\AdminBundle\Repository\PackageRepository;

class ImportTikilivePromoCodeFormType extends AbstractType {

	
    public function buildForm(FormBuilderInterface $builder, array $options) {
         
        $builder->add('batchName','text',array('required' => true))
                ->add('csvFile','file',array(
                'required' => true,
                'mapped' => false
            ));
    }

    public function getName() {
        return 'dhi_admin_tikilive_promo_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\TikilivePromoCode'
        ));
    }
}