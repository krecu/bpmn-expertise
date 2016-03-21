<?php

namespace ExpertiseBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ExpertiseGroupType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('groupId')
            ->add('entityId')
            ->add('entityType')
            ->add('timeStart')
            ->add('timeEnd')
            ->add('status')
            ->add('entityStatus')
            ->add('bpmnId')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'ExpertiseBundle\Entity\ExpertiseGroup'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'expertisebundle_expertisegroup';
    }
}
