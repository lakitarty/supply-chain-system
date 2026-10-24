(() => {
    const field = id => document.getElementById(id);
    const page = field('coveragePage');
    const message = field('coverageMessage');
    const form = field('coverageForm');
    const areaSelect = field('areaId');
    const startInput = field('startDate');
    const endInput = field('endDate');
    const saveButton = field('saveCoverageButton');
    const refreshButton = field('refreshCoverageButton');
    const tableBody = field('coverageTableBody');
    const emptyMessage = field('coverageEmpty');
    const countLabel = field('coverageCount');

    let coverageRecords = [];
    let areas = [];
    let saving = false;

    function localDateString() {
        const date = new Date();
        date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
        return date.toISOString().slice(0, 10);
    }

    function node(tag, className, text) {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (text !== undefined && text !== null) element.textContent = String(text);
        return element;
    }

    function showMessage(text, type = '') {
        message.textContent = text;
        message.className = `form-message${type ? ` ${type}` : ''}`;
    }

    function setSaving(value) {
        saving = value;
        saveButton.disabled = value;
        saveButton.textContent = value ? 'Saving...' : 'Save coverage';
        refreshButton.disabled = value;
    }

    function readableDate(value) {
        if (!value) return 'No planned end date';
        return String(value).slice(0, 10);
    }

    function populateAreas() {
        areaSelect.replaceChildren(new Option('Select a county', ''));
        areas.forEach(area => areaSelect.add(new Option(area.area_name, String(area.area_id))));
        areaSelect.disabled = areas.length === 0;
    }

    function renderCoverage() {
        tableBody.replaceChildren();
        countLabel.textContent = `${coverageRecords.length} record${coverageRecords.length === 1 ? '' : 's'}`;
        emptyMessage.hidden = coverageRecords.length > 0;

        coverageRecords.forEach(item => {
            const row = document.createElement('tr');
            row.append(
                node('td', 'coverage-county-cell', item.area_name || 'Unknown county'),
                node('td', '', readableDate(item.start_date)),
                node('td', '', readableDate(item.end_date)),
                node('td', '', item.status === 'ACTIVE' ? 'Active' : 'Inactive')
            );

            const actionCell = node('td', 'coverage-action-cell');
            if (item.status === 'ACTIVE') {
                const endButton = node('button', 'secondary-button coverage-end-button', 'End coverage');
                endButton.type = 'button';
                endButton.addEventListener('click', () => endCoverage(item));
                actionCell.append(endButton);
            } else {
                actionCell.textContent = 'History retained';
            }

            row.append(actionCell);
            tableBody.append(row);
        });
    }

    async function loadCoverage(clearMessage = true) {
        refreshButton.disabled = true;
        try {
            const result = await getJson('network/coverage-list.php');
            if (!result.ok || result.data?.success !== true) {
                throw new Error(result.data?.error || 'Unable to load supplier coverage.');
            }

            areas = Array.isArray(result.data.areas) ? result.data.areas : [];
            coverageRecords = Array.isArray(result.data.coverage) ? result.data.coverage : [];
            populateAreas();
            renderCoverage();
            if (clearMessage) showMessage('', '');
            return true;
        } catch (error) {
            showMessage(error.message || 'Unable to load supplier coverage.', 'error');
            return false;
        } finally {
            refreshButton.disabled = false;
        }
    }

    async function saveCoverage(event) {
        event.preventDefault();
        showMessage('');

        const areaId = areaSelect.value;
        const startDate = startInput.value;
        const endDate = endInput.value;
        const today = localDateString();

        if (!areaId) {
            showMessage('Choose a county.', 'error');
            areaSelect.focus();
            return;
        }
        if (!startDate || startDate > today) {
            showMessage('Choose a start date that is today or earlier.', 'error');
            startInput.focus();
            return;
        }
        if (endDate && endDate < today) {
            showMessage('End date must be today or later.', 'error');
            endInput.focus();
            return;
        }
        if (endDate && endDate < startDate) {
            showMessage('End date cannot be earlier than the start date.', 'error');
            endInput.focus();
            return;
        }

        setSaving(true);
        try {
            const result = await postJson('network/coverage-save.php', {
                areaId: Number(areaId),
                startDate,
                endDate
            });

            if (!result.ok || result.data?.success !== true) {
                throw new Error(result.data?.error || 'Unable to save supplier coverage.');
            }

            form.reset();
            startInput.value = today;
            const refreshed = await loadCoverage(false);
            if (refreshed) showMessage(result.data.message || 'Supplier coverage saved.', 'success');
        } catch (error) {
            showMessage(error.message || 'Unable to save supplier coverage.', 'error');
        } finally {
            setSaving(false);
        }
    }

    async function endCoverage(item) {
        if (item.status !== 'ACTIVE') return;

        const confirmed = window.confirm(
            `End supplier coverage for ${item.area_name}? The record will remain in history.`
        );
        if (!confirmed) return;

        refreshButton.disabled = true;
        try {
            const result = await postJson('network/coverage-end.php', {
                coverageId: Number(item.supplier_area_id)
            });
            if (!result.ok || result.data?.success !== true) {
                throw new Error(result.data?.error || 'Unable to end supplier coverage.');
            }

            const refreshed = await loadCoverage(false);
            if (refreshed) showMessage(result.data.message || 'Supplier coverage ended.', 'success');
        } catch (error) {
            showMessage(error.message || 'Unable to end supplier coverage.', 'error');
        } finally {
            refreshButton.disabled = false;
        }
    }

    function initialiseDateFields() {
        const today = localDateString();
        startInput.value = today;
        startInput.max = today;
        endInput.min = today;
    }

    form.addEventListener('submit', saveCoverage);
    refreshButton.addEventListener('click', loadCoverage);

    shellReady.then(async account => {
        if (!account) return;

        const allowedTypes = ['SUPPLIER', 'MANUFACTURER_SUPPLIER'];
        if (account.user?.role !== 'ADMIN' || !allowedTypes.includes(account.organization?.organization_type)) {
            form.hidden = true;
            refreshButton.hidden = true;
            showMessage('Supplier organisation administrator access is required.', 'error');
            return;
        }

        initialiseDateFields();
        await loadCoverage();
    }).catch(() => {
        if (page) page.hidden = false;
        showMessage('Unable to initialise supplier coverage.', 'error');
    });
})();