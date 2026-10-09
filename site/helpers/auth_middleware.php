<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth_helper.php';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isPublicPage = preg_match(
    '~/(?:login/(?:login|cadastro)|politica/(?:politica|politica_cookies|termos))\.php$~',
    $scriptName
) === 1;

if (!$isPublicPage) {
    $usuarioLogado = validarTokenAutenticacao($_COOKIE['auth_token'] ?? null);

    if ($usuarioLogado) {
        require_once __DIR__ . '/../config/config.php';
        $stmt = $pdo->prepare('SELECT id, nome_completo, usuario, email FROM usuarios WHERE id = :id');
        $stmt->execute(['id' => $usuarioLogado['user_id']]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $_SESSION['user_id'] = $usuario['id'];
            $_SESSION['user_full_name'] = $usuario['nome_completo'];
            $_SESSION['user_username'] = $usuario['usuario'];
            $_SESSION['user_email'] = $usuario['email'];
        } else {
            $usuarioLogado = false;
        }
    }

    if (!$usuarioLogado) {
        $scriptDirectory = str_replace('\\', '/', dirname($scriptName ?: '/index.php'));
        $sitePath = preg_replace('~/(?:login|politica|functions)$~', '', $scriptDirectory);
        $sitePath = rtrim($sitePath, '/');

        header('Location: ' . $sitePath . '/login/login.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $user_name = $_SESSION['user_full_name'];
    $user_username = $_SESSION['user_username'];
    $user_email = $_SESSION['user_email'];
}
