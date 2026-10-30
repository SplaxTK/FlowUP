<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/senha_helper.php';

$token = (string) ($_POST['token'] ?? $_GET['token'] ?? '');
$mensagem = '';
$tipoMensagem = 'error';
$podeRedefinir = false;

if (!flowup_token_senha_formato_valido($token)) {
    $mensagem = 'Este link de redefinição é inválido ou expirou. Solicite um novo link.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $novaSenha = (string) ($_POST['nova_senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');
    $erroSenha = flowup_validar_nova_senha($novaSenha, $confirmacao);

    if ($erroSenha !== null) {
        $mensagem = $erroSenha;
        $podeRedefinir = flowup_buscar_token_senha($pdo, flowup_hash_token_senha($token)) !== null;
    } elseif (flowup_trocar_senha_com_token(
        $pdo,
        flowup_hash_token_senha($token),
        password_hash($novaSenha, PASSWORD_DEFAULT)
    )) {
        $_SESSION['senha_redefinida'] = true;
        header('Location: login.php');
        exit;
    } else {
        $mensagem = 'Este link já foi usado ou expirou. Solicite um novo link.';
    }
} else {
    $podeRedefinir = flowup_buscar_token_senha($pdo, flowup_hash_token_senha($token)) !== null;

    if (!$podeRedefinir) {
        $mensagem = 'Este link já foi usado ou expirou. Solicite um novo link.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<body class="auth-shell-page">
    <div class="app-shell">
        <div class="main-painel">
            <main class="content">
                <section class="hero-card">
                    <div>
                        <p class="small-label">Recuperação de conta</p>
                        <h1>Redefinir senha</h1>
                        <?php if ($mensagem !== ''): ?>
                            <p class="form-message <?php echo htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
                                <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>
                        <?php if ($podeRedefinir): ?>
                            <form method="post" class="auth-form">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="form-field">
                                    <label for="nova_senha">Nova senha</label>
                                    <input id="nova_senha" name="nova_senha" type="password" autocomplete="new-password" minlength="6" maxlength="72" required>
                                </div>
                                <div class="form-field">
                                    <label for="confirmar_senha">Confirmar nova senha</label>
                                    <input id="confirmar_senha" name="confirmar_senha" type="password" autocomplete="new-password" minlength="6" maxlength="72" required>
                                </div>
                                <button class="pill" type="submit">Salvar nova senha</button>
                            </form>
                        <?php else: ?>
                            <p><a href="esqueci_senha.php">Solicitar outro link</a></p>
                        <?php endif; ?>
                        <p><a href="login.php">Voltar para entrar</a></p>
                    </div>
                </section>
            </main>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
