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

class BannerFormType extends AbstractType {

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

				->add('bannerImages','file',array(
						'label' => 'Banner',
						'data_class' => null,
						'required' => false
					))

				->add('orderNo', 'choice', array('choices'  => array(1=> 1,2=>2,3=>3,4=>4,5=>5,6=>6,7=>7,8=>8,9=>9),'required' => false, 'empty_value' => 'Select Position'))

				->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));
			
				

    }

    public function getName() {
        return 'dhi_admin_banner';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\Banner'
        ));
    }


}
