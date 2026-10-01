<?php
require_once __DIR__ . '/../config/config.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'] ?? '';
    $usuario = $_POST['usuario'] ?? '';
    $email = $_POST['email'] ?? '';

    if ($nome && $usuario && $email) {
        $stmt = $pdo->prepare('INSERT INTO usuarios (nome_completo, usuario, email, senha) VALUES (:nome_completo, :usuario, :email, :senha)');
        $stmt->execute(['nome_completo' => $nome, 'usuario' => $usuario, 'email' => $email, 'senha' => password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT)]);
        $message = 'Cadastro realizado com sucesso!';
    } else {
        $message = 'Preencha todos os campos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Formulário PHP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <h1>Formulário PHP</h1>
    </header>
    <main>
        <div class="container">
            <?php if ($message): ?>
                <p><?php echo htmlspecialchars($message); ?></p>
            <?php endif; ?>
            <form method="post">
                <div>
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                <div>
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>

                    <label for="usuario">Usuário:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>
                <button type="submit">Enviar</button>
            </form>
        </div>
    </main>
</body>
</html>
