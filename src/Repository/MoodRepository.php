<?php

namespace App\Repository;

use App\Entity\Mood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Mood>
 */
class MoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mood::class);
    }
public function searchMood($userId, $humeur, $intensite, $date)
{
    $qb = $this->createQueryBuilder('m')
        ->where('m.user = :user')
        ->setParameter('user', $userId);

    if ($humeur) {
        $qb->andWhere('m.humeur = :humeur')
           ->setParameter('humeur', $humeur);
    }

    if ($intensite) {
        $qb->andWhere('m.intensite = :intensite')
           ->setParameter('intensite', $intensite);
    }

    if ($date) {
        $qb->andWhere('m.dateMood = :date')
           ->setParameter('date', $date);
    }

    return $qb->getQuery()->getResult();
}

//    /**
//     * @return Mood[] Returns an array of Mood objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Mood
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
