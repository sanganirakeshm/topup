<?php

namespace Dhi\AdminBundle\Form\Type;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Extension\Core\View\ChoiceView;

use Dhi\AdminBundle\Repository\ServiceLocationRepository;
use Dhi\AdminBundle\Repository\WhiteLabelRepository;
use Dhi\AdminBundle\Entity\ServiceLocation;

class ServiceLocationWiseSiteFormType extends AbstractType {
    
    protected $admin;

    public function __construct($options){
        $this->admin = $options['admin'];
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder->add('serviceLocation', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
                    'query_builder' => function(ServiceLocationRepository $er) {

                        $serviceLocation = array();
                        if ($this->admin->getGroup() != 'Super Admin') {
                            $location = $this->admin->getServiceLocations();
                            foreach ($location as $key => $value) {
                                $serviceLocation[] = $value->getId();
                            }
                        }

                        $res = $er->createQueryBuilder('sl')
                            ->leftJoin('sl.serviceLocationWiseSite', 'lw', 'with', 'sl.id = lw.serviceLocation AND lw.isDeleted = false')
                            ->where('lw.id IS NULL')
                            ->orderBy("sl.name");
                        if (!empty($serviceLocation)) {
                            $res
                                ->andWhere("sl.id IN (:serviceLocation)")
                                ->setParameter("serviceLocation", $serviceLocation);
                        }

                        return $res;

                    },
                    'empty_value' => 'Select'
                ))
                ->add('whiteLabel', 'entity', array(
                    'multiple' => false,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'fullName',
                    'class' => 'Dhi\AdminBundle\Entity\WhiteLabel',
                    'query_builder' => function(WhiteLabelRepository $er) {
                        return $er->createQueryBuilder('wl')
                                ->where('wl.isDeleted = :isdeleted')
                                ->setParameter('isdeleted', 0)
                                ->andWhere('wl.status = :status')
                                ->setParameter('status', 1)
                                ->orderBy('wl.companyName');
                    },
                    'empty_value' => 'Select'
                ));
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationWiseSite',
        ));
    }
    
    public function getDefaultOptions(array $options)
    {
        return array(
                'data_class' => 'Dhi\AdminBundle\Entity\ServiceLocationWiseSite',
        );
    }
    
    public function getName() {
        
        return 'dhi_admin_service_location_wise_site';
    }

}
