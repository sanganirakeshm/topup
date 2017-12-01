<?php

namespace Dhi\AdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class SettingFormType extends AbstractType {
    
    protected $isMaintenance;

    public function __construct ($isMaintenance = false) {
        
        $this->isMaintenance = $isMaintenance;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options) {

        $builder->add('name', 'text' ,array("read_only" => $builder->getData()->getId() ? true : false, "disabled" => $builder->getData()->getId() ? true : false));
       
        if ($this->isMaintenance) {
            
            $builder->add('value', 'choice', array(
                'choices' => array('True' => 'True', 'False' => 'False'),
            ));
        } else {
            
            $builder->add('value');
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver) {

        $resolver->setDefaults(array(
            'data_class' => 'Dhi\AdminBundle\Entity\Setting'
        ));
    }

    /**
     * @return string
     */
    public function getName() {

        return 'dhi_admin_setting';
    }

}
