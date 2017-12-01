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

class FrontHomeFormType extends AbstractType {

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('country', 'entity', array(
                    'class' => 'DhiUserBundle:Country',
                    'property' => 'name',
                    'query_builder' => function(CountryRepository $er) {
                        return $er->createQueryBuilder('c')
                                ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                                ->add('orderBy', 'ORD ASC')
                                ->addOrderBy('c.name', 'ASC');
                    },
                    'required' => true))
				->add('column1', 'text',array('label'=> 'column1', 'required' => true))
                ->add('message1', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ))
				->add('column2', 'text',array('label'=> 'column2', 'required' => false))
                ->add('message2', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ))
				->add('column3', 'text',array('label'=> 'column3', 'required' => false))
                ->add('message3', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ))
				->add('column4', 'text',array('label'=> 'column4', 'required' => false))
                ->add('message4', 'textarea', array(
                    'attr' => array('class' => 'tinymce')
                ));

			

    }

    public function getName() {
        return 'dhi_admin_front_home';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\FrontHome'
        ));
    }

  
}
