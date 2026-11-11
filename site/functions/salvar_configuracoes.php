<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../configuracoes.php");
    exit;
}

if (empty($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

if (!is_string($_POST['usuario'] ?? null) || !is_string($_POST['email'] ?? null)) {
    header('Location: ../configuracoes.php?erro=1');
    exit;
}

$usuario = trim($_POST['usuario']);
$email = strtolower(trim($_POST['email']));
$comprimentoUsuario = function_exists('mb_strlen') ? mb_strlen($usuario, 'UTF-8') : strlen($usuario);
$comprimentoEmail = function_exists('mb_strlen') ? mb_strlen($email, 'UTF-8') : strlen($email);

if ($usuario === '' || $email === '' || $comprimentoUsuario > 50 || $comprimentoEmail > 150) {
    header('Location: ../configuracoes.php?erro=1');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../configuracoes.php?erro=email');
    exit;
}

require_once __DIR__ . '/../config/config.php';

$usuarioId = (int) $_SESSION['user_id'];
$verifica = $pdo->prepare(
    'SELECT id FROM usuarios
     WHERE (usuario = :usuario OR email = :email) AND id <> :id
     LIMIT 1'
);
$verifica->execute(['usuario' => $usuario, 'email' => $email, 'id' => $usuarioId]);

if ($verifica->fetchColumn()) {
    header('Location: ../configuracoes.php?erro=duplicado');
    exit;
}

try {
    $stmt = $pdo->prepare('UPDATE usuarios SET usuario = :usuario, email = :email WHERE id = :id');
    $stmt->execute(['usuario' => $usuario, 'email' => $email, 'id' => $usuarioId]);
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        header('Location: ../configuracoes.php?erro=duplicado');
        exit;
    }

    error_log('[FlowUp] Falha ao salvar configurações da conta: ' . $exception->getMessage());
    header('Location: ../configuracoes.php?erro=1');
    exit;
}

$_SESSION['user_username'] = $usuario;
$_SESSION['user_email'] = $email;

header('Location: ../configuracoes.php?sucesso=1');
exit;