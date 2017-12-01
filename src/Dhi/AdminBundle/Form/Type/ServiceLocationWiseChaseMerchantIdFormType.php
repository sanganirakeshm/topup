<?php

namespace Dhi\AdminBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\ServiceLocationRepository;
use Dhi\AdminBundle\Repository\ChaseMerchantIdsRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class ServiceLocationWiseChaseMerchantIdFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('serviceLocation', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
                    'query_builder' => function(ServiceLocationRepository $er) {
                        return $er->createQueryBuilder('sl')
                                ->leftJoin('sl.serviceLocationWiseChaseMarchantid', 'cm', 'with', 'sl.id = cm.serviceLocation AND cm.isDeleted = false')
                                ->where('cm.id IS NULL')
                                ->orderBy('sl.name', 'ASC')
                                ;
                    },
                    'empty_value' => 'Select'
                ))
                ->add('chaseMerchantIds', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'fullName',
                    'class' => 'Dhi\AdminBundle\Entity\ChaseMerchantIds',
                    'empty_value' => 'Select',
                    'query_builder' => function(ChaseMerchantIdsRepository $cmi) {
                        return $cmi->createQueryBuilder('cmi')
                                ->where('cmi.isActive = :isActive')
                                ->setParameter('isActive', true)
                                ->orderBy('cmi.merchantName', 'ASC')
                            ;
                    }
                ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationWiseChaseMerchantId',
        );
    }
    
    public function getName() {
        
        return 'dhi_admin_service_location_wise_chase_merchantId';
    }

}
