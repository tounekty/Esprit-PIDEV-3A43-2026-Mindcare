<?php

namespace App\Command;

use App\Message\GenerateMoodPdfMessage;
use App\Message\GenerateJournalPdfMessage;
use App\Repository\MoodRepository;
use App\Repository\JournalEmotionnelRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:regenerate-pdfs',
    description: 'Regenerate all PDFs for moods and journals',
)]
class RegeneratePdfsCommand extends Command
{
    public function __construct(
        private MoodRepository $moodRepository,
        private JournalEmotionnelRepository $journalRepository,
        private MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new \Symfony\Component\Console\Style\SymfonyStyle($input, $output);

        // Regenerate mood PDFs
        $moods = $this->moodRepository->findAll();
        $io->writeln(sprintf('Found %d moods to process...', count($moods)));
        
        foreach ($moods as $mood) {
            $this->messageBus->dispatch(new GenerateMoodPdfMessage(
                $mood->getId(),
                $mood->getUser()->getId()
            ));
            $io->writeln(sprintf('Queued PDF generation for mood %d', $mood->getId()));
        }

        // Regenerate journal PDFs
        $journals = $this->journalRepository->findAll();
        $io->writeln(sprintf('Found %d journals to process...', count($journals)));
        
        foreach ($journals as $journal) {
            $this->messageBus->dispatch(new GenerateJournalPdfMessage(
                $journal->getId(),
                $journal->getUser()->getId()
            ));
            $io->writeln(sprintf('Queued PDF generation for journal %d', $journal->getId()));
        }

        $io->success('All PDFs have been queued for regeneration!');
        return Command::SUCCESS;
    }
}
