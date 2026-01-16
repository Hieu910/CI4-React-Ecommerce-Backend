<?php
echo "<pre>";
echo "=== ENVIRONMENT VARIABLES ===\n";
echo "Hostname: " . (getenv('database.default.hostname') ?: 'NOT SET') . "\n";
echo "Port: " . (getenv('database.default.port') ?: 'NOT SET') . "\n";
echo "Database: " . (getenv('database.default.database') ?: 'NOT SET') . "\n";
echo "Username: " . (getenv('database.default.username') ?: 'NOT SET') . "\n";
echo "Password: " . (getenv('database.default.password') ? 'SET' : 'NOT SET') . "\n";

echo "\n=== ALL ENV VARS ===\n";
print_r(getenv());
echo "</pre>";