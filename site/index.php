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
                <a class="active" href="index.php"><img src="assets/img/icons/dashboard-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Dashboard</a>
                <a class="nav-link" href="tarefa.php"><img src="assets/img/icons/tasks-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Tarefas</a>
                <a class="nav-link" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
                <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
                <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Dashboard</p>
                    <h1>Bem-vindo ao FlowUp</h1>
                </div>

                <div class="topbar-actions points-actions" data-preferencia-inicio="pontos">
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
                <section class="hero-card" data-preferencia-inicio="resumo">
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
                    <article class="card large-card" data-preferencia-inicio="planejamento">
                        <div class="card-title">Planejamento</div>
                    </article>

                    <article class="card" data-preferencia-inicio="calendario">
                        <div class="card-title">Calendário</div>
                    </article>
                </section>

                <section class="bottom-grid">
                    <article class="card" data-preferencia-inicio="tarefas">
                        <div class="card-title">Lista de tarefas</div>
                    </article>

                    <article class="card_clima" data-preferencia-inicio="clima">
                        <p class="weather-status" data-weather-status></p>
                        <div class="elfsight-app-f3057ea1-0a30-4ca5-9abd-1cdefccf7fb0" data-elfsight-app-lazy data-weather-widget hidden></div>
                    </article>

                </section>
            </main>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
