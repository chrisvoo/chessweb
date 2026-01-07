<?php

require __DIR__ . '/../vendor/autoload.php';

$dataDir = __DIR__ . '/../data';

if (isset($_ENV['CREATE_TEST_DATABASE']) && boolval($_ENV['CREATE_TEST_DATABASE']) === true) {
    echo "\nCreating test database...\n";
    $output = shell_exec('mysql -uroot --password=test < ' . $dataDir . '/chess_test.sql 2>&1');
    if (str_contains($output, 'ERROR')) {
        echo $output;
        die('stopping tests');
    }
    echo "Done!\n";
}
