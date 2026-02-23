<?php

namespace App\Repository;

use App\Entity\MessageForum;
use App\Entity\SujetForum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
        $qb = $this->createQueryBuilder('m');

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(m.contenu) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->orderBy('m.dateMessage', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findBySujetAndSearch(SujetForum $sujet, ?string $query): array
    {
        $qb = $this->createQueryBuilder('m')
            ->andWhere('m.sujet = :sujet')
            ->setParameter('sujet', $sujet);

        if ($query !== null && $query !== '') {
            $qb->andWhere('LOWER(m.contenu) LIKE :query')
                ->setParameter('query', '%' . strtolower($query) . '%');
        }

        return $qb->orderBy('m.dateMessage', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
