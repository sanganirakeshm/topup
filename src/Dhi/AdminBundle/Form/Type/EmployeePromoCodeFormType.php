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

class EmployeePromoCodeFormType extends AbstractType {

	private $employees;

	public function __construct($employees)
	{
		$this->employees = $employees;
	}

    public function buildForm(FormBuilderInterface $builder, array $options) {

	$builder->add('employeeName','choice',array(
                    'choices' => $this->employees,
                    'required' => true,
					'empty_value' => 'Select'
                ))
                ->add('amountType', 'choice', array(
                    'choices'  => array(
                        'percentage' => 'Percentage',
                        'amount' =>'Amount'),
                    'required' => true,
                    'empty_value' => 'Select Amount Type'))

                ->add('amount','text',array(
                    'required' => true,
                ))
				->add('employeepromocode', 'text')
//                ->add('reason', 'textarea', array(
//                    'attr' => array('class' => 'tinymce')
//                ))
                ->add('note', 'textarea', array('required' => true))

				->add('status', 'choice', array('choices'  => array(1 => 'Active',0 =>'Inactive'),'required' => true, 'empty_value' => 'Select'));


    }

    public function getName() {
        return 'dhi_admin_employee_promo_code';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver) {
        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\EmployeePromoCode'
        ));
    }

}
