<?php
/**
 * Test AI on actual patient file data
 * Run: php test_ai_patientfile.php
 */

require __DIR__.'/vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__.'/.env');

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$kernel->boot();
$container = $kernel->getContainer();

// Get services
$em = $container->get('doctrine')->getManager();
$ollamaService = $container->get('App\Service\OllamaService');

echo "🔍 Testing AI on Patient Files...\n\n";

// Test connection first
echo "1️⃣ Testing Ollama connection...\n";
if (!$ollamaService->testConnection()) {
    echo "❌ Ollama not responding\n";
    exit(1);
}
echo "✅ Ollama is connected\n\n";

// Find a patient file with data
echo "2️⃣ Looking for patient files with data...\n";
$patientFiles = $em->getRepository('App\Entity\PatientFile')->findAll();

if (empty($patientFiles)) {
    echo "⚠️  No patient files found in database\n";
    echo "   Create one by visiting a patient file page and saving it\n";
    exit(0);
}

echo "Found " . count($patientFiles) . " patient file(s)\n\n";

foreach ($patientFiles as $patientFile) {
    $student = $patientFile->getStudent();
    echo "👤 Patient: " . $student->getFirstName() . " " . $student->getLastName() . "\n";
    echo "   ID: " . $patientFile->getId() . "\n";
    
    $hasHistory = !empty($patientFile->getAntecedentsPersonnels());
    $hasNotes = !empty($patientFile->getNotesGenerales());
    
    echo "   Has History: " . ($hasHistory ? "✅ Yes" : "❌ No") . "\n";
    echo "   Has Notes: " . ($hasNotes ? "✅ Yes" : "❌ No") . "\n";
    
    if ($hasHistory || $hasNotes) {
        echo "   📝 Generating AI insights...\n";
        $startTime = microtime(true);
        
        try {
            $history = $patientFile->getAntecedentsPersonnels() ?? 'None';
            $notes = $patientFile->getNotesGenerales() ?? 'None';
            $insights = $ollamaService->generateClinicalInsights($history, $notes);
            
            $duration = round(microtime(true) - $startTime, 2);
            
            if (!empty($insights)) {
                echo "   ✅ AI Insights generated in {$duration}s:\n";
                echo "   " . str_repeat("-", 60) . "\n";
                echo "   " . substr($insights, 0, 200) . "...\n";
                echo "   " . str_repeat("-", 60) . "\n";
            } else {
                echo "   ⚠️  Empty response (took {$duration}s)\n";
            }
        } catch (\Exception $e) {
            echo "   ❌ Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ⚠️  No data to generate insights from\n";
        echo "   💡 Add some text in 'Antécédents Personnels' or 'Notes Générales'\n";
    }
    echo "\n";
}

echo "✅ Test complete!\n";
