<?php
echo "DIR: " . __DIR__ . "<br>";

$cfg = __DIR__ . '/config.php';
echo "Looking for: $cfg<br>";
echo "Exists: " . (file_exists($cfg) ? 'YES' : 'NO') . "<br>";
echo "Readable: " . (is_readable($cfg) ? 'YES' : 'NO') . "<br>";