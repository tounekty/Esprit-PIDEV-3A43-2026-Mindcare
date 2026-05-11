<?php

namespace App\Repository;

use App\Entity\EntryTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EntryTemplate>
 */
class EntryTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EntryTemplate::class);
    }

    public function findActiveTemplates()
    {
        return $this->createQueryBuilder('t')
            ->where('t.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('t.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByCategory(string $category)
    {
        return $this->createQueryBuilder('t')
            ->where('t.category = :category')
            ->andWhere('t.isActive = :active')
            ->setParameter('category', $category)
            ->setParameter('active', true)
            ->orderBy('t.displayOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
