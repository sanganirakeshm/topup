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
use Dhi\AdminBundle\Repository\ServiceLocationRepository;

class BusinessFormType extends AbstractType {

    public function __construct($type)
    {
        $this->type = $type;
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('name', 'text',array('required' => true))
            ->add('description', 'textarea',array('required' => false))
            ->add('pocName', 'text',array('required' => false))
            ->add('pocEmail', 'text',array('required' => false))
            ->add('pocPhone', 'text',array('required' => false))
            ->add('reason', 'textarea',array('required' => true))
            ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));
        
        if($this->type == 'add'){
            $builder->add('services', 'entity', array(
                    'class' => 'DhiUserBundle:Service',
                    'property' => 'name',
                    'multiple' => true,
                    'query_builder' => function(ServiceRepository $er) {
                        return $er->createQueryBuilder('s')
                                ->where('s.status = :statusVal')
                                ->setParameter('statusVal', 1)
                                ->andWhere('s.name IN (:services)')
                                ->setParameter('services', array('IPTV', 'ISP', 'BUNDLE'))
                                ->andWhere('s.status = :status')->setParameter('status', 1)
                                ->addOrderBy('s.name', 'ASC');
                    },
                    'required' => true,
                    'empty_value' => 'Select Service'));
        }
    }

    public function getName() {
        return 'dhi_admin_business';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\Business'
        ));
    }
}