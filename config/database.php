<?php

// Carrega o .env local somente se ele existir.
$envPath = dirname(__DIR__) . '/.env';
$env = [];

if (is_file($envPath) && is_readable($envPath)) {
    $parsedEnv = @parse_ini_file($envPath, true, INI_SCANNER_TYPED);
    if (is_array($parsedEnv)) {
        $env = $parsedEnv;
    }
}

// Produção (Vercel/Aiven) usa DB_*.
// Localmente, se DB_HOST não existir, usa o XAMPP.
$host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
$port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3306');
$db   = getenv('DB_NAME') ?: ($env['DB_NAME'] ?? 'flowup');
$user = getenv('DB_USER') ?: ($env['DB_USER'] ?? 'root');

// Senha:
// - Vercel: DB_PASSWORD
// - Local: SENHA do .env
$pass = getenv('DB_PASSWORD');

if ($pass === false || $pass === '') {
    $pass = $env['DB_PASSWORD'] ?? ($env['SENHA'] ?? '');
}

$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erro MySQL: ' . $e->getMessage());
}