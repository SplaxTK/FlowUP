<?php

// Carrega o .env local, caso exista
$env = parse_ini_file(dirname(__DIR__) . '/.env') ?: [];

// Produção (Vercel/Aiven) usa DB_*.
// Localmente, se DB_HOST não existir, usa o XAMPP.
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$db   = getenv('DB_NAME') ?: 'flowup';
$user = getenv('DB_USER') ?: 'root';

// Senha:
// - Vercel: DB_PASSWORD
// - Local: SENHA do .env
$pass = getenv('DB_PASSWORD');

if ($pass === false || $pass === '') {
    $pass = $env['SENHA'] ?? '';
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