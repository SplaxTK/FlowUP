<?php if (!in_array($_COOKIE['flowup_consent'] ?? '', ['accepted', 'rejected'], true)): ?>
    <aside class="cookie-notice" id="cookieNotice" role="region" aria-labelledby="cookieNoticeTitle" aria-describedby="cookieNoticeText">
        <div class="cookie-notice-copy">
            <h2 id="cookieNoticeTitle">Suas preferências e privacidade</h2>
            <p id="cookieNoticeText">Usamos o armazenamento local para guardar opções de personalização, como tema, cor e fonte. Se aceitar, suas escolhas serão lembradas neste navegador e o widget de clima poderá ser carregado. <a class="form-link" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/politica/politica_cookies.php">Política de Cookies</a>.</p>
        </div>
        <div class="cookie-notice-actions">
            <button class="btn-cookie btn-cookie-reject" type="button" data-consent="rejected">Recusar</button>
            <button class="btn-cookie btn-cookie-accept" type="button" data-consent="accepted">Aceitar</button>
        </div>
    </aside>
<?php endif; ?>
<script src="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/js/preferencias.js" defer></script>
</body>
</html>
