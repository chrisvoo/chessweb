<?php

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

try {
    // 2. Connect to Database
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Migrations script started\n";

    // 3. Ensure Migrations Table Exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=INNODB;");

    // 4. Get list of already applied migrations
    $stmt = $pdo->query("SELECT migration FROM migrations");
    $appliedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // 5. Get all SQL files in the current directory
    $files = glob(__DIR__ . '/*.sql');

    // Sort files alphabetically to ensure execution order (e.g., 001_..., 002_...)
    sort($files);

    $newMigrations = [];

    foreach ($files as $file) {
        $filename = basename($file);

        // Skip if already applied
        if (in_array($filename, $appliedMigrations)) {
            continue;
        }

        $newMigrations[] = $file;
    }

    // 6. Execute new migrations
    if (empty($newMigrations)) {
        echo "No new migrations to apply.\n";
    } else {
        echo "Found " . count($newMigrations) . " new migration(s).\n";

        foreach ($newMigrations as $file) {
            $filename = basename($file);
            echo "Applying: $filename ... ";

            // Get SQL content
            $sql = file_get_contents($file);

            // Execute SQL
            try {
                // Use a transaction for each migration file to ensure atomicity
                // Note: DDL statements (CREATE TABLE, ALTER TABLE) usually cause implicit commit in MySQL
                // preventing true rollback, but this helps for DML (INSERT/UPDATE).
                $pdo->beginTransaction();

                $pdo->exec($sql);

                // Log the migration
                $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (:migration)");
                $stmt->execute(['migration' => $filename]);

                $pdo->commit();
                echo "\033[32mDONE\033[0m\n"; // Green 'DONE'
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo "\033[31mFAILED\033[0m\n"; // Red 'FAILED'
                echo "Error: " . $e->getMessage() . "\n";
                // Stop execution on first failure to prevent database inconsistency
                exit(1);
            }
        }
        echo "All migrations applied successfully.\n";
    }
} catch (\PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
