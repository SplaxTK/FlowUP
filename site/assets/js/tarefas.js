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

(function () {
    const filter = document.querySelector('.task-filter');
    if (!filter) return;

    const toggle = filter.querySelector('.filter-button');
    const panel = filter.querySelector('.task-filter-panel');
    const resetButton = filter.querySelector('.task-filter-reset');
    const list = document.querySelector('.task-list-column');
    const groupsContainer = list ? list.querySelector('.task-groups') : null;
    if (!toggle || !panel || !resetButton || !groupsContainer) return;

    const groups = Array.from(groupsContainer.querySelectorAll('[data-task-group]'));
    const rows = Array.from(groupsContainer.querySelectorAll('[data-task-row]'));
    const emptyState = list.querySelector('[data-task-filter-empty]');
    const searchInput = list.querySelector('[data-task-search]');
    const typeFilters = Array.from(panel.querySelectorAll('[data-task-type-filter]'));
    const completionFilters = Array.from(panel.querySelectorAll('[data-task-completion-filter]'));
    const sortFilters = Array.from(panel.querySelectorAll('[data-task-sort-filter]'));

    function applyFilters() {
        const searchTerm = searchInput ? normalizeText(searchInput.value.trim()) : '';
        const selectedTypes = new Set(typeFilters.filter(function (input) {
            return input.checked;
        }).map(function (input) {
            return input.dataset.taskTypeFilter;
        }));
        const selectedCompletions = new Set(completionFilters.filter(function (input) {
            return input.checked;
        }).map(function (input) {
            return input.dataset.taskCompletionFilter === 'complete';
        }));
        let visibleCount = 0;

        rows.forEach(function (row) {
            const visible = selectedTypes.has(row.dataset.taskType)
                && selectedCompletions.has(row.dataset.taskCompleted === 'true')
                && normalizeText(row.textContent).includes(searchTerm);
            row.hidden = !visible;
            if (visible) visibleCount += 1;
        });

        groups.forEach(function (group) {
            const allRows = group.querySelectorAll('[data-task-row]');
            const visibleRows = group.querySelectorAll('[data-task-row]:not([hidden])');
            group.hidden = !selectedTypes.has(group.dataset.taskType)
                || (allRows.length > 0 && visibleRows.length === 0);
            const count = group.querySelector('.task-count');
            if (count) count.textContent = String(visibleRows.length);
        });

        if (emptyState) emptyState.hidden = visibleCount > 0;

        const orderedGroups = groups.slice();
        const selectedSort = sortFilters.find(function (input) {
            return input.checked;
        });
        if (selectedSort) {
            orderedGroups.sort(function (a, b) {
                const resetDifference = Number(a.dataset.resetDays) - Number(b.dataset.resetDays);
                const order = selectedSort.dataset.taskSortFilter === 'far' ? -1 : 1;
                return resetDifference * order || groups.indexOf(a) - groups.indexOf(b);
            });
        }
        orderedGroups.forEach(function (group) {
            groupsContainer.appendChild(group);
        });
    }

    function normalizeText(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    }

    function closeFilter() {
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    toggle.addEventListener('click', function () {
        panel.hidden = !panel.hidden;
        toggle.setAttribute('aria-expanded', String(!panel.hidden));
    });

    panel.addEventListener('change', applyFilters);
    if (searchInput) searchInput.addEventListener('input', applyFilters);

    resetButton.addEventListener('click', function () {
        typeFilters.forEach(function (input) {
            input.checked = true;
        });
        completionFilters.forEach(function (input) {
            input.checked = true;
        });
        sortFilters.forEach(function (input) {
            input.checked = false;
        });
        applyFilters();
    });

    document.addEventListener('click', function (event) {
        if (!filter.contains(event.target)) closeFilter();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !panel.hidden) {
            closeFilter();
            toggle.focus();
        }
    });

    applyFilters();
}());

(function () {
    const groupsContainer = document.querySelector('.task-groups');
    const maximizeButton = document.querySelector('.maximize-button');
    if (!groupsContainer || !maximizeButton) return;

    const groups = Array.from(groupsContainer.querySelectorAll('[data-task-group]'));
    if (groups.length === 0) return;

    function setGroupCollapsed(group, collapsed) {
        const toggle = group.querySelector('.task-group-toggle');
        group.classList.toggle('is-collapsed', collapsed);
        if (toggle) {
            const label = collapsed ? 'Expandir tarefas desta categoria' : 'Recolher tarefas desta categoria';
            toggle.setAttribute('aria-expanded', String(!collapsed));
            toggle.setAttribute('aria-label', label);
            toggle.title = label;
        }
    }

    function syncMaximizeButton() {
        const allCollapsed = groups.every(function (group) {
            return group.classList.contains('is-collapsed');
        });
        const label = allCollapsed ? 'Expandir todas as categorias' : 'Recolher todas as categorias';
        maximizeButton.setAttribute('aria-pressed', String(allCollapsed));
        maximizeButton.setAttribute('aria-label', label);
        maximizeButton.title = label;
        return allCollapsed;
    }

    groupsContainer.addEventListener('click', function (event) {
        const toggle = event.target.closest('.task-group-toggle');
        if (!toggle || !groupsContainer.contains(toggle)) return;

        const group = toggle.closest('[data-task-group]');
        if (!group) return;
        setGroupCollapsed(group, !group.classList.contains('is-collapsed'));
        syncMaximizeButton();
    });

    maximizeButton.addEventListener('click', function () {
        const shouldCollapse = !syncMaximizeButton();
        groups.forEach(function (group) {
            setGroupCollapsed(group, shouldCollapse);
        });
        syncMaximizeButton();
    });

    syncMaximizeButton();
}());