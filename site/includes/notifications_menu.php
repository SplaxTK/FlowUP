<div class="notifications-container" data-notifications data-notifications-user="<?php echo htmlspecialchars((string) (flowup_user_id() ?? 'visitante'), ENT_QUOTES, 'UTF-8'); ?>">
    <button class="topbar-actions notifications-actions" type="button" aria-label="Notificações" aria-expanded="false" aria-controls="notificationsPanel" data-notifications-button>
        <img class="notifications-icon" src="assets/img/icons/bell-notfi-icon.png" alt="" aria-hidden="true">
        <span class="notifications-badge" data-notifications-badge hidden></span>
    </button>
    <section class="notifications-panel" id="notificationsPanel" aria-label="Notificações recentes" hidden>
        <h2>Notificações</h2>
        <?php if ($notificacoes): ?>
            <div class="notifications-list">
                <?php foreach ($notificacoes as $notificacao): ?>
                    <article class="notification-item<?php echo $notificacao['dt_leitura'] === null ? ' notification-unread' : ''; ?>">
                        <h3><?php echo htmlspecialchars($notificacao['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p><?php echo htmlspecialchars($notificacao['mensagem'], ENT_QUOTES, 'UTF-8'); ?></p>
                        <time datetime="<?php echo htmlspecialchars($notificacao['dt_criacao'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo date('d/m/Y H:i', strtotime($notificacao['dt_criacao'])); ?>
                        </time>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="notifications-empty">Você não tem notificações.</p>
        <?php endif; ?>
    </section>
</div>