<?php
echo "<pre>";
echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_PORT: " . ($_ENV['DB_PORT'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "DB_HOST: " . (env('DB_HOST') ?? 'NOT SET') . "\n";
echo "DB_PORT: " . (env('DB_PORT') ?? 'NOT SET') . "\n";
echo "DB_USER: " . (env('DB_USER') ?? 'NOT SET') . "\n";
echo "</pre>";