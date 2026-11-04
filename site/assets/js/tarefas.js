(function () {
    const modal = document.getElementById('taskEditModal');
    const confirmModal = document.getElementById('taskConfirmModal');
    const editForm = document.getElementById('taskEditForm');
    const deleteForm = document.getElementById('taskDeleteForm');
    const editButtons = document.querySelectorAll('.task-edit-button');
    const confirmTitle = document.getElementById('taskConfirmTitle');
    const confirmMessage = document.getElementById('taskConfirmMessage');
    const confirmCancel = document.getElementById('taskConfirmCancel');
    const confirmAccept = document.getElementById('taskConfirmAccept');
    const taskId = document.getElementById('editTaskId');
    const description = document.getElementById('editTaskDescription');
    const type = document.getElementById('editTaskType');
    const points = document.getElementById('editTaskPoints');
    const deleteTaskId = document.getElementById('deleteTaskId');
    const deleteButton = document.getElementById('deleteTaskButton');

    if (!modal || !confirmModal || !editForm || !deleteForm) return;

    let originalValues = null;
    let confirmAction = null;
    let pendingUrl = null;
    let closingEdit = false;
    let editTrigger = null;

    function currentValues() {
        return {
            descricao: description.value.trim(),
            tipo: type.value,
            pontos: points.value
        };
    }

    function hasUnsavedChanges() {
        return originalValues !== null && JSON.stringify(currentValues()) !== JSON.stringify(originalValues);
    }

    function openConfirm(title, message, cancelText, acceptText, action, destructive) {
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        confirmCancel.textContent = cancelText;
        confirmAccept.textContent = acceptText;
        confirmAccept.classList.add('task-confirm-accept');
        confirmAccept.classList.toggle('btn-sair', destructive);
        confirmAccept.classList.toggle('task-confirm-danger', destructive);
        confirmAction = action;
        confirmModal.hidden = false;
        confirmCancel.focus();
    }

    function closeConfirm() {
        confirmModal.hidden = true;
        confirmAction = null;
    }

    function closeEditor() {
        closingEdit = true;
        modal.hidden = true;
        confirmModal.hidden = true;
        document.body.classList.remove('modal-aberto');
        closingEdit = false;
        if (editTrigger) editTrigger.focus();
    }

    function requestEditorClose() {
        if (hasUnsavedChanges()) {
            openConfirm(
                'Sair sem salvar?',
                'As alterações desta tarefa não foram salvas. Deseja sair mesmo assim?',
                'Continuar editando',
                'Sair sem salvar',
                closeEditor,
                false
            );
            return;
        }
        closeEditor();
    }

    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            editTrigger = button;
            taskId.value = button.dataset.tarefaId;
            deleteTaskId.value = button.dataset.tarefaId;
            description.value = button.dataset.descricao || '';
            type.value = button.dataset.tipo || 'diaria';
            points.value = button.dataset.pontos || '';
            originalValues = currentValues();
            modal.hidden = false;
            document.body.classList.add('modal-aberto');
            description.focus();
        });
    });

    modal.querySelectorAll('[data-close-task-edit]').forEach(function (element) {
        element.addEventListener('click', requestEditorClose);
    });

    editForm.addEventListener('submit', function () {
        closingEdit = true;
    });

    deleteButton.addEventListener('click', function () {
        openConfirm(
            'Excluir tarefa?',
            'A tarefa será removida permanentemente. Deseja continuar?',
            'Cancelar',
            'Excluir tarefa',
            function () {
                closingEdit = true;
                modal.hidden = true;
                document.body.classList.remove('modal-aberto');
                deleteForm.requestSubmit();
            },
            true
        );
    });

    confirmCancel.addEventListener('click', closeConfirm);
    confirmAccept.addEventListener('click', function () {
        const action = confirmAction;
        closeConfirm();
        if (action) action();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !confirmModal.hidden) {
            closeConfirm();
        } else if (event.key === 'Escape' && !modal.hidden) {
            requestEditorClose();
        }
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('a[href]');
        if (!link || modal.hidden || !hasUnsavedChanges() || event.defaultPrevented ||
            event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey ||
            (link.target && link.target !== '_self') || link.hasAttribute('download')) {
            return;
        }

        event.preventDefault();
        pendingUrl = link.href;
        openConfirm(
            'Sair sem salvar?',
            'As alterações desta tarefa não foram salvas. Deseja sair mesmo assim?',
            'Continuar editando',
            'Sair sem salvar',
            function () {
                closingEdit = true;
                window.location.assign(pendingUrl);
            },
            false
        );
    });

    window.addEventListener('beforeunload', function (event) {
        if (!closingEdit && !modal.hidden && hasUnsavedChanges()) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
