
<?php
require_once __DIR__ . '/config/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/functions/flowup.php';

$csrf = $_SESSION['calendar_csrf'] ??= bin2hex(random_bytes(32));

$calendarFlashError = $_SESSION['calendar_flash_error'] ?? '';
unset($_SESSION['calendar_flash_error']);

$userId = flowup_user_id();
$pontosUsuario = flowup_pontos($pdo, $userId);

require_once __DIR__ . '/includes/header.php';
?>

<body>
    <div class="app-shell">

        <aside class="sidebar" id="primarySidebar">

            <div class="sidebar-header">
                <div class="logo">
                    <span class="sidebar-logo-text">FlowUp</span>
                </div>

                <button
                    class="sidebar-toggle"
                    id="sidebarToggle"
                    type="button"
                    aria-label="Recolher menu lateral"
                    aria-expanded="true"
                    aria-controls="primarySidebar"
                >
                    <img
                        class="sidebar-toggle-icon sidebar-toggle-icon-expanded"
                        src="assets/img/icons/sidebar-left-icon.png"
                        alt=""
                        aria-hidden="true"
                    >

                    <img
                        class="sidebar-toggle-icon sidebar-toggle-icon-collapsed"
                        src="assets/img/icons/sidebar-right-icon.png"
                        alt=""
                        aria-hidden="true"
                    >
                </button>
            </div>

            <nav class="side-nav">
                <a class="nav-link" href="index.php">
                    <img src="assets/img/icons/dashboard-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Dashboard
                </a>

                <a class="nav-link" href="tarefa.php">
                    <img src="assets/img/icons/tasks-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Tarefas
                </a>

                <a class="active" href="calendario.php">
                    <img src="assets/img/icons/calendar-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Calendário
                </a>

                <a class="nav-link" href="personalizar.php">
                    <img src="assets/img/icons/customize-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Personalizar
                </a>

                <a class="nav-link" href="resgatar.php">
                    <img src="assets/img/icons/redeem-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Resgatar
                </a>

                <a class="nav-link" href="configuracoes.php">
                    <img src="assets/img/icons/settings-icon.png"
                         alt=""
                         aria-hidden="true"
                         class="sidebar-icon">
                    Configurações
                </a>
            </nav>

            <div class="calendar-sidebar-bottom">
                <div class="google-mini-status">
                    <span class="status-dot"></span>
                    <span id="google-sidebar-status">
                        Google Agenda desconectado
                    </span>
                </div>
            </div>

        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Seu espaço, seu tempo</p>
                    <h1>Calendário</h1>
                </div>

                <div class="calendar-top-actions">

                    <span class="google-status" id="google-status">
                        <span class="status-dot"></span>
                        <span>Verificando conexão...</span>
                    </span>

                    <button
                        class="calendar-button google-connect"
                        id="google-connect"
                        type="button"
                    >
                        ◉ <span>Conectar Google Agenda</span>
                    </button>

                    <button
                        class="calendar-button sync-now"
                        id="sync-now"
                        type="button"
                        hidden
                    >
                        ↻ <span>Sincronizar agora</span>
                    </button>

                </div>
                <div
                    class="topbar-actions points-actions"
                    data-preferencia-inicio="pontos"
                >
                    <span class="points-badge" aria-label="Pontos acumulados">
                        <?php
                        echo number_format(
                            (float) $pontosUsuario,
                            0,
                            ',',
                            '.'
                        );
                        ?> pts
                    </span>
                </div>
                <?php require __DIR__ . '/includes/profile_menu.php'; ?>

            </header>

            <main class="content">
                <section class="calendar-workspace">
                    <div class="calendar-page-actions">
                        <a
                            class="calendar-button primary-button"
                            href="#novo-evento"
                            data-new-event>
                            <img src=assets/img/icons/calendar-plus-icon.png>
                        </a>
                    </div>

                    <!-- BARRA DE FERRAMENTAS DO CALENDÁRIO -->
                    <div class="calendar-toolbar">

                        <div class="calendar-date-controls">

                            <button
                                class="calendar-icon-button"
                                id="calendar-prev"
                                type="button"
                                aria-label="Período anterior"
                            >
                                ‹
                            </button>

                            <button
                                class="calendar-icon-button"
                                id="calendar-next"
                                type="button"
                                aria-label="Próximo período"
                            >
                                ›
                            </button>

                            <button
                                class="calendar-button today-button"
                                id="calendar-today"
                                type="button"
                            >
                                Hoje
                            </button>

                            <h2 id="calendar-title"></h2>

                        </div>

                        <div class="calendar-tools">

                            <!-- PESQUISA DE EVENTOS -->
                            <label class="calendar-search">
                                <span aria-hidden="true">⌕</span>

                                <input
                                    id="event-search"
                                    type="search"
                                    placeholder="Buscar eventos..."
                                    aria-label="Buscar eventos"
                                >
                            </label>

                            <!-- FILTRO DE CATEGORIA -->
                            <select
                                id="category-filter"
                                class="calendar-select"
                                aria-label="Filtrar por categoria"
                            >
                                <option value="">
                                    Todas as categorias
                                </option>
                            </select>

                            <!-- VISUALIZAÇÃO -->
                            <div
                                class="calendar-views"
                                role="group"
                                aria-label="Visualização do calendário"
                            >
                                <button
                                    type="button"
                                    data-view="dayGridMonth"
                                >
                                    Mês
                                </button>

                                <button
                                    type="button"
                                    data-view="timeGridWeek"
                                >
                                    Semana
                                </button>

                                <button
                                    type="button"
                                    data-view="timeGridDay"
                                >
                                    Dia
                                </button>
                            </div>

                        </div>
                    </div>

                    <!-- LEGENDA E ESTADO DA SINCRONIZAÇÃO -->
                    <div class="calendar-legend">

                        <span>
                            <i class="legend-dot work"></i>
                            Trabalho
                        </span>

                        <span>
                            <i class="legend-dot personal"></i>
                            Pessoal
                        </span>

                        <span>
                            <i class="legend-dot health"></i>
                            Saúde
                        </span>

                        <span>
                            <i class="legend-dot other"></i>
                            Outros
                        </span>

                        <span id="sync-indicator" class="sync-indicator">
                            <i></i>
                            Agenda atualizada
                        </span>

                    </div>

                    <!-- CALENDÁRIO -->
                    <div class="calendar-card">
                        <div id="calendar"></div>
                    </div>

                </section>

            </main>

        </div>
    </div>

    <!-- MODAL DE CRIAÇÃO E EDIÇÃO DE EVENTOS -->
    <div
        class="calendar-modal-backdrop"
        id="event-modal"
        hidden
    >
        <section
            class="event-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="event-modal-title"
        >

            <header class="event-modal-header">

                <div>
                    <p
                        class="calendar-eyebrow"
                        id="modal-eyebrow"
                    >
                        ORGANIZE SEU TEMPO
                    </p>

                    <h2 id="event-modal-title">Novo evento</h2>
                </div>

                <button
                    class="modal-close"
                    type="button"
                    data-close-modal
                    aria-label="Fechar"
                >
                    ×
                </button>

            </header>

            <form id="event-form">

                <input type="hidden" name="id">
                <input type="hidden" name="location">

                <div class="event-form-field full-width">
                    <label for="event-title">Título</label>

                    <input
                        id="event-title"
                        name="title"
                        maxlength="180"
                        required
                        placeholder="Ex.: Reunião de planejamento"
                    >
                </div>

                <div class="event-form-field full-width">
                    <label for="event-description">Descrição</label>

                    <textarea
                        id="event-description"
                        name="description"
                        rows="2"
                        maxlength="5000"
                        placeholder="Adicione detalhes ao seu evento"
                    ></textarea>
                </div>

                <label class="all-day-toggle">
                    <input
                        type="checkbox"
                        name="all_day"
                        id="event-all-day"
                    >
                    Dia inteiro
                </label>

                <div class="event-form-field">
                    <label for="event-start">Início</label>

                    <input
                        id="event-start"
                        name="start"
                        type="datetime-local"
                        required
                    >
                </div>

                <div class="event-form-field" id="end-field">
                    <label for="event-end">Término</label>

                    <input
                        id="event-end"
                        name="end"
                        type="datetime-local"
                        required
                    >
                </div>

                <div class="event-form-field">
                    <label for="event-time">Horário</label>

                    <input
                        id="event-time"
                        name="time"
                        type="time"
                        step="60"
                    >
                </div>

                <div class="event-form-field">
                    <label for="event-category">Categoria</label>

                    <select
                        id="event-category"
                        name="category_id"
                    ></select>
                </div>

                <div class="event-form-field">
                    <label for="event-color">Cor</label>

                    <select id="event-color" name="color">
                        <option value="#5688ff">Azul</option>
                        <option value="#aa83ff">Roxo</option>
                        <option value="#42c7a5">Verde</option>
                        <option value="#f5aa53">Laranja</option>
                        <option value="#fa7188">Rosa</option>
                    </select>
                </div>

                <div class="event-form-field">
                    <label for="event-guests">Convidados</label>

                    <input
                        id="event-guests"
                        name="guests"
                        maxlength="1000"
                        placeholder="email@exemplo.com, ..."
                    >
                </div>

                <div class="event-form-field">
                    <label for="event-repeat">Repetição</label>

                    <select id="event-repeat" name="repeat_rule">
                        <option value="">Não se repete</option>
                        <option value="DAILY">Todos os dias</option>
                        <option value="WEEKLY">Toda semana</option>
                        <option value="MONTHLY">Todo mês</option>
                        <option value="YEARLY">Todo ano</option>
                    </select>
                </div>

                <div class="event-form-field">
                    <label for="event-reminder">Lembrete</label>

                    <select
                        id="event-reminder"
                        name="reminder_minutes"
                    >
                        <option value="">Sem lembrete</option>
                        <option value="5">5 minutos antes</option>
                        <option value="10">10 minutos antes</option>
                        <option value="30">30 minutos antes</option>
                        <option value="60">1 hora antes</option>
                        <option value="1440">1 dia antes</option>
                    </select>
                </div>

                <div class="event-form-field full-width">
                    <label for="event-notes">Observações</label>

                    <textarea
                        id="event-notes"
                        name="notes"
                        rows="2"
                        maxlength="5000"
                        placeholder="Notas privadas"
                    ></textarea>
                </div>

                <p
                    class="event-source-note"
                    id="event-source-note"
                    hidden
                ></p>

                <p
                    class="calendar-form-error"
                    id="event-error"
                    role="alert"
                    hidden
                ></p>

                <footer class="event-modal-footer">

                    <div>
                        <button
                            type="button"
                            class="text-danger"
                            id="delete-event"
                            hidden
                        >
                            Excluir
                        </button>

                        <button
                            type="button"
                            class="text-secondary"
                            id="duplicate-event"
                            hidden
                        >
                            Duplicar
                        </button>
                    </div>

                    <div>
                        <button
                            type="button"
                            class="calendar-button"
                            data-close-modal
                        >
                            Cancelar
                        </button>

                        <button
                            type="submit"
                            class="calendar-button primary-button"
                            id="save-event"
                        >
                            Salvar evento
                        </button>
                    </div>

                </footer>

            </form>
        </section>
    </div>

    <!-- CONFIGURAÇÃO DA API DO CALENDÁRIO -->
    <script>
        window.FLOWUP_CALENDAR = {
            api: 'api/calendar.php',
            csrf: <?php echo json_encode($csrf); ?>,
            oauthConfigured: <?php
                echo json_encode(
                    (bool) (getenv('GOOGLE_CLIENT_ID') ?: '')
                );
            ?>,
            flashError: <?php
                echo json_encode(
                    $calendarFlashError,
                    JSON_HEX_TAG
                    | JSON_HEX_AMP
                    | JSON_HEX_APOS
                    | JSON_HEX_QUOT
                );
            ?>
        };
    </script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>

    <script src="assets/js/calendar.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
