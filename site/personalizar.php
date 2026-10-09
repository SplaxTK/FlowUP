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
                <a class="active" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
                <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Personalizar</p>
                    <h1>Personalize seu espaço</h1>
                </div>
                    <div class="topbar-actions points-actions"><span class="points-badge" aria-label="Pontos acumulados"><?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts</span></div>
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

            <main class="content personalizar-content">
                <form method="post">
                    <section class="card personalizar-card">
                        <div class="titulo-card">
                            <div class="icone">🎨</div>
                            <div><h2>Aparência</h2><p>Escolha como o FlowUp será exibido.</p></div>
                        </div>
                        <div class="opcao">
                            <div><h3>Tema</h3><p>Escolha entre o tema claro ou escuro.</p></div>
                            <select name="tema" id="tema"><option value="claro">Claro</option><option value="escuro">Escuro</option></select>
                        </div>
                        <div class="opcao">
                            <div><h3>Cor principal</h3><p>Escolha a cor dos principais elementos.</p></div>
                            <div class="cores">
                                <label title="Azul"><input type="radio" name="cor" value="azul" aria-label="Azul"><span class="cor azul"></span></label>
                                <label title="Índigo"><input type="radio" name="cor" value="indigo" aria-label="Índigo"><span class="cor indigo"></span></label>
                                <label title="Roxo"><input type="radio" name="cor" value="roxo" aria-label="Roxo"><span class="cor roxo"></span></label>
                                <label title="Fúcsia"><input type="radio" name="cor" value="fucsia" aria-label="Fúcsia"><span class="cor fucsia"></span></label>
                                <label title="Pink"><input type="radio" name="cor" value="pink" aria-label="Pink"><span class="cor pink"></span></label>
                                <label title="Rosa"><input type="radio" name="cor" value="rosa" aria-label="Rosa"><span class="cor rosa"></span></label>
                                <label title="Vermelho"><input type="radio" name="cor" value="vermelho" aria-label="Vermelho"><span class="cor vermelho"></span></label>
                                <label title="Laranja"><input type="radio" name="cor" value="laranja" aria-label="Laranja"><span class="cor laranja"></span></label>
                                <label title="Âmbar"><input type="radio" name="cor" value="ambar" aria-label="Âmbar"><span class="cor ambar"></span></label>
                                <label title="Amarelo"><input type="radio" name="cor" value="amarelo" aria-label="Amarelo"><span class="cor amarelo"></span></label>
                                <label title="Lima"><input type="radio" name="cor" value="lima" aria-label="Lima"><span class="cor lima"></span></label>
                                <label title="Verde"><input type="radio" name="cor" value="verde" aria-label="Verde"><span class="cor verde"></span></label>
                                <label title="Esmeralda"><input type="radio" name="cor" value="esmeralda" aria-label="Esmeralda"><span class="cor esmeralda"></span></label>
                                <label title="Turquesa"><input type="radio" name="cor" value="turquesa" aria-label="Turquesa"><span class="cor turquesa"></span></label>
                                <label title="Ciano"><input type="radio" name="cor" value="ciano" aria-label="Ciano"><span class="cor ciano"></span></label>
                                <label title="Azul-céu"><input type="radio" name="cor" value="ceu" aria-label="Azul-céu"><span class="cor ceu"></span></label>
                                <label title="Branco"><input type="radio" name="cor" value="branco" aria-label="Branco"><span class="cor branco"></span></label>
                                <label title="Prata"><input type="radio" name="cor" value="prata" aria-label="Prata"><span class="cor prata"></span></label>
                                <label title="Grafite"><input type="radio" name="cor" value="grafite" aria-label="Grafite"><span class="cor grafite"></span></label>
                                <label title="Preto"><input type="radio" name="cor" value="preto" aria-label="Preto"><span class="cor preto"></span></label>
                            </div>
                        </div>
                        <div class="opcao">
                            <div><h3>Espaçamento</h3><p>Defina o espaço entre os elementos.</p></div>
                            <select name="densidade"><option value="compacta">Compacta</option><option value="normal" selected>Normal</option><option value="espacosa">Espaçosa</option></select>
                        </div>
                        <div class="opcao">
                            <div><h3>Tamanho da fonte</h3><p>Ajuste o tamanho dos textos.</p></div>
                            <select name="fonte">
                                <option value="pequena">Pequena</option>
                                <option value="media" selected>Média</option>
                                <option value="grande">Grande</option>
                            </select>
                        </div>
                        <label class="switch-item">
                            <div><strong>Texto em negrito</strong><span>Aumentar o destaque dos textos.</span></div>
                            <input type="checkbox" name="negrito">
                            <span class="switch"></span>
                        </label>
                    </section>

                    <section class="card personalizar-card">
                        <div class="titulo-card">
                            <div class="icone">🏠</div>
                            <div><h2>Página inicial</h2><p>Escolha quais blocos deseja visualizar no painel.</p></div>
                        </div>
                        <label class="switch-item"><div><strong>Calendário</strong><span>Mostrar o calendário no painel.</span></div><input type="checkbox" name="calendario" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Lista de tarefas</strong><span>Mostrar suas tarefas recentes.</span></div><input type="checkbox" name="tarefas" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Resumo do dia</strong><span>Mostrar seu progresso diário.</span></div><input type="checkbox" name="resumo" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Planejamento</strong><span>Mostrar o bloco de planejamento.</span></div><input type="checkbox" name="planejamento" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Clima</strong><span>Mostrar o widget de clima.</span></div><input type="checkbox" name="clima" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Pontos</strong><span>Mostrar seus pontos no painel.</span></div><input type="checkbox" name="pontos" checked><span class="switch"></span></label>
                    </section>

                    <section class="card personalizar-card">
                        <div class="titulo-card">
                            <div class="icone">🍪</div>
                            <div><h2>Privacidade e cookies</h2><p>Revise sua escolha de cookies e armazenamento local.</p></div>
                        </div>
                        <div class="opcao cookie-preferences">
                            <div>
                                <h3>Gerenciar cookies</h3>
                                <p>Altere sua decisão sobre salvar personalizações e carregar o widget de clima.</p>
                                <a class="form-link" href="politica/politica_cookies.php">Política de Cookies</a>
                            </div>
                            <button class="btn-gerenciar-cookies" type="button" id="manageCookies">Gerenciar cookies</button>
                        </div>
                    </section>

                    <div class="acoes">
                        <button type="button" class="btn-restaurar" id="btnCancelar">
                            <span aria-hidden="true">↻</span> Restaurar padrões
                        </button>
                        <button type="submit" class="btn-salvar">Salvar alterações</button>
                    </div>
                    <p class="preferences-message" id="preferencesMessage" role="status" aria-live="polite" hidden></p>
                </form>
            </main>
        </div>
    </div>
    <div class="logout-modal restore-modal" id="restoreModal" role="dialog" aria-modal="true" aria-labelledby="restoreModalTitle" hidden>
        <div class="logout-modal-backdrop" data-close-restore></div>
        <div class="logout-modal-card">
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-close-restore>&times;</button>
            <div class="logout-modal-icon" aria-hidden="true">↻</div>
            <h2 id="restoreModalTitle">Restaurar personalizações?</h2>
            <p>Suas opções voltarão aos padrões. Essa alteração não pode ser desfeita.</p>
            <div class="logout-modal-actions">
                <button class="btn-secundario" type="button" data-close-restore>Cancelar</button>
                <button class="btn-restaurar-confirm" type="button" id="confirmRestore">Restaurar padrões</button>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
