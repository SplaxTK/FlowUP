<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/auth_helper.php';

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$isPublicPage = preg_match(
    '~/(?:login/(?:login|cadastro|esqueci_senha|redefinir_senha)|politica/(?:politica|politica_cookies|termos))\.php$~',
    $scriptName
) === 1;

if (!$isPublicPage) {
    $usuarioLogado = false;
    $sessionUserId = isset($_SESSION['user_id']) ? filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT) : false;

    if ($sessionUserId !== false && $sessionUserId > 0 && !empty($_SESSION['user_email'])) {
        $usuarioLogado = [
            'user_id' => $sessionUserId,
            'email' => $_SESSION['user_email']
        ];
    } else {
        $usuarioLogado = validarTokenAutenticacao($_COOKIE['auth_token'] ?? null);
    }

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
            $_SESSION['is_admin'] = flowup_user_is_admin($usuario);
        } else {
            $usuarioLogado = false;
        }
    }

    if ($usuarioLogado && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $user_name = $_SESSION['user_full_name'];
        $user_username = $_SESSION['user_username'];
        $user_email = $_SESSION['user_email'];
    } else {

        $user_id = null;
        $user_name = 'Visitante';
        $user_username = null;
        $user_email = null;
    }
}
