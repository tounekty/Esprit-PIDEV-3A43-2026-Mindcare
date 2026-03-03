<?php
// Parse DATABASE_URL from .env
$env_content = file_get_contents('.env');
preg_match('/DATABASE_URL="([^"]+)"/', $env_content, $match);
$db_url = parse_url($match[1] ?? 'mysql://root:@127.0.0.1:3306/mindcare');

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=utf8mb4',
    $db_url['host'] ?? 'localhost',
    $db_url['path'] ? ltrim($db_url['path'], '/') : 'mindcare'
);

$conn = new PDO(
    $dsn,
    $db_url['user'] ?? 'root',
    $db_url['pass'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// Columns to add: table, column_definition, after_column
$toAdd = [
    ['journal_emotionnel', 'id_user INT NOT NULL DEFAULT 1', 'dateecriture'],
    ['patient_file', 'student_id INT DEFAULT NULL', 'id'],
    ['sujet_forum', 'id_user INT NOT NULL DEFAULT 1', 'id'],
    ['message_forum', 'id_user INT NOT NULL DEFAULT 1', 'id'],
    ['message_forum', 'sujet_id INT NOT NULL DEFAULT 1', 'id'],
    ['commentaire', 'id_user INT NOT NULL DEFAULT 1', 'id'],
    ['commentaire', 'message_id INT DEFAULT NULL', 'id'],
    ['resource', 'id_user INT NOT NULL DEFAULT 1', 'id'],
    ['appointment', 'id_user INT NOT NULL DEFAULT 1', 'id'],
    ['event', 'id_user INT NOT NULL DEFAULT 1', 'id'],
];

foreach ($toAdd as [$table, $col_def, $after]) {
    try {
        $conn->exec("ALTER TABLE $table ADD COLUMN $col_def AFTER $after");
        echo "✓ Added to $table: $col_def\n";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), '1060') !== false) {
            echo "✓ (exists) $table.$col_def\n";
        } else {
            echo "⚠ $table: " . substr($e->getMessage(), 0, 60) . "\n";
        }
    }
}

echo "\n=== Final Schema ===\n";
$tables = ['user', 'mood', 'journal_emotionnel', 'patient_file', 'sujet_forum', 'message_forum'];
foreach ($tables as $t) {
    $cols = $conn->query("SELECT GROUP_CONCAT(COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='$t'")->fetchColumn();
    echo "$t: $cols\n";
}
