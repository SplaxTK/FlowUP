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
                <a class="nav-link" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
                <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
                <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="active" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div><p class="small-label">Configurações</p><h1>Gerencie sua conta</h1></div>
    
                <div class="topbar-actions points-actions" data-preferencia-inicio="pontos">
                <span class="points-badge" aria-label="Pontos acumulados">
                <?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts
                </span>
                </div>                          
                <?php require __DIR__ . '/includes/profile_menu.php'; ?>
            </header>

            <main class="content configuracoes-content">
                <div class="config-intro"><h2>Configurações</h2><p>Gerencie sua conta e as preferências do FlowUp.</p></div>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone icone-tema"><img class="img-icon icone-claro" src="assets/img/icons/user-icon.png" alt="Ícone de perfil"><img class="img-icon icone-escuro" src="assets/img/icons/user-icon-2.png" alt="" aria-hidden="true"></div><div><h2>Minha conta</h2><p>Gerencie suas informações pessoais.</p></div></div>
                    <form action="functions/salvar_configuracoes.php" method="post" class="config-form">
                        <div class="campo"><label for="usuario">Usuário</label><input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" required value="<?php echo htmlspecialchars($_SESSION['user_username'] ?? ''); ?>"></div>
                        <div class="campo"><label for="email">E-mail</label><input type="email" id="email" name="email" placeholder="Digite seu e-mail" required value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>"></div>
                        <button type="submit" class="btn">Salvar informações</button>
                    </form>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone icone-tema"><img class="img-icon icone-claro" src="assets/img/icons/bell-icon.png" alt="Ícone de notificações"><img class="img-icon icone-escuro" src="assets/img/icons/bell-icon-2.png" alt="" aria-hidden="true"></div><div><h2>Notificações</h2><p>Escolha quais avisos deseja receber.</p></div></div>
                    <label class="switch-item"><div><strong>Lembretes de tarefas</strong><span>Receba lembretes sobre suas tarefas.</span></div><input type="checkbox" id="notificacaoTarefas" checked><span class="switch"></span></label>
                    <label class="switch-item"><div><strong>Metas</strong><span>Receba avisos sobre seu progresso.</span></div><input type="checkbox" id="notificacaoMetas" checked><span class="switch"></span></label>
                    <label class="switch-item"><div><strong>Recompensas</strong><span>Saiba quando ganhar novos mimos.</span></div><input type="checkbox" id="notificacaoMimos" checked><span class="switch"></span></label>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone icone-tema"><img class="img-icon icone-claro" src="assets/img/icons/earth-icon.png" alt="Ícone de preferências"><img class="img-icon icone-escuro" src="assets/img/icons/earth-icon-2.png" alt="" aria-hidden="true"></div><div><h2>Preferências</h2><p>Configure opções gerais do sistema.</p></div></div>
                    <div class="opcao"><div><strong>Idioma</strong><p>Idioma utilizado pelo FlowUp.</p></div><select id="idioma"><option value="pt-br">Português (Brasil)</option><option value="en">English</option></select></div>
                    <div class="opcao"><div><strong>Formato de data</strong><p>Escolha como as datas serão exibidas.</p></div><select id="formatoData"><option value="br">DD/MM/AAAA</option><option value="us">MM/DD/AAAA</option></select></div>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone icone-tema"><img class="img-icon icone-claro" src="assets/img/icons/lock-icon.png" alt="Ícone de segurança"><img class="img-icon icone-escuro" src="assets/img/icons/lock-icon-2.png" alt="" aria-hidden="true"></div><div><h2>Segurança</h2><p>Proteja sua conta no FlowUp.</p></div></div>
                    <div class="config-actions"><button type="button" class="btn-secundario" id="btnSenha">Alterar senha</button>
                    <button type="button" class="btn-sair" id="btnSair">Sair da conta</button></div>
                </section>
            </main>
        </div>
    </div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
