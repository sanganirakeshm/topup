<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Repository\SolarWindsRequestTypeRepository;
use Dhi\AdminBundle\Repository\WhiteLabelRepository;

class SolarWindsRequestFormType extends AbstractType {
    
    protected $admin;

    public function __construct($options){
        $this->admin = $options['admin'];
        $this->solarid = $options['id'];
    }
     public function buildForm(FormBuilderInterface $builder, array $options) {
                
            if($this->solarid == 0){
                $builder->add('supportsite', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'fullName',
                    'class' => 'Dhi\AdminBundle\Entity\WhiteLabel',
                    'query_builder' => function(WhiteLabelRepository $er) {
                        $res = $er->createQueryBuilder('wl')
                                 ->where('wl.isDeleted = :isDeleted')
                                 ->setParameter('isDeleted', 0)
                                 ->andWhere('wl.status = :status')
                                 ->setParameter('status', 1)
                                 ->orderby('wl.companyName','asc');
                        return $res;

                    },
                    'empty_value' => 'Select Site'
                ));

                $builder->add('supportLocation', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\UserBundle\Entity\SupportLocation',
                    'empty_value' => 'Select Support Location'
                ));
            }             

            $builder ->add('solarWindsRequestType', 'entity', array(
                'multiple' => false,
                'expanded' => false,
                'required' => true,
                'property' => 'requestTypeName',
                'class' => 'Dhi\UserBundle\Entity\SolarWindsRequestType',
                'query_builder' => function(SolarWindsRequestTypeRepository $er) {
                    $query = $er->createQueryBuilder('sw');
                    $query->orderby('sw.requestTypeName','asc');
                    return $query;
                },
                'empty_value' => 'Select Solar Winds Request Type'
            ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\SolarWindsSupportLocation',
        ));
    }

    public function getName() {
        return 'dhi_admin_solar_winds_location';
    }
}

