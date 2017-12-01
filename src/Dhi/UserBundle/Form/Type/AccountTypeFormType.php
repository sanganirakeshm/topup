<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dhi\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Repository\CountryRepository;

class AccountTypeFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('userType', 'choice', array('choices' => array('US Military' => 'US Military' ,'US Government' => 'US Government' , 'Civilian' => 'Civilian'),'required' => true,'expanded' => true ));    
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
         //   'data_class' => 'Dhi\UserBundle\Entity\User',
            'csrf_protection' => false
        ));
    }

    public function getName() {
        return 'dhi_user_account_type_update';
    }

}
