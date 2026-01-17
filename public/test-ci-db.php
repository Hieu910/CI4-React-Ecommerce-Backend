<?php
require_once __DIR__ . '/../vendor/autoload.php';

// Load CodeIgniter
$pathsConfig = require __DIR__ . '/../app/Config/Paths.php';
$paths = new $pathsConfig();

require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$app = \Config\Services::codeigniter();
$app->initialize();

echo "<pre>";
echo "=== CODEIGNITER DATABASE CONNECTION TEST ===\n\n";

try {
    // Load database config
    $config = new \Config\Database();
    echo "Database Config:\n";
    echo "Hostname: " . $config->default['hostname'] . "\n";
    echo "Port: " . $config->default['port'] . "\n";
    echo "Username: " . $config->default['username'] . "\n";
    echo "Database: " . $config->default['database'] . "\n";
    echo "SSL Verify: " . ($config->default['encrypt']['ssl_verify'] ? 'true' : 'false') . "\n\n";
    
    // Test connection
    echo "Testing connection...\n";
    $start = microtime(true);
    
    $db = \Config\Database::connect();
    
    $elapsed = microtime(true) - $start;
    
    echo "✅ CONNECTED! (took " . round($elapsed, 2) . "s)\n";
    
    // Test query
    $query = $db->query("SELECT VERSION() as version");
    $result = $query->getRow();
    echo "MySQL Version: " . $result->version . "\n";
    
} catch (\Exception $e) {
    $elapsed = microtime(true) - $start;
    echo "❌ FAILED! (after " . round($elapsed, 2) . "s)\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "</pre>";