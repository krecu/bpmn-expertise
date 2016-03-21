<?php
namespace ExpertiseBundle\Entity;

use Doctrine\ORM\EntityRepository;

class ExpertiseGroupRepository extends EntityRepository
{
    public function expertiseNotComplete()
    {
        $qb = $this->createQueryBuilder('eg');
        $qb->where('eg.entityStatus != :status')
           ->setParameter('status', "agree");
        return $qb->getQuery()
                  ->getResult();
    }

    public function findActiveExpertise($groupId, $entityId, $entityType)
    {
        $qb = $this->createQueryBuilder('eg');
        $qb->where('eg.entityStatus != :status AND eg.entity')
           ->setParameter('status', "agree");
        return $qb->getQuery()
                  ->getResult();
    }
}