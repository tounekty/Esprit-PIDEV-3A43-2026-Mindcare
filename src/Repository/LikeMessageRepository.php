<?php

namespace App\Repository;

use App\Entity\LikeMessage;
use App\Entity\MessageForum;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LikeMessage>
 */
class LikeMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LikeMessage::class);
    }

    public function findOneByUserAndMessage(User $user, MessageForum $message): ?LikeMessage
    {
        return $this->findOneBy([
            'user' => $user,
            'message' => $message,
        ]);
    }

    public function findLikedMessageIdsForUserAndMessages(User $user, array $messageIds): array
    {
        if ($messageIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('lm')
            ->select('IDENTITY(lm.message) AS messageId')
            ->andWhere('lm.user = :user')
            ->andWhere('lm.message IN (:messageIds)')
            ->setParameter('user', $user)
            ->setParameter('messageIds', $messageIds)
            ->getQuery()
            ->getArrayResult();

        return array_map(static fn (array $row): int => (int) $row['messageId'], $rows);
    }

    public function getLikeCountsByMessageIds(array $messageIds): array
    {
        if ($messageIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('lm')
            ->select('IDENTITY(lm.message) AS messageId, COUNT(lm.id) AS likeCount')
            ->andWhere('lm.message IN (:messageIds)')
            ->setParameter('messageIds', $messageIds)
            ->groupBy('lm.message')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['messageId']] = (int) $row['likeCount'];
        }

        return $counts;
    }
}
