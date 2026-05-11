<?php
/**
 * Quick AI diagnostic script
 * Run: php test_ai.php
 */

// Test if Ollama is running
echo "🔍 Testing Ollama AI...\n\n";

// Test 1: Check if service is running
echo "1️⃣ Checking if Ollama is running on localhost:11434...\n";
$ch = curl_init('http://localhost:11434/api/tags');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 2);
$response = curl_exec($ch);
$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($statusCode === 200) {
    echo "✅ Ollama is running!\n\n";
} else {
    echo "❌ Ollama is NOT running. Start it with: ollama serve\n";
    exit(1);
}

// Test 2: List available models
echo "2️⃣ Available models:\n";
$data = json_decode($response, true);
if (!empty($data['models'])) {
    foreach ($data['models'] as $model) {
        echo "   - " . $model['name'] . "\n";
    }
} else {
    echo "   No models found. Install one with: ollama pull mistral\n";
}
echo "\n";

// Test 3: Quick generation test
echo "3️⃣ Testing text generation (this may take 10-30 seconds)...\n";
$ch = curl_init('http://localhost:11434/api/generate');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'model' => 'mistral',
    'prompt' => 'Say "AI is working" in exactly 3 words.',
    'stream' => false,
    'options' => [
        'temperature' => 0.7,
        'num_predict' => 20
    ]
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Error: $error\n";
} else {
    $data = json_decode($response, true);
    if (isset($data['response'])) {
        echo "✅ AI Response: " . trim($data['response']) . "\n";
        echo "\n🎉 AI is working correctly!\n";
    } else {
        echo "❌ Unexpected response format\n";
        echo "Response: $response\n";
    }
}

echo "\n💡 Tips:\n";
echo "   - If slow: Use a smaller model (mistral is good)\n";
echo "   - If freezing site: The timeout is now 5s, page won't freeze\n";
echo "   - AI features work on Patient Files with data\n";
