<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CronController extends AbstractController
{
    #[Route('/cron/update-appointments', name: 'cron_update_appointments', methods: ['GET'])]
    public function updateAppointments(
        Request $request,
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        // Security: Check secret token
        $token = $request->query->get('token');
        $expectedToken = $_ENV['CRON_SECRET_TOKEN'] ?? 'change-me-in-env';
        
        if ($token !== $expectedToken) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        $now = new \DateTime();
        $oneHourAgo = (clone $now)->modify('-1 hour');
        $updated = 0;

        // 1. accepted → in_progress or completed
        $acceptedAppointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :now')
            ->setParameter('status', 'accepted')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($acceptedAppointments as $appointment) {
            $appointmentEnd = (clone $appointment->getDate())->modify('+1 hour');
            
            if ($appointmentEnd <= $now) {
                $appointment->setStatus('completed');
                $updated++;
            } else {
                $appointment->setStatus('in_progress');
                $updated++;
            }
        }

        // 2. in_progress → completed
        $toComplete = $appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :oneHourAgo')
            ->setParameter('status', 'in_progress')
            ->setParameter('oneHourAgo', $oneHourAgo)
            ->getQuery()
            ->getResult();

        foreach ($toComplete as $appointment) {
            $appointment->setStatus('completed');
            $updated++;
        }

        // 3. completed → archived (30 days)
        $thirtyDaysAgo = (clone $now)->modify('-30 days')->modify('-1 hour');
        $toArchive = $appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :thirtyDaysAgo')
            ->setParameter('status', 'completed')
            ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
            ->getQuery()
            ->getResult();

        foreach ($toArchive as $appointment) {
            $appointment->setStatus('archived');
            $updated++;
        }

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'updated' => $updated,
            'checked' => [
                'accepted' => count($acceptedAppointments),
                'in_progress' => count($toComplete),
                'to_archive' => count($toArchive)
            ],
            'timestamp' => $now->format('Y-m-d H:i:s')
        ]);
    }
}