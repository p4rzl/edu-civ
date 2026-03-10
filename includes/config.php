<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dataDir = __DIR__ . '/../data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

$dbPath = $dataDir . '/lanz.sqlite';

if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
    http_response_code(500);
    exit(
        'Errore configurazione server: il driver PDO SQLite non e attivo. ' .
        'Abilita l\'estensione pdo_sqlite (e sqlite3) nel tuo PHP oppure usa una distribuzione PHP che le includa.'
    );
}

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (PDOException $e) {
    http_response_code(500);
    exit('Connessione al database non riuscita: ' . $e->getMessage());
}

require_once __DIR__ . '/db_init.php';
initializeDatabase($pdo);
