<?php
echo "<pre>";
echo "DB_HOST: " . ($_SERVER['DB_HOST'] ?? getenv('DB_HOST') ?? $_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_PORT: " . ($_SERVER['DB_PORT'] ??  getenv('DB_PORT') ?? $_ENV['DB_PORT'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_SERVER['DB_USER'] ??   getenv('DB_USER') ?? $_ENV['DB_USER'] ?? 'NOT SET') . "\n";
echo "</pre>";