<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dhi\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Dhi\UserBundle\Repository\CountryRepository;

class RegistrationFormType extends AbstractType
{
    private $class;
    private $container;         
    /**
     * @param string $class The User class name
     */
    public function __construct($class, $container)
    {
        $this->class = $class;
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            //->add('email', 'email', array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('email', 'repeated', array(
                'type' => 'email',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.email'),
                'second_options' => array('label' => 'form.email_confirmation'),
                'invalid_message' => 'Email and confirm email do not match.',
                'required' => true))
            ->add('username', null, array('label' => 'form.username', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('plainPassword', 'repeated', array(
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.password'),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch'))
            ->add('firstname', 'text', array('label' => 'form.first_name', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('lastname', 'text', array('label' => 'form.last_name', 'translation_domain' => 'FOSUserBundle', 'required' => true)) 
            ->add('address', 'text', array('label' => 'form.address', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('city', 'text', array('label' => 'form.city', 'translation_domain' => 'FOSUserBundle', 'required' => true))
//            ->add('state', 'text', array('label' => 'form.state', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('state', 'choice', array('label' => 'form.state', 'translation_domain' => 'FOSUserBundle', 'required' => true,
                'choices'=>array('' =>'Select State',
                    'AL'=>"Alabama",  
                    'AK'=>"Alaska",  
                    'AZ'=>"Arizona",  
                    'AR'=>"Arkansas",  
                    'CA'=>"California",  
                    'CO'=>"Colorado",  
                    'CT'=>"Connecticut",  
                    'DE'=>"Delaware",  
                    'DC'=>"District Of Columbia",  
                    'FL'=>"Florida",  
                    'GA'=>"Georgia",  
                    'HI'=>"Hawaii",  
                    'ID'=>"Idaho",  
                    'IL'=>"Illinois",  
                    'IN'=>"Indiana",  
                    'IA'=>"Iowa",  
                    'KS'=>"Kansas",  
                    'KY'=>"Kentucky",  
                    'LA'=>"Louisiana",  
                    'ME'=>"Maine",  
                    'MD'=>"Maryland",  
                    'MA'=>"Massachusetts",  
                    'MI'=>"Michigan",  
                    'MN'=>"Minnesota",  
                    'MS'=>"Mississippi",  
                    'MO'=>"Missouri",  
                    'MT'=>"Montana",
                    'NE'=>"Nebraska",
                    'NV'=>"Nevada",
                    'NH'=>"New Hampshire",
                    'NJ'=>"New Jersey",
                    'NM'=>"New Mexico",
                    'NY'=>"New York",
                    'NC'=>"North Carolina",
                    'ND'=>"North Dakota",
                    'OH'=>"Ohio",  
                    'OK'=>"Oklahoma",  
                    'OR'=>"Oregon",  
                    'PA'=>"Pennsylvania",  
                    'RI'=>"Rhode Island",  
                    'SC'=>"South Carolina",  
                    'SD'=>"South Dakota",
                    'TN'=>"Tennessee",  
                    'TX'=>"Texas",  
                    'UT'=>"Utah",  
                    'VT'=>"Vermont",  
                    'VA'=>"Virginia",  
                    'WA'=>"Washington",  
                    'WV'=>"West Virginia",  
                    'WI'=>"Wisconsin",  
                    'WY'=>"Wyoming",
                    'Others' => 'Others')
                ))
            ->add('zip', 'text', array('label' => 'form.zip', 'translation_domain' => 'FOSUserBundle', 'required' => true))
            ->add('country','entity',array(
            'class'=>'DhiUserBundle:Country',
           // 'empty_value' => 'Country',
            'property'      => 'name',
            'query_builder' => function(CountryRepository $er) {
                return $er->createQueryBuilder('c')
                    ->addSelect('(CASE c.name WHEN \'UNITED KINGDOM\' THEN 2 WHEN \'UNITED STATES\' THEN 1 ELSE 3 END) AS HIDDEN ORD')
                    ->add('orderBy','ORD ASC')
                    ->addOrderBy('c.name','ASC');
            },
            'required'=>true));    
            
        if ($this->container->get('kernel')->getEnvironment() != 'test') {
            
            $builder->add('captcha', 'captcha', array('invalid_message' => 'Captcha does not match','error_bubbling'=> false, 'background_color' => [255, 255, 255]));
            $builder->add('terms', 'checkbox', array('mapped' => false, 'required' => true));
            
        }
            
        /*$builder->add('captcha', 'captcha', array('invalid_message' => 'Invalid CAPTCHA','error_bubbling'=>false));
        $builder->add('terms', 'checkbox', array('mapped' => false, 'required' => true));*/
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => $this->class,
            'intention'  => 'registration',
        ));
    }

    public function getName()
    {
        return 'dhi_user_registration';
    }
}
