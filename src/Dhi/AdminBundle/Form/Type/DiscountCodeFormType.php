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

class DiscountCodeFormType extends AbstractType {

    protected $serviceLocations = array();

    public function __construct($params = array()){
        $this->serviceLocations = !empty($params['serviceLocation']) ? $params['serviceLocation'] : array();
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder
                ->add('serviceLocations', 'entity', array(
                    'multiple' => true,
                    'expanded' => false,
                    'required' => true,
                    'property' => 'name',
                    'class' => 'Dhi\AdminBundle\Entity\ServiceLocation',
                    'mapped' => false,
                    'query_builder' => function (ServiceLocationRepository $er) {
                        $res = $er->createQueryBuilder('sl')
                            ->orderBy('sl.name', 'ASC');

                        if (!empty($this->serviceLocations)) {
                            $res
                                ->where('sl.id IN (:serviceLocation)')
                                ->setParameter('serviceLocation', $this->serviceLocations);
                        }
                        return $res;
                    }
                ))
                ->add('amountType', 'choice', array( 'choices'  => array( 'percentage' => 'Percentage','amount' =>'Amount'),'required' => true, 'empty_value' => 'Select Amount Type'))
                ->add('discountCode', 'text',array(
                    'required' => true,
                ))
                ->add('startdate', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy',
                    'required' => true,
                ))
                ->add('discountImage','file',array(
                    'label' => 'Discount Code Banner Image:',
                    'data_class' => null,
                    'required' => false
				))
                ->add('enddate', 'date', array(
                    'widget' => 'single_text',
                    'format' => 'MM-dd-yyyy',
                    'required' => true,
                ))
                ->add('amount','text',array(
                    'required' => true,
                ))
                 ->add('note', 'textarea', array('required' => true))

                ->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select Status'));

    }

    public function getName() {
        return 'dhi_admin_discount_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\DiscountCode',

        ));
    }

//    public function finishView(FormView $view, FormInterface $form, array $options)
//    {
//        $new_choice = new ChoiceView(array(), '0', 'Both'); // <- new option
//        $view->children['services']->vars['choices'][0] = $new_choice;//<- adding the new option
//
//        asort($view->children['services']->vars['choices']);
//    }
}
