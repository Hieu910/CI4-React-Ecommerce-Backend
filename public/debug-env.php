<?php
echo "<pre>";
echo "DB_HOST: " . ($_SERVER['DB_HOST'] ?? 'NOT SET') . "\n";
echo "DB_PORT: " . ($_SERVER['DB_PORT'] ?? 'NOT SET') . "\n";
echo "DB_USER: " . ($_SERVER['DB_USER'] ?? 'NOT SET') . "\n";
echo "</pre>";