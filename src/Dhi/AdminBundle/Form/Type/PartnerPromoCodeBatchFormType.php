<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\ServicePartnerRepository;

class PartnerPromoCodeBatchFormType extends AbstractType {


    public function buildForm(FormBuilderInterface $builder, array $options) {
       $builder->add('partner', 'entity', array(
            'class' => 'DhiAdminBundle:ServicePartner',
            'property' => 'name',
            'multiple' => false,
            'query_builder' => function(ServicePartnerRepository $sr) {
               return $sr->createQueryBuilder('sp')
                       ->select('sp')
                       ->where('sp.status = :sta')
                       ->setParameter('sta', 1)
                       ->andWhere('sp.isDeleted = :isDeleted')
                       ->setParameter('isDeleted', 0)
                       ->orderBy('sp.name');
            },
            'required' => true,
            'empty_value' => 'Select'));
        $builder->add('noOfCodes', 'text')
                ->add('reason', 'textarea', array('required' => true))
                ->add('note', 'textarea', array('required' => true));

        $builder->add('batchName','text');
    }

    public function getName() {
        return 'dhi_admin_partner_promo_code_batch';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\PartnerPromoCodeBatch'
        ));
    }

}
