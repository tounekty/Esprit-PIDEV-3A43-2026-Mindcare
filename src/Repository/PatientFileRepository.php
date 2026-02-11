<?php

namespace App\Repository;

use App\Entity\PatientFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PatientFile>
 */
class PatientFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PatientFile::class);
    }

    /**
     * Finds all student users and joins their patient files if they exist.
     * Allows searching by student name and sorting by date.
     */
    public function findAllStudentsWithFiles($search = null, $sortBy = 'name', $sortOrder = 'ASC')
    {
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('u', 'pf')
            ->from('App\Entity\User', 'u')
            ->leftJoin('u.patientFile', 'pf')
            ->where('u.role = :role')
            ->setParameter('role', 'etudiant');

        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(u.firstName)', ':search'),
                    $qb->expr()->like('LOWER(u.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(u.firstName, \' \', u.lastName))', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        if ($sortBy === 'date') {
            $qb->orderBy('pf.createdAt', strtoupper($sortOrder));
        } else {
            $qb->orderBy('u.lastName', strtoupper($sortOrder));
        }

        return $qb->getQuery()->getResult();
    }
}
