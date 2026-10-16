// ==========================
// ALTERAR SENHA
// ==========================

const btnSenha = document.getElementById("btnSenha");

function abrirModalSenha(email) {
    // evita abrir duas vezes
    if (document.getElementById("modalSenha")) return;

    const overlay = document.createElement("div");
    overlay.id = "modalSenha";
    overlay.className = "modal-overlay";

    overlay.innerHTML = `
        <div class="modal-box" role="dialog" aria-modal="true">
            <button class="modal-fechar" aria-label="Fechar">&times;</button>
            <div class="modal-icone">✉️</div>
            <h2>Verifique seu e-mail</h2>
            <p>
                Enviamos um link para redefinir sua senha${email ? ` para <strong>${email}</strong>` : ""}.
                Confira sua caixa de entrada e a pasta de spam.
            </p>
            <button class="modal-ok">Entendi</button>
        </div>
    `;

    document.body.appendChild(overlay);

    const fechar = () => {
        overlay.remove();
        document.removeEventListener("keydown", onEsc);
    };
    const onEsc = (e) => { if (e.key === "Escape") fechar(); };

    overlay.querySelector(".modal-fechar").addEventListener("click", fechar);
    overlay.querySelector(".modal-ok").addEventListener("click", fechar);
    overlay.addEventListener("click", (e) => { if (e.target === overlay) fechar(); });
    document.addEventListener("keydown", onEsc);
}

if (btnSenha) {
    btnSenha.addEventListener("click", function () {
        // coloque aqui o e-mail do usuário, se tiver (ex: "usuario@email.com")
        abrirModalSenha("");
    });
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