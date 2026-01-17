<?php

use App\Domain\Common\Slugger;
use Dotenv\Dotenv;

// 1. Load Autoloader and Environment Variables from parent directory
require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// Check if '-t' argument is passed in the command line
$isTestMode = in_array('-t', $argv);

// Configuration
$host = $_ENV['DB_HOST'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];
$port = $_ENV['DB_PORT'] ?? 3306;
$charset = 'utf8mb4';

if ($isTestMode) {
    $db .= '_test';
    echo "\n\033[33m[!] TEST MODE DETECTED.\033[0m\n";
    echo "Target Database: \033[1m$db\033[0m\n\n";
} else {
    echo "Target Database: $db\n";
}

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    // Enable multiple statements per migration file (e.g. Create Table + Insert)
    PDO::MYSQL_ATTR_MULTI_STATEMENTS => true,
];

function addSlug(string $field, string $table, PDO $pdo): void
{
    $stmt = $pdo->query("SELECT id, $field FROM $table");
    $entities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmtEntity = $pdo->prepare("UPDATE $table SET slug = :slug WHERE id = :id");
    foreach ($entities as $row) {
        $slug = Slugger::generate($row[$field]);
        $stmtEntity->execute(['slug' => $slug, 'id' => $row['id']]);
    }
    echo "Updated " . count($entities) . " entities for table $table\n";
}

try {
    // 2. Connect to Database
    $pdo = new PDO($dsn, $user, $pass, $options);
    addSlug('title', 'articles', $pdo);
    addSlug('name', 'tags', $pdo);
    addSlug('name', 'categories', $pdo);
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
