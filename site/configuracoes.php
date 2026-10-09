<?php
    require_once __DIR__ . '/includes/header.php';
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

            <main class="content configuracoes-content">
                <div class="config-intro"><h2>Configurações</h2><p>Gerencie sua conta e as preferências do FlowUp.</p></div>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone"><img class="img-icon" src="assets/img/icons/user-icon.png" alt="Ícone de perfil"></div><div><h2>Minha conta</h2><p>Gerencie suas informações pessoais.</p></div></div>
                    <form action="functions/salvar_configuracoes.php" method="post" class="config-form">
                        <div class="campo"><label for="usuario">Usuário</label><input type="text" id="usuario" name="usuario" placeholder="Digite seu usuário" required value="<?php echo htmlspecialchars($_SESSION['user_username'] ?? ''); ?>"></div>
                        <div class="campo"><label for="email">E-mail</label><input type="email" id="email" name="email" placeholder="Digite seu e-mail" required value="<?php echo htmlspecialchars($_SESSION['user_email'] ?? ''); ?>"></div>
                        <button type="submit" class="btn">Salvar informações</button>
                    </form>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone"><img class="img-icon" src="assets/img/icons/bell-icon.png" alt="Ícone de notificações"></div><div><h2>Notificações</h2><p>Escolha quais avisos deseja receber.</p></div></div>
                    <label class="switch-item"><div><strong>Lembretes de tarefas</strong><span>Receba lembretes sobre suas tarefas.</span></div><input type="checkbox" id="notificacaoTarefas" checked><span class="switch"></span></label>
                    <label class="switch-item"><div><strong>Metas</strong><span>Receba avisos sobre seu progresso.</span></div><input type="checkbox" id="notificacaoMetas" checked><span class="switch"></span></label>
                    <label class="switch-item"><div><strong>Recompensas</strong><span>Saiba quando ganhar novos mimos.</span></div><input type="checkbox" id="notificacaoMimos" checked><span class="switch"></span></label>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone"><img class="img-icon" src="assets/img/icons/earth-icon.png" alt="Ícone de preferências"></div><div><h2>Preferências</h2><p>Configure opções gerais do sistema.</p></div></div>
                    <div class="opcao"><div><strong>Idioma</strong><p>Idioma utilizado pelo FlowUp.</p></div><select id="idioma"><option value="pt-br">Português (Brasil)</option><option value="en">English</option></select></div>
                    <div class="opcao"><div><strong>Formato de data</strong><p>Escolha como as datas serão exibidas.</p></div><select id="formatoData"><option value="br">DD/MM/AAAA</option><option value="us">MM/DD/AAAA</option></select></div>
                </section>

                <section class="card config-card">
                    <div class="titulo-card"><div class="icone"><img class="img-icon" src="assets/img/icons/lock-icon.png" alt="Ícone de segurança"></div><div><h2>Segurança</h2><p>Proteja sua conta no FlowUp.</p></div></div>
                    <div class="config-actions"><button type="button" class="btn-secundario" id="btnSenha">Alterar senha</button>
                    <button type="button" class="btn-sair" id="btnSair">Sair da conta</button></div>
                </section>
            </main>
        </div>
    </div>

    <div class="logout-modal" id="logoutModal" role="dialog" aria-modal="true" aria-labelledby="logoutModalTitle" hidden>
        <div class="logout-modal-backdrop" data-close-logout></div>
        <div class="logout-modal-card">
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-close-logout>&times;</button>
            <h2 id="logoutModalTitle">Sair da conta</h2>
            <p>Você tem certeza que deseja encerrar sua sessão?</p>
            <div class="logout-modal-actions">
                <button class="btn-secundario" type="button" data-close-logout>Continuar aqui</button>
                <a class="btn-sair" href="login/logout.php">Sair agora</a>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
