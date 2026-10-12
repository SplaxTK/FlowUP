<?php 
    require_once __DIR__ . '/includes/header.php';
    ?>
<body>
    <div class="app-shell">
        <aside class="sidebar" id="primarySidebar">
            <div class="sidebar-header">
                <div class="logo"><span class="sidebar-logo-text">FlowUp</span></div>
                <button class="sidebar-toggle" id="sidebarToggle" type="button" aria-label="Recolher menu lateral" aria-expanded="true" aria-controls="primarySidebar">
                    <img class="sidebar-toggle-icon sidebar-toggle-icon-expanded" src="assets/img/icons/sidebar-left-icon.png" alt="" aria-hidden="true">
                    <img class="sidebar-toggle-icon sidebar-toggle-icon-collapsed" src="assets/img/icons/sidebar-right-icon.png" alt="" aria-hidden="true">
                </button>
            </div>
            <nav class="side-nav">
                <a class="nav-link" href="index.php"><img src="assets/img/icons/dashboard-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Dashboard</a>
                <a class="nav-link" href="tarefa.php"><img src="assets/img/icons/tasks-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Tarefas</a>
                <a class="active" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
                <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
                <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>
        
        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Calendário</p>
                    <h1>Seu calendário de tarefas</h1>
                </div>

                <div class="topbar-actions points-actions" data-preferencia-inicio="pontos">
                <span class="points-badge" aria-label="Pontos acumulados">
                <?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts
                </span>
                </div>
                        
                <?php require __DIR__ . '/includes/profile_menu.php'; ?>
            </header>
            </main>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
