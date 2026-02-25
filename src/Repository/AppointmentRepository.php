<?php

namespace App\Repository;

use App\Entity\Appointment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Appointment>
 */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function countByPsychologueAndStatus($psychologue, $status): int
    {
        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.psychologue = :psychologue')
            ->andWhere('a.status = :status')
            ->setParameter('psychologue', $psychologue)
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findByPsychologueOrderedByDate($psychologue, $status = null)
    {
        $qb = $this->createQueryBuilder('a')
            ->andWhere('a.psychologue = :psychologue')
            ->setParameter('psychologue', $psychologue)
            ->orderBy('a.date', 'ASC');

        if ($status) {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find appointments by psychologue with filters, search, and sorting
     */
    public function findByPsychologueWithFilters($psychologue, $status = null, $search = null, $sortBy = 'date', $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.etudiant', 'e')
            ->andWhere('a.psychologue = :psychologue')
            ->setParameter('psychologue', $psychologue);

        // Filter by status
        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        // Search by student name
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.firstName)', ':search'),
                    $qb->expr()->like('LOWER(e.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(e.firstName, \' \', e.lastName))', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Sorting
        $validSortFields = ['date', 'status'];
        $validSortOrders = ['ASC', 'DESC'];
        
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'date';
        }
        if (!in_array(strtoupper($sortOrder), $validSortOrders)) {
            $sortOrder = 'ASC';
        }

        $qb->orderBy('a.' . $sortBy, strtoupper($sortOrder));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find appointments by student with filters, search (by psychologue), and sorting
     */
    public function findByEtudiantWithFilters($etudiant, $status = null, $search = null, $sortBy = 'date', $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.psychologue', 'p')
            ->andWhere('a.etudiant = :etudiant')
            ->setParameter('etudiant', $etudiant);

        // Filter by status
        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        // Search by psychologue name
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(p.firstName)', ':search'),
                    $qb->expr()->like('LOWER(p.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(p.firstName, \' \', p.lastName))', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Sorting
        $validSortFields = ['date', 'status'];
        $validSortOrders = ['ASC', 'DESC'];
        
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'date';
        }
        if (!in_array(strtoupper($sortOrder), $validSortOrders)) {
            $sortOrder = 'ASC';
        }

        $qb->orderBy('a.' . $sortBy, strtoupper($sortOrder));

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all appointments with filters, searching across both students and psychologues
     */
    public function findAllWithFilters($status = null, $search = null, $sortBy = 'date', $sortOrder = 'ASC')
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.etudiant', 'e')
            ->leftJoin('a.psychologue', 'p');

        // Filter by status
        if ($status && $status !== 'all') {
            $qb->andWhere('a.status = :status')
               ->setParameter('status', $status);
        }

        // Search by student OR psychologue name
        if ($search) {
            $qb->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->like('LOWER(e.firstName)', ':search'),
                    $qb->expr()->like('LOWER(e.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(e.firstName, \' \', e.lastName))', ':search'),
                    $qb->expr()->like('LOWER(p.firstName)', ':search'),
                    $qb->expr()->like('LOWER(p.lastName)', ':search'),
                    $qb->expr()->like('LOWER(CONCAT(p.firstName, \' \', p.lastName))', ':search')
                )
            )
            ->setParameter('search', '%' . strtolower($search) . '%');
        }

        // Sorting
        $validSortFields = ['date', 'status'];
        $validSortOrders = ['ASC', 'DESC'];
        
        if (!in_array($sortBy, $validSortFields)) {
            $sortBy = 'date';
        }
        if (!in_array(strtoupper($sortOrder), $validSortOrders)) {
            $sortOrder = 'ASC';
        }

        $qb->orderBy('a.' . $sortBy, strtoupper($sortOrder));

        return $qb->getQuery()->getResult();
    }

    /**
     * Check if student already has an appointment with this psychologue in the same week
     */
    public function hasAppointmentThisWeekWithPsychologue($student, $psychologue, $appointmentDate, $excludeAppointmentId = null): bool
    {
        // Get Monday and Sunday of the week
        $weekStart = (clone $appointmentDate)->modify('Monday this week')->setTime(0, 0, 0);
        $weekEnd = (clone $appointmentDate)->modify('Sunday this week')->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->andWhere('a.etudiant = :student')
            ->andWhere('a.psychologue = :psychologue')
            ->andWhere('a.date >= :weekStart')
            ->andWhere('a.date <= :weekEnd')
            ->andWhere('a.status NOT IN (:cancelled, :refused)')
            ->setParameter('student', $student)
            ->setParameter('psychologue', $psychologue)
            ->setParameter('weekStart', $weekStart)
            ->setParameter('weekEnd', $weekEnd)
            ->setParameter('cancelled', 'cancelled')
            ->setParameter('refused', 'refused');

        // Exclude the current appointment if editing
        if ($excludeAppointmentId) {
            $qb->andWhere('a.id != :excludeId')
               ->setParameter('excludeId', $excludeAppointmentId);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        return $count > 0;
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
