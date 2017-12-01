<?php

namespace Dhi\IsppartnerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

class ResetPartnerPasswordType extends AbstractType
{
     public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('current_password', 'password', array(
            'label' => 'password',
            'mapped' => false,
            'validation_groups' => array('Default'),
        ));

        $builder ->add('password', 'repeated', array(
                        'type' => 'password',
                        'first_options' => array('label' => 'form.password'),
                        'second_options' => array('label' => 'form.password_confirmation'),
                       
                        'constraints' =>
                        array(
                            new Length(array(
                                'min' => 8,
                                'max' => 18,
                                'minMessage' => 'Your password must have minimum {{ limit }} characters.',
                                'maxMessage' => 'Your password can have maximum {{ limit }} characters.',
                                    ))
                        )));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServicePartner',
            'intention'  => 'change_password',
            'validation_groups' => 'ChangePassword',
        ));
    }

    public function getName()
    {
        
        return 'dhi_isppartner_user';
    }
}
