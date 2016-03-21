<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = array(
          new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
          new Symfony\Bundle\MonologBundle\MonologBundle(),
          new Doctrine\Bundle\DoctrineBundle\DoctrineBundle(),
          new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
          new OldSound\RabbitMqBundle\OldSoundRabbitMqBundle(),
          new Symfony\Bundle\TwigBundle\TwigBundle(),
          new FOS\RestBundle\FOSRestBundle(),
          new JMS\SerializerBundle\JMSSerializerBundle($this),
          new Nelmio\CorsBundle\NelmioCorsBundle(),
          new Nelmio\ApiDocBundle\NelmioApiDocBundle(),
          new JavierEguiluz\Bundle\EasyAdminBundle\EasyAdminBundle(),

        );

        $bundles[] = new ExpertiseBundle\ExpertiseBundle();
        $bundles[] = new Snc\RedisBundle\SncRedisBundle();
        $bundles[] = new AppBundle\AppBundle();
        $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
        $bundles[] = new Sensio\Bundle\DistributionBundle\SensioDistributionBundle();
        $bundles[] = new Sensio\Bundle\GeneratorBundle\SensioGeneratorBundle();

        return $bundles;
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(
          $this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml'
        );
    }
}
