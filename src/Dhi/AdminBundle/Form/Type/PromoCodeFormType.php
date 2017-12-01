<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\UserBundle\Entity\Service;
use Dhi\UserBundle\Entity\User;
use Dhi\UserBundle\Repository\UserRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;
use Dhi\AdminBundle\Repository\ServiceLocationRepository;
use Dhi\UserBundle\Repository\PromoCodeRepository;
use Dhi\UserBundle\Repository\ServiceRepository;

class PromoCodeFormType extends AbstractType {

	private $packages;

	public function __construct($packages)
	{
		$this->packages = $packages;
	}

    public function buildForm(FormBuilderInterface $builder, array $options) {
         
	$builder	->add('serviceLocations', 'entity', array(
				'class' => 'DhiAdminBundle:ServiceLocation',
				'property' => 'name',
				'multiple' => false,
				'query_builder' => function(ServiceLocationRepository $sr) {
				   return $sr->createQueryBuilder('sl');
				},
				'required' => true,
				'empty_value' => 'Select'))

				->add('service', 'entity', array(
                    'class' => 'DhiUserBundle:Service',
                    'property' => 'name',
                    'multiple' => false,
                    'query_builder' => function(ServiceRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->where('s.status = :statusVal')
                                ->setParameter('statusVal', 1)
								->andWhere('s.name != :tvod')
								->setParameter('tvod','TVOD')
                                ->addOrderBy('s.name', 'ASC');
                    },
                    'required' => true,
					'empty_value' => 'Select'))

				->add('packageId','choice',array(
                    'choices' => $this->packages,
                    'required' => true,
					'empty_value' => 'Select'
                ))
				->add('promocode', 'text')
				->add('duration', 'text')
				
				->add('expiredAt', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy'
                ))
                ->add('note', 'textarea', array('required' => true))
				->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select'));
               
                
    }

    public function getName() {
        return 'dhi_admin_promo_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\PromoCode'
        ));
    }

}
