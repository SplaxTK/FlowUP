<?php
    require_once __DIR__ . '/includes/header.php';

$email = flowup_email($pdo);
$message = '';
$messageType = 'success';
if ($email) {
    flowup_prepare_schema($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['adicionar_recompensa'])) {
            $descricao = trim($_POST['descricao'] ?? '');
            $pontos = (int) ($_POST['pontos'] ?? 0);
            if ($descricao === '' || $pontos < 1) {
                $message = 'Informe uma recompensa e uma quantidade válida de pontos.';
                $messageType = 'error';
            } else {
                $stmt = $pdo->prepare('INSERT INTO recompensas (descricao, qtd_pon_neces) VALUES (:descricao, :pontos)');
                $stmt->execute(['descricao' => $descricao, 'pontos' => $pontos]);
                $message = 'Recompensa adicionada.';
            }
        } elseif (isset($_POST['resgatar'])) {
            $message = flowup_resgatar($pdo, $email, (int) $_POST['id_recompensa']);
            $messageType = str_contains($message, 'sucesso') ? 'success' : 'error';
        }
    }
    $rewards = $pdo->query('SELECT * FROM recompensas WHERE status = \'disponivel\' ORDER BY qtd_pon_neces ASC, data_criacao DESC')->fetchAll();
    $pontosUsuario = flowup_pontos($pdo, $email);
} else {
    $rewards = [];
    $pontosUsuario = 0;
    $message = 'Entre na sua conta para resgatar recompensas.';
    $messageType = 'error';
}
?>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="logo">FlowUp</div>
        <nav class="side-nav">
            <a class="nav-link" href="index.php"><img src="assets/img/icons/dashboard-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Dashboard</a>
            <a class="nav-link" href="tarefa.php"><img src="assets/img/icons/tasks-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Tarefas</a>
            <a class="nav-link" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
            <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
            <a class="active" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
            <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
        </nav>
    </aside>
    <div class="main-panel">
        <header class="topbar">
            <div><p class="small-label">Recompensas</p><h1>Troque seus pontos por recompensas</h1></div>
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
        <main class="content reward-page">
            <?php if ($message): ?><p class="form-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
            <section class="reward-layout">
                <div><div class="section-heading"><div><p class="small-label">Catálogo</p><h2>Suas recompensas</h2></div><span class="muted-text"><?php echo count($rewards); ?> disponíveis</span></div><div class="reward-grid">
                    <?php foreach ($rewards as $reward): $canRedeem = $pontosUsuario >= (int) $reward['qtd_pon_neces']; ?>
                        <article class="card reward-card"><h3><?php echo htmlspecialchars($reward['descricao']); ?></h3><p>Use seus pontos para resgatar esta recompensa.</p><div class="reward-footer"><strong><?php echo number_format((int) $reward['qtd_pon_neces'], 0, ',', '.'); ?> pts</strong><?php if ($email): ?><form method="post"><input type="hidden" name="id_recompensa" value="<?php echo (int) $reward['id_recompensa']; ?>"><button class="complete-button" name="resgatar" type="submit" <?php echo $canRedeem ? '' : 'disabled'; ?>><?php echo $canRedeem ? 'Resgatar' : 'Faltam pontos'; ?></button></form><?php endif; ?></div></article>
                    <?php endforeach; if (!$rewards): ?><div class="card empty-state">Nenhuma recompensa cadastrada ainda.</div><?php endif; ?>
                </div></div>
                <aside class="card add-task-card"><p class="small-label">Sua próxima conquista</p><h2>Adicionar recompensa</h2><p>Cadastre algo que você quer conquistar e defina o custo em pontos.</p><?php if ($email): ?><form method="post" class="task-form"><label for="descricao">Recompensa</label><input id="descricao" name="descricao" required maxlength="180" placeholder="Ex.: Uma hora de videogame"><label for="pontos">Custo em pontos</label><input id="pontos" name="pontos" type="number" min="1" max="100000" required placeholder="300"><button class="btn form-submit" name="adicionar_recompensa" type="submit">Adicionar recompensa</button></form><?php else: ?><a class="pill active" href="login/login.php">Entrar para continuar</a><?php endif; ?></aside>
            </section>
        </main>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
