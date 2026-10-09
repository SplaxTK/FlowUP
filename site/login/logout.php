<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../helpers/auth_helper.php';

setcookie('auth_token', '', flowup_auth_cookie_options(time() - 3600));
flowup_clear_session();

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$siteDirectory = preg_replace('~/login$~', '', dirname($scriptName));
$sitePath = rtrim($siteDirectory, '/');

header('Location: ' . $sitePath . '/login/login.php');
exit;
