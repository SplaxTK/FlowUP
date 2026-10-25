<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/auth_helper.php';
require_once __DIR__ . '/../helpers/senha_helper.php';

$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $mensagem = 'Informe um e-mail válido.';
        $tipoMensagem = 'error';
    } else {
        $mensagem = 'Se o e-mail estiver cadastrado, você receberá as instruções para redefinir sua senha. Verifique também a caixa de spam.';
        $tipoMensagem = 'success';

        $stmt = $pdo->prepare('SELECT id, nome_completo, email FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $limite = $pdo->prepare(
                'SELECT COUNT(*) AS quantidade, MAX(criado_em) AS ultimo_pedido
                 FROM alteracao_senha_tokens
                 WHERE usuario_id = :id
                   AND criado_em >= DATE_SUB(NOW(), INTERVAL 1 HOUR)'
            );
            $limite->execute(['id' => $usuario['id']]);
            $pedidos = $limite->fetch(PDO::FETCH_ASSOC);
            $ultimoPedido = !empty($pedidos['ultimo_pedido']) ? strtotime($pedidos['ultimo_pedido']) : false;
            $intervaloAtivo = $ultimoPedido !== false
                && $ultimoPedido > time() - FLOWUP_SENHA_INTERVALO_SEGUNDOS;

            if ((int) $pedidos['quantidade'] < FLOWUP_SENHA_MAX_PEDIDOS_HORA && !$intervaloAtivo) {
                $urlBase = flowup_url_base();

                if ($urlBase === null) {
                    error_log('[FlowUp] APP_URL ausente ou inválida; não foi possível criar link de redefinição.');
                } else {
                    [$token, $tokenHash] = flowup_gerar_token_senha();
                    $insereToken = $pdo->prepare(
                        'INSERT INTO alteracao_senha_tokens (usuario_id, token_hash, expira_em)
                         VALUES (:usuario_id, :token_hash, DATE_ADD(NOW(), INTERVAL ' . FLOWUP_SENHA_TOKEN_MINUTOS . ' MINUTE))'
                    );
                    $insereToken->execute([
                        'usuario_id' => $usuario['id'],
                        'token_hash' => $tokenHash,
                    ]);
                    $tokenId = (int) $pdo->lastInsertId();

                    $link = $urlBase . '/login/redefinir_senha.php?token=' . rawurlencode($token);
                    [$html, $texto] = flowup_montar_email_alteracao_senha(
                        (string) $usuario['nome_completo'],
                        $link,
                        FLOWUP_SENHA_TOKEN_MINUTOS
                    );
                    $envio = flowup_enviar_email_resend(
                        (string) $usuario['email'],
                        'Redefinição de senha do FlowUp',
                        $html,
                        $texto
                    );

                    if ($envio['ok']) {
                        $invalidaAnteriores = $pdo->prepare(
                            'UPDATE alteracao_senha_tokens
                             SET usado_em = NOW()
                             WHERE usuario_id = :usuario_id
                               AND id <> :id
                               AND usado_em IS NULL'
                        );
                        $invalidaAnteriores->execute([
                            'usuario_id' => $usuario['id'],
                            'id' => $tokenId,
                        ]);
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<body>
    <div class="app-shell">
        <div class="main-panel">
            <main class="content">
                <section class="hero-card">
                    <div>
                        <p class="small-label">Recuperação de conta</p>
                        <h1>Esqueci minha senha</h1>
                        <p>Informe o e-mail cadastrado para receber um link seguro de redefinição.</p>
                        <?php if ($mensagem !== ''): ?>
                            <p class="form-message <?php echo htmlspecialchars($tipoMensagem, ENT_QUOTES, 'UTF-8'); ?>" role="status">
                                <?php echo htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        <?php endif; ?>
                        <form method="post" class="auth-form">
                            <div class="form-field">
                                <label for="email">E-mail</label>
                                <input id="email" name="email" type="email" autocomplete="email" required>
                            </div>
                            <button class="pill" type="submit">Enviar instruções</button>
                        </form>
                        <p><a href="login.php">Voltar para entrar</a></p>
                    </div>
                </section>
            </main>
        </div>
    </div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
