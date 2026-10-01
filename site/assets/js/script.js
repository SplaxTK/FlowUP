const tema = document.getElementById("tema");
const cores = document.querySelectorAll('input[name="cor"]');
const btnCancelar = document.getElementById("btnCancelar");


// ==========================
// ALTERAR TEMA
// ==========================

if (tema) tema.addEventListener("change", function () {

    if (this.value === "escuro") {
        document.body.classList.add("escuro");
    } else {
        document.body.classList.remove("escuro");
    }

});


// ==========================
// ALTERAR COR
// ==========================

cores.forEach(function (radio) {

    radio.addEventListener("change", function () {

        const corSelecionada = this.value;

        document.body.dataset.cor = corSelecionada;

        const preview = document.querySelector(".preview");
        if (!preview) return;

        if (corSelecionada === "azul") {
            preview.style.setProperty("--cor", "#2563eb");
        }

        if (corSelecionada === "roxo") {
            preview.style.setProperty("--cor", "#7c3aed");
        }

        if (corSelecionada === "verde") {
            preview.style.setProperty("--cor", "#16a34a");
        }

        if (corSelecionada === "laranja") {
            preview.style.setProperty("--cor", "#ea580c");
        }

    });

});


// ==========================
// RESTAURAR
// ==========================

if (btnCancelar) btnCancelar.addEventListener("click", function () {

    const confirmar = confirm(
        "Deseja restaurar as configurações padrão?"
    );

    if (!confirmar) {
        return;
    }

    tema.value = "claro";

    document.body.classList.remove("escuro");

    document.querySelector(
        'input[name="cor"][value="azul"]'
    ).checked = true;

    document.querySelector(
        'select[name="densidade"]'
    ).value = "normal";

    document.querySelectorAll(
        '.switch-item input'
    ).forEach(function (input) {
        input.checked = true;
    });

});

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