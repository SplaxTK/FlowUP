<?php

// Carrega o .env local somente se ele existir.
$envPath = dirname(__DIR__) . '/.env';
$env = [];

if (is_file($envPath) && is_readable($envPath)) {
    $envContents = file_get_contents($envPath);
    if ($envContents === false) {
        error_log('Não foi possível ler o arquivo .env.');
    } else {
        $normalizedEnv = preg_replace('/^\s*#.*$/m', '', $envContents);
        $parsedEnv = $normalizedEnv === null ? false : @parse_ini_string($normalizedEnv, true, INI_SCANNER_TYPED);
        if (is_array($parsedEnv)) {
            $env = $parsedEnv;
        } else {
            error_log('Não foi possível interpretar o arquivo .env.');
        }
    }
}

foreach (['JWT_SECRET_KEY', 'GOOGLE_CLIENT_ID', 'GOOGLE_CLIENT_SECRET', 'GOOGLE_REDIRECT_URI', 'APP_ENCRYPTION_KEY', 'APP_TIMEZONE'] as $name) {
    if (getenv($name) === false && isset($env[$name])) {
        putenv($name . '=' . $env[$name]);
    }
}

// Produção (Vercel/Aiven) usa DB_*.
// Localmente, se DB_HOST não existir, usa o XAMPP.
$host = getenv('DB_HOST') ?: ($env['DB_HOST'] ?? 'localhost');
$port = getenv('DB_PORT') ?: ($env['DB_PORT'] ?? '3307');
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
    if (defined('FLOWUP_JSON_API_REQUEST') && FLOWUP_JSON_API_REQUEST) {
        error_log('Database connection failed: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        exit(json_encode(['error' => 'Não foi possível conectar ao banco de dados.'], JSON_UNESCAPED_UNICODE));
    }

    exit('Erro MySQL: ' . $e->getMessage());
}