<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$error = '';
$identificador = '';
$success = $_SESSION['cadastro_sucesso'] ?? '';
unset($_SESSION['cadastro_sucesso']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identificador = trim($_POST['identificador'] ?? '');
    $senha = $_POST['password'] ?? '';

    if ($identificador === '' || $senha === '') {
        $error = 'Preencha usuário ou e-mail e senha.';
    } else {
        $stmt = $pdo->prepare('SELECT id, nome_completo, usuario, email, senha FROM usuarios WHERE usuario = :usuario OR email = :email');
        $stmt->execute(['usuario' => $identificador, 'email' => $identificador]);
        $user = $stmt->fetch();

        if ($user && password_verify($senha, $user['senha'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_full_name'] = $user['nome_completo'];
            $_SESSION['user_username'] = $user['usuario'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: ../index.php');
            exit;
        }

        $error = 'E-mail ou senha inválidos.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<body>
            <aside class="sidebar">
            <div class="logo">FlowUp</div>
            <nav class="side-nav">
                <a class="active" href="../index.php">Dashboard</a>
                <a class="nav-link" href="../tarefa.php">Tarefas</a>
                <a class="nav-link" href="../calendario.php">Calendário</a>
                <a class="nav-link" href="../personalizar.php">Personalizar</a>
                <a class="nav-link" href="../resgatar.php">Resgatar</a>
                <a class="nav-link" href="../configuracoes.php">Configurações</a>
            </nav>
        </aside>
        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Login</p>
                    <h1>Entrar</h1>
                </div>
            </header>
           <main class="content">
                <section class="hero-card">
                    <div>
                        <p class="small-label">Login</p>
                        <h2>Preencha os campos abaixo para entrar</h2>
                        <?php if ($success): ?>
                            <p class="form-message success"><?php echo htmlspecialchars($success); ?></p>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <p class="form-message error"><?php echo htmlspecialchars($error); ?></p>
                        <?php endif; ?>

                    <form method="post" class="auth-form">
                        <div class="form-field">
                            <label for="identificador">Usuário ou e-mail</label>
                            <input id="identificador" name="identificador" type="text" autocomplete="username" required value="<?php echo htmlspecialchars($identificador); ?>">
                        </div>

                        <div class="form-field">
                            <label for="password">Senha</label>
                            <input id="password" name="password" type="password" autocomplete="current-password" required>
                        </div>

                        <button class="pill" type="submit">Entrar</button>
                    </form>
                    <p>Não tem conta? <a href="cadastro.php">Cadastrar</a></p>
                    </div>
                </section>
            </main>
        </div>
    </div>
</body>
</html>
