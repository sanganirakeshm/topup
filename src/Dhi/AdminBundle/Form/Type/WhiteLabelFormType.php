<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;
use Dhi\UserBundle\Repository\CountryRepository;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DatetimeType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;

class WhiteLabelFormType extends AbstractType {
   public function buildForm(FormBuilderInterface $builder, array $options) {
       $builder->add('companyName', 'text',array('required' => true))
               ->add('domain', 'text',array('required' => true))
               ->add('fromEmail', 'text',array('required' => true))
               ->add('supportEmail', 'text',array('required' => true))
               ->add('supportpage', 'text',array('required' => true))
               ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'))
               ->add('headerLogo','file',array(
                            'label' => 'Header Logo',
                            'data_class' => null,
                            'required' => false
                    ))
               ->add('footerLogo','file',array(
                            'label' => 'Footer Logo',
                            'data_class' => null,
                            'required' => false
                    ))
               ->add('brandingBanner','file',array(
                            'label' => 'Branding Banner',
                            'data_class' => null,
                            'required' => false
                    ))
               ->add('brandingBannerInnerPage','file',array(
                            'label' => 'Branding Banner Inner Page',
                            'data_class' => null,
                            'required' => false
                    ))
               ->add('backgroundimage','file',array(
                            'label' => 'Background Image',
                            'data_class' => null,
                            'required' => false
                    ))
               ->add('favicon','file',array(
                            'label' => 'Favicon',
                            'data_class' => null,
                            'required' => false
                    ));
               
   } 
   
   public function getName() {
        return 'dhi_admin_white_label';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\WhiteLabel'
        ));
    }
}
