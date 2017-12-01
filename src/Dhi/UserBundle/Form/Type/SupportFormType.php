<?php

namespace Dhi\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Repository\SupportLocationRepository;
use Dhi\UserBundle\Repository\ServiceRepository;
use Dhi\UserBundle\Entity\Support;
use Dhi\UserBundle\Entity\Service;
use Dhi\UserBundle\Repository\CountryRepository;
use Dhi\UserBundle\Repository\SupportCategoryRepository;
use Dhi\UserBundle\Repository\SupportServiceRepository;

class SupportFormType extends AbstractType {
    private $container;
    protected $session;
    protected $whiteLabelSite;
    
    public function __construct($container) {
        $this->container = $container;
		$this->session   = $container->get('session');
        $this->whiteLabelSite = array();
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options) {
        $this->whiteLabelSite = $this->session->has('brand') ? $this->session->get('brand') : array();

		$builder
            ->add('firstname','text', array('required' => true))
            ->add('lastname','text', array('required'  => true))
            ->add('time','text', array('required'      => true))
            ->add('number','text', array('required'    => true))
            ->add('message', 'textarea', array('label' => 'Description'))
            ->add('roomNumber', 'text', array('required' => false))
            ->add('building', 'text', array('required' => false))
            ->add('category', 'entity', array(
                'class'         =>'DhiUserBundle:SupportCategory',
                'empty_value'   => 'Select Support Category',
                'property'      => 'name',
                'query_builder' => function(SupportCategoryRepository $sc) {
                    $res = $sc->createQueryBuilder('c')
                        ->innerJoin('c.supportsite', 'wl')
                        ->where('c.isDeleted = :isdeleted')
                        ->setParameter('isdeleted' , 0);
                    if (!empty($this->whiteLabelSite['id'])) {
                        $res->andWhere("wl.id = :id")->setParameter('id', $this->whiteLabelSite['id']);
                    }
                    $res->addOrderBy('c.sequenceNumber','ASC');
                    return $res;
                },
                'required'=>true)
            )
            ->add('email', 'repeated', array(
                    'type'            => 'email',
                    'options'         => array('translation_domain' => 'FOSUserBundle'),
                    'first_options'   => array('label' => 'Email'),
                    'second_options'  => array('label' => 'Confirm Email'),
                    'invalid_message' => 'fos_user.email.mismatch',
                    'required' => true
                )
            )
            ->add('country','entity',array(
                'class'         =>'DhiUserBundle:Country',
                'empty_value'   => 'Select Country',
                'property'      => 'name',
                'query_builder' => function(CountryRepository $er) {
                    return $er->createQueryBuilder('c')
                        ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                        ->add('orderBy','ORD ASC')
                        ->addOrderBy('c.name','ASC');
                },
                'required'=>true)
            )
                         ->add('supportService','entity',array(
                'class'         =>'DhiUserBundle:SupportService',
                'empty_value'   => 'Select Support Service',
                'property'      => 'serviceName',
                'query_builder' => function(SupportServiceRepository $slr) {
                    $res = $slr->createQueryBuilder('ss')
                            ->where('ss.isDeleted = :isdeleted')
                            ->setParameter('isdeleted' , 0)
                            ->andwhere('ss.isActive = :isActive')
                            ->setParameter('isActive' , 1)
                            ->OrderBy('ss.serviceName','ASC');
                    return $res;
                },
                'required'=>true)
            )
            ->add('location','entity',array(
                'class'         =>'DhiUserBundle:SupportLocation',
                'empty_value'   => 'Select Location',
                'property'      => 'name',
                'query_builder' => function(SupportLocationRepository $slr) {
                    $res = $slr->createQueryBuilder('sl')
                        ->innerJoin('sl.supportsite', 'cs')
                        ->innerJoin('sl.solarwindsSupportLocation', 'sw')
                        ->where('sl.isDeleted = :isdeleted')
                        ->setParameter('isdeleted', 0)
                        ->addOrderBy('sl.sequenceNumber','ASC');
                    if (!empty($this->whiteLabelSite['id'])) {
                        $res->andwhere("cs.id = :id")->setParameter('id', $this->whiteLabelSite['id']);
                    }
                    return $res;
                },
                'required'=>true)
            );

        if ($this->container->get('kernel')->getEnvironment() != 'test') {
            $builder->add('captcha', 'captcha', array('invalid_message' => 'Captcha does not match','as_url' => true, 'reload' => true,'error_bubbling'=>false, 'mapped' => false, 'background_color' => [255, 255, 255]));
        }
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\UserBundle\Entity\Support',
        ));
    }

    public function getName() {
        return 'dhi_user_support';
    }

}
