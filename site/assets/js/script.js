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
                    <input type="password" id="senhaAtualModal" name="senha_atual"
                           autocomplete="current-password" maxlength="1024" required>
                </div>
                <div class="campo">
                    <label for="novaSenhaModal">Nova senha</label>
                    <input type="password" id="novaSenhaModal" name="nova_senha"
                           autocomplete="new-password" minlength="6" maxlength="72" required>
                    <small class="campo-ajuda">Mínimo de 6 caracteres.</small>
                </div>
                <div class="campo">
                    <label for="confirmarSenhaModal">Confirmar nova senha</label>
                    <input type="password" id="confirmarSenhaModal" name="confirmar_senha"
                           autocomplete="new-password" minlength="6" maxlength="72" required>
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

document.addEventListener('DOMContentLoaded', function () {
    const groups = Array.from(document.querySelectorAll('.task-group'));
    const minimizeAllButton = document.querySelector('.maximize-button');
    const groupToggleButtons = document.querySelectorAll('.task-group-toggle');
    const STORAGE_KEY = 'taskGroupsState';

    function loadState() {
        try {
            const saved = localStorage.getItem(STORAGE_KEY);
            if (!saved) return {};

            const state = JSON.parse(saved);
            if (!state || typeof state !== 'object' || Array.isArray(state)) {
                throw new TypeError('O estado salvo deve ser um objeto.');
            }
            return state;
        } catch (error) {
            console.error('Não foi possível carregar o estado das categorias de tarefas.', error);
            return {};
        }
    }

    function saveState() {
        const state = {};
        groups.forEach(function (group, index) {
            const groupKey = group.dataset.taskType || group.id || 'group-' + index;
            state[groupKey] = group.classList.contains('is-collapsed');
        });
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
        } catch (error) {
            console.error('Não foi possível salvar o estado das categorias de tarefas.', error);
        }
    }

    function updateGroupToggle(group) {
        const button = group.querySelector('.task-group-toggle');
        if (!button) return;

        const isExpanded = !group.classList.contains('is-collapsed');
        button.setAttribute('aria-expanded', String(isExpanded));
        button.setAttribute(
            'aria-label',
            isExpanded ? 'Recolher tarefas desta categoria' : 'Expandir tarefas desta categoria'
        );
        button.title = isExpanded ? 'Recolher tarefas desta categoria' : 'Expandir tarefas desta categoria';
    }

    function updateMinimizeAllButton() {
        if (!minimizeAllButton) return;

        const allCollapsed = groups.length > 0 && groups.every(function (group) {
            return group.classList.contains('is-collapsed');
        });
        const label = allCollapsed ? 'Expandir todas as categorias' : 'Recolher todas as categorias';
        minimizeAllButton.setAttribute('aria-pressed', String(allCollapsed));
        minimizeAllButton.setAttribute('aria-label', label);
        minimizeAllButton.title = label;
    }

    const savedState = groups.length > 0 ? loadState() : {};
    groups.forEach(function (group, index) {
        const groupKey = group.dataset.taskType || group.id || 'group-' + index;
        const savedValue = savedState[groupKey] !== undefined
            ? savedState[groupKey]
            : savedState['group-' + index];
        if (typeof savedValue === 'boolean') {
            group.classList.toggle('is-collapsed', savedValue);
        }
    });

    groupToggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const group = button.closest('.task-group');
            if (!group) return;

            group.classList.toggle('is-collapsed');
            updateGroupToggle(group);
            updateMinimizeAllButton();
            saveState();
        });
    });

    if (minimizeAllButton) {
        minimizeAllButton.disabled = groups.length === 0;
        minimizeAllButton.addEventListener('click', function () {
            const allCollapsed = groups.length > 0 && groups.every(function (group) {
                return group.classList.contains('is-collapsed');
            });

            groups.forEach(function (group) {
                group.classList.toggle('is-collapsed', !allCollapsed);
                updateGroupToggle(group);
            });
            updateMinimizeAllButton();
            saveState();
        });
    }

    groups.forEach(updateGroupToggle);
    updateMinimizeAllButton();
});