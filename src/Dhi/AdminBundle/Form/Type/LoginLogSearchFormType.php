<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginLogSearchFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $searchParams = $options['data']['searchParams'];

        $builder->add('search', 'text', array(
                    'data' => $searchParams ? $searchParams['search'] : '',
                    'required' => false
                ))
                ->add('startDate','text', array(
                   'data' => $searchParams ? $searchParams['startDate'] : '',
                   'required' => false
               ))
               ->add('endDate','text', array(
                   'data' => $searchParams ? $searchParams['endDate'] : '',
                   'required' => false
               ));
    }

    public function getName() {

        return 'search';
    }

}
