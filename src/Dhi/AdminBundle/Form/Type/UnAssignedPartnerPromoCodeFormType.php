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
use Dhi\AdminBundle\Repository\ServicePartnerRepository;
use Dhi\AdminBundle\Entity\PartnerPromoCodes;
use Dhi\AdminBundle\Repository\PackageRepository;

class UnAssignedPartnerPromoCodeFormType extends AbstractType {

	private $packages;
	public function __construct($packages)
	{
        $this->packages = $packages;
	}

    public function buildForm(FormBuilderInterface $builder, array $options) {
         
        $builder->add('serviceLocations', 'entity', array(
            'class' => 'DhiAdminBundle:ServiceLocation',
            'property' => 'name',
            'multiple' => false,
            'query_builder' => function(ServiceLocationRepository $sr) {
               return $sr->createQueryBuilder('sl');
            },
            'required' => true,
            'empty_value' => 'Select'));
            
        $builder->add('packageId','choice',array(
                    'choices' => $this->packages,
                    'required' => true,
                    'empty_value' => 'Select'
            ))
            ->add('duration', 'text', array('required' => true))
            ->add('note', 'textarea', array('required' => true))
            ->add('expirydate', 'date', array(
                'widget' => 'single_text',
                'format' => 'MM-dd-yyyy',
                'required' => true
            ));
        
        $builder->add('partnerValue','text')
            ->add('customerValue','text')
            ->add('status', 'choice', array('choices'  => 
                array('' => 'Select', 'Active' => 'Active','Inactive' =>'Inactive'),'required' => true)
            );
    }

    public function getName() {
        return 'dhi_admin_unassigned_partner_promo_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\PartnerPromoCodes'
        ));
    }
}