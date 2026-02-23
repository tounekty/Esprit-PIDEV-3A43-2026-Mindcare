<?php

namespace App\Command;

use App\Repository\UserRepository;
use App\Service\PsychologicalAlertService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-psychological-alerts',
    description: 'Check all users for psychological alerts',
)]
final class CheckPsychologicalAlertsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private PsychologicalAlertService $alertService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🚨 Vérification des alertes psychologiques');

        $users = $this->userRepository->findAll();
        $io->info('Vérification de ' . count($users) . ' utilisateurs...');

        $alertCount = 0;
        foreach ($users as $user) {
            try {
                $this->alertService->checkUserAlerts($user);
                $io->writeln('✓ Utilisateur ' . $user->getEmail() . ' vérifié');
            } catch (\Exception $e) {
                $io->error('Erreur pour l\'utilisateur ' . $user->getEmail() . ': ' . $e->getMessage());
            }
        }

        $io->success('Vérification des alertes psychologiques terminée.');
        return Command::SUCCESS;
    }
}
