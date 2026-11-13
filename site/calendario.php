<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/functions/flowup.php';

$csrf = $_SESSION['calendar_csrf'] ??= bin2hex(random_bytes(32));

$calendarFlashError = $_SESSION['calendar_flash_error'] ?? '';
unset($_SESSION['calendar_flash_error']);

$userId = flowup_user_id();
$pontosUsuario = flowup_pontos($pdo, $userId);

require_once __DIR__ . '/includes/header.php';
?>

<body class="calendar-page">
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

        </aside>

        <div class="main-panel">
            <header class="topbar">
                <div>
                    <p class="small-label">Seu espaço, seu tempo</p>
                    <h1>Calendário</h1>
                </div>

                <div class="calendar-top-actions">

                    <div class="calendar-actions-menu">
                        <button
                            class="calendar-button calendar-actions-toggle"
                            type="button"
                            id="calendar-actions-toggle"
                            aria-label="Ações do calendário"
                            aria-haspopup="true"
                            aria-expanded="false"
                            aria-controls="calendar-actions-dropdown"
                        >⋯</button>
                        <div class="calendar-dropdown calendar-actions-dropdown" id="calendar-actions-dropdown" role="menu" hidden>
                            <button class="calendar-action-button" type="button" id="import-calendar" role="menuitem">
                                <img class="calendar-action-icon calendar-action-icon-light" src="assets/img/icons/import-icon.png" alt="" aria-hidden="true">
                                <img class="calendar-action-icon calendar-action-icon-dark" src="assets/img/icons/import-icon2.png" alt="" aria-hidden="true">
                                <span>Importar</span>
                            </button>
                            <button class="calendar-action-button" type="button" id="export-calendar" role="menuitem">
                                <img class="calendar-action-icon calendar-action-icon-light" src="assets/img/icons/export-icon.png" alt="" aria-hidden="true">
                                <img class="calendar-action-icon calendar-action-icon-dark" src="assets/img/icons/export-icon2.png" alt="" aria-hidden="true">
                                <span>Exportar</span>
                            </button>
                        </div>
                    </div>
                    <input type="file" id="calendar-import-file" accept=".zip,.ics,application/zip,text/calendar" hidden>

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
                        <div class="calendar-create-menu">
                            <button
                                class="calendar-button primary-button calendar-create-event"
                                id="create-menu-toggle"
                                type="button"
                                aria-haspopup="true"
                                aria-expanded="false"
                                aria-controls="create-menu"
                            >
                                <span aria-hidden="true">＋</span>
                                <span>Criar</span>
                                <span aria-hidden="true">▾</span>
                            </button>
                            <div class="calendar-dropdown create-dropdown" id="create-menu" role="menu" hidden>
                                <button type="button" role="menuitem" data-create-type="event">Evento</button>
                                <button type="button" role="menuitem" data-create-type="task">Tarefa</button>
                                <button type="button" role="menuitem" data-create-type="appointment">Agendamento de horários</button>
                            </div>
                        </div>
                    </div>

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

                            <div class="calendar-view-menu">
                                <button
                                    class="calendar-button view-menu-toggle"
                                    id="view-menu-toggle"
                                    type="button"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-controls="view-menu"
                                >
                                    <span id="current-view-label">Semana</span>
                                    <span aria-hidden="true">▾</span>
                                </button>
                                <div class="calendar-dropdown view-dropdown" id="view-menu" hidden>
                                    <div class="view-options" role="group" aria-label="Visualização do calendário">
                                        <button type="button" data-view="timeGridDay">Dia <kbd>D</kbd></button>
                                        <button type="button" data-view="timeGridWeek">Semana <kbd>W</kbd></button>
                                        <button type="button" data-view="dayGridMonth">Mês <kbd>M</kbd></button>
                                        <button type="button" data-view="multiMonthYear">Ano <kbd>Y</kbd></button>
                                        <button type="button" data-view="listWeek">Programação <kbd>A</kbd></button>
                                        <button type="button" data-view="timeGridFourDay">4 dias <kbd>X</kbd></button>
                                    </div>
                                    <div class="view-filters">
                                        <label><input id="show-weekends" type="checkbox" checked> Mostrar fins de semana</label>
                                        <label><input id="show-declined" type="checkbox" checked> Mostrar eventos recusados</label>
                                        <label><input id="show-completed-tasks" type="checkbox" checked> Mostrar tarefas concluídas</label>
                                    </div>
                                </div>
                            </div>

                        </div>
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
                <input type="hidden" name="event_type" value="event">

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

                <label class="all-day-toggle task-completed-toggle" id="task-completed-field" hidden>
                    <input type="checkbox" name="is_completed" id="event-completed">
                    Tarefa concluída
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
            api: <?php echo json_encode($siteUrl . '/api/calendar.php', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
            csrf: <?php echo json_encode($csrf); ?>,
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

    <script src="assets/js/calendar.js?v=<?php echo filemtime(__DIR__ . '/assets/js/calendar.js'); ?>" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
