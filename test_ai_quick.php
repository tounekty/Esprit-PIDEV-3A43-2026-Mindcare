<?php
/**
 * Quick AI test with Symfony's HTTP client
 */

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

echo "🔍 Testing AI with 5s timeout (same as app)...\n\n";

$client = HttpClient::create();

try {
    echo "Testing AI generation...\n";
    $startTime = microtime(true);
    
    $response = $client->request('POST', 'http://localhost:11434/api/generate', [
        'json' => [
            'model' => 'mistral',
            'prompt' => 'Say only "AI works" and nothing else.',
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'num_predict' => 10
            ]
        ],
        'timeout' => 5,
        'max_duration' => 5,
    ]);
    
    $data = $response->toArray();
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    if (isset($data['response']) && !empty($data['response'])) {
        echo "✅ AI Response: " . trim($data['response']) . "\n";
        echo "⏱️  Time taken: {$duration}s\n";
        
        if ($duration > 5) {
            echo "⚠️  Warning: Response took longer than timeout!\n";
        } else {
            echo "🎉 AI is working properly!\n";
        }
    } else {
        echo "❌ Empty response from AI\n";
        var_dump($data);
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n💡 Possible issues:\n";
    echo "   - Ollama is running but model is too slow (>5s)\n";
    echo "   - Model needs to load first time (can take 30s+)\n";
    echo "   - Try: ollama run mistral (to preload the model)\n";
}
