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
use Dhi\UserBundle\Repository\ServiceRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class CompensationFormType extends AbstractType {
    
    private $admin;

    public function __construct($admin) {
    	
		$this->admin = $admin;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        
        if($this->admin && $this->admin->getGroup() == 'Super Admin') {
        	
            //$compensationType =  array('ServiceLocation' => 'Service Location', 'Customer' => 'Customer');            
            $compensationType =  'ServiceLocation';
        } else {
            
            //$compensationType =  array('Customer' => 'Customer');
            $compensationType =  '';
        }
        
        $builder
                ->add('title', null, array('required' => true))
                ->add('ispHours', 'text')
                ->add('iptvDays', 'text')
                ->add('services', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Dhi\UserBundle\Entity\Service',
                                'query_builder' => function (ServiceRepository $er) {
                                    return $er->createQueryBuilder('s')
                                        ->where('s.name IN (:service)')->setParameter('service', array('ISP', 'IPTV'))
                                        ->andWhere('s.name <> :serviceBundle')->setParameter('serviceBundle','BUNDLE')
                                         ->andWhere('s.status = :status')->setParameter('status', 1)
                                        ->orderBy('s.name', 'ASC');
                                    }
                ))
                // ->add('users', 'entity', array(
                //                 'multiple' => true,
                //                 'expanded' => false,
                //                 'required' => true,
                //                 'property' => 'username',
                //                 'class' => 'Dhi\UserBundle\Entity\User',
                //                 'query_builder' => function(UserRepository $ur) {
                //                     return $ur->getAllCustomer();
                //                 },
                // ))
                ->add('serviceLocations', 'entity', array(
                                'multiple' => true,
                                'expanded' => false,
                                'required' => true,
                                'property' => 'name',
                                'class' => 'Dhi\AdminBundle\Entity\ServiceLocation'
                ))
             //   ->add('status', 'choice', array('choices'  => array('Queued' => 'Queued','Inprogress' =>'Inprogress','Completed' =>'Completed'),'required' => true))
                ->add('isActive', 'choice', array('choices'  => array(1 => 'Active', 0 => 'Inactive'),'required' => true, 'empty_value' => 'Select Status'))
                ->add('isEmailActive', 'checkbox', array('data' => false))
                ->add('emailSubject', 'text')
                ->add('emailContent', 'textarea', array(
                        'attr' => array('class' => 'tinymce')
                ))
                ->add('note', 'textarea', array(
                        'attr' => array('required' => true)
                ))
                ->add('type', 'hidden', array('required' => true,'data' => $compensationType));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\Compensation',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\UserBundle\Entity\Compensation',
        );
    }
    
    public function getName() {
        
        return 'dhi_admin_compensation';
    }

}
