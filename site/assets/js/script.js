window.FlowUpNotifications = (function () {
    "use strict";

    const container = document.querySelector("[data-notifications]");
    const panel = document.getElementById("notificationsPanel");
    const button = document.querySelector("[data-notifications-button]");
    const emptyMessage = panel ? panel.querySelector(".notifications-empty") : null;
    let list = panel ? panel.querySelector(".notifications-list") : null;
    if (panel && !list) {
        list = document.createElement("div");
        list.className = "notifications-list";
        if (emptyMessage) {
            panel.insertBefore(list, emptyMessage);
        } else {
            panel.appendChild(list);
        }
    }
    const badge = document.querySelector("[data-notifications-badge]");
    const storageKey = "flowup_notification_history_" + (container ? container.dataset.notificationsUser : "visitante");
    const serverItems = list ? Array.from(list.querySelectorAll(".notification-item")).map(function (item) {
        const time = item.querySelector("time");
        return {
            title: item.querySelector("h3").textContent,
            message: item.querySelector("p").textContent,
            createdAt: time ? time.dateTime : "",
            read: !item.classList.contains("notification-unread"),
            server: true
        };
    }) : [];
    let history = [];
    let toastTimer = null;
    let activeToast = null;

    function readHistory() {
        try {
            const stored = window.localStorage.getItem(storageKey);
            const parsed = stored ? JSON.parse(stored) : [];
            history = Array.isArray(parsed) ? parsed.filter(function (item) {
                return item && typeof item.title === "string" &&
                    typeof item.message === "string" &&
                    typeof item.createdAt === "string";
            }) : [];
        } catch (error) {
            console.error("Não foi possível carregar o histórico de notificações do FlowUp.", error);
            history = [];
        }
    }

    function saveHistory() {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(history.slice(0, 10)));
        } catch (error) {
            console.error("Não foi possível salvar o histórico de notificações do FlowUp.", error);
        }
    }

    function formatDate(value) {
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString("pt-BR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit"
        });
    }

    function renderHistory() {
        if (!list) return;

        const items = serverItems.concat(history.map(function (item) {
            return Object.assign({ server: false }, item);
        })).sort(function (a, b) {
            return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
        }).slice(0, 10);

        list.replaceChildren();
        items.forEach(function (item) {
            const article = document.createElement("article");
            article.className = "notification-item" + (item.read ? "" : " notification-unread");
            const title = document.createElement("h3");
            title.textContent = item.title;
            const message = document.createElement("p");
            message.textContent = item.message;
            const time = document.createElement("time");
            time.dateTime = item.createdAt;
            time.textContent = formatDate(item.createdAt);
            article.append(title, message, time);
            list.appendChild(article);
        });

        if (emptyMessage) emptyMessage.hidden = items.length > 0;
        if (badge) {
            const unreadCount = history.filter(function (item) { return !item.read; }).length;
            badge.textContent = unreadCount > 9 ? "9+" : String(unreadCount);
            badge.hidden = unreadCount === 0;
            if (button) button.setAttribute("aria-label", unreadCount
                ? "Notificações, " + unreadCount + " não lidas"
                : "Notificações");
        }
    }

    function dismissToast() {
        window.clearTimeout(toastTimer);
        toastTimer = null;
        if (activeToast) activeToast.remove();
        activeToast = null;
    }

    function showToast(message, type) {
        dismissToast();
        let region = document.querySelector("[data-flowup-toast-region]");
        if (!region) {
            region = document.createElement("div");
            region.className = "flowup-toast-region";
            region.dataset.flowupToastRegion = "true";
            region.setAttribute("aria-live", type === "error" ? "assertive" : "polite");
            region.setAttribute("aria-relevant", "additions");
            document.body.appendChild(region);
        } else {
            region.setAttribute("aria-live", type === "error" ? "assertive" : "polite");
        }

        const toast = document.createElement("div");
        toast.className = "flowup-toast" + (type === "error" ? " flowup-toast-error" : "");
        toast.setAttribute("role", type === "error" ? "alert" : "status");
        const text = document.createElement("span");
        text.className = "flowup-toast-text";
        text.textContent = message;
        const close = document.createElement("button");
        close.className = "flowup-toast-close";
        close.type = "button";
        close.setAttribute("aria-label", "Fechar notificação");
        close.textContent = "×";
        const progress = document.createElement("span");
        progress.className = "flowup-toast-progress";
        progress.setAttribute("aria-hidden", "true");
        toast.append(text, close, progress);
        region.appendChild(toast);
        activeToast = toast;
        close.addEventListener("click", dismissToast);
        toastTimer = window.setTimeout(dismissToast, 5000);
    }

    function notify(message, options) {
        const settings = options || {};
        const type = settings.type === "error" ? "error" : "success";
        const title = settings.title || (type === "error" ? "Não foi possível concluir" : "Atualização concluída");
        const text = String(message || "");
        if (!text) return;

        if (container && list) {
            history.unshift({
                title: title,
                message: text,
                createdAt: new Date().toISOString(),
                read: false
            });
            history = history.slice(0, 10);
            saveHistory();
            renderHistory();
        }
        showToast(text, type);
    }

    readHistory();
    renderHistory();

    document.querySelectorAll("[data-flowup-notice]").forEach(function (element) {
        const type = element.dataset.flowupNoticeType === "error" ? "error" : "success";
        const title = element.dataset.flowupNoticeTitle;
        const message = element.textContent.trim();
        element.hidden = true;
        if (message) notify(message, { type: type, title: title });
    });

    if (window.location.pathname.endsWith("/configuracoes.php")) {
        const url = new URL(window.location.href);
        if (url.searchParams.get("sucesso") === "1") {
            url.searchParams.delete("sucesso");
            window.history.replaceState({}, "", url.href);
        }
    }

    if (container && panel && button) {
        button.addEventListener("click", function () {
            if (panel.hidden) {
                history = history.map(function (item) {
                    return Object.assign({}, item, { read: true });
                });
                saveHistory();
                renderHistory();
            }
        });
    }

    return Object.freeze({ notify: notify });
})();

// ==========================
// ALTERAR SENHA (com senha atual)
// ==========================

const btnSenha = document.getElementById("btnSenha");

function abrirModalSenha() {
    if (document.getElementById("modalSenha")) return;

    const endpoint = btnSenha ? btnSenha.dataset.endpoint : "";
    const csrf = btnSenha ? (btnSenha.dataset.csrf || "") : "";
    let enviando = false;

    const overlay = document.createElement("div");
    overlay.id = "modalSenha";
    overlay.className = "logout-modal modal-senha";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-labelledby", "modalSenhaTitulo");

    overlay.innerHTML = `
        <div class="logout-modal-backdrop" data-fechar-senha></div>
        <div class="logout-modal-card">
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-fechar-senha>&times;</button>
            <h2 id="modalSenhaTitulo">Alterar senha</h2>
            <p id="modalSenhaTexto">Confirme a senha atual e defina uma nova senha para sua conta.</p>

            <form id="formModalSenha" novalidate>
                <div class="campo">
                    <label for="senhaAtualModal">Senha atual</label>
                    <div class="password-input-wrap">
                        <input type="password" id="senhaAtualModal" name="senha_atual"
                               autocomplete="current-password" maxlength="1024" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar senha" aria-controls="senhaAtualModal" aria-pressed="false">
                            <svg data-password-icon="show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg data-password-icon="hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="m3 3 18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.2A11 11 0 0 1 12 5c7 0 11 7 11 7a16 16 0 0 1-3.1 3.8"></path>
                                <path d="M6.6 6.6C3.6 8.5 1 12 1 12s4 7 11 7a10.8 10.8 0 0 0 4-.8"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="campo">
                    <label for="novaSenhaModal">Nova senha</label>
                    <div class="password-input-wrap">
                        <input type="password" id="novaSenhaModal" name="nova_senha"
                               autocomplete="new-password" minlength="6" maxlength="72" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar senha" aria-controls="novaSenhaModal" aria-pressed="false">
                            <svg data-password-icon="show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg data-password-icon="hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="m3 3 18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.2A11 11 0 0 1 12 5c7 0 11 7 11 7a16 16 0 0 1-3.1 3.8"></path>
                                <path d="M6.6 6.6C3.6 8.5 1 12 1 12s4 7 11 7a10.8 10.8 0 0 0 4-.8"></path>
                            </svg>
                        </button>
                    </div>
                    <small class="campo-ajuda">Mínimo de 6 caracteres.</small>
                </div>
                <div class="campo">
                    <label for="confirmarSenhaModal">Confirmar nova senha</label>
                    <div class="password-input-wrap">
                        <input type="password" id="confirmarSenhaModal" name="confirmar_senha"
                               autocomplete="new-password" minlength="6" maxlength="72" required>
                        <button type="button" class="toggle-password" aria-label="Mostrar senha" aria-controls="confirmarSenhaModal" aria-pressed="false">
                            <svg data-password-icon="show" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg data-password-icon="hide" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" hidden>
                                <path d="m3 3 18 18"></path>
                                <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
                                <path d="M9.9 5.2A11 11 0 0 1 12 5c7 0 11 7 11 7a16 16 0 0 1-3.1 3.8"></path>
                                <path d="M6.6 6.6C3.6 8.5 1 12 1 12s4 7 11 7a10.8 10.8 0 0 0 4-.8"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <p class="form-message error" id="modalSenhaErro" role="alert" hidden></p>

                <div class="logout-modal-actions">
                    <button type="button" class="btn-secundario" data-fechar-senha>Cancelar</button>
                    <button type="submit" class="btn" id="modalSenhaEnviar">Alterar senha</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(overlay);
    document.body.classList.add("modal-aberto");

    const form          = overlay.querySelector("#formModalSenha");
    const inputAtual    = overlay.querySelector("#senhaAtualModal");
    const inputNova     = overlay.querySelector("#novaSenhaModal");
    const inputConfirm  = overlay.querySelector("#confirmarSenhaModal");
    const botaoEnviar   = overlay.querySelector("#modalSenhaEnviar");
    const areaErro      = overlay.querySelector("#modalSenhaErro");

    function fechar() {
        if (enviando) return;
        overlay.remove();
        document.body.classList.remove("modal-aberto");
        document.removeEventListener("keydown", aoPressionarTecla);
        if (btnSenha) btnSenha.focus();
    }

    function aoPressionarTecla(evento) {
        if (evento.key === "Escape") fechar();
    }

    function mostrarErro(mensagem) {
        areaErro.textContent = mensagem;
        areaErro.hidden = false;
    }

    function limparErro() {
        areaErro.hidden = true;
        areaErro.textContent = "";
    }

    function definirCarregando(ativo) {
        enviando = ativo;
        botaoEnviar.disabled = ativo;
        inputAtual.disabled = ativo;
        inputNova.disabled = ativo;
        inputConfirm.disabled = ativo;
        botaoEnviar.classList.toggle("carregando", ativo);
        botaoEnviar.textContent = ativo ? "Salvando..." : "Alterar senha";
        form.setAttribute("aria-busy", String(ativo));
    }

    function validarLocal(senhaAtual, nova, confirmar) {
        if (senhaAtual === "") return { campo: inputAtual, msg: "Informe a sua senha atual." };
        if (nova === "")          return { campo: inputNova,  msg: "Informe a nova senha." };
        if (nova.length < 6)      return { campo: inputNova,  msg: "A nova senha deve ter ao menos 6 caracteres." };
        if (new TextEncoder().encode(nova).length > 72) return { campo: inputNova, msg: "A nova senha deve ter no máximo 72 bytes." };
        if (nova === senhaAtual)  return { campo: inputNova,  msg: "A nova senha deve ser diferente da atual." };
        if (confirmar !== nova)   return { campo: inputConfirm, msg: "A confirmação não confere com a nova senha." };
        return null;
    }

    function mostrarSucesso() {
        const card = overlay.querySelector(".logout-modal-card");
        card.innerHTML = `
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-fechar-senha>&times;</button>
            <h2 id="modalSenhaTitulo">Senha alterada</h2>
            <p id="modalSenhaTexto">Sua senha foi atualizada com sucesso. Use a nova senha no próximo login.</p>
            <div class="logout-modal-actions">
                <button type="button" class="btn" data-fechar-senha>Entendi</button>
            </div>
        `;
        card.querySelectorAll("[data-fechar-senha]").forEach(function (el) {
            el.addEventListener("click", fechar);
        });
        card.querySelector(".btn").focus();
    }

    form.addEventListener("submit", async function (evento) {
        evento.preventDefault();
        if (enviando) return;

        limparErro();

        const senhaAtual = inputAtual.value;
        const novaSenha  = inputNova.value;
        const confirmar  = inputConfirm.value;

        const erroLocal = validarLocal(senhaAtual, novaSenha, confirmar);
        if (erroLocal) {
            mostrarErro(erroLocal.msg);
            erroLocal.campo.focus();
            return;
        }

        definirCarregando(true);

        try {
            const resposta = await fetch(endpoint, {
                method: "POST",
                credentials: "same-origin",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-Token": csrf
                },
                body: JSON.stringify({
                    senha_atual: senhaAtual,
                    nova_senha: novaSenha,
                    confirmar_senha: confirmar
                })
            });

            let dados = null;
            try { dados = await resposta.json(); } catch (_) { dados = null; }

            inputAtual.value = "";
            inputNova.value = "";
            inputConfirm.value = "";

            if (resposta.ok && dados && dados.ok === true) {
                definirCarregando(false);
                mostrarSucesso();
                return;
            }

            definirCarregando(false);
            mostrarErro(
                dados && typeof dados.mensagem === "string" && dados.mensagem !== ""
                    ? dados.mensagem
                    : "Não foi possível alterar a senha. Tente novamente mais tarde."
            );
            inputAtual.focus();
        } catch (erroRede) {
            definirCarregando(false);
            mostrarErro("Sem conexão com o servidor. Verifique sua internet e tente novamente.");
            inputAtual.focus();
        }
    });

    overlay.addEventListener("click", function (evento) {
        if (evento.target.closest("[data-fechar-senha]")) fechar();
    });
    document.addEventListener("keydown", aoPressionarTecla);

    inputAtual.focus();
}

if (btnSenha) {
    btnSenha.addEventListener("click", abrirModalSenha);
}

document.addEventListener("click", function (evento) {
    const botao = evento.target instanceof Element
        ? evento.target.closest(".toggle-password")
        : null;
    if (!botao) return;

    const inputId = botao.getAttribute("aria-controls");
    const grupoCampo = botao.closest(".password-input-wrap");
    const campo = inputId
        ? document.getElementById(inputId)
        : grupoCampo
            ? grupoCampo.querySelector("input")
            : null;
    if (!campo) return;

    const mostrarSenha = campo.type === "password";
    campo.type = mostrarSenha ? "text" : "password";
    botao.setAttribute("aria-pressed", String(mostrarSenha));
    botao.setAttribute("aria-label", mostrarSenha ? "Ocultar senha" : "Mostrar senha");

    const iconeMostrar = botao.querySelector('[data-password-icon="show"]');
    const iconeOcultar = botao.querySelector('[data-password-icon="hide"]');
    if (iconeMostrar && iconeOcultar) {
        iconeMostrar.hidden = mostrarSenha;
        iconeOcultar.hidden = !mostrarSenha;
    }
});

// ==========================
// SAIR DA CONTA
// ==========================

const logoutModal = document.getElementById("logoutModal");

function fecharLogoutModal() {
    if (!logoutModal) return;
    logoutModal.hidden = true;
    document.body.classList.remove("modal-aberto");
}

if (logoutModal) {
    document.querySelectorAll("#btnSair, [data-open-logout]").forEach(function (trigger) {
        trigger.addEventListener("click", function () {
            logoutModal.hidden = false;
            document.body.classList.add("modal-aberto");
            const perfilMenu = document.getElementById("perfilMenu");
            const perfilButton = document.getElementById("perfilButton");
            if (perfilMenu && perfilButton) {
                perfilMenu.hidden = true;
                perfilButton.setAttribute("aria-expanded", "false");
            }
            logoutModal.querySelector(".logout-modal-close").focus();
        });
    });

    logoutModal.querySelectorAll("[data-close-logout]").forEach(function (elemento) {
        elemento.addEventListener("click", fecharLogoutModal);
    });

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape" && !logoutModal.hidden) {
            fecharLogoutModal();
        }
    });
}


// ==========================
// PREFERÊNCIAS
// ==========================

const idioma = document.getElementById("idioma");

if (idioma) idioma.addEventListener("change", function () {

    if (this.value === "en") {

        alert(
            "A tradução para inglês será aplicada em uma próxima versão."
        );

    }

});


const formatoData = document.getElementById("formatoData");

if (formatoData) formatoData.addEventListener("change", function () {

    console.log(
        "Formato selecionado:",
        this.value
    );

});

const sidebarToggle = document.getElementById("sidebarToggle");
const appShell = sidebarToggle ? sidebarToggle.closest(".app-shell") : null;

if (sidebarToggle && appShell) {
    sidebarToggle.addEventListener("click", function () {
        const isCollapsed = appShell.classList.toggle("sidebar-collapsed");
        sidebarToggle.setAttribute("aria-expanded", String(!isCollapsed));
        sidebarToggle.setAttribute(
            "aria-label",
            isCollapsed ? "Expandir menu lateral" : "Recolher menu lateral"
        );
    });
}

const perfilActions = document.getElementById("perfilActions");
const perfilButton = document.getElementById("perfilButton");
const perfilMenu = document.getElementById("perfilMenu");
const notificationsContainer = document.querySelector("[data-notifications]");
const notificationsButton = document.querySelector("[data-notifications-button]");
const notificationsPanel = document.getElementById("notificationsPanel");

if (notificationsContainer && notificationsButton && notificationsPanel) {
    function fecharNotificacoes(devolverFoco) {
        notificationsPanel.hidden = true;
        notificationsButton.setAttribute("aria-expanded", "false");
        if (devolverFoco) notificationsButton.focus();
    }

    notificationsButton.addEventListener("click", function () {
        const abrirPainel = notificationsPanel.hidden;
        notificationsPanel.hidden = !abrirPainel;
        notificationsButton.setAttribute("aria-expanded", String(abrirPainel));
        if (abrirPainel && perfilMenu && perfilButton) {
            perfilMenu.hidden = true;
            perfilButton.setAttribute("aria-expanded", "false");
        }
    });

    document.addEventListener("click", function (evento) {
        if (!notificationsContainer.contains(evento.target)) {
            fecharNotificacoes(false);
        }
    });

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape" && !notificationsPanel.hidden) {
            fecharNotificacoes(true);
        }
    });
}

if (perfilActions && perfilButton && perfilMenu) {
    function fecharMenuPerfil(devolverFoco) {
        perfilMenu.hidden = true;
        perfilButton.setAttribute("aria-expanded", "false");
        if (devolverFoco) perfilButton.focus();
    }

    perfilButton.addEventListener("click", function () {
        const abrirMenu = perfilMenu.hidden;
        perfilMenu.hidden = !abrirMenu;
        perfilButton.setAttribute("aria-expanded", String(abrirMenu));
        if (abrirMenu && notificationsPanel && notificationsButton) {
            notificationsPanel.hidden = true;
            notificationsButton.setAttribute("aria-expanded", "false");
        }
    });

    document.addEventListener("click", function (evento) {
        if (!perfilActions.contains(evento.target)) {
            fecharMenuPerfil(false);
        }
    });

    document.addEventListener("keydown", function (evento) {
        if (evento.key === "Escape" && !perfilMenu.hidden) {
            fecharMenuPerfil(true);
        }
    });
}