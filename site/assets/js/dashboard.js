(function () {
    const dashboard = document.querySelector('.app-shell');
    const editDashboardButton = document.getElementById('editDashboardButton');

    if (!dashboard || !editDashboardButton) return;

    editDashboardButton.addEventListener('click', function () {
        const modoEdicao = editDashboardButton.getAttribute('aria-pressed') !== 'true';

        dashboard.classList.toggle('modo-edicao', modoEdicao);
        editDashboardButton.setAttribute('aria-pressed', String(modoEdicao));
        editDashboardButton.setAttribute(
            'aria-label',
            modoEdicao ? 'Sair do modo de edição' : 'Editar dashboard'
        );
        editDashboardButton.title = modoEdicao ? 'Sair do modo de edição' : 'Editar dashboard';
    });
})();
