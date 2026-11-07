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
        confirmCancel.focus();
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
            editTrigger = button;
            rewardId.value = button.dataset.recompensaId;
            deleteRewardId.value = button.dataset.recompensaId;
            description.value = button.dataset.descricao || '';
            points.value = button.dataset.pontos || '';
            originalValues = currentValues();
            modal.hidden = false;
            document.body.classList.add('modal-aberto');
            description.focus();
        });
    });

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

(function () {
    const toolbar = document.querySelector('.reward-toolbar');
    const grid = document.querySelector('[data-reward-grid]');
    if (!toolbar || !grid) return;

    const searchInput = toolbar.querySelector('[data-reward-search]');
    const filter = toolbar.querySelector('.reward-filter');
    const toggle = filter ? filter.querySelector('.filter-button') : null;
    const panel = filter ? filter.querySelector('.reward-filter-panel') : null;
    const resetButton = panel ? panel.querySelector('.reward-filter-reset') : null;
    const affordabilityFilters = panel
        ? Array.from(panel.querySelectorAll('[data-reward-affordability-filter]'))
        : [];
    const sortFilters = panel
        ? Array.from(panel.querySelectorAll('[data-reward-sort-filter]'))
        : [];
    const cards = Array.from(grid.querySelectorAll('[data-reward-card]'));
    const originalOrder = cards.slice();
    const emptyState = document.querySelector('[data-reward-filter-empty]');
    const resultCount = document.querySelector('[data-reward-count]');

    function normalizeText(value) {
        return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLocaleLowerCase('pt-BR');
    }

    function applyFilters() {
        const searchTerm = searchInput ? normalizeText(searchInput.value.trim()) : '';
        const selectedAffordability = new Set(affordabilityFilters.filter(function (input) {
            return input.checked;
        }).map(function (input) {
            return input.dataset.rewardAffordabilityFilter;
        }));
        let visibleCount = 0;

        cards.forEach(function (card) {
            const affordability = card.dataset.rewardAffordable === 'true' ? 'affordable' : 'saving';
            const matches = selectedAffordability.has(affordability)
                && normalizeText(card.textContent).includes(searchTerm);
            card.hidden = !matches;
            if (matches) visibleCount += 1;
        });

        const selectedSort = sortFilters.find(function (input) {
            return input.checked;
        });
        if (selectedSort) {
            const direction = selectedSort.dataset.rewardSortFilter === 'desc' ? -1 : 1;
            cards.slice().sort(function (a, b) {
                return (Number(a.dataset.rewardCost) - Number(b.dataset.rewardCost)) * direction;
            }).forEach(function (card) {
                grid.appendChild(card);
            });
        } else {
            originalOrder.forEach(function (card) {
                grid.appendChild(card);
            });
        }

        if (emptyState) emptyState.hidden = visibleCount > 0 || cards.length === 0;
        if (resultCount) {
            resultCount.textContent = visibleCount === cards.length
                ? visibleCount + ' disponíveis'
                : visibleCount + ' de ' + cards.length + ' disponíveis';
        }
    }

    function closeFilter() {
        if (!panel || !toggle) return;
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    }

    if (toggle && panel) {
        toggle.addEventListener('click', function () {
            panel.hidden = !panel.hidden;
            toggle.setAttribute('aria-expanded', String(!panel.hidden));
        });

        document.addEventListener('click', function (event) {
            if (filter && !filter.contains(event.target)) closeFilter();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                closeFilter();
                toggle.focus();
            }
        });
    }

    if (searchInput) searchInput.addEventListener('input', applyFilters);
    if (panel) panel.addEventListener('change', applyFilters);

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            if (searchInput) searchInput.value = '';
            affordabilityFilters.forEach(function (input) {
                input.checked = true;
            });
            sortFilters.forEach(function (input) {
                input.checked = false;
            });
            applyFilters();
        });
    }

    applyFilters();
}());