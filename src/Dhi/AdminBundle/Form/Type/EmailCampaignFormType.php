<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

class EmailCampaignFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('subject', 'text')
                ->add('message', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ))
                ->add('services', 'entity', array(
                                    'class' => 'DhiUserBundle:Service',
                                    'empty_value' => 'Select Service',
                                    'property' => 'name',
                                    'multiple' => true,
                ))
                ->add('serviceLocations', 'entity', array(
                        'class' => 'DhiAdminBundle:ServiceLocation',
                        'empty_value' => 'Select Service Location',
                        'property' => 'name',
                        'multiple' => true,
                ))
                /* ->add('startDate', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                ))
                ->add('endDate', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                )) */
                ->add('emailType', 'choice', array(
                    'choices'  => array('' => 'Please Select', 'M' => 'Marketing', 'S' => 'Support'),
                ))
                ->add('emailStatus', 'choice', array(
                        'choices' => array('Inactive' => 'Inactive', 'In Progress' => 'In Progress')
                ));
    }

    public function getName() {
        return 'dhi_admin_email_campaign';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\EmailCampaign'
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        $new_choice = new ChoiceView(array(), '0', 'Both'); // <- new option
        $view->children['services']->vars['choices'][0] = $new_choice;//<- adding the new option
    
        asort($view->children['services']->vars['choices']);
    }
}
