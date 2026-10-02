<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/functions/flowup.php';

$email = flowup_email($pdo);
$message = $_SESSION['form_message'] ?? '';
$messageType = $_SESSION['form_message_type'] ?? '';

unset($_SESSION['form_message'], $_SESSION['form_message_type']);

if ($email) {
    flowup_prepare_schema($pdo);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['adicionar'])) {
            $descricao = trim($_POST['descricao'] ?? '');
            $tipo = $_POST['tipo'] ?? 'diaria';
            $pontos = (int) ($_POST['pontos'] ?? 0);
            $tipos = ['diaria', 'semanal', 'mensal', 'meta'];
            
            if ($descricao === '' || $pontos < 1 || !in_array($tipo, $tipos, true)) {
                $_SESSION['form_message'] = 'Preencha a tarefa, o tipo e uma pontuação válida.';
                $_SESSION['form_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO novas_tarefas (descricao, tipo, qtd_pon_ganho, email) VALUES (:descricao, :tipo, :pontos, :email)');
                $stmt->execute(['descricao' => $descricao, 'tipo' => $tipo, 'pontos' => $pontos, 'email' => $email]);
                
                $_SESSION['form_message'] = 'Tarefa adicionada com sucesso!';
                $_SESSION['form_message_type'] = 'success';
            }
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } elseif (isset($_POST['concluir'])) {
            $msgResult = flowup_concluir($pdo, $email, (int) $_POST['id_tarefa']);
            
            $_SESSION['form_message'] = $msgResult;
            $_SESSION['form_message_type'] = 'success';

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    $stmt = $pdo->prepare('SELECT * FROM novas_tarefas WHERE email = :email AND status <> \'inativa\' ORDER BY FIELD(tipo, \'diaria\', \'semanal\', \'mensal\', \'meta\'), data_criacao DESC');
    $stmt->execute(['email' => $email]);
    $tarefas = $stmt->fetchAll();
    $concluidas = flowup_concluidas($pdo, $email, $tarefas);
    $pontosUsuario = flowup_pontos($pdo, $email);
} else {
    $tarefas = [];
    $concluidas = [];
    $pontosUsuario = 0;
    
    if (empty($message)) {
        $message = 'Entre na sua conta para criar e concluir tarefas.';
        $messageType = 'error';
    }
}
$grupos = ['diaria' => 'Diárias', 'semanal' => 'Semanais', 'mensal' => 'Mensais', 'meta' => 'Metas'];
require_once __DIR__ . '/includes/header.php';
?>


<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="logo">FlowUp</div>
        <nav class="side-nav">
            <a class="nav-link" href="index.php">Dashboard</a>
            <a class="active" href="tarefa.php">Tarefas</a>
            <a class="nav-link" href="calendario.php">Calendário</a>
            <a class="nav-link" href="personalizar.php">Personalizar</a>
            <a class="nav-link" href="resgatar.php">Resgatar</a>
            <a class="nav-link" href="configuracoes.php">Configurações</a>
        </nav>
    </aside>
    <div class="main-panel">
        <header class="topbar">
            <div><p class="small-label">Organização</p><h1>Suas tarefas</h1></div>
            <div class="topbar-actions points-actions"><span class="points-badge" aria-label="Pontos acumulados"><?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts</span></div>

                            <div class="topbar-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="configuracoes.php" class="pill active">
                            <?php echo htmlspecialchars($_SESSION['user_username'] ?? 'Perfil'); ?>
                        </a>
                    <?php else: ?>
                        <a href="login/login.php" class="pill">Entrar</a>
                        <a href="login/cadastro.php" class="pill active">Cadastrar</a>
                    <?php endif; ?>
                </div>
                
        </header>
        <main class="content task-page">
            <?php if ($message): ?><p class="form-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
            <section class="task-layout">
                <div class="task-list-column">
                    <?php foreach ($grupos as $tipo => $titulo): ?>
                        <section class="card task-group">
                            <div class="task-group-heading"><div><p class="small-label"><?php echo $tipo === 'diaria' ? 'Recomeça hoje' : 'Seu ritmo'; ?></p><h2><?php echo $titulo; ?></h2></div><span class="task-count"><?php echo count(array_filter($tarefas, fn($t) => $t['tipo'] === $tipo)); ?></span></div>
                            <?php $temTarefa = false; foreach ($tarefas as $tarefa): if ($tarefa['tipo'] !== $tipo) continue; $temTarefa = true; $feito = isset($concluidas[(int) $tarefa['id_tarefas']]); ?>
                                <div class="task-row <?php echo $feito ? 'is-complete' : ''; ?>">
                                    <div><strong><?php echo htmlspecialchars($tarefa['descricao']); ?></strong><span>+<?php echo (int) $tarefa['qtd_pon_ganho']; ?> pontos</span></div>
                                    <?php if ($feito): ?><span class="completed-label">Concluída</span><?php else: ?><form method="post"><input type="hidden" name="id_tarefa" value="<?php echo (int) $tarefa['id_tarefas']; ?>"><button class="complete-button" name="concluir" type="submit">Completar</button></form><?php endif; ?>
                                </div>
                            <?php endforeach; if (!$temTarefa): ?><p class="empty-state">Nenhuma tarefa nesta categoria ainda.</p><?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
                <aside class="card add-task-card">
                    <p class="small-label">Novo objetivo</p><h2>Adicionar tarefa</h2><p>Crie uma missão e defina quantos pontos ela vale.</p>
                    <?php if ($email): ?><form method="post" class="task-form"><label for="descricao">Tarefa</label><input id="descricao" name="descricao" required maxlength="180" placeholder="Ex.: Caminhar por 20 minutos"><label for="tipo">Frequência</label><select id="tipo" name="tipo"><option value="diaria">Diária</option><option value="semanal">Semanal</option><option value="mensal">Mensal</option><option value="meta">Meta</option></select><label for="pontos">Pontos</label><input id="pontos" name="pontos" type="number" min="1" max="10000" required placeholder="10"><button class="btn form-submit" name="adicionar" type="submit">Adicionar tarefa</button></form><?php else: ?><a class="pill active" href="login/login.php">Entrar para adicionar</a><?php endif; ?>
                </aside>
            </section>
        </main>
    </div>
</div>
</body>
</html>
