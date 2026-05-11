<?php
require 'vendor/autoload.php';
$dotenv = new Symfony\Component\Dotenv\Dotenv();
$dotenv->load(__DIR__ . '/.env');

$sid   = $_SERVER['TWILIO_ACCOUNT_SID']  ?? getenv('TWILIO_ACCOUNT_SID')  ?? '';
$token = $_SERVER['TWILIO_AUTH_TOKEN']   ?? getenv('TWILIO_AUTH_TOKEN')   ?? '';
$from  = $_SERVER['TWILIO_FROM_NUMBER']  ?? getenv('TWILIO_FROM_NUMBER')  ?? '';

echo "=== Twilio Config ===" . PHP_EOL;
echo "SID:   " . (empty($sid)   ? 'MISSING' : substr($sid,  0, 8) . '...') . PHP_EOL;
echo "Token: " . (empty($token) ? 'MISSING' : substr($token,0, 6) . '...') . PHP_EOL;
echo "From:  " . ($from ?: 'MISSING') . PHP_EOL;
echo "SID starts with AC: " . (str_starts_with($sid, 'AC') ? 'YES (valid)' : 'NO (invalid!)') . PHP_EOL;
echo PHP_EOL;

if (empty($sid) || empty($token) || empty($from)) {
    echo "ERROR: Missing Twilio credentials!" . PHP_EOL;
    exit(1);
}

// Try to send a test SMS - replace with a real verified number on Twilio trial
$testTo = '+21612345678'; // Change this to a verified number!

try {
    $client = new Twilio\Rest\Client($sid, $token);
    echo "Client created OK." . PHP_EOL;

    // Try fetching account to validate credentials
    try {
        $accounts = $client->api->accounts->page(['pageSize' => 1]);
        echo "Credentials are VALID!" . PHP_EOL;
    } catch (\Twilio\Exceptions\RestException $e) {
        echo "Twilio REST error (code {$e->getStatusCode()}): " . $e->getMessage() . PHP_EOL;
        if ($e->getStatusCode() === 401) {
            echo "=> CAUSE: Invalid Account SID or Auth Token. Update .env with correct values from console.twilio.com" . PHP_EOL;
        } elseif ($e->getStatusCode() === 403) {
            echo "=> CAUSE: Account suspended or permissions missing." . PHP_EOL;
        }
        exit(1);
    } catch (\Exception $e) {
        echo "Error: " . get_class($e) . ": " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
} catch (\Exception $e) {
    echo "ERROR creating Twilio client: " . get_class($e) . ": " . $e->getMessage() . PHP_EOL;
    exit(1);
}

// Check upcoming reservations
use Doctrine\DBAL\DriverManager;
$connectionParams = ['url' => $_SERVER['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? ''];
try {
    $conn = DriverManager::getConnection($connectionParams);
    $now = new DateTime();
    $deadline = (clone $now)->modify('+24 hours');
    
    $sql = "SELECT er.id, er.telephone, er.statut, er.sms_reminder_sent, e.titre, e.date_event
            FROM event_reservation er
            JOIN event e ON er.event_id = e.id
            WHERE er.statut = 'accepted'
            ORDER BY e.date_event ASC
            LIMIT 10";
    $rows = $conn->fetchAllAssociative($sql);
    
    echo "=== Accepted Reservations (up to 10) ===" . PHP_EOL;
    if (empty($rows)) {
        echo "No accepted reservations found at all!" . PHP_EOL;
    } else {
        foreach ($rows as $row) {
            $eventDate = new DateTime($row['date_event']);
            $inWindow = $eventDate > $now && $eventDate <= $deadline;
            echo "ID: {$row['id']} | Phone: {$row['telephone']} | Event: {$row['titre']} | Date: {$row['date_event']} | SMS sent: {$row['sms_reminder_sent']} | In 24h window: " . ($inWindow ? 'YES' : 'NO') . PHP_EOL;
        }
    }
} catch (\Exception $e) {
    echo "DB Error: " . $e->getMessage() . PHP_EOL;
}
