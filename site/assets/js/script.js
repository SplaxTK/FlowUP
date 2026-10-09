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

const btnSair = document.getElementById("btnSair");
const logoutModal = document.getElementById("logoutModal");

function fecharLogoutModal() {
    if (!logoutModal) return;
    logoutModal.hidden = true;
    document.body.classList.remove("modal-aberto");
}

if (btnSair && logoutModal) btnSair.addEventListener("click", function () {
    logoutModal.hidden = false;
    document.body.classList.add("modal-aberto");
    logoutModal.querySelector("[data-close-logout]").focus();
});

if (logoutModal) {
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