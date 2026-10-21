// ==========================
// ALTERAR SENHA
// ==========================

const btnSenha = document.getElementById("btnSenha");

function abrirModalSenha() {
    // evita abrir duas vezes
    if (document.getElementById("modalSenha")) return;

    const endpoint = btnSenha ? btnSenha.dataset.endpoint : "";
    const csrf = btnSenha ? btnSenha.dataset.csrf || "" : "";
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
            <div class="logout-modal-icon" aria-hidden="true">🔒</div>
            <h2 id="modalSenhaTitulo">Alterar senha</h2>
            <p id="modalSenhaTexto">Para sua segurança, confirme a senha atual. Depois enviaremos um link de confirmação para o seu e-mail.</p>
            <form id="formModalSenha" novalidate>
                <div class="campo">
                    <label for="senhaAtualModal">Senha atual</label>
                    <input type="password" id="senhaAtualModal" autocomplete="current-password" maxlength="1024" required>
                </div>
                <p class="form-message error" id="modalSenhaErro" role="alert" hidden></p>
                <div class="logout-modal-actions">
                    <button type="button" class="btn-secundario" data-fechar-senha>Cancelar</button>
                    <button type="submit" class="btn" id="modalSenhaEnviar">Enviar link</button>
                </div>
            </form>
        </div>
    `;

    document.body.appendChild(overlay);
    document.body.classList.add("modal-aberto");

    const form = overlay.querySelector("#formModalSenha");
    const inputSenha = overlay.querySelector("#senhaAtualModal");
    const botaoEnviar = overlay.querySelector("#modalSenhaEnviar");
    const areaErro = overlay.querySelector("#modalSenhaErro");

    function fechar() {
        if (enviando) return; // não fecha no meio do envio
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

    function definirCarregando(ativo) {
        enviando = ativo;
        botaoEnviar.disabled = ativo;
        inputSenha.disabled = ativo;
        botaoEnviar.classList.toggle("carregando", ativo);
        botaoEnviar.textContent = ativo ? "Enviando..." : "Enviar link";
        form.setAttribute("aria-busy", String(ativo));
    }

    function mostrarSucesso(dados) {
        const card = overlay.querySelector(".logout-modal-card");
        card.innerHTML = `
            <button class="logout-modal-close" type="button" aria-label="Fechar" data-fechar-senha>&times;</button>
            <div class="logout-modal-icon" aria-hidden="true">✉️</div>
            <h2 id="modalSenhaTitulo">Verifique seu e-mail</h2>
            <p id="modalSenhaTexto"></p>
            <div class="logout-modal-actions">
                <button type="button" class="btn" data-fechar-senha>Entendi</button>
            </div>
        `;

        const texto = card.querySelector("#modalSenhaTexto");
        texto.append("Enviamos um link de confirmação para ");
        if (dados.email) {
            const destino = document.createElement("strong");
            destino.textContent = dados.email;
            texto.append(destino, ". ");
        } else {
            texto.append("o seu e-mail. ");
        }
        const minutos = Number(dados.expira_em_minutos) || 30;
        texto.append("O link vale por " + minutos + " minutos. Confira também a pasta de spam.");

        card.querySelectorAll("[data-fechar-senha]").forEach(function (el) {
            el.addEventListener("click", fechar);
        });
        card.querySelector(".btn").focus();
    }

    form.addEventListener("submit", async function (evento) {
        evento.preventDefault();
        if (enviando) return; // evita envio duplicado

        areaErro.hidden = true;
        const senhaAtual = inputSenha.value;

        if (senhaAtual === "") {
            mostrarErro("Informe a sua senha atual.");
            inputSenha.focus();
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
                body: JSON.stringify({ senha_atual: senhaAtual })
            });

            let dados = null;
            try {
                dados = await resposta.json();
            } catch (erroJson) {
                dados = null;
            }

            inputSenha.value = "";

            if (resposta.ok && dados && dados.ok === true) {
                definirCarregando(false);
                mostrarSucesso(dados);
                return;
            }

            definirCarregando(false);
            mostrarErro(
                dados && typeof dados.mensagem === "string" && dados.mensagem !== ""
                    ? dados.mensagem
                    : "Não foi possível processar sua solicitação. Tente novamente em instantes."
            );
            inputSenha.focus();
        } catch (erroRede) {
            definirCarregando(false);
            mostrarErro("Sem conexão com o servidor. Verifique sua internet e tente novamente.");
            inputSenha.focus();
        }
    });

    overlay.addEventListener("click", function (evento) {
        if (evento.target.closest("[data-fechar-senha]")) fechar();
    });
    document.addEventListener("keydown", aoPressionarTecla);

    inputSenha.focus();
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