(function () {
    const consentCookie = 'flowup_consent';
    const notice = document.getElementById('cookieNotice');
    const weatherWidget = document.querySelector('[data-weather-widget]');
    const weatherStatus = document.querySelector('[data-weather-status]');
    const preferencesApi = window.FlowUpPrefs;

    function readConsent() {
        const cookie = document.cookie.split(';').map(function (value) {
            return value.trim();
        }).find(function (value) {
            return value.indexOf(consentCookie + '=') === 0;
        });

        return cookie ? cookie.slice(consentCookie.length + 1) || null : null;
    }

    function showWeatherStatus(message) {
        if (!weatherStatus) return;
        weatherStatus.textContent = message;
        weatherStatus.hidden = false;
    }

    function loadWeatherWidget() {
        if (!weatherWidget) return;
        if (preferencesApi && !preferencesApi.get().clima) {
            weatherWidget.hidden = true;
            return;
        }

        weatherWidget.hidden = false;
        if (weatherStatus) weatherStatus.hidden = true;
        if (document.querySelector('script[data-weather-script]')) return;

        const script = document.createElement('script');
        script.src = 'https://elfsightcdn.com/platform.js';
        script.async = true;
        script.dataset.weatherScript = 'true';
        script.addEventListener('error', function () {
            showWeatherStatus('Não foi possível carregar o widget de clima. Tente novamente mais tarde.');
            console.error('Falha ao carregar o widget externo de clima.');
        });
        document.head.appendChild(script);
    }

    function saveConsent(value) {
        if (!['accepted', 'rejected'].includes(value)) return false;

        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = consentCookie + '=' + value + '; Max-Age=31536000; Path=/; SameSite=Lax' + secure;
        if (readConsent() !== value) {
            console.error('Não foi possível salvar a escolha de cookies do FlowUp.');
            window.alert('Não foi possível salvar sua escolha de cookies. Verifique as configurações do navegador e tente novamente.');
            return false;
        }

        try {
            if (preferencesApi) {
                preferencesApi.setConsent(value === 'accepted');
            }
        } catch (error) {
            console.error('Não foi possível atualizar as preferências locais do FlowUp.', error);
            window.alert('Não foi possível atualizar as preferências locais. Verifique as configurações de armazenamento do navegador e tente novamente.');
        }

        if (notice) notice.hidden = true;
        if (value === 'accepted') {
            loadWeatherWidget();
        } else if (weatherWidget) {
            showWeatherStatus('O widget de clima não foi carregado porque você recusou cookies.');
        }
        return true;
    }

    if (notice) {
        notice.querySelectorAll('[data-consent]').forEach(function (button) {
            button.addEventListener('click', function () {
                saveConsent(button.dataset.consent);
            });
        });
    }

    const consent = readConsent();
    if (consent === 'accepted') {
        loadWeatherWidget();
    } else if (weatherWidget && consent === 'rejected') {
        showWeatherStatus('O widget de clima não foi carregado porque você recusou cookies.');
    } else if (weatherWidget) {
        showWeatherStatus('Aceite o uso de cookies para carregar o widget de clima.');
    }

    document.querySelectorAll('[data-voltar-anterior]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            const fallback = new URL(link.href, window.location.href);
            const previousPage = document.referrer ? new URL(document.referrer) : null;
            if (previousPage && previousPage.origin === window.location.origin) {
                event.preventDefault();
                if (window.history.length > 1) {
                    window.history.back();
                } else {
                    window.location.assign(previousPage.href);
                }
            } else {
                link.href = fallback.href;
            }
        });
    });

    if (!preferencesApi) return;

    const preferenceDefaults = preferencesApi.defaults;

    function applyHomePreferences(values) {
        document.querySelectorAll('[data-preferencia-inicio]').forEach(function (element) {
            const preference = element.dataset.preferenciaInicio;
            element.hidden = typeof values[preference] === 'boolean' && !values[preference];
        });

        document.querySelectorAll('.cards-grid, .bottom-grid').forEach(function (section) {
            const contentBlocks = Array.from(section.children).filter(function (element) {
                return element.hasAttribute('data-preferencia-inicio');
            });
            section.hidden = contentBlocks.length > 0 && contentBlocks.every(function (element) {
                return element.hidden;
            });
        });
    }

    const saved = preferencesApi.get();
    applyHomePreferences(saved);

    const form = document.querySelector('.personalizar-content form');
    if (!form) return;

    const theme = form.querySelector('[name="tema"]');
    const density = form.querySelector('[name="densidade"]');
    const fontSize = form.querySelector('[name="fonte"]');
    const bold = form.querySelector('[name="negrito"]');
    const colorInputs = form.querySelectorAll('input[name="cor"]');
    const homeInputs = {
        calendario: form.querySelector('[name="calendario"]'),
        tarefas: form.querySelector('[name="tarefas"]'),
        resumo: form.querySelector('[name="resumo"]'),
        planejamento: form.querySelector('[name="planejamento"]'),
        clima: form.querySelector('[name="clima"]'),
        pontos: form.querySelector('[name="pontos"]')
    };
    const restoreButton = document.getElementById('btnCancelar');
    const restoreModal = document.getElementById('restoreModal');
    const confirmRestore = document.getElementById('confirmRestore');
    const message = document.getElementById('preferencesMessage');
    const manageCookies = document.getElementById('manageCookies');

    function formValues() {
        const selectedColor = form.querySelector('input[name="cor"]:checked');
        const values = {
            tema: theme.value,
            cor: selectedColor ? selectedColor.value : preferenceDefaults.cor,
            densidade: density.value,
            fonte: fontSize.value,
            negrito: bold.checked
        };
        Object.keys(homeInputs).forEach(function (key) {
            values[key] = homeInputs[key].checked;
        });
        return values;
    }

    function setFormValues(values) {
        theme.value = values.tema;
        density.value = values.densidade;
        fontSize.value = values.fonte;
        bold.checked = values.negrito;
        colorInputs.forEach(function (input) {
            input.checked = input.value === values.cor;
        });
        Object.keys(homeInputs).forEach(function (key) {
            homeInputs[key].checked = values[key];
        });
        preferencesApi.apply(values);
    }

    function showMessage(text, kind) {
        if (!message) return;
        message.textContent = text;
        message.className = 'preferences-message ' + kind;
        message.hidden = false;
    }

    function saveValues(values, savedText, temporaryText) {
        try {
            const updated = preferencesApi.save(values);
            setFormValues(updated);
            applyHomePreferences(updated);
            const feedback = preferencesApi.hasConsent() ? savedText : temporaryText;
            showMessage(feedback, 'success');
        } catch (error) {
            console.error('Não foi possível salvar as preferências locais do FlowUp.', error);
            showMessage('Não foi possível salvar as preferências. Verifique o armazenamento do navegador e tente novamente.', 'error');
        }
    }

    function closeRestoreModal() {
        if (!restoreModal) return;
        restoreModal.hidden = true;
        document.body.classList.remove('modal-aberto');
        if (restoreButton) restoreButton.focus();
    }

    setFormValues(saved);

    form.addEventListener('change', function () {
        preferencesApi.apply(formValues());
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        saveValues(
            formValues(),
            'Configurações salvas com sucesso.',
            'Configurações aplicadas nesta visita. Aceite os cookies para salvá-las.'
        );
    });

    if (restoreButton && restoreModal) {
        restoreButton.addEventListener('click', function () {
            restoreModal.hidden = false;
            document.body.classList.add('modal-aberto');
            restoreModal.querySelector('[data-close-restore]').focus();
        });

        restoreModal.querySelectorAll('[data-close-restore]').forEach(function (element) {
            element.addEventListener('click', closeRestoreModal);
        });

        if (confirmRestore) {
            confirmRestore.addEventListener('click', function () {
                closeRestoreModal();
                saveValues(
                    preferenceDefaults,
                    'Personalizações restauradas com sucesso.',
                    'Personalizações restauradas nesta visita. Aceite os cookies para salvá-las.'
                );
            });
        }

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !restoreModal.hidden) {
                closeRestoreModal();
            }
        });
    }

    if (manageCookies) {
        manageCookies.addEventListener('click', function () {
            document.cookie = consentCookie + '=; Max-Age=0; Path=/; SameSite=Lax';
            if (readConsent() !== null) {
                console.error('Não foi possível reabrir as opções de cookies.');
                window.alert('Não foi possível abrir as opções de cookies. Verifique as configurações do navegador e tente novamente.');
                return;
            }
            window.location.reload();
        });
    }
})();
