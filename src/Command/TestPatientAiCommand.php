<?php

namespace App\Command;

use App\Repository\PatientFileRepository;
use App\Service\OllamaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-patient-ai',
    description: 'Test AI insights on actual patient files',
)]
class TestPatientAiCommand extends Command
{
    public function __construct(
        private OllamaService $ollamaService,
        private PatientFileRepository $patientFileRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Testing AI on Patient Files');

        // Test connection
        $io->section('Testing Ollama Connection');
        if (!$this->ollamaService->testConnection()) {
            $io->error('Ollama not responding');
            return Command::FAILURE;
        }
        $io->success('Ollama is connected');

        // Find patient files
        $io->section('Looking for Patient Files');
        $patientFiles = $this->patientFileRepository->findAll();

        if (empty($patientFiles)) {
            $io->warning('No patient files found in database');
            $io->note('Create one by visiting a patient file page and saving it');
            return Command::SUCCESS;
        }

        $io->text('Found ' . count($patientFiles) . ' patient file(s)');
        $io->newLine();

        foreach ($patientFiles as $patientFile) {
            $student = $patientFile->getStudent();
            $io->writeln('<info>👤 Patient:</info> ' . $student->getFirstName() . ' ' . $student->getLastName());
            $io->writeln('<comment>   ID:</comment> ' . $patientFile->getId());
            
            $hasHistory = !empty($patientFile->getAntecedentsPersonnels());
            $hasNotes = !empty($patientFile->getNotesGenerales());
            
            $io->writeln('   Has History: ' . ($hasHistory ? '✅ Yes' : '❌ No'));
            $io->writeln('   Has Notes: ' . ($hasNotes ? '✅ Yes' : '❌ No'));
            
            if ($hasHistory || $hasNotes) {
                $io->writeln('   📝 Generating AI insights...');
                $startTime = microtime(true);
                
                try {
                    $history = $patientFile->getAntecedentsPersonnels() ?? 'None';
                    $notes = $patientFile->getNotesGenerales() ?? 'None';
                    $insights = $this->ollamaService->generateClinicalInsights($history, $notes);
                    
                    $duration = round(microtime(true) - $startTime, 2);
                    
                    if (!empty($insights)) {
                        $io->success("AI Insights generated in {$duration}s");
                        $io->block($insights, null, 'fg=white;bg=blue', ' ', true);
                    } else {
                        $io->warning("Empty response (took {$duration}s)");
                    }
                } catch (\Exception $e) {
                    $io->error('Error: ' . $e->getMessage());
                }
            } else {
                $io->warning('No data to generate insights from');
                $io->note('Add some text in "Antécédents Personnels" or "Notes Générales"');
            }
            $io->newLine();
        }

        $io->success('Test complete!');

        return Command::SUCCESS;
    }
}