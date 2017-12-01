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

class AccountFormType extends AbstractType {
//->add('email', 'repeated', array(
//                    'type' => 'email',
//                    'options' => array('translation_domain' => 'FOSUserBundle'),
//                    'first_options' => array('label' => 'form.email'),
//                    'second_options' => array('label' => 'form.email_confirmation'),
//                    'invalid_message' => 'fos_user.email.mismatch',
//                    'required' => true))
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder
                //->add('email', 'repeated', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'required' => true))
                ->add('email', 'repeated', array('label' => 'form.email', 'options' => array('translation_domain' => 'FOSUserBundle'), 'invalid_message' => 'Email does not match confirm email.', 'required' => true))
                ->add('username', null, array('label' => 'form.username', 'read_only' => $builder->getData()->getId() ? true : false, 'translation_domain' => 'FOSUserBundle', 'required' => true, 'disabled' => $builder->getData()->getId() ? true : false))
                ->add('firstname', null, array('label' => 'First Name', 'translation_domain' => 'FOSUserBundle'))
                ->add('lastname', null, array('label' => 'Last Name', 'translation_domain' => 'FOSUserBundle'))
                ->add('address', 'text', array('label' => 'form.address', 'translation_domain' => 'FOSUserBundle', 'required' => true))
                ->add('city', 'text', array('label' => 'form.city', 'translation_domain' => 'FOSUserBundle', 'required' => true))
                ->add('state', 'text', array('label' => 'form.state', 'translation_domain' => 'FOSUserBundle', 'required' => true))
                ->add('zip', 'text', array('label' => 'form.zip', 'translation_domain' => 'FOSUserBundle', 'required' => true))
                ->add('country','entity',array(
                'class'=>'DhiUserBundle:Country',
               // 'empty_value' => 'Country',
                'property'      => 'name',
                'query_builder' => function(CountryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                        ->add('orderBy','ORD ASC')
                        ->addOrderBy('c.name','ASC');
                },
                'required'=>true));    
        
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\User',
        ));
    }

    public function getName() {
        return 'dhi_user_account_update';
    }

}
