<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(30);

echo "<pre>";
echo "=== MYSQL CONNECTION TEST ===\n\n";

$host = $_ENV['DB_HOST'] ?? 'NOT_SET';
$port = $_ENV['DB_PORT'] ?? 'NOT_SET';
$user = $_ENV['DB_USER'] ?? 'NOT_SET';
$pass = $_ENV['DB_PASSWORD'] ?? 'NOT_SET';
$name = $_ENV['DB_NAME'] ?? 'NOT_SET';

echo "Host: $host\n";
echo "Port: $port\n";
echo "User: $user\n";
echo "DB: $name\n\n";

// Test 1: Check DNS resolution
echo "--- DNS RESOLUTION TEST ---\n";
$ip = gethostbyname($host);
echo "Resolved IP: $ip\n";
if ($ip === $host) {
    echo "❌ DNS resolution failed!\n\n";
} else {
    echo "✅ DNS OK\n\n";
}

// Test 2: Check port connectivity
echo "--- PORT CONNECTIVITY TEST ---\n";
$timeout = 5;
$start = microtime(true);
$fp = @fsockopen($host, $port, $errno, $errstr, $timeout);
$elapsed = microtime(true) - $start;

if ($fp) {
    echo "✅ Port $port is reachable (took " . round($elapsed, 2) . "s)\n";
    fclose($fp);
} else {
    echo "❌ Cannot reach port $port\n";
    echo "Error: $errstr ($errno)\n";
    echo "Time elapsed: " . round($elapsed, 2) . "s\n";
}
echo "\n";

// Test 3: MySQLi connection
echo "--- MYSQLI CONNECTION TEST ---\n";
if (!extension_loaded('mysqli')) {
    echo "❌ MySQLi extension not loaded!\n";
} else {
    echo "✅ MySQLi extension loaded\n";
    
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    $start = microtime(true);
    try {
        $mysqli = new mysqli();
        $mysqli->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);
        $mysqli->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
        
        $mysqli->real_connect($host, $user, $pass, $name, $port);
        
        $elapsed = microtime(true) - $start;
        
        echo "✅ CONNECTED! (took " . round($elapsed, 2) . "s)\n";
        echo "MySQL version: " . $mysqli->server_info . "\n";
        
        $mysqli->close();
    } catch (mysqli_sql_exception $e) {
        $elapsed = microtime(true) - $start;
        echo "❌ CONNECTION FAILED (after " . round($elapsed, 2) . "s)\n";
        echo "Error: " . $e->getMessage() . "\n";
        echo "Code: " . $e->getCode() . "\n";
    }
}

echo "</pre>";