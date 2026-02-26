<?php

namespace App\Repository;

use App\Entity\MessageForum;
use App\Entity\MessageForumAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MessageForumAnalysis>
 */
class MessageForumAnalysisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MessageForumAnalysis::class);
    }

    public function findOneByMessage(MessageForum $message): ?MessageForumAnalysis
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.message = :message')
            ->setParameter('message', $message)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
