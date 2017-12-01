<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Symfony\Component\Validator\Constraints\NotBlank;

class RequestPasswordFormType extends AbstractType {
    
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('usernameoremail', 'text', array('required' => false,'mapped' => false,'constraints' => array(
                         new NotBlank(array('message' => 'Please enter username/email.'))
                    )));        
    }

    public function getName() {
        return 'dhi_admin_request_password';
    }

}