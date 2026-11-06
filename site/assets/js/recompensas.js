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
