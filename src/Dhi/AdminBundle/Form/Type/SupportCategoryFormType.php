<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\AdminBundle\Repository\WhiteLabelRepository;
use Dhi\UserBundle\Repository\ServiceRepository;

class SupportCategoryFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
        	->add('name', null, array('label' => 'Support Category', 'required' => true))
            ->add('supportsite', 'entity', array(
	            'class' => 'DhiAdminBundle:WhiteLabel',
	            'empty_value' => 'Select Site',
	            'property' => 'fullName',
	            'required' => true,
	            'query_builder' => function (WhiteLabelRepository $er) {
	                
	                 return $er->createQueryBuilder('wl')
	                        ->where('wl.isDeleted = :isdeleted')
	                        ->setParameter('isdeleted', 0)
	                        ->andWhere('wl.status = :status')
	                        ->setParameter('status', 1)
	                       ->orderBy('wl.companyName', 'ASC');
	            },
	            'multiple' => false )
            );

    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\SupportCategory',
        ));
    }

    public function getName() {
        return 'dhi_user_support_category';
    }

}
