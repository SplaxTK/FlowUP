// ==========================
// ALTERAR SENHA
// ==========================

const btnSenha = document.getElementById("btnSenha");

if (btnSenha) btnSenha.addEventListener("click", function () {

    const novaSenha = prompt(
        "Digite sua nova senha:"
    );

    if (novaSenha === null) {
        return;
    }

    if (novaSenha.length < 6) {

        alert(
            "A senha deve possuir pelo menos 6 caracteres."
        );

        return;
    }

    alert(
        "Senha alterada com sucesso!"
    );

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