<?php 
    require_once __DIR__ . '/includes/header.php';
    ?>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="logo">FlowUp</div>
            <nav class="side-nav">
                <a class="nav-link" href="index.php">Dashboard</a>
                <a class="nav-link" href="tarefa.php">Tarefas</a>
                <a class="active" href="calendario.php">Calendário</a>
                <a class="nav-link" href="personalizar.php">Personalizar</a>
                <a class="nav-link" href="resgatar.php">Resgatar</a>
                <a class="nav-link" href="configuracoes.php">Configurações</a>
            </nav>
        </aside>
        
        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Dashboard</p>
                    <h1>Bem-vindo ao FlowUp</h1>
                </div>

                <div class="topbar-actions points-actions">
                <span class="points-badge" aria-label="Pontos acumulados">
                <?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts
                </span>
                </div>
                        
                 <div class="topbar-actions">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="configuracoes.php" class="pill active">
                            <?php echo ($_SESSION['user_username'] ?? 'Perfil'); ?>
                        </a>
                    <?php else: ?>
                        <a href="login/login.php" class="pill">Entrar</a>
                        <a href="login/cadastro.php" class="pill active">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </header>
            </main>
        </div>
    </div>
</body>
</html>
