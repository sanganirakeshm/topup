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

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Repository\UserRepository;

class TransferMacAddressFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('macAddress', 'text', array(
            'label' => 'Mac Address',
			'read_only' => 'true'
        ))
				->add('user', 'entity', array(
                                'multiple' => false,
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
            'data_class' => 'Dhi\UserBundle\Entity\UserMacAddress'
        ));
    }

    public function getName()
    {
        return 'dhi_transfer_mac_address';
    }
}
