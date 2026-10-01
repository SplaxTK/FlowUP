<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$message = '';
$messageType = '';
$emailError = '';
$usuarioError = '';
$senhaError = '';
$confirmacaoError = '';
$formNome = '';
$formUsuario = '';
$formEmail = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['password'] ?? '';
    $confirmacao = $_POST['re-password'] ?? '';
    $formNome = $nome;
    $formUsuario = $usuario;
    $formEmail = $email;

    if ($nome === '' || $usuario === '' || $email === '' || $senha === '' || $confirmacao === '') {
        $message = 'Preencha todos os campos.';
        $messageType = 'error';
    } elseif (strlen($senha) < 6) {
        $senhaError = 'A senha deve ter pelo menos 6 caracteres.';
        $messageType = 'error';
    } elseif ($senha !== $confirmacao) {
        $confirmacaoError = 'As senhas não coincidem.';
        $messageType = 'error';
    } else {
        $check = $pdo->prepare('SELECT id, usuario, email FROM usuarios WHERE email = :email OR usuario = :usuario');
        $check->execute(['email' => $email, 'usuario' => $usuario]);

        $existing = $check->fetch();
        if ($existing) {
            if ($existing['usuario'] === $usuario) {
                $usuarioError = 'Este usuário já foi cadastrado.';
            } else {
                $emailError = 'Este e-mail já foi cadastrado.';
            }
            $messageType = 'error';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO usuarios (nome_completo, usuario, email, senha) VALUES (:nome_completo, :usuario, :email, :senha)'
            );
            $stmt->execute([
                'nome_completo' => $nome,
                'usuario' => $usuario,
                'email' => $email,
                'senha' => password_hash($senha, PASSWORD_DEFAULT),
            ]);

            $_SESSION['cadastro_sucesso'] = 'Cadastro realizado com sucesso!';
            header('Location: login.php');
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<body class="cadastro-page">

        <main class="content">
            <a class="back-link" href="../index.php" aria-label="Voltar para a página inicial">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m12 19-7-7 7-7"></path>
                    <path d="M19 12H5"></path>
                </svg>
                <span>Voltar</span>
            </a>

            <section class="hero-card cadastro-card">
                <div class="cadastro-content">
                    <p class="small-label">Cadastro</p>
                    <h2>Preencha os campos abaixo para criar sua conta</h2>
                    <?php if ($message): ?>
                        <p class="form-message <?php echo htmlspecialchars($messageType); ?>">
                            <?php echo htmlspecialchars($message); ?>
                        </p>
                    <?php endif; ?>

                    <form method="post" class="auth-form">
                    
                    <div class="form-field">
                            <label for="nome">Nome completo</label>
                            <input id="nome" name="nome" type="text" autocomplete="name" required value="<?php echo htmlspecialchars($formNome); ?>">
                        </div>

                        <div class="form-field">
                            <label for="usuario">Usuário</label>
                            <input id="usuario" name="usuario" type="text" autocomplete="username" required value="<?php echo htmlspecialchars($formUsuario); ?>">
                            <?php if ($usuarioError): ?>
                                <p class="field-error"><?php echo htmlspecialchars($usuarioError); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="email">E-mail</label>
                            <input id="email" name="email" type="email" autocomplete="email" required value="<?php echo htmlspecialchars($formEmail); ?>">
                            <?php if ($emailError): ?>
                                <p class="field-error"><?php echo htmlspecialchars($emailError); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="password">Senha</label>
                            <input id="password" name="password" type="password" autocomplete="new-password" required>
                            <?php if ($senhaError): ?>
                                <p class="field-error"><?php echo htmlspecialchars($senhaError); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="form-field">
                            <label for="re-password">Confirmar senha</label>
                            <input id="re-password" name="re-password" type="password" autocomplete="new-password" required>
                            <?php if ($confirmacaoError): ?>
                                <p class="field-error"><?php echo htmlspecialchars($confirmacaoError); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="cadastro-actions">
                            <button class="pill active form-submit" type="submit">Cadastrar</button>
                            <p class="form-help">Já tem conta? <a href="login.php">Entrar</a></p>
                        </div>
                    </form>

                    <p class="form-link-text">
                        Ao entrar, você concorda com os nossos termos.
                    <br>  
                    <a class="form-link" href="../politica/termos.php">Termos de Serviço</a> & <a class="form-link" href="../politica/politica.php">Política de Privacidade</a>.
                    </p>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
