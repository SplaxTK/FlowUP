<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/functions/flowup.php';

$continuarSemLogin = ($_GET['continuar_sem_login'] ?? '') === '1';
$userId = flowup_user_id();
if ($userId === null && !$continuarSemLogin) {
    header('Location: login/login.php');
    exit;
}

$cookieLayoutNome = 'flowup_dashboard_layout_' . ($userId ?? 'visitante');

$dadosExistentes = json_decode($_COOKIE[$cookieLayoutNome] ?? '[]', true);
if (!is_array($dadosExistentes)) {
    $dadosExistentes = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_layout'])) {
    $layoutData = json_decode($_POST['layout'] ?? '', true);
    if (is_array($layoutData)) {
        $dadosAtualizados = array_merge($dadosExistentes, $layoutData);

        setcookie($cookieLayoutNome, json_encode($dadosAtualizados), [
            'expires' => time() + (86400 * 365),
            'path' => '/',
            'samesite' => 'Lax'
        ]);
        header('Content-Type: application/json');
        echo json_encode(['status' => 'ok']);
        exit;
    }
}

$blocos = [
    'planejamento' => '
        <article class="card large-card" data-preferencia-inicio="planejamento" data-card-id="planejamento">
            <div class="card-title">Planejamento</div>
        </article>',
    'calendario' => '
        <article class="card" data-preferencia-inicio="calendario" data-card-id="calendario">
            <div class="card-title">Calendário</div>
        </article>',
    'tarefas' => '
        <article class="card" data-preferencia-inicio="tarefas" data-card-id="tarefas">
            <div class="card-title">Lista de tarefas</div>
        </article>',
    'clima' => '
        <article class="card card_clima" data-preferencia-inicio="clima" data-card-id="clima">
            <div class="card-title">Clima</div>
            <p class="weather-status" data-weather-status></p>
            <div class="elfsight-app-f3057ea1-0a30-4ca5-9abd-1cdefccf7fb0" data-elfsight-app-lazy data-weather-widget hidden></div>
        </article>'
];

$ordemCardsGrid  = $dadosExistentes['cards-grid']  ?? ['planejamento', 'calendario'];
$ordemBottomGrid = $dadosExistentes['bottom-grid'] ?? ['tarefas', 'clima'];

$faltantes = array_diff(array_keys($blocos), array_merge($ordemCardsGrid, $ordemBottomGrid));
$ordemBottomGrid = array_merge($ordemBottomGrid, $faltantes);

require_once __DIR__ . '/includes/header.php';

$tarefasHoje = flowup_tarefas_hoje($pdo, $userId);
$metasHoje = flowup_metas_hoje($pdo, $userId);
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
                <a class="active" href="index.php"><img src="assets/img/icons/dashboard-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Dashboard</a>
                <a class="nav-link" href="tarefa.php"><img src="assets/img/icons/tasks-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Tarefas</a>
                <a class="nav-link" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
                <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
                <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>

        <div class="main-painel">
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
                <?php require __DIR__ . '/includes/profile_menu.php'; ?>
            </header>

            <button class="edit-dashbord-button" id="editDashboardButton" type="button" aria-label="Editar dashboard" aria-pressed="false" title="Editar dashboard">
                <img class="sidebar-icon" src="assets/img/icons/edit-icon.png" alt="" aria-hidden="true">
            </button>

            <main class="content">
                <article class="hero-card" data-preferencia-inicio="resumo">
                    <div>
                        <p class="small-label">Hoje</p>
                        <h2>Organize sua rotina com mais clareza</h2>
                    </div>
                    <div class="hero-stats">
                        <div class="stat-box"><?php echo $tarefasHoje; ?> tarefas</div>
                        <div class="stat-box"><?php echo $metasHoje; ?> metas</div>
                    </div>
                </article>

                <section class="cards-grid" id="cardsGrid">
                    <?php foreach ($ordemCardsGrid as $id): ?>
                        <?php if (isset($blocos[$id])) echo $blocos[$id]; ?>
                    <?php endforeach; ?>
                </section>

                <section class="bottom-grid" id="bottomGrid">
                    <?php foreach ($ordemBottomGrid as $id): ?>
                        <?php if (isset($blocos[$id])) echo $blocos[$id]; ?>
                    <?php endforeach; ?>
                </section>
            </main>
        </div>
    </div>

    <script src="assets/js/dashboard.js?v=<?php echo filemtime(__DIR__ . '/assets/js/dashboard.js'); ?>" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
