<?php
    require_once __DIR__ . '/includes/header.php';
    $tarefasHoje = flowup_tarefas_hoje($pdo, $_SESSION['user_email'] ?? null);
    $metasHoje = flowup_metas_hoje($pdo, $_SESSION['user_email'] ?? null);
?>
<body>
    <div class="app-shell">
        <aside class="sidebar">
            <div class="logo">FlowUp</div>
            <nav class="side-nav">
                <a class="active" href="index.php">Dashboard</a>
                <a class="nav-link" href="tarefa.php">Tarefas</a>
                <a class="nav-link" href="calendario.php">Calendário</a>
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

            <main class="content">
                <section class="hero-card">
                    <div>
                        <p class="small-label">Hoje</p>
                        <h2>Organize sua rotina com mais clareza</h2>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-box"><?php echo $tarefasHoje; ?> tarefas</div>
                        <div class="stat-box"><?php echo $metasHoje; ?> metas</div>
                    </div>
                </section>

                <section class="cards-grid">
                    <article class="card large-card">
                        <div class="card-title">Planejamento</div>
                    </article>

                    <article class="card">
                        <div class="card-title">Calendário</div>
                    </article>
                </section>

                <section class="bottom-grid">
                    <article class="card">
                        <div class="card-title">Lista de tarefas</div>
                    </article>

                    <article class="card_clima">
                    <script src="https://elfsightcdn.com/platform.js" async></script>
                    <div class="elfsight-app-f3057ea1-0a30-4ca5-9abd-1cdefccf7fb0" data-elfsight-app-lazy></div>
                    </article>

                </section>
            </main>
        </div>
    </div>
</body>
</html>
