<?php

namespace ExpertiseBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExpertiseFieldType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('fieldId')
            ->add('comment')
            ->add('value')
            ->add('expertiseUser')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ExpertiseBundle\Entity\ExpertiseField'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'expertisebundle_expertisefield';
    }
}
