<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;

class ChangeIspPinPasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    	/* $builder->add('password', 'password');
    	$builder->add('newPassword', 'repeated', array(
    			'type' => 'password',
    			'invalid_message' => 'The password fields must match.',
    			'required' => true,
    			'first_options'  => array('label' => 'Password'),
    			'second_options' => array('label' => 'Repeat Password'),
    			'mapped' => false
    	)); */
    	
        $builder->add('password', 'repeated', array(
            											'type' => 'password',            
            											'first_options' => array('label' => 'form.new_password'),
            											'second_options' => array('label' => 'form.new_password_confirmation'),
            											'invalid_message' => 'New password and confirm password mismatch'
        												)
        		);
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
        		
            'data_class' => 'Dhi\AdminBundle\Entity\IspPin',
        ));
    }

    public function getName()
    {
        return 'dhi_change_isp_pin';
    }
}
