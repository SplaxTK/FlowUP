<?php
require_once __DIR__ . '/auth_helper.php';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isAuthenticationPage = preg_match('~/(?:login/login|login/cadastro)\.php$~', $scriptName) === 1;

if (!$isAuthenticationPage) {
    $token = $_COOKIE['auth_token'] ?? null;
    $usuarioLogado = validarTokenAutenticacao($token);

    if (!$usuarioLogado) {
        $scriptDirectory = str_replace('\\', '/', dirname($scriptName ?: '/index.php'));
        $sitePath = preg_replace('~/(?:login|politica|functions)$~', '', $scriptDirectory);
        $sitePath = rtrim($sitePath, '/');

        header('Location: ' . $sitePath . '/login/login.php');
        exit;
    }
}