<?php

namespace App\Repository;

use App\Entity\MessageForum;
use App\Entity\SujetForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageForum>
 */
class MessageForumRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageForum::class);
    }

    public function findBySearch(?string $query): array
    {
        return $this->createSearchQueryBuilder($query)
            ->getQuery()
            ->getResult();
    }

    public function findBySujetAndSearch(SujetForum $sujet, ?string $query): array
    {
        return $this->createSujetSearchQueryBuilder($sujet, $query)
            ->getQuery()
            ->getResult();
    }

    public function createSearchQueryBuilder(?string $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->leftJoin('m.analysis', 'a')
            ->addSelect('a');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(m.contenu) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->orderBy('m.dateMessage', 'DESC');
    }

    public function createSujetSearchQueryBuilder(SujetForum $sujet, ?string $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.sujet = :sujet')
            ->setParameter('sujet', $sujet);

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(m.contenu) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->orderBy('m.dateMessage', 'DESC');
    }

    public function createSujetRootSearchQueryBuilder(SujetForum $sujet, ?string $query): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.sujet = :sujet')
            ->andWhere('m.parentMessage IS NULL')
            ->setParameter('sujet', $sujet);

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(m.contenu) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->orderBy('m.dateMessage', 'DESC');
    }

    /**
     * @return MessageForum[]
     */
    public function findChildrenForTopic(SujetForum $sujet): array
    {
        return $this->createQueryBuilder('m')
            ->leftJoin('m.parentMessage', 'p')
            ->addSelect('p')
            ->andWhere('m.sujet = :sujet')
            ->andWhere('m.parentMessage IS NOT NULL')
            ->setParameter('sujet', $sujet)
            ->orderBy('m.dateMessage', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
