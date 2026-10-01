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
                <a class="nav-link" href="calendario.php">Calendário</a>
                <a class="active" href="personalizar.php">Personalizar</a>
                <a class="nav-link" href="resgatar.php">Resgatar</a>
                <a class="nav-link" href="configuracoes.php">Configurações</a>
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
                <form action="functions/salvar_personalizacao.php" method="post">
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
                                <label><input type="radio" name="cor" value="azul" checked><span class="cor azul" aria-label="Azul"></span></label>
                                <label><input type="radio" name="cor" value="roxo"><span class="cor roxo" aria-label="Roxo"></span></label>
                                <label><input type="radio" name="cor" value="verde"><span class="cor verde" aria-label="Verde"></span></label>
                                <label><input type="radio" name="cor" value="laranja"><span class="cor laranja" aria-label="Laranja"></span></label>
                            </div>
                        </div>
                        <div class="opcao">
                            <div><h3>Espaçamento</h3><p>Defina o espaço entre os elementos.</p></div>
                            <select name="densidade"><option value="compacta">Compacta</option><option value="normal" selected>Normal</option><option value="espacosa">Espaçosa</option></select>
                        </div>
                    </section>

                    <section class="card personalizar-card">
                        <div class="titulo-card">
                            <div class="icone">🏠</div>
                            <div><h2>Página inicial</h2><p>Escolha o que deseja visualizar no painel.</p></div>
                        </div>
                        <label class="switch-item"><div><strong>Calendário</strong><span>Mostrar o calendário no painel.</span></div><input type="checkbox" name="calendario" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Lista de tarefas</strong><span>Mostrar suas tarefas recentes.</span></div><input type="checkbox" name="tarefas" checked><span class="switch"></span></label>
                        <label class="switch-item"><div><strong>Resumo do dia</strong><span>Mostrar seu progresso diário.</span></div><input type="checkbox" name="resumo" checked><span class="switch"></span></label>
                    </section>

                    <section class="card personalizar-card">
                        <div class="titulo-card">
                            <div class="icone">👀</div>
                            <div><h2>Pré-visualização</h2><p>Veja uma demonstração da aparência escolhida.</p></div>
                        </div>
                        <div class="preview">
                            <div class="preview-menu"><strong>FlowUp</strong><span>Início</span><span>Tarefas</span><span>Calendário</span></div>
                            <div class="preview-conteudo"><div class="preview-header"><div></div><div></div></div><div class="preview-cards"><div></div><div></div><div></div></div></div>
                        </div>
                    </section>

                    <div class="acoes"><button type="button" class="btn-cancelar" id="btnCancelar">Restaurar</button><button type="submit" class="btn-salvar">Salvar alterações</button></div>
                </form>
            </main>
        </div>
    </div>
</body>
</html>
