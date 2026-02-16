<?php

namespace App\Service;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class StatisticsService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {}

    public function getUserStatistics(): array
    {
        return [
            'totalUsers' => $this->getTotalUsers(),
            'verifiedUsers' => $this->getVerifiedUsers(),
            'bannedUsers' => $this->getBannedUsers(),
            'totalPsychologists' => $this->getTotalPsychologists(),
            'totalStudents' => $this->getTotalStudents(),
            'unverifiedUsers' => $this->getUnverifiedUsers(),
        ];
    }

    private function getTotalUsers(): int
    {
        return $this->userRepository->count([]);
    }

    private function getVerifiedUsers(): int
    {
        return $this->userRepository->count(['isVerified' => true]);
    }

    private function getUnverifiedUsers(): int
    {
        return $this->userRepository->count(['isVerified' => false]);
    }

    private function getBannedUsers(): int
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->where('u.bannedUntil IS NOT NULL')
            ->andWhere('u.bannedUntil > :now')
            ->setParameter('now', new \DateTime());
        
        return count($qb->getQuery()->getResult());
    }

    private function getTotalPsychologists(): int
    {
        return $this->userRepository->count(['role' => 'psychologue']);
    }

    private function getTotalStudents(): int
    {
        return $this->userRepository->count(['role' => 'etudiant']);
    }
}
