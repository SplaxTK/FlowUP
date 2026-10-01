<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../configuracoes.php");
    exit;
}


/*
 * Recebe os dados do formulário.
 */

$usuario = trim($_POST["usuario"] ?? "");
$email = trim($_POST["email"] ?? "");


/*
 * Validação básica.
 */

if ($usuario === "" || $email === "" || empty($_SESSION['user_id'])) {

    header(
        "Location: ../configuracoes.php?erro=1"
    );

    exit;
}


/*
 * Validação do e-mail.
 */

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    header(
        "Location: ../configuracoes.php?erro=email"
    );

    exit;
}


require_once __DIR__ . '/../config/config.php';
$stmt = $pdo->prepare('UPDATE usuarios SET usuario = :usuario, email = :email WHERE id = :id');
$stmt->execute(['usuario' => $usuario, 'email' => $email, 'id' => $_SESSION['user_id']]);
$_SESSION['user_username'] = $usuario;
$_SESSION['user_email'] = $email;


/*
 * Retorna para configurações.
 */

header(
    "Location: ../configuracoes.php?sucesso=1"
);

exit;

?>