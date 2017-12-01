<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

class SurveyMonkeyMailFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('subject', 'text')
                ->add('message', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ))
                ->add('emailStatus', 'choice', array(
                        'choices' => array('Sent' => 'Sent', 'Sending' => 'Sending')
                ));
    }

    public function getName() {
        return 'dhi_admin_survey_monkey_mail';
    }
}
