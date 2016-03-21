<?php

namespace ExpertiseBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExpertiseUserType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('userId')
            ->add('timeStart')
            ->add('timeEnd')
            ->add('expertiseGroup')
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ExpertiseBundle\Entity\ExpertiseUser'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'expertisebundle_expertiseuser';
    }
}
