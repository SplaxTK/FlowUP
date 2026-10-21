<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/flowup.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/auth_middleware.php';
require_once __DIR__ . '/../helpers/senha_helper.php';

$pontosUsuario = flowup_pontos($pdo, flowup_user_id());

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
$scheme = $isHttps ? 'https' : 'http';

// Tenta obter o APP_URL do ambiente
$envAppUrl = flowup_env('APP_URL');

// Valida se o APP_URL lido do .env pertence ao mesmo host atual.
// Se no .env estiver uma URL de produção (ex: vercel.app) e você estiver em localhost,
// ele ignora o .env para não quebrar o CSS local.
$isLocalHost = in_array(explode(':', $host)[0], ['localhost', '127.0.0.1'], true);
$envHost = $envAppUrl ? (parse_url($envAppUrl, PHP_URL_HOST) ?: '') : '';

if ($envAppUrl && !$isLocalHost && strtolower($envHost) === strtolower(explode(':', $host)[0])) {
    $siteUrl = $envAppUrl;
} else {
    // Cálculo dinâmico para ambiente local ou quando APP_URL não bate com o host
    $docRoot = rtrim(str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
    $siteDir = str_replace('\\', '/', dirname(__DIR__)); // Diretório 'site'

    if (!empty($docRoot) && strpos($siteDir, $docRoot) === 0) {
        $sitePath = substr($siteDir, strlen($docRoot));
    } else {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $sitePath = str_replace('\\', '/', dirname($scriptName));
        $sitePath = preg_replace('~/(includes|functions|helpers|crud|config|login|politica)$~', '', $sitePath);
    }

    $siteUrl = $scheme . '://' . $host . '/' . ltrim($sitePath, '/');
}

$siteUrl = rtrim((string)$siteUrl, '/');

require_once __DIR__ . '/head.php';