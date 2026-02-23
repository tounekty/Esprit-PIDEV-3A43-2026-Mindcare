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
            'unverifiedUsers' => $this->getUnverifiedUsers(),
            'bannedUsers' => $this->getBannedUsers(),
            'totalPsychologists' => $this->getTotalPsychologists(),
            'totalStudents' => $this->getTotalStudents(),
            'activeUsers' => $this->getTotalUsers() - $this->getBannedUsers(),
        ];
    }

    public function getChartData(): array
    {
        return [
            'roleDistribution' => $this->getRoleDistributionChart(),
            'verificationStatus' => $this->getVerificationStatusChart(),
            'userStatus' => $this->getUserStatusChart(),
            'registrationTrend' => $this->getRegistrationTrendChart(),
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

    private function getRoleDistributionChart(): array
    {
        $students = $this->getTotalStudents();
        $psychologists = $this->getTotalPsychologists();
        $admins = $this->userRepository->count(['role' => 'admin']);

        return [
            'labels' => ['Students', 'Psychologists', 'Admins'],
            'data' => [$students, $psychologists, $admins],
            'backgroundColor' => ['#3498db', '#2ecc71', '#e74c3c'],
            'borderColor' => ['#2980b9', '#27ae60', '#c0392b'],
        ];
    }

    private function getVerificationStatusChart(): array
    {
        $verified = $this->getVerifiedUsers();
        $unverified = $this->getUnverifiedUsers();

        return [
            'labels' => ['Verified', 'Unverified'],
            'data' => [$verified, $unverified],
            'backgroundColor' => ['#2ecc71', '#f39c12'],
            'borderColor' => ['#27ae60', '#d68910'],
        ];
    }

    private function getUserStatusChart(): array
    {
        $active = $this->getTotalUsers() - $this->getBannedUsers();
        $banned = $this->getBannedUsers();

        return [
            'labels' => ['Active', 'Banned'],
            'data' => [$active, $banned],
            'backgroundColor' => ['#3498db', '#e74c3c'],
            'borderColor' => ['#2980b9', '#c0392b'],
        ];
    }

    private function getRegistrationTrendChart(): array
    {
        $conn = $this->em->getConnection();
        
        // Get registration data for last 12 months
        $registrations = $conn->executeQuery(
            "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
             FROM user 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY DATE_FORMAT(created_at, '%Y-%m')
             ORDER BY month ASC"
        )->fetchAllAssociative();

        $months = [];
        $counts = [];

        foreach ($registrations as $reg) {
            $months[] = $reg['month'];
            $counts[] = (int)$reg['count'];
        }

        return [
            'labels' => $months,
            'data' => $counts,
            'borderColor' => '#3498db',
            'backgroundColor' => 'rgba(52, 152, 219, 0.1)',
            'borderWidth' => 2,
            'fill' => true,
            'tension' => 0.4,
        ];
    }
}
