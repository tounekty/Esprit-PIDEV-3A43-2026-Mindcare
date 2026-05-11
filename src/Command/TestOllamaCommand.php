<?php

namespace App\Command;

use App\Service\OllamaService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-ollama',
    description: 'Test Ollama AI integration',
)]
class TestOllamaCommand extends Command
{
    private OllamaService $ollamaService;

    public function __construct(OllamaService $ollamaService)
    {
        parent::__construct();
        $this->ollamaService = $ollamaService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Testing Ollama AI Integration');

        // Test 1: Connection
        $io->section('Test 1: Connection to Ollama');
        if ($this->ollamaService->testConnection()) {
            $io->success('✓ Connected to Ollama successfully!');
        } else {
            $io->error('✗ Cannot connect to Ollama. Make sure it\'s running on http://localhost:11434');
            return Command::FAILURE;
        }

        // Test 2: Simple text generation
        $io->section('Test 2: Simple Text Generation');
        $io->text('Asking: "What is mental health in one sentence?"');
        $response = $this->ollamaService->generate('What is mental health? Answer in one sentence.', ['max_tokens' => 100]);
        $io->writeln('Response: ' . $response);

        // Test 3: Session summary
        $io->section('Test 3: Session Notes Summary');
        $testNotes = "Patient reported feeling anxious about upcoming exams. Discussed coping strategies including deep breathing and time management. Patient seems receptive to cognitive behavioral therapy approaches. Will continue weekly sessions.";
        $io->text('Test Notes: ' . $testNotes);
        $summary = $this->ollamaService->summarizeSessionNotes($testNotes);
        $io->writeln('AI Summary: ' . $summary);

        // Test 4: Mood trends
        $io->section('Test 4: Mood Analysis');
        $moodData = [
            ['date' => new \DateTime('2026-02-18'), 'mood' => 'anxious', 'intensity' => 7],
            ['date' => new \DateTime('2026-02-20'), 'mood' => 'calm', 'intensity' => 4],
            ['date' => new \DateTime('2026-02-22'), 'mood' => 'stressed', 'intensity' => 8],
            ['date' => new \DateTime('2026-02-24'), 'mood' => 'better', 'intensity' => 5],
        ];
        $analysis = $this->ollamaService->analyzeMoodTrends($moodData);
        $io->writeln('AI Mood Analysis: ' . $analysis);

        $io->success('All tests completed successfully!');
        $io->note('AI features are ready to use in PatientFile and Appointment modules.');

        return Command::SUCCESS;
    }
}