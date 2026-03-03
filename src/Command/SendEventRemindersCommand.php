<?php

namespace App\Command;

use App\Repository\EventReservationRepository;
use App\Service\TwilioSmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Send SMS reminders for events happening in the next 24 hours',
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventReservationRepository $reservationRepository,
        private TwilioSmsService $smsService,
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $reservations = $this->reservationRepository->findUpcomingForSmsReminder(24);

        if (empty($reservations)) {
            $io->info('No upcoming event reminders to send.');
            return Command::SUCCESS;
        }

        $sent = 0;
        $failed = 0;

        foreach ($reservations as $reservation) {
            $event = $reservation->getEvent();
            $phone = $reservation->getTelephone();

            if (!$phone) {
                $io->warning("Reservation #{$reservation->getId()} has no phone number, skipping.");
                continue;
            }

            $message = sprintf(
                "Rappel MindCare: Votre evenement \"%s\" est prevu le %s a %s. Lieu: %s. A bientot!",
                $event->getTitre(),
                $event->getDateEvent()->format('d/m/Y'),
                $event->getDateEvent()->format('H:i'),
                $event->getLieu()
            );

            $success = $this->smsService->sendSms($phone, $message);

            if ($success) {
                $reservation->setSmsReminderSent(true);
                $sent++;
                $io->text("SMS sent to {$phone} for event \"{$event->getTitre()}\"");
            } else {
                $failed++;
                $error = $this->smsService->getLastError();
                $io->warning("Failed to send SMS to {$phone} for event \"{$event->getTitre()}\": {$error}");
            }
        }

        $this->em->flush();

        $io->success("Done. Sent: {$sent}, Failed: {$failed}");

        return Command::SUCCESS;
    }
}
