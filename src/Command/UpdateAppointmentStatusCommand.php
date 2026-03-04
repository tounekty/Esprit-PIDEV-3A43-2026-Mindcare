<?php

namespace App\Command;

use App\Repository\AppointmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:update-appointment-status',
    description: 'Auto-update appointment statuses based on time (accepted → in_progress → completed → archived)',
)]
class UpdateAppointmentStatusCommand extends Command
{
    private AppointmentRepository $appointmentRepository;
    private EntityManagerInterface $em;

    public function __construct(
        AppointmentRepository $appointmentRepository,
        EntityManagerInterface $em
    ) {
        parent::__construct();
        $this->appointmentRepository = $appointmentRepository;
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = new \DateTime();
        $oneHourAgo = (clone $now)->modify('-1 hour');
        $updated = 0;

        // Get all accepted appointments
        $acceptedAppointments = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :now')
            ->setParameter('status', 'accepted')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        foreach ($acceptedAppointments as $appointment) {
            $appointmentEnd = (clone $appointment->getDate())->modify('+1 hour');
            
            // If appointment ended more than 1 hour ago → completed
            if ($appointmentEnd <= $now) {
                $appointment->setStatus('completed');
                $io->writeln(sprintf(
                    'Appointment #%d → completed (ended at %s)',
                    $appointment->getId(),
                    $appointmentEnd->format('Y-m-d H:i')
                ));
                $updated++;
            }
            // If appointment started but not ended yet → in_progress
            else {
                $appointment->setStatus('in_progress');
                $io->writeln(sprintf(
                    'Appointment #%d → in_progress (started at %s)',
                    $appointment->getId(),
                    $appointment->getDate()->format('Y-m-d H:i')
                ));
                $updated++;
            }
        }

        // 2. in_progress → completed (1 hour after start)
        $toComplete = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :oneHourAgo')
            ->setParameter('status', 'in_progress')
            ->setParameter('oneHourAgo', $oneHourAgo)
            ->getQuery()
            ->getResult();

        foreach ($toComplete as $appointment) {
            $appointment->setStatus('completed');
            $appointmentEnd = (clone $appointment->getDate())->modify('+1 hour');
            $io->writeln(sprintf(
                'Appointment #%d → completed (ended at %s)',
                $appointment->getId(),
                $appointmentEnd->format('Y-m-d H:i')
            ));
            $updated++;
        }

        // 3. completed → archived (30 days after completion)
        $thirtyDaysAgo = (clone $now)->modify('-30 days')->modify('-1 hour'); // -1h for session duration
        $toArchive = $this->appointmentRepository->createQueryBuilder('a')
            ->where('a.status = :status')
            ->andWhere('a.date <= :thirtyDaysAgo')
            ->setParameter('status', 'completed')
            ->setParameter('thirtyDaysAgo', $thirtyDaysAgo)
            ->getQuery()
            ->getResult();

        foreach ($toArchive as $appointment) {
            $appointment->setStatus('archived');
            $io->writeln(sprintf(
                'Appointment #%d → archived (completed %s)',
                $appointment->getId(),
                $appointment->getDate()->format('Y-m-d')
            ));
            $updated++;
        }

        $this->em->flush();

        if ($updated > 0) {
            $io->success(sprintf('Updated %d appointment(s).', $updated));
        } else {
            $io->info('No appointments to update.');
        }

        return Command::SUCCESS;
    }
}