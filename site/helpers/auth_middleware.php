<?php
require_once __DIR__ . '/auth_helper.php';

$token = $_COOKIE['auth_token'] ?? null;
$usuarioLogado = validarTokenAutenticacao($token);

if (!$usuarioLogado) {
    header('Location: /login/login.php');
    exit;
}
$user_id        = $usuarioLogado['id'];
$user_name      = $usuarioLogado['full_name'];
$user_username  = $usuarioLogado['username'];
$user_email     = $usuarioLogado['email'];
