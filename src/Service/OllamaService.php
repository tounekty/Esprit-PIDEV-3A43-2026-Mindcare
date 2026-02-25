<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OllamaService
{
    private HttpClientInterface $httpClient;
    private string $ollamaUrl;
    private string $model;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->ollamaUrl = 'http://localhost:11434/api/generate';
        $this->model = 'mistral';
    }

    /**
     * Set custom model (e.g., 'mistral', 'llama2', 'llama3')
     */
    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    /**
     * Generate text completion from Ollama
     */
    public function generate(string $prompt, array $options = []): string
    {
        $defaultOptions = [
            'temperature' => 0.7,
            'max_tokens' => 500,
            'timeout' => 180, // 3 minutes - let Ollama take its time
        ];

        $options = array_merge($defaultOptions, $options);

        $response = $this->httpClient->request('POST', $this->ollamaUrl, [
            'json' => [
                'model' => $this->model,
                'prompt' => $prompt,
                'stream' => false,
                'options' => [
                    'temperature' => $options['temperature'],
                    'num_predict' => $options['max_tokens'],
                ]
            ],
            'timeout' => $options['timeout'],
            'max_duration' => $options['timeout'],
        ]);

        $data = $response->toArray();
        
        if (!isset($data['response']) || empty($data['response'])) {
            throw new \RuntimeException('Ollama returned empty response');
        }
        
        return $data['response'];
    }

    /**
     * Summarize patient session notes
     */
    public function summarizeSessionNotes(string $notes): string
    {
        $prompt = "You are a mental health assistant. Summarize the following therapy session notes in a clear, professional manner. Focus on key points, patient concerns, and therapeutic outcomes. Keep it concise (3-5 sentences).\n\nSession Notes:\n{$notes}\n\nSummary:";
        
        return $this->generate($prompt, ['max_tokens' => 300]);
    }

    /**
     * Analyze mood trends from patient mood data
     */
    public function analyzeMoodTrends(array $moodData): string
    {
        $moodSummary = implode(', ', array_map(function($mood) {
            return "{$mood['date']->format('Y-m-d')}: {$mood['mood']} ({$mood['intensity']}/10)";
        }, $moodData));

        $prompt = "You are a mental health assistant. Analyze the following mood tracking data and provide insights about patterns, trends, and potential concerns. Be empathetic and professional.\n\nMood Data:\n{$moodSummary}\n\nAnalysis:";
        
        return $this->generate($prompt, ['max_tokens' => 400]);
    }

    /**
     * Generate clinical insights for patient file
     */
    public function generateClinicalInsights(string $patientHistory, string $recentNotes): string
    {
        // Shorter prompt for faster response
        $prompt = "Analyze this mental health data. Give 2-3 brief observations (max 4 sentences).\n\nHistory: {$patientHistory}\n\nNotes: {$recentNotes}\n\nObservations:";
        
        return $this->generate($prompt, ['max_tokens' => 150]); // Use default 180s timeout
    }

    /**
     * Suggest optimal appointment times based on patterns
     */
    public function suggestAppointmentTimes(array $existingAppointments, string $psychologueSchedule): string
    {
        $appointmentSummary = implode(', ', array_map(function($apt) {
            return $apt['date']->format('l H:i');
        }, $existingAppointments));

        $prompt = "You are a scheduling assistant. Based on the following existing appointments and psychologist availability, suggest 3 optimal time slots for the next appointment. Consider patterns like preferred days/times.\n\nExisting Appointments:\n{$appointmentSummary}\n\nPsychologist Schedule:\n{$psychologueSchedule}\n\nSuggestions:";
        
        return $this->generate($prompt, ['max_tokens' => 300]);
    }

    /**
     * Match student with best psychologist based on needs and availability
     */
    public function matchStudentWithPsychologist(string $studentNeeds, array $availablePsychologists): string
    {
        $psychologistsList = implode("\n", array_map(function($psy, $index) {
            return ($index + 1) . ". {$psy['name']} - Specialties: {$psy['specialties']}, Availability: {$psy['availability']}";
        }, $availablePsychologists, array_keys($availablePsychologists)));

        $prompt = "You are a mental health matching assistant. Based on the student's needs and available psychologists, recommend the best match. Provide reasoning.\n\nStudent Needs:\n{$studentNeeds}\n\nAvailable Psychologists:\n{$psychologistsList}\n\nRecommendation:";
        
        return $this->generate($prompt, ['max_tokens' => 400]);
    }

    /**
     * Test connection to Ollama
     */
    public function testConnection(): bool
    {
        try {
            $response = $this->httpClient->request('GET', 'http://localhost:11434/api/tags', [
                'timeout' => 2, // Quick check only
            ]);
            return $response->getStatusCode() === 200;
        } catch (\Exception $e) {
            return false;
        }
    }
}
