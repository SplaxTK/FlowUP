<div class="perfil-actions" id="perfilActions">
    <?php if (isset($_SESSION['user_id'])): ?>
        <button class="perfil-button" id="perfilButton" type="button" aria-label="Abrir menu de perfil" aria-expanded="false" aria-controls="perfilMenu">
            <img class="perfil-user-icon" src="assets/img/icons/user-icon.png" alt="" aria-hidden="true">
        </button>
        <div class="perfil-menu" id="perfilMenu" hidden>
            <div class="perfil-menu-user">
                <img class="perfil-user-icon" src="assets/img/icons/user-icon.png" alt="" aria-hidden="true">
                <div class="perfil-menu-user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_username'] ?? 'Perfil', ENT_QUOTES, 'UTF-8'); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_full_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
            <nav class="perfil-menu-links" aria-label="Ações do perfil">
                <a href="perfil.php">Perfil</a>
                <?php if (($_SESSION['is_admin'] ?? false) === true): ?>
                    <a href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/crud/painel.php" target="_blank">Painel de administrador</a>
                <?php endif; ?>
                <a href="acessibilidade.php">Acessibilidade</a>
                <button class="perfil-menu-logout" type="button" data-open-logout>Sair</button>
            </nav>
        </div>
    <?php else: ?>
        <a href="login/login.php" class="pill">Entrar</a>
        <a href="login/cadastro.php" class="pill active">Cadastrar</a>
    <?php endif; ?>
</div>
<?php if (isset($_SESSION['user_id'])): ?>
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
<?php endif; ?>
