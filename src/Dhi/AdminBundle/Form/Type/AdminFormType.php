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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Doctrine\ORM\EntityRepository;
use Dhi\UserBundle\Repository\GroupRepository;

class AdminFormType extends AbstractType {

    

    private $adminGroup;
    private $userGroup;
    
    public function __construct($admin, $user)
    {
        $this->adminGroup = $admin->getGroup();
       //  $this->userGroup = $user->getGroup();
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
      
       
        $builder
                ->add('email', 'email', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle'))
                ->add('username', null, array('label' => 'form.username', 'read_only' => $builder->getData()->getId() ? true : false, 'translation_domain' => 'FOSUserBundle', 'constraints' =>
                    array(
                        new Length(array(
                            'min' => 8,
                            'max' => 18,
                            'minMessage' => 'Your username must have minimum {{ limit }} characters.',
                            'maxMessage' => 'Your username can have maximum {{ limit }} characters.',
                                )),
                        new Regex(array(
                            'pattern' => '/^[A-Za-z0-9-_!@./#$]+$/',
                            'match' => true,
                            'message' => 'username can contains character, number and special chars like -_!@./#$'
                                )),
        )));
               
        // disable password fields while editing.
        if (!$builder->getData()->getId()) {
            $builder
                    ->add('plainPassword', 'repeated', array(
                        'type' => 'password',
                        'options' => array('translation_domain' => 'FOSUserBundle'),
                        'first_options' => array('label' => 'form.password'),
                        'second_options' => array('label' => 'form.password_confirmation'),
                        'invalid_message' => 'fos_user.password.mismatch', 'constraints' =>
                        array(
                            new Length(array(
                                'min' => 8,
                                'max' => 18,
                                'minMessage' => 'Your password must have minimum {{ limit }} characters.',
                                'maxMessage' => 'Your password can have maximum {{ limit }} characters.',
                                    )),
                            new Regex(array(
                                'pattern' => '/^[A-Za-z0-9!@#$_]+$/',
                                'match' => true,
                                'message' => 'Password can contains characters, numbers and special chars like !@#$_'
                                    )),
                        )
            ));
        }
        else {
            $builder->add('firstname', null, array('label' => 'Firstname', 'translation_domain' => 'FOSUserBundle'));
            $builder->add('lastname', null, array('label' => 'Lastname', 'translation_domain' => 'FOSUserBundle'));
        }
         $builder
        ->add('groups', 'entity', array(
        		'class' => 'DhiUserBundle:Group',
        		'property' => 'name',
        		'label' => 'Group',        		
        		'required' => true,
                        'multiple' => true,
                'query_builder' => function(GroupRepository $gr) {
                    return $gr->getGroupByAdmin($this->adminGroup);
                },
        ));
        if($this->adminGroup == 'Super Admin')
         {  
            $builder
            ->add('enabled', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));
         }
                
                }

    public function getName() {
        return 'dhi_admin_registration';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\User',
            'intention' => 'registration',
            'validation_groups' => array('Profile'),
        ));
    }

}
