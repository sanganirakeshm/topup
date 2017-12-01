<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\BusinessRepository;

class BusinessPromoCodeBatchFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
       $builder->add('business', 'entity', array(
            'class' => 'DhiAdminBundle:Business',
            'property' => 'name',
            'multiple' => false,
            'query_builder' => function(BusinessRepository $br) {
               return $br->createQueryBuilder('bu')
                       ->select('bu')
                       ->where('bu.status = :sta')
                       ->setParameter('sta', '1')
                       ->andWhere('bu.isDeleted = :isDeleted')
                       ->setParameter('isDeleted', false)
                       ->orderBy('bu.name');
            },
            'required' => true,
            'empty_value' => 'Select'));
        $builder->add('noOfCodes', 'text')
                ->add('reason', 'textarea', array('required' => true))
                ->add('note', 'textarea', array('required' => true));

        $builder->add('batchName','text');
    }

    public function getName() {
        return 'dhi_admin_business_promo_code_batch';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\BusinessPromoCodeBatch'
        ));
    }

}
