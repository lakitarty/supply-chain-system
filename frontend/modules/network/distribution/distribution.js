const distributionForm = document.getElementById("distributionForm");
const distributionMessage = document.getElementById("distributionMessage");
const productSearch = document.getElementById("productSearch");
const supplierDropdownButton = document.getElementById("supplierDropdownButton");
const supplierDropdownLabel = document.getElementById("supplierDropdownLabel");
const supplierPicker = document.getElementById("supplierDropdownPicker");
const productList = document.getElementById("distributionProductList");
const supplierList = document.getElementById("distributionSupplierList");
const selectAll = document.getElementById("distributionSelectAll");
const productCount = document.getElementById("productCount");
const selectedProductCount = document.getElementById("selectedProductCount");
const supplierCount = document.getElementById("supplierCount");
const areaSelect = document.getElementById("distributionArea");
const startDateInput = document.getElementById("distributionStartDate");
const endDateInput = document.getElementById("distributionEndDate");
const exclusiveInput = document.getElementById("distributionExclusive");
const saveButton = document.getElementById("saveAppointmentButton");
const refreshButton = document.getElementById("refreshDistributionButton");
const selectionSummary = document.getElementById("distributionSelectionSummary");
const appointmentsBody = document.getElementById("distributionAppointmentsBody");
const statusFilter = document.getElementById("appointmentStatusFilter");
const appointmentsSection = document.getElementById("appointmentsSection");
const viewAppointmentsButton = document.getElementById("viewAppointmentsButton");

let distributionData = {
    products: [],
    suppliers: [],
    areas: [],
    appointments: []
};

let selectedProductIds = new Set();
let selectedSupplierId = "";
let loading = false;

function setSupplierDropdownOpen(isOpen) {
    const open = Boolean(isOpen);

    supplierList.hidden = !open;
    supplierDropdownButton.setAttribute("aria-expanded", String(open));
}

function updateSupplierDropdownLabel() {
    const selectedSupplier = distributionData.suppliers.find(
        supplier => String(supplier.supplier_id) === selectedSupplierId
    );

    supplierDropdownLabel.textContent = selectedSupplier
        ? selectedSupplier.supplier_name
        : "Select supplier";

    supplierDropdownButton.setAttribute(
        "aria-label",
        selectedSupplier
            ? `Selected supplier: ${selectedSupplier.supplier_name}. Click to change supplier.`
            : "Select supplier"
    );

    supplierDropdownButton.classList.toggle("has-selection", Boolean(selectedSupplier));
}

function todayString() {
    const date = new Date();
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
    return date.toISOString().slice(0, 10);
}

function makeElement(tag, className, text) {
    const node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = String(text);
    return node;
}

function showMessage(message, type = "") {
    distributionMessage.textContent = message || "";
    distributionMessage.className = message
        ? `distribution-message ${type}`.trim()
        : "distribution-message";
}

async function fetchDistributionData() {
    const result = await getJson("network/distribution-list.php");
    const data = result?.data || {};

    if (!result?.ok || data.success !== true) {
        throw new Error(data.error || data.message || "Unable to load distribution data.");
    }

    distributionData = {
        products: data.products || [],
        suppliers: data.suppliers || [],
        areas: data.areas || [],
        appointments: data.appointments || []
    };

    const validIds = new Set(
        distributionData.products.map(product => String(product.product_id))
    );
    selectedProductIds = new Set(
        [...selectedProductIds].filter(id => validIds.has(id))
    );

    renderProducts();
    renderSuppliers();
    renderAreas();
    renderAppointments();
    updateSelectionSummary();
}

function renderProducts() {
    const query = productSearch.value.trim().toLocaleLowerCase();
    const products = distributionData.products.filter(product => {
        const name = String(product.product_name || "").toLocaleLowerCase();
        const category = String(product.category || "").toLocaleLowerCase();
        return name.includes(query) || category.includes(query);
    });

    productList.replaceChildren();
    productCount.textContent = `${distributionData.products.length} products`;

    if (!products.length) {
        productList.appendChild(
            makeElement("p", "distribution-empty", "No matching products.")
        );
    }

    products.forEach(product => {
        const id = String(product.product_id);
        const row = makeElement("label", "distribution-product-row");
        const checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.checked = selectedProductIds.has(id);
        checkbox.value = id;
        checkbox.setAttribute("aria-label", `Select ${product.product_name}`);

        const copy = makeElement("span", "distribution-product-copy");
        copy.appendChild(makeElement("strong", "", product.product_name));

        if (product.category) {
            copy.appendChild(makeElement("small", "", product.category));
        }

        row.append(checkbox, copy);
        checkbox.addEventListener("change", () => {
            if (checkbox.checked) {
                selectedProductIds.add(id);
            } else {
                selectedProductIds.delete(id);
            }
            updateSelectionSummary();
        });

        productList.appendChild(row);
    });

    const total = distributionData.products.length;
    selectAll.checked = total > 0 && selectedProductIds.size === total;
    selectAll.indeterminate =
        selectedProductIds.size > 0 && selectedProductIds.size < total;
}

function updateSelectionSummary() {
    const count = selectedProductIds.size;
    selectedProductCount.textContent = `${count} selected`;
    selectionSummary.textContent = count
        ? `${count} product${count === 1 ? "" : "s"} selected`
        : "No products selected";

    saveButton.disabled = count === 0 || loading;

    const total = distributionData.products.length;
    selectAll.checked = total > 0 && count === total;
    selectAll.indeterminate = count > 0 && count < total;
}

function renderSuppliers() {
    const suppliers = distributionData.suppliers;

    supplierList.replaceChildren();
    supplierCount.textContent = `${distributionData.suppliers.length} available`;

    if (!suppliers.length) {
        supplierList.appendChild(makeElement(
            "p",
            "distribution-empty",
            distributionData.suppliers.length
                ? "No suppliers match your search."
                : "No approved supplier relationships are available."
        ));
        return;
    }

    suppliers.forEach((supplier, index) => {
        const id = String(supplier.supplier_id);
        const row = makeElement("label", "distribution-supplier-row");
        const radio = document.createElement("input");
        radio.type = "radio";
        radio.name = "distributionSupplierChoice";
        radio.value = id;
        radio.checked = id === selectedSupplierId;
        radio.id = `distributionSupplier${index}`;

        const copy = makeElement("span", "distribution-supplier-copy");
        copy.appendChild(makeElement("strong", "", supplier.supplier_name));

        if (supplier.supplier_type === "MANUFACTURER_SUPPLIER") {
            copy.appendChild(makeElement("small", "", "Manufacturer and supplier"));
        } else {
            copy.appendChild(makeElement("small", "", "Approved supplier"));
        }

        row.append(radio, copy);

        radio.addEventListener("change", () => {
            if (radio.checked) {
                selectedSupplierId = id;
                updateSupplierDropdownLabel();
                setSupplierDropdownOpen(false);
            }
        });

        supplierList.appendChild(row);
    });
    updateSupplierDropdownLabel();
}

function renderAreas() {
    const previous = areaSelect.value;
    areaSelect.replaceChildren(new Option("Select a county", ""));

    distributionData.areas.forEach(area => {
        areaSelect.add(new Option(area.area_name, String(area.area_id)));
    });

    if (previous && [...areaSelect.options].some(option => option.value === previous)) {
        areaSelect.value = previous;
    }
}

function appointmentStatus(appointment) {
    if (String(appointment.status).toUpperCase() !== "ACTIVE") return "Ended";
    if (appointment.end_date && appointment.end_date < todayString()) return "Ended";
    if (appointment.start_date > todayString()) return "Scheduled";
    return "Running";
}

function renderAppointments() {
    appointmentsBody.replaceChildren();

    const all = distributionData.appointments;
    const filtered = all.filter(appointment => {
        const status = appointmentStatus(appointment);

        if (statusFilter.value === "running") return status === "Running";
        if (statusFilter.value === "scheduled") return status === "Scheduled";
        if (statusFilter.value === "ended") return status === "Ended";
        return true;
    });

    document.getElementById("appointmentTotalCount").textContent =
        `${all.length} total`;

    if (!filtered.length) {
        const row = document.createElement("tr");
        const cell = makeElement(
            "td",
            "distribution-empty",
            all.length
                ? "No appointments in this category."
                : "No appointments have been created yet."
        );
        cell.colSpan = 7;
        row.appendChild(cell);
        appointmentsBody.appendChild(row);
        return;
    }

    filtered.forEach(appointment => {
        const row = document.createElement("tr");
        const status = appointmentStatus(appointment);
        const dates = `${appointment.start_date}${
            appointment.end_date ? ` to ${appointment.end_date}` : " onward"
        }`;

        row.append(
            makeElement("td", "distribution-table-product", appointment.product_name),
            makeElement("td", "", appointment.supplier_name),
            makeElement("td", "", appointment.area_name || "County not assigned"),
            makeElement("td", "distribution-table-date", dates),
            makeElement("td", "", status),
            makeElement(
                "td",
                "",
                Number(appointment.is_exclusive) === 1 ? "Exclusive" : "Non-exclusive"
            )
        );

        const action = makeElement("td", "");

        if (status === "Running" || status === "Scheduled") {
            const button = makeElement(
                "button",
                "distribution-end-button",
                status === "Scheduled" ? "Cancel" : "End appointment"
            );
            button.type = "button";
            button.addEventListener("click", () => endAppointment(appointment));
            action.appendChild(button);
        } else {
            action.textContent = "—";
        }

        row.appendChild(action);
        appointmentsBody.appendChild(row);
    });
}

async function endAppointment(appointment) {
    const action = appointmentStatus(appointment) === "Scheduled"
        ? "cancel"
        : "end";

    const confirmed = window.confirm(
        `Are you sure you want to ${action} the appointment for ` +
        `${appointment.product_name} with ${appointment.supplier_name} in ` +
        `${appointment.area_name || "the unassigned county"}? The record will remain in history.`
    );

    if (!confirmed) return;

    showMessage("Updating appointment…", "info");

    try {
        const result = await postJson("network/distribution-end.php", {
            appointmentId: Number(appointment.product_supplier_id)
        });
        const data = result?.data || {};

        if (!result?.ok || data.success !== true) {
            throw new Error(data.error || data.message || "Unable to update appointment.");
        }

        await fetchDistributionData();
        showMessage(data.message || "Appointment updated.", "success");
    } catch (error) {
        showMessage(error.message || "Unable to update appointment.");
    }
}

async function createAppointments(event) {
    event.preventDefault();
    showMessage("");

    const chosenProducts = distributionData.products.filter(product =>
        selectedProductIds.has(String(product.product_id))
    );
    const areaId = areaSelect.value;
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;

    if (!chosenProducts.length) {
        showMessage("Select at least one product.");
        return;
    }

    if (!selectedSupplierId) {
        showMessage("Select an approved supplier from the right-hand panel.");
        return;
    }

    if (!areaId || !startDate) {
        showMessage("Select the county and effective start date.");
        return;
    }

    if (endDate && endDate < startDate) {
        showMessage("The end date cannot be earlier than the start date.");
        endDateInput.focus();
        return;
    }

    loading = true;
    saveButton.disabled = true;
    saveButton.textContent = "Creating appointments…";

    let created = 0;
    const failures = [];
    let coverageBlocked = false;

    try {
        for (const product of chosenProducts) {
            const result = await postJson("network/distribution-save.php", {
                productId: Number(product.product_id),
                supplierId: Number(selectedSupplierId),
                areaId: Number(areaId),
                startDate,
                endDate: endDate || null,
                isExclusive: exclusiveInput.checked
            });

            const data = result?.data || {};

            if (result?.ok && data.success === true) {
                created += 1;
                selectedProductIds.delete(String(product.product_id));
                continue;
            }

            const reason = data.error || data.message || "Could not create appointment.";
            failures.push(`${product.product_name}: ${reason}`);

            if (data.code === "SUPPLIER_COVERAGE_REQUIRED") {
                coverageBlocked = true;
                break;
            }
        }

        await fetchDistributionData();

        if (created && !failures.length) {
            showMessage(
                `Successfully created ${created} appointment${created === 1 ? "" : "s"}.`,
                "success"
            );
        } else if (created) {
            const explanation = coverageBlocked
                ? "The supplier must declare matching county coverage before more appointments can be created."
                : failures.slice(0, 2).join(" ");
            showMessage(`Created ${created}; ${failures.length} failed. ${explanation}`);
        } else {
            const explanation = coverageBlocked
                ? "The supplier has not declared coverage matching this county and appointment period. Ask the supplier to update its coverage first."
                : failures.slice(0, 3).join(" ");
            showMessage(explanation || "No appointments were created.");
        }

        updateSelectionSummary();

        if (created && appointmentsSection.hidden) {
            appointmentsSection.hidden = false;
            viewAppointmentsButton.setAttribute("aria-expanded", "true");
            viewAppointmentsButton.querySelector("span").textContent = "Hide appointments";
        }
    } catch (error) {
        showMessage(error.message || "Unable to contact the server.");
    } finally {
        loading = false;
        saveButton.textContent = "Create appointments";
        updateSelectionSummary();
    }
}

function initialiseDistribution() {
    const today = todayString();
    startDateInput.min = today;
    startDateInput.value = today;
    endDateInput.min = today;

    startDateInput.addEventListener("change", () => {
        endDateInput.min = startDateInput.value || todayString();
        if (endDateInput.value && endDateInput.value < startDateInput.value) {
            endDateInput.value = "";
        }
    });

    productSearch.addEventListener("input", renderProducts);
    setSupplierDropdownOpen(false);

    supplierDropdownButton.addEventListener("click", () => {
        setSupplierDropdownOpen(supplierList.hidden);
    });

    supplierDropdownButton.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            setSupplierDropdownOpen(false);
        }
    });

    supplierList.addEventListener("keydown", event => {
        if (event.key === "Escape") {
            setSupplierDropdownOpen(false);
            supplierDropdownButton.focus();
        }
    });

    document.addEventListener("click", event => {
        if (!supplierPicker.contains(event.target)) {
            setSupplierDropdownOpen(false);
        }
    });

    selectAll.addEventListener("change", () => {
        if (selectAll.checked) {
            distributionData.products.forEach(product => {
                selectedProductIds.add(String(product.product_id));
            });
        } else {
            selectedProductIds.clear();
        }

        renderProducts();
        updateSelectionSummary();
    });

    distributionForm.addEventListener("submit", createAppointments);
    statusFilter.addEventListener("change", renderAppointments);

    viewAppointmentsButton.addEventListener("click", () => {
        appointmentsSection.hidden = !appointmentsSection.hidden;
        const open = !appointmentsSection.hidden;
        viewAppointmentsButton.setAttribute("aria-expanded", String(open));
        viewAppointmentsButton.querySelector("span").textContent =
            open ? "Hide appointments" : "View appointments";

        if (open) {
            renderAppointments();
            appointmentsSection.scrollIntoView({ behavior: "smooth", block: "start" });
        }
    });

    refreshButton.addEventListener("click", async () => {
        refreshButton.disabled = true;
        try {
            await fetchDistributionData();
            showMessage("Distribution refreshed.", "success");
        } catch (error) {
            showMessage(error.message || "Unable to refresh distribution.");
        } finally {
            refreshButton.disabled = false;
        }
    });

    shellReady.then(async account => {
        if (!account) return;

        const validTypes = ["MANUFACTURER", "MANUFACTURER_SUPPLIER"];
        const permitted = account.user.role === "ADMIN" &&
            validTypes.includes(account.organization.organization_type);

        if (!permitted) {
            distributionForm.hidden = true;
            showMessage("Manufacturer organisation administrator access is required.");
            return;
        }

        try {
            await fetchDistributionData();
        } catch (error) {
            showMessage(error.message || "Unable to load distribution.");
        }
    });
}

initialiseDistribution();