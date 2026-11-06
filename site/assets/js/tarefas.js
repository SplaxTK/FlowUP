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
