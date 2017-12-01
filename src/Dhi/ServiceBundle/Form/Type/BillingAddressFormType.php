<?php

namespace Dhi\ServiceBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\ServiceBundle\Repository\BillingAddressRepository;
use Dhi\UserBundle\Repository\CountryRepository;


class BillingAddressFormType extends AbstractType {
    
    protected $user;
   
    public function __construct ($user = false) {
        
       $this->email = $user->getEmail();
        
    }    

    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        $builder->add('firstname', null, array('label' => 'First Name', 'required' => true))
                ->add('lastname', null, array('label' => 'LastName', 'required' => true))
                ->add('address', null, array('label' => 'Address', 'required' => true))
                ->add('city', 'text', array('label' => 'City', 'required' => true))
                ->add('state', 'text', array('label' => 'State', 'required' => true))
                ->add('zip', 'text', array('label' => 'Zip', 'required' => true))
                ->add('country','entity',array(
                                                'class'=>'DhiUserBundle:Country',
                                                //'empty_value' => 'Country',
                                                'property'      => 'name',
                                                'query_builder' => function(CountryRepository $er) {
                                                                        return $er->createQueryBuilder('c')
                                                                                  ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                                                                                  ->add('orderBy','ORD ASC')
                                                                                  ->addOrderBy('c.name','ASC');
                                                                    },
                                                'required'=>true
                ));       
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\ServiceBundle\Entity\BillingAddress',
        ));
    }

    public function getName() {
        return 'dhi_billing_address';
    }

}
