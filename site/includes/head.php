<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'FlowUp', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/img/flowUp.png">
    <script>
        (function () {
            const storageKey = 'flowup_preferences';
            const defaults = {
                tema: 'claro',
                cor: 'azul',
                densidade: 'normal',
                fonte: 'media',
                negrito: false,
                calendario: true,
                tarefas: true,
                resumo: true,
                planejamento: true,
                clima: true,
                pontos: true
            };
            const validThemes = ['claro', 'escuro'];
            const validColors = ['azul', 'roxo', 'verde', 'laranja', 'ciano', 'ceu', 'turquesa', 'esmeralda', 'lima', 'amarelo', 'ambar', 'vermelho', 'rosa', 'pink', 'fucsia', 'indigo'];
            const validDensities = ['compacta', 'normal', 'espacosa'];
            const validFontSizes = ['pequena', 'media', 'grande'];
            let consented = document.cookie.split(';').some(function (value) {
                return value.trim() === 'flowup_consent=accepted';
            });

            function sanitize(value) {
                const source = value && typeof value === 'object' ? value : {};
                return {
                    tema: validThemes.includes(source.tema) ? source.tema : defaults.tema,
                    cor: validColors.includes(source.cor) ? source.cor : defaults.cor,
                    densidade: validDensities.includes(source.densidade) ? source.densidade : defaults.densidade,
                    fonte: validFontSizes.includes(source.fonte) ? source.fonte : defaults.fonte,
                    negrito: typeof source.negrito === 'boolean' ? source.negrito : defaults.negrito,
                    calendario: typeof source.calendario === 'boolean' ? source.calendario : defaults.calendario,
                    tarefas: typeof source.tarefas === 'boolean' ? source.tarefas : defaults.tarefas,
                    resumo: typeof source.resumo === 'boolean' ? source.resumo : defaults.resumo,
                    planejamento: typeof source.planejamento === 'boolean' ? source.planejamento : defaults.planejamento,
                    clima: typeof source.clima === 'boolean' ? source.clima : defaults.clima,
                    pontos: typeof source.pontos === 'boolean' ? source.pontos : defaults.pontos
                };
            }

            let preferences = defaults;
            if (consented) {
                try {
                    const stored = localStorage.getItem(storageKey);
                    if (stored !== null) {
                        preferences = sanitize(JSON.parse(stored));
                    }
                } catch (error) {
                    console.error('Não foi possível ler as preferências locais do FlowUp.', error);
                }
            } else {
                try {
                    localStorage.removeItem(storageKey);
                } catch (error) {
                    console.error('Não foi possível remover preferências locais sem consentimento.', error);
                }
            }

            function apply(value) {
                const activePreferences = value ? sanitize(value) : preferences;
                const root = document.documentElement;
                root.dataset.tema = activePreferences.tema;
                root.dataset.cor = activePreferences.cor;
                root.dataset.densidade = activePreferences.densidade;
                root.dataset.fonte = activePreferences.fonte;
                root.dataset.negrito = activePreferences.negrito ? 'true' : 'false';
                if (document.body) {
                    document.body.classList.toggle('escuro', activePreferences.tema === 'escuro');
                    document.body.classList.toggle('texto-negrito', activePreferences.negrito);
                }
            }

            function save(value) {
                const next = sanitize(value);
                if (consented) {
                    localStorage.setItem(storageKey, JSON.stringify(next));
                }
                preferences = next;
                apply();
                return get();
            }

            function get() {
                return Object.assign({}, preferences);
            }

            function setConsent(value) {
                if (value) {
                    localStorage.setItem(storageKey, JSON.stringify(preferences));
                    consented = true;
                } else {
                    consented = false;
                    localStorage.removeItem(storageKey);
                }
            }

            apply();
            if (!document.body) {
                const observer = new MutationObserver(function () {
                    if (document.body) {
                        apply();
                        observer.disconnect();
                    }
                });
                observer.observe(document.documentElement, { childList: true });
            }

            window.FlowUpPrefs = Object.freeze({
                defaults: Object.freeze(Object.assign({}, defaults)),
                get: get,
                save: save,
                setConsent: setConsent,
                hasConsent: function () {
                    return consented;
                },
                reset: function () {
                    return save(defaults);
                },
                apply: apply
            });
        })();
    </script>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/css/style.css">
    <script src="<?php echo htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8'); ?>/assets/js/script.js" defer></script>
</head>
