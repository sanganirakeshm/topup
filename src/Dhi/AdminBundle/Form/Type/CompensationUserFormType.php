<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Repository\UserRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Collection;

class CompensationUserFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('customers', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'username',
                                'class' => 'Dhi\UserBundle\Entity\User',
                                'query_builder' => function(UserRepository $ur) {
                                    return $ur->getAllCustomer();
                                },
                ));                            
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\User'
        ));
        
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\UserBundle\Entity\User',
        );
    }

    public function getName() {
        
        return 'compensation_user';
    }

}
