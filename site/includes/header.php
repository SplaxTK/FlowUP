<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../functions/flowup.php';
require_once __DIR__ . '/../config/config.php';

$pontosUsuario = flowup_pontos($pdo, $_SESSION['user_email'] ?? null);

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
$scheme = $isHttps ? 'https' : 'http';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
$sitePath = str_replace('\\', '/', dirname($scriptName));
$sitePath = preg_replace('~/(login|politica|functions)$~', '', $sitePath);
$sitePath = rtrim($sitePath, '/');

$siteUrl = getenv('APP_URL');
if (!$siteUrl) {
    $siteUrl = $scheme . '://' . $host . $sitePath;
}
$siteUrl = rtrim($siteUrl, '/');
require_once __DIR__ . '/head.php';
