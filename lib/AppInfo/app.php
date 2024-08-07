<?php
// Verifica che il file autoload esista per evitare errori
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    throw new \Exception('Il file autoload.php di Composer non è stato trovato. Esegui composer install.');
}
