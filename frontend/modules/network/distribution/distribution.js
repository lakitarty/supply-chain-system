const distributionForm = document.getElementById('distributionForm');
const distributionMessage = document.getElementById('distributionMessage');
const productSelect = document.getElementById('distributionProduct');
const supplierSelect = document.getElementById('distributionSupplier');
const areaSelect = document.getElementById('distributionArea');
const startDateInput = document.getElementById('distributionStartDate');
const endDateInput = document.getElementById('distributionEndDate');
const exclusiveInput = document.getElementById('distributionExclusive');
const saveAppointmentButton = document.getElementById('saveAppointmentButton');
const refreshButton = document.getElementById('refreshDistributionButton');
const appointmentsBody = document.getElementById('distributionAppointmentsBody');

let distributionData = { products: [], suppliers: [], areas: [], appointments: [] };

function localDateString() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    return now.toISOString().slice(0, 10);
}

function distributionElement(tag, className, text) {
    const element = document.createElement(tag);
    if (className) element.className = className;
    if (text !== undefined && text !== null) element.textContent = String(text);
    return element;
}

function showDistributionMessage(message, type = 'error') {
    distributionMessage.textContent = message;
    distributionMessage.className = message ? `form-message ${type}` : 'form-message';
}

function replaceOptions(select, placeholder, items, valueKey, labelBuilder, previousValue) {
    select.replaceChildren(new Option(placeholder, ''));
    for (const item of items) {
        select.add(new Option(labelBuilder(item), String(item[valueKey])));
    }
    if (previousValue && [...select.options].some(option => option.value === previousValue)) {
        select.value = previousValue;
    }
}

function populateDistributionForm() {
    const productValue = productSelect.value;
    const supplierValue = supplierSelect.value;
    const areaValue = areaSelect.value;

    replaceOptions(
        productSelect,
        'Select your product',
        distributionData.products,
        'product_id',
        product => product.category ? `${product.product_name}  |  ${product.category}` : product.product_name,
        productValue
    );
    replaceOptions(
        supplierSelect,
        'Select a supplier',
        distributionData.suppliers,
        'supplier_id',
        supplier => `${supplier.supplier_name}${supplier.supplier_type === 'MANUFACTURER_SUPPLIER' ? '  |  Manufacturer and supplier' : ''}`,
        supplierValue
    );
    replaceOptions(
        areaSelect,
        'Select a county',
        distributionData.areas,
        'area_id',
        area => area.area_name,
        areaValue
    );
}

function dateStatus(appointment) {
    if (appointment.status !== 'ACTIVE') return 'Ended / cancelled';

    const today = localDateString();
    if (appointment.start_date > today) return 'Scheduled';
    if (appointment.end_date && appointment.end_date < today) return 'Expired';
    return 'Active';
}

function renderAppointments() {
    appointmentsBody.replaceChildren();

    if (distributionData.appointments.length === 0) {
        const row = distributionElement('tr');
        row.append(distributionElement('td', 'distribution-empty', 'No appointments recorded yet.'));
        row.firstElementChild.colSpan = 7;
        appointmentsBody.append(row);
        return;
    }

    for (const appointment of distributionData.appointments) {
        const row = distributionElement('tr');
        const dates = `${appointment.start_date}${appointment.end_date ? ` to ${appointment.end_date}` : ' onward'}`;
        const state = dateStatus(appointment);
        const effective = appointment.status === 'ACTIVE'
            && (!appointment.end_date || appointment.end_date >= localDateString());

        row.append(
            distributionElement('td', 'distribution-product-cell', appointment.product_name),
            distributionElement('td', '', appointment.supplier_name),
            distributionElement('td', appointment.area_id ? '' : 'distribution-legacy-area', appointment.area_name || 'County not assigned'),
            distributionElement('td', 'distribution-dates', dates),
            distributionElement('td', '', state),
            distributionElement('td', '', Number(appointment.is_exclusive) === 1 ? 'Exclusive' : 'Non-exclusive')
        );

        const actionCell = distributionElement('td', 'distribution-action-cell');
        if (effective) {
            const endButton = distributionElement('button', 'distribution-end-button', appointment.start_date > localDateString() ? 'Cancel' : 'End appointment');
            endButton.type = 'button';
            endButton.addEventListener('click', () => endAppointment(appointment));
            actionCell.append(endButton);
        } else {
            actionCell.append(distributionElement('span', 'distribution-no-action', '-'));
        }
        row.append(actionCell);
        appointmentsBody.append(row);
    }
}

async function loadDistributionData() {
    const previousProduct = productSelect.value;
    const previousSupplier = supplierSelect.value;
    const previousArea = areaSelect.value;

    const result = await getJson('network/distribution-list.php');
    if (!result.ok || result.data.success !== true) {
        throw new Error(result.data.error || 'Unable to load regional distribution.');
    }

    distributionData = {
        products: result.data.products || [],
        suppliers: result.data.suppliers || [],
        areas: result.data.areas || [],
        appointments: result.data.appointments || []
    };

    populateDistributionForm();
    if (previousProduct) productSelect.value = previousProduct;
    if (previousSupplier) supplierSelect.value = previousSupplier;
    if (previousArea) areaSelect.value = previousArea;
    renderAppointments();
}

async function endAppointment(appointment) {
    const question = appointment.start_date > localDateString()
        ? `Cancel the scheduled appointment for ${appointment.product_name} with ${appointment.supplier_name} in ${appointment.area_name || 'an unassigned county'}?`
        : `End the appointment for ${appointment.product_name} with ${appointment.supplier_name} in ${appointment.area_name || 'an unassigned county'}?`;

    if (!window.confirm(`${question}\n\nThe record will be retained in history.`)) return;

    showDistributionMessage('Updating appointmentâ€¦', '');
    try {
        const result = await postJson('network/distribution-end.php', {
            appointmentId: Number(appointment.product_supplier_id)
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || 'Unable to end the appointment.');
        }

        await loadDistributionData();
        showDistributionMessage(result.data.message || 'Appointment ended.', 'success');
    } catch (error) {
        showDistributionMessage(error.message || 'Unable to contact the server.');
    }
}

async function saveAppointment(event) {
    event.preventDefault();
    showDistributionMessage('', '');

    if (!productSelect.value || !supplierSelect.value || !areaSelect.value || !startDateInput.value) {
        showDistributionMessage('Choose a product, approved supplier, county and start date.');
        return;
    }

    if (endDateInput.value && endDateInput.value < startDateInput.value) {
        showDistributionMessage('The end date cannot be earlier than the start date.');
        endDateInput.focus();
        return;
    }

    saveAppointmentButton.disabled = true;
    saveAppointmentButton.textContent = 'Creating appointmentâ€¦';

    try {
        const result = await postJson('network/distribution-save.php', {
            productId: Number(productSelect.value),
            supplierId: Number(supplierSelect.value),
            areaId: Number(areaSelect.value),
            startDate: startDateInput.value,
            endDate: endDateInput.value || null,
            isExclusive: exclusiveInput.checked
        });

        if (!result.ok || result.data.success !== true) {
            throw new Error(result.data.error || 'Unable to create the appointment.');
        }

        await loadDistributionData();
        endDateInput.value = '';
        exclusiveInput.checked = false;
        showDistributionMessage(result.data.message || 'Regional appointment created.', 'success');
    } catch (error) {
        showDistributionMessage(error.message || 'Unable to contact the server.');
    } finally {
        saveAppointmentButton.disabled = false;
        saveAppointmentButton.textContent = 'Create appointment';
    }
}

function initialiseDistribution() {
    shellReady.then(async account => {
        if (!account) return;

        const allowedTypes = ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'];
        if (account.user.role !== 'ADMIN' || !allowedTypes.includes(account.organization.organization_type)) {
            distributionForm.hidden = true;
            showDistributionMessage('Manufacturer organisation administrator access is required.');
            appointmentsBody.replaceChildren();
            const row = distributionElement('tr');
            row.append(distributionElement('td', 'distribution-empty', 'You do not have permission to view distribution appointments.'));
            row.firstElementChild.colSpan = 7;
            appointmentsBody.append(row);
            return;
        }

        try {
            await loadDistributionData();
        } catch (error) {
            showDistributionMessage(error.message || 'Unable to load regional distribution.');
        }
    });
}

distributionForm.addEventListener('submit', saveAppointment);
refreshButton.addEventListener('click', async () => {
    refreshButton.disabled = true;
    try {
        await loadDistributionData();
        showDistributionMessage('Appointments refreshed.', 'success');
    } catch (error) {
        showDistributionMessage(error.message || 'Unable to refresh appointments.');
    } finally {
        refreshButton.disabled = false;
    }
});

initialiseDistribution();