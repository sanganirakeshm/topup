<?php

namespace Dhi\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CreditSearchFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $searchParams = $options['data']['searchParams'];

        $builder->add('credit', 'text', array(
                    'data' => $searchParams ? $searchParams['credit'] : ''
                ))
                ->add('amount', 'text', array(
                    'data' => $searchParams ? $searchParams['amount'] : ''
                ))  ;
               
    }

    public function getName() {

        return 'search';
    }

}