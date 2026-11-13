(function () {
    const modal = document.getElementById('rewardEditModal');
    const confirmModal = document.getElementById('rewardConfirmModal');
    const editForm = document.getElementById('rewardEditForm');
    const deleteForm = document.getElementById('rewardDeleteForm');
    const editButtons = document.querySelectorAll('.reward-edit-button');
    const confirmTitle = document.getElementById('rewardConfirmTitle');
    const confirmMessage = document.getElementById('rewardConfirmMessage');
    const confirmCancel = document.getElementById('rewardConfirmCancel');
    const confirmAccept = document.getElementById('rewardConfirmAccept');
    const rewardId = document.getElementById('editRewardId');
    const description = document.getElementById('editRewardDescription');
    const points = document.getElementById('editRewardPoints');
    const deleteRewardId = document.getElementById('deleteRewardId');
    const deleteButton = document.getElementById('deleteRewardButton');
    const addButton = document.querySelector('.add-reward-button');
    const modalEyebrow = document.getElementById('rewardEditEyebrow');
    const modalTitle = document.getElementById('rewardEditTitle');
    const submitButton = document.getElementById('rewardFormSubmit');

    if (!modal || !confirmModal || !editForm || !deleteForm) return;

    let originalValues = null;
    let confirmAction = null;
    let pendingUrl = null;
    let closingEdit = false;
    let editTrigger = null;
    let confirmTrigger = null;

    function currentValues() {
        return {
            descricao: description.value.trim(),
            pontos: points.value
        };
    }

    function hasUnsavedChanges() {
        return originalValues !== null && JSON.stringify(currentValues()) !== JSON.stringify(originalValues);
    }

    function openConfirm(title, message, cancelText, acceptText, action, destructive, trigger) {
        confirmTitle.textContent = title;
        confirmMessage.textContent = message;
        confirmCancel.textContent = cancelText;
        confirmAccept.textContent = acceptText;
        confirmAccept.classList.toggle('task-confirm-danger', destructive);
        confirmTrigger = trigger || document.activeElement;
        confirmAction = action;
        confirmModal.hidden = false;
        confirmCancel.focus()
    }

    function openEditor(trigger, isEditing, reward) {
        if (isEditing) {
        rewardId.value = reward.id;
        } else {
        rewardId.value = '';
        }
       // rewardId.value = isEditing ? reward.id : '';
        deleteRewardId.value = isEditing ? reward.id : '';
        description.value = isEditing ? reward.description : '';
        points.value = isEditing ? reward.points : '';
        modalEyebrow.textContent = isEditing ? 'Atualize sua conquista' : 'Defina sua próxima conquista';
        modalTitle.textContent = isEditing ? 'Editar recompensa' : 'Adicionar recompensa';
        submitButton.name = isEditing ? 'editar_recompensa' : 'adicionar_recompensa';
        submitButton.textContent = isEditing ? 'Salvar alterações' : 'Adicionar recompensa';
        deleteButton.hidden = !isEditing;
        originalValues = currentValues();
        modal.hidden = false;
        document.body.classList.add('modal-aberto');
        description.focus();
    }

    function closeConfirm() {
        confirmModal.hidden = true;
        confirmAction = null;
        if (confirmTrigger) confirmTrigger.focus();
        confirmTrigger = null;
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
                'As alterações desta recompensa não foram salvas. Deseja sair mesmo assim?',
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
            openEditor(button, true, {
                id: button.dataset.recompensaId,
                description: button.dataset.descricao || '',
                points: button.dataset.pontos || ''
            });
        });
    });

    if (addButton) {
        addButton.addEventListener('click', function () {
            openEditor(addButton, false, {});
        });
    }

    modal.querySelectorAll('[data-close-reward-edit]').forEach(function (element) {
        element.addEventListener('click', requestEditorClose);
    });

    editForm.addEventListener('submit', function () {
        closingEdit = true;
    });

    deleteButton.addEventListener('click', function () {
        openConfirm(
            'Excluir recompensa?',
            'A recompensa será removida permanentemente. Deseja continuar?',
            'Cancelar',
            'Excluir recompensa',
            function () {
                closingEdit = true;
                modal.hidden = true;
                document.body.classList.remove('modal-aberto');
                deleteForm.requestSubmit();
            },
            true,
            deleteButton
        );
    });

    confirmCancel.addEventListener('click', closeConfirm);
    confirmAccept.addEventListener('click', function () {
        const action = confirmAction;
        confirmModal.hidden = true;
        confirmAction = null;
        confirmTrigger = null;
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
            'As alterações desta recompensa não foram salvas. Deseja sair mesmo assim?',
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
