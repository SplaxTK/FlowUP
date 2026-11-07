<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/helpers/auth_middleware.php';
require_once __DIR__ . '/functions/flowup.php';

$userId = flowup_user_id();
$message = $_SESSION['reward_message'] ?? '';
$messageType = $_SESSION['reward_message_type'] ?? '';
unset($_SESSION['reward_message'], $_SESSION['reward_message_type']);

function flowup_reward_description_is_valid(string $descricao): bool
{
    if (trim($descricao) === '' || preg_match_all('/./us', $descricao, $matches) === false) {
        return false;
    }

    return count($matches[0]) <= 180;
}

if ($userId !== null) {
    flowup_prepare_schema($pdo);
    flowup_prepare_rewards_schema($pdo);

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    $csrfToken = $_SESSION['csrf_token'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!is_string($_POST['csrf_token'] ?? null) || !hash_equals($csrfToken, $_POST['csrf_token'])) {
            $_SESSION['reward_message'] = 'Sua sessão expirou. Atualize a página e tente novamente.';
            $_SESSION['reward_message_type'] = 'error';
        } elseif (isset($_POST['adicionar_recompensa'])) {
            $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';
            $pontos = filter_var($_POST['pontos'] ?? null, FILTER_VALIDATE_INT);

            if (!flowup_reward_description_is_valid($descricao) || $pontos === false || $pontos < 1 || $pontos > 100000) {
                $_SESSION['reward_message'] = 'Informe uma recompensa e uma quantidade válida de pontos.';
                $_SESSION['reward_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO recompensas (user_id, descricao, qtd_pon_neces)
                     VALUES (:user_id, :descricao, :pontos)'
                );
                $stmt->execute(['user_id' => $userId, 'descricao' => $descricao, 'pontos' => $pontos]);
                $_SESSION['reward_message'] = 'Recompensa adicionada.';
                $_SESSION['reward_message_type'] = 'success';
            }
        } elseif (isset($_POST['editar_recompensa'])) {
            $idRecompensa = filter_var($_POST['id_recompensa'] ?? null, FILTER_VALIDATE_INT);
            $descricao = is_string($_POST['descricao'] ?? null) ? trim($_POST['descricao']) : '';
            $pontos = filter_var($_POST['pontos'] ?? null, FILTER_VALIDATE_INT);

            if ($idRecompensa === false || $idRecompensa < 1 || !flowup_reward_description_is_valid($descricao) || $pontos === false || $pontos < 1 || $pontos > 100000) {
                $_SESSION['reward_message'] = 'Confira a recompensa e a quantidade de pontos informadas.';
                $_SESSION['reward_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'SELECT id_recompensa
                     FROM recompensas
                     WHERE id_recompensa = :id
                       AND user_id = :user_id
                       AND status = \'disponivel\''
                );
                $stmt->execute(['id' => $idRecompensa, 'user_id' => $userId]);

                if (!$stmt->fetchColumn()) {
                    $_SESSION['reward_message'] = 'Recompensa não encontrada.';
                    $_SESSION['reward_message_type'] = 'error';
                } else {
                    $stmt = $pdo->prepare(
                        'UPDATE recompensas
                         SET descricao = :descricao, qtd_pon_neces = :pontos
                         WHERE id_recompensa = :id
                           AND user_id = :user_id
                           AND status = \'disponivel\''
                    );
                    $stmt->execute([
                        'descricao' => $descricao,
                        'pontos' => $pontos,
                        'id' => $idRecompensa,
                        'user_id' => $userId,
                    ]);
                    $_SESSION['reward_message'] = 'Recompensa atualizada com sucesso.';
                    $_SESSION['reward_message_type'] = 'success';
                }
            }
        } elseif (isset($_POST['excluir_recompensa'])) {
            $idRecompensa = filter_var($_POST['id_recompensa'] ?? null, FILTER_VALIDATE_INT);

            if ($idRecompensa === false || $idRecompensa < 1) {
                $_SESSION['reward_message'] = 'Não foi possível identificar a recompensa.';
                $_SESSION['reward_message_type'] = 'error';
            } else {
                $stmt = $pdo->prepare(
                    'DELETE FROM recompensas
                     WHERE id_recompensa = :id
                       AND user_id = :user_id
                       AND status = \'disponivel\''
                );
                $stmt->execute(['id' => $idRecompensa, 'user_id' => $userId]);
                $recompensaExcluida = $stmt->rowCount() > 0;
                $_SESSION['reward_message'] = $recompensaExcluida
                    ? 'Recompensa excluída com sucesso.'
                    : 'Recompensa não encontrada.';
                $_SESSION['reward_message_type'] = $recompensaExcluida ? 'success' : 'error';
            }
        } elseif (isset($_POST['resgatar'])) {
            $idRecompensa = filter_var($_POST['id_recompensa'] ?? null, FILTER_VALIDATE_INT);
            $resgateMessage = $idRecompensa !== false && $idRecompensa > 0
                ? flowup_resgatar($pdo, $userId, $idRecompensa)
                : 'Não foi possível identificar a recompensa.';
            $_SESSION['reward_message'] = $resgateMessage;
            $_SESSION['reward_message_type'] = str_contains($resgateMessage, 'sucesso') ? 'success' : 'error';
        } else {
            $_SESSION['reward_message'] = 'Ação inválida.';
            $_SESSION['reward_message_type'] = 'error';
        }

        header('Location: resgatar.php');
        exit;
    }

    $stmt = $pdo->prepare(
        'SELECT *
         FROM recompensas
         WHERE status = \'disponivel\'
           AND (user_id IS NULL OR user_id = :user_id)
         ORDER BY qtd_pon_neces ASC, data_criacao DESC'
    );
    $stmt->execute(['user_id' => $userId]);
    $rewards = $stmt->fetchAll();
    $pontosUsuario = flowup_pontos($pdo, $userId);
} else {
    $rewards = [];
    $pontosUsuario = 0;
    $csrfToken = '';
    if (empty($message)) {
        $message = 'Entre na sua conta para resgatar recompensas.';
        $messageType = 'error';
    }
}

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
                <a class="active" href="resgatar.php"><img src="assets/img/icons/redeem-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Resgatar</a>
                <a class="nav-link" href="configuracoes.php"><img src="assets/img/icons/settings-icon.png" alt="" aria-hidden="true" class="sidebar-icon">Configurações</a>
            </nav>
        </aside>
        <div class="main-painel">
            <header class="topbar">
                <div><p class="small-label">Recompensas</p><h1>Troque seus pontos por recompensas</h1></div>
                <div class="topbar-actions points-actions"><span class="points-badge" aria-label="Pontos acumulados"><?php echo number_format($pontosUsuario, 0, ',', '.'); ?> pts</span></div>
                <?php require __DIR__ . '/includes/profile_menu.php'; ?>
            </header>
            <main class="content reward-page">
                <?php if ($message): ?><p class="form-message <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
                <div class="reward-toolbar">
                    <label class="task-search reward-search">
                        <svg class="task-search-icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="2"></circle>
                            <path d="m16 16 5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round"></path>
                        </svg>
                        <input type="search" data-reward-search placeholder="Pesquisar recompensas" aria-label="Pesquisar recompensas">
                    </label>
                    <div class="reward-filter">
                        <button class="filter-button" type="button" aria-label="Filtrar recompensas" aria-expanded="false" aria-controls="rewardFilterPanel">
                            <span class="filter-icon-light" aria-hidden="true"></span>
                            <span class="filter-icon-dark" aria-hidden="true"></span>
                            Filtrar
                        </button>
                        <div class="card task-filter-panel reward-filter-panel" id="rewardFilterPanel" aria-label="Filtros de recompensas" hidden>
                            <fieldset>
                                <legend>Pontos</legend>
                                <label><input type="checkbox" data-reward-affordability-filter="affordable" checked> Posso resgatar</label>
                                <label><input type="checkbox" data-reward-affordability-filter="saving" checked> Preciso juntar pontos</label>
                            </fieldset>
                            <fieldset>
                                <legend>Ordenação por custo</legend>
                                <label><input type="radio" name="reward-sort-order" data-reward-sort-filter="asc" value="asc"> Menor custo</label>
                                <label><input type="radio" name="reward-sort-order" data-reward-sort-filter="desc" value="desc"> Maior custo</label>
                            </fieldset>
                            <button class="task-filter-reset reward-filter-reset" type="button">Limpar filtros</button>
                        </div>
                    </div>
                </div>
                <section class="reward-layout">
                    <div>
                        <div class="section-heading">
                            <div><p class="small-label">Catálogo</p><h2>Suas recompensas</h2></div>
                            <span class="muted-text" data-reward-count aria-live="polite"><?php echo count($rewards); ?> disponíveis</span>
                        </div>
                        <p class="empty-state reward-filter-empty" data-reward-filter-empty role="status" aria-live="polite" hidden>Nenhuma recompensa corresponde à pesquisa ou aos filtros selecionados.</p>
                        <div class="reward-grid" data-reward-grid>
                            <?php foreach ($rewards as $reward):
                                $canRedeem = $pontosUsuario >= (int) $reward['qtd_pon_neces'];
                                $isOwner = $userId !== null && (int) ($reward['user_id'] ?? 0) === $userId;
                            ?>
                                <article class="card reward-card" data-reward-card data-reward-cost="<?php echo (int) $reward['qtd_pon_neces']; ?>" data-reward-affordable="<?php echo $canRedeem ? 'true' : 'false'; ?>">
                                    <h3><?php echo htmlspecialchars($reward['descricao'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p>Use seus pontos para resgatar esta recompensa.</p>
                                    <div class="reward-footer">
                                        <strong><?php echo number_format((int) $reward['qtd_pon_neces'], 0, ',', '.'); ?> pts</strong>
                                        <div class="reward-actions">
                                            <?php if ($isOwner): ?>
                                                <button class="task-edit-button reward-edit-button" type="button"
                                                    data-recompensa-id="<?php echo (int) $reward['id_recompensa']; ?>"
                                                    data-descricao="<?php echo htmlspecialchars($reward['descricao'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-pontos="<?php echo (int) $reward['qtd_pon_neces']; ?>">Editar</button>
                                            <?php endif; ?>
                                            <?php if ($userId !== null): ?>
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="id_recompensa" value="<?php echo (int) $reward['id_recompensa']; ?>">
                                                    <button class="complete-button" name="resgatar" type="submit" <?php echo $canRedeem ? '' : 'disabled'; ?>>
                                                        <?php echo $canRedeem ? 'Resgatar' : 'Faltam pontos'; ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                            <?php if (!$rewards): ?><div class="card empty-state">Nenhuma recompensa cadastrada ainda.</div><?php endif; ?>
                        </div>
                    </div>
                    <aside class="card add-task-card">
                        <p class="small-label">Sua próxima conquista</p>
                        <h2>Adicionar recompensa</h2>
                        <p>Cadastre algo que você quer conquistar e defina o custo em pontos.</p>
                        <?php if ($userId !== null): ?>
                            <form method="post" class="task-form">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                <label for="descricao">Recompensa</label>
                                <input id="descricao" name="descricao" required maxlength="180" placeholder="Ex.: Uma hora de videogame">
                                <label for="pontos">Custo em pontos</label>
                                <input id="pontos" name="pontos" type="number" min="1" max="100000" required placeholder="300">
                                <button class="btn form-submit" name="adicionar_recompensa" type="submit">Adicionar recompensa</button>
                            </form>
                        <?php else: ?>
                            <a class="pill active" href="login/login.php">Entrar para continuar</a>
                        <?php endif; ?>
                    </aside>
                </section>
            </main>
        </div>
    </div>
    <?php if ($userId !== null): ?>
        <div class="logout-modal task-edit-modal reward-edit-modal" id="rewardEditModal" role="dialog" aria-modal="true" aria-labelledby="rewardEditTitle" hidden>
            <div class="logout-modal-backdrop" data-close-reward-edit></div>
            <div class="logout-modal-card task-edit-card">
                <button class="logout-modal-close" type="button" aria-label="Fechar" data-close-reward-edit>&times;</button>
                <p class="small-label">Atualize sua conquista</p>
                <h2 id="rewardEditTitle">Editar recompensa</h2>
                <form method="post" id="rewardEditForm" class="task-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id_recompensa" id="editRewardId">
                    <label for="editRewardDescription">Nome da recompensa</label>
                    <input id="editRewardDescription" name="descricao" required maxlength="180">
                    <label for="editRewardPoints">Custo em pontos</label>
                    <input id="editRewardPoints" name="pontos" type="number" min="1" max="100000" required>
                    <div class="task-edit-actions">
                        <button class="task-delete-button" type="button" id="deleteRewardButton">Excluir</button>
                        <button class="btn-secundario" type="button" data-close-reward-edit>Cancelar</button>
                        <button class="btn form-submit" type="submit" name="editar_recompensa">Salvar alterações</button>
                    </div>
                </form>
                <form method="post" id="rewardDeleteForm" hidden>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="id_recompensa" id="deleteRewardId">
                    <input type="hidden" name="excluir_recompensa" value="1">
                </form>
            </div>
        </div>
        <div class="logout-modal task-confirm-modal" id="rewardConfirmModal" role="alertdialog" aria-modal="true" aria-labelledby="rewardConfirmTitle" aria-describedby="rewardConfirmMessage" hidden>
            <div class="logout-modal-backdrop"></div>
            <div class="logout-modal-card">
                <h2 id="rewardConfirmTitle"></h2>
                <p id="rewardConfirmMessage"></p>
                <div class="logout-modal-actions">
                    <button class="btn-secundario" type="button" id="rewardConfirmCancel"></button>
                    <button class="task-confirm-accept" type="button" id="rewardConfirmAccept"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <script src="assets/js/recompensas.js" defer></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>