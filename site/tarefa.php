<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/functions/flowup.php';

$userId = flowup_user_id();
$message = $_SESSION['form_message'] ?? '';
$messageType = $_SESSION['form_message_type'] ?? '';
$tiposTarefa = ['diaria', 'semanal', 'mensal', 'meta'];

unset($_SESSION['form_message'], $_SESSION['form_message_type']);

function flowup_task_description_is_valid(string $descricao): bool
{
    if (trim($descricao) === '' || preg_match_all('/./us', $descricao, $matches) === false) {
        return false;
    }

    return count($matches[0]) <= 180;
}

if ($userId !== null) {
    flowup_prepare_schema($pdo);
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
            $_SESSION['form_message'] = 'Sua sessão expirou. Atualize a página e tente novamente.';
            $_SESSION['form_message_type'] = 'error';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } elseif (isset($_POST['adicionar'])) {
            $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';
            $tipo = is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : '';
            $pontos = filter_var($_POST['pontos'] ?? null, FILTER_VALIDATE_INT);

            if (!flowup_task_description_is_valid($descricao) || $pontos === false || $pontos < 1 || $pontos > 10000 || !in_array($tipo, $tiposTarefa, true)) {
                $_SESSION['form_message'] = 'Preencha a tarefa, o tipo e uma pontuação válida.';
                $_SESSION['form_message_type'] = 'error';
            } else {
                if (flowup_table_has_column($pdo, 'novas_tarefas', 'user_id')) {
                    $stmt = $pdo->prepare(
                        'INSERT INTO novas_tarefas (user_id, descricao, tipo, qtd_pon_ganho)
                         VALUES (:user_id, :descricao, :tipo, :pontos)'
                    );
                    $stmt->execute(['user_id' => $userId, 'descricao' => $descricao, 'tipo' => $tipo, 'pontos' => $pontos]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO novas_tarefas (email, descricao, tipo, qtd_pon_ganho)
                         SELECT email, :descricao, :tipo, :pontos FROM usuarios WHERE id = :user_id'
                    );
                    $stmt->execute(['descricao' => $descricao, 'tipo' => $tipo, 'pontos' => $pontos, 'user_id' => $userId]);
                }
                
                $_SESSION['form_message'] = 'Tarefa adicionada com sucesso!';
                $_SESSION['form_message_type'] = 'success';
            }
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } elseif (isset($_POST['editar_tarefa'])) {
            $idTarefa = filter_var($_POST['id_tarefa'] ?? null, FILTER_VALIDATE_INT);
            $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';
            $tipo = is_string($_POST['tipo'] ?? null) ? $_POST['tipo'] : '';
            $pontos = filter_var($_POST['pontos'] ?? null, FILTER_VALIDATE_INT);

            if ($idTarefa === false || $idTarefa < 1 || !flowup_task_description_is_valid($descricao) || $pontos === false || $pontos < 1 || $pontos > 10000 || !in_array($tipo, $tiposTarefa, true)) {
                $_SESSION['form_message'] = 'Confira a tarefa, a frequência e a pontuação informadas.';
                $_SESSION['form_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'SELECT t.id_tarefas
                     FROM novas_tarefas AS t
                     WHERE t.id_tarefas = :id
                       AND ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
                       AND t.status <> \'inativa\''
                );
                $stmt->execute(['id' => $idTarefa, 'user_id' => $userId]);

                if (!$stmt->fetchColumn()) {
                    $_SESSION['form_message'] = 'Tarefa não encontrada.';
                    $_SESSION['form_message_type'] = 'error';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE novas_tarefas AS t
                             SET descricao = :descricao, tipo = :tipo, qtd_pon_ganho = :pontos
                         WHERE t.id_tarefas = :id
                           AND ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
                           AND t.status <> \'inativa\''
                    );
                    $stmt->execute([
                        'descricao' => $descricao,
                        'tipo' => $tipo,
                        'pontos' => $pontos,
                        'id' => $idTarefa,
                        'user_id' => $userId,
                    ]);
                    $_SESSION['form_message'] = 'Tarefa atualizada com sucesso!';
                    $_SESSION['form_message_type'] = 'success';
                }
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } elseif (isset($_POST['excluir_tarefa'])) {
            $idTarefa = filter_var($_POST['id_tarefa'] ?? null, FILTER_VALIDATE_INT);

            if ($idTarefa === false || $idTarefa < 1) {
                $_SESSION['form_message'] = 'Não foi possível identificar a tarefa.';
                $_SESSION['form_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'DELETE t
                     FROM novas_tarefas AS t
                     WHERE t.id_tarefas = :id
                       AND ' . flowup_user_filter($pdo, 'novas_tarefas', 't')
                );
                $stmt->execute(['id' => $idTarefa, 'user_id' => $userId]);
                $tarefaExcluida = $stmt->rowCount() > 0;
                $_SESSION['form_message'] = $tarefaExcluida
                    ? 'Tarefa excluída com sucesso.'
                    : 'Tarefa não encontrada.';
                $_SESSION['form_message_type'] = $tarefaExcluida ? 'success' : 'error';
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } elseif (isset($_POST['concluir'])) {
            $msgResult = flowup_concluir($pdo, $userId, (int) $_POST['id_tarefa']);
            
            $_SESSION['form_message'] = $msgResult;
            $_SESSION['form_message_type'] = 'success';

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    }
    $stmt = $pdo->prepare(
        'SELECT t.*
         FROM novas_tarefas AS t
         WHERE ' . flowup_user_filter($pdo, 'novas_tarefas', 't') . '
           AND t.status <> \'inativa\'
         ORDER BY FIELD(t.tipo, \'diaria\', \'semanal\', \'mensal\', \'meta\'), t.data_criacao DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $tarefas = $stmt->fetchAll();
    $concluidas = flowup_concluidas($pdo, $userId, $tarefas);
    $pontosUsuario = flowup_pontos($pdo, $userId);
} else {
    $tarefas = [];
    $concluidas = [];
    $pontosUsuario = 0;
    $csrfToken = '';
    
    if (empty($message)) {
        $message = 'Entre na sua conta para criar e concluir tarefas.';
        $messageType = 'error';
    }
}
$grupos = ['diaria' => 'Diárias', 'semanal' => 'Semanais', 'mensal' => 'Mensais', 'meta' => 'Metas'];
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
            <a class="active" href="tarefa.php"><img src="assets/img/icons/tasks-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Tarefas</a>
            <a class="nav-link" href="calendario.php"><img src="assets/img/icons/calendar-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Calendário</a>
            <a class="nav-link" href="personalizar.php"><img src="assets/img/icons/customize-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Personalizar</a>
            <a class="nav-link" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
            <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
        </nav>
    </aside>
    <div class="main-painel">
        <header class="topbar">
            <div><p class="small-label">Organização</p><h1>Suas tarefas</h1></div>
            <div class="topbar-actions points-actions"><span class="points-badge" aria-label="Pontos acumulados"><?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts</span></div>

            <?php require __DIR__ . '/includes/profile_menu.php'; ?>
                
        </header>
        <main class="content task-page">
            <?php if ($message): ?><p class="form-message <?php echo $messageType; ?>"><?php echo htmlspecialchars($message); ?></p><?php endif; ?>
            <section class="task-layout">
                <div class="task-list-column">
                    <?php foreach ($grupos as $tipo => $titulo): ?>
                        <section class="card task-group">
                            <div class="task-group-heading"><div><p class="small-label"><?php echo $tipo === 'diaria' ? 'Recomeça hoje' : 'Seu ritmo'; ?></p><h2><?php echo $titulo; ?></h2></div><span class="task-count"><?php echo count(array_filter($tarefas, fn($t) => $t['tipo'] === $tipo)); ?></span></div>
                            <?php $temTarefa = false; foreach ($tarefas as $tarefa): if ($tarefa['tipo'] !== $tipo) continue; $temTarefa = true; $feito = isset($concluidas[(int) $tarefa['id_tarefas']]); ?>
                                <div class="task-row <?php echo $feito ? 'is-complete' : ''; ?>">
                                    <div><strong><?php echo htmlspecialchars($tarefa['descricao']); ?></strong><span>+<?php echo (int) $tarefa['qtd_pon_ganho']; ?> pontos</span></div>
                                    <div class="task-actions">
                                        <button class="btn btn-secondary task-edit-button" type="button"
                                            data-tarefa-id="<?php echo (int) $tarefa['id_tarefas']; ?>"
                                            data-descricao="<?php echo htmlspecialchars($tarefa['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-tipo="<?php echo htmlspecialchars($tarefa['tipo'], ENT_QUOTES, 'UTF-8'); ?>"
                                            data-pontos="<?php echo (int) $tarefa['qtd_pon_ganho']; ?>">Editar</button>
                                        <?php if ($feito): ?>
                                            <span class="completed-label">Concluída</span>
                                        <?php else: ?>
                                            <form method="post">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="id_tarefa" value="<?php echo (int) $tarefa['id_tarefas']; ?>">
                                                <button class="complete-button" name="concluir" type="submit">Completar</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; if (!$temTarefa): ?><p class="empty-state">Nenhuma tarefa nesta categoria ainda.</p><?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>
<?php if ($userId !== null): ?>
    <div class="logout-modal task-edit-modal" id="taskEditModal" role="dialog" aria-modal="true" aria-labelledby="taskEditTitle" hidden>
        <div class="logout-modal-backdrop" data-close-task-edit></div>
        <div class="logout-modal-card task-edit-card">
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-close-task-edit>&times;</button>
            <p class="small-label">Atualize seu objetivo</p>
            <h2 id="taskEditTitle">Editar tarefa</h2>
            <form method="post" id="taskEditForm" class="task-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id_tarefa" id="editTaskId">
                <label for="editTaskDescription">Nome da tarefa</label>
                <input id="editTaskDescription" name="descricao" required maxlength="180">
                <label for="editTaskType">Frequência</label>
                <select id="editTaskType" name="tipo" required>
                    <option value="diaria">Diária</option>
                    <option value="semanal">Semanal</option>
                    <option value="mensal">Mensal</option>
                    <option value="meta">Meta</option>
                </select>
                <label for="editTaskPoints">Pontos</label>
                <input id="editTaskPoints" name="pontos" type="number" min="1" max="10000" required>
                <div class="task-edit-actions">
                    <button class="task-delete-button" type="button" id="deleteTaskButton">Excluir</button>
                    <button class="btn-secundario" type="button" data-close-task-edit>Cancelar</button>
                    <button class="btn form-submit" type="submit" name="editar_tarefa">Salvar alterações</button>
                </div>
            </form>
            <form method="post" id="taskDeleteForm" hidden>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="id_tarefa" id="deleteTaskId">
                <input type="hidden" name="excluir_tarefa" value="1">
                <button type="submit">Excluir</button>
            </form>
        </div>
    </div>
    <div class="logout-modal task-confirm-modal" id="taskConfirmModal" role="alertdialog" aria-modal="true" aria-labelledby="taskConfirmTitle" aria-describedby="taskConfirmMessage" hidden>
        <div class="logout-modal-backdrop"></div>
        <div class="logout-modal-card">
            <h2 id="taskConfirmTitle"></h2>
            <p id="taskConfirmMessage"></p>
            <div class="logout-modal-actions">
                <button class="btn-secundario" type="button" id="taskConfirmCancel"></button>
                <button class="btn-sair" type="button" id="taskConfirmAccept"></button>
            </div>
        </div>
    </div>
    <script src="assets/js/tarefas.js" defer></script>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
