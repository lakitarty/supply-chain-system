(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = { account: null, eligibilities: [], offers: [], busy: false };
    const categoryLabels = { VAT_INCLUSIVE: "VAT inclusive", VAT_EXCLUSIVE: "VAT exclusive" };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function localToday() {
        const date = new Date();
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
    }

    function unwrap(response, fallback) {
        const data = response && response.data;
        if (!response || !response.ok || !data || data.success !== true) {
            const message = data && (data.error || data.message || data.warning);
            throw new Error(typeof message === "string" && message.trim() ? message : fallback);
        }
        return data;
    }

    function setStatus(message, kind) {
        const node = byId("offersStatus");
        node.textContent = message || "";
        node.classList.toggle("is-error", kind === "error");
        node.classList.toggle("is-success", kind === "success");
    }

    function money(value) {
        const amount = Number(value);
        return new Intl.NumberFormat("en-KE", { style: "currency", currency: "KES", minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number.isFinite(amount) ? amount : 0);
    }

    function versionLabel(item) {
        const pack = item.pack_size || item.packaging_description || "Package unspecified";
        const barcode = item.barcode ? `Barcode ${item.barcode}` : "No barcode";
        return `${item.product_name} | Version ${item.version_number} | ${pack} | ${barcode} | ${item.manufacturer_name}`;
    }

    function populateVersions(selectedValue) {
        const select = byId("offerVersion");
        const unique = new Map();
        state.eligibilities.forEach(item => {
            const key = String(item.product_version_id);
            if (!unique.has(key)) unique.set(key, item);
        });
        select.replaceChildren(new Option(unique.size ? "Select product package version" : "No authorised product appointments found", ""));
        for (const [id, item] of unique.entries()) select.add(new Option(versionLabel(item), id));
        if (selectedValue && Array.from(select.options).some(option => option.value === String(selectedValue))) select.value = String(selectedValue);
        populateAreas();
        select.disabled = unique.size === 0;
    }

    function populateAreas() {
        const versionId = byId("offerVersion").value;
        const select = byId("offerArea");
        const records = state.eligibilities.filter(item => String(item.product_version_id) === String(versionId));
        const uniqueAreas = new Map();
        records.forEach(item => {
            const key = String(item.area_id);
            if (!uniqueAreas.has(key)) uniqueAreas.set(key, item.area_name);
        });
        select.replaceChildren(new Option(uniqueAreas.size ? "Select authorised county" : "No county appointment for this version", ""));
        for (const [id, name] of uniqueAreas.entries()) select.add(new Option(name, id));
        select.disabled = uniqueAreas.size === 0;
    }

    function offerStatus(row) {
        const today = localToday();
        if (row.status !== "ACTIVE") return "Inactive";
        if (row.effective_from > today) return "Scheduled";
        if (row.effective_to && row.effective_to < today) return "Ended";
        return "Effective";
    }

    function renderOffers() {
        const body = byId("offerRows");
        if (!state.offers.length) {
            body.innerHTML = '<tr><td colspan="5" class="pricing-empty">No supplier offers have been recorded yet.</td></tr>';
            return;
        }
        body.innerHTML = state.offers.map(row => {
            const packageName = row.pack_size || row.packaging_description || "Package unspecified";
            const status = offerStatus(row);
            const canEnd = row.status === "ACTIVE" && !row.effective_to;
            const start = String(row.effective_from || "");
            const defaultEnd = start > localToday() ? start : localToday();
            const actions = canEnd
                ? `<div class="pricing-end-controls"><input class="pricing-end-date" type="date" data-end-date-for="${escapeHtml(row.supplier_product_offer_id)}" min="${escapeHtml(start)}" value="${escapeHtml(defaultEnd)}" aria-label="Offer end date"><button class="pricing-end-button" type="button" data-end-offer="${escapeHtml(row.supplier_product_offer_id)}">Save end date</button></div>`
                : (row.effective_to ? escapeHtml(row.effective_to) : "No action");
            return `<tr>
                <td><strong>${escapeHtml(row.product_name)}</strong><small>${escapeHtml(row.manufacturer_name)} | Version ${escapeHtml(row.version_number)} | ${escapeHtml(packageName)}${row.barcode ? ` | ${escapeHtml(row.barcode)}` : ""}</small><small>${escapeHtml(row.area_name)}${row.offer_note ? ` | ${escapeHtml(row.offer_note)}` : ""}</small></td>
                <td><strong>${escapeHtml(money(row.unit_price))}</strong><small>${escapeHtml(categoryLabels[row.price_basis] || row.price_basis)}</small></td>
                <td class="pricing-period">${escapeHtml(row.effective_from)}<small>to ${escapeHtml(row.effective_to || "No planned end")}</small></td>
                <td><span class="pricing-chip">${escapeHtml(status)}</span></td>
                <td>${actions}</td>
            </tr>`;
        }).join("");
    }

    async function loadData(preferredVersion) {
        const response = await getJson("network/supplier-offers-list.php");
        const data = unwrap(response, "Unable to load supplier offers.");
        state.eligibilities = Array.isArray(data.eligibilities) ? data.eligibilities : [];
        state.offers = Array.isArray(data.offers) ? data.offers : [];
        populateVersions(preferredVersion);
        renderOffers();
    }

    function clearForm() {
        byId("offerForm").reset();
        byId("offerFrom").value = localToday();
        populateVersions();
    }

    async function saveOffer(event) {
        event.preventDefault();
        if (state.busy) return;
        const versionId = byId("offerVersion").value;
        const eligibility = state.eligibilities.find(item => String(item.product_version_id) === String(versionId));
        const areaId = byId("offerArea").value;
        const effectiveFrom = byId("offerFrom").value;
        const effectiveTo = byId("offerTo").value;
        if (!versionId || !eligibility || !areaId || !effectiveFrom || !byId("offerPrice").value) {
            setStatus("Select a product version and authorised county, then enter a price and start date.", "error");
            return;
        }
        if (effectiveTo && effectiveTo < effectiveFrom) {
            setStatus("The offer end date cannot be earlier than its start date.", "error");
            return;
        }
        state.busy = true;
        byId("saveOffer").disabled = true;
        byId("saveOffer").textContent = "Publishing offer...";
        setStatus("Saving supplier offer...", "");
        try {
            const response = await postJson("network/supplier-offer-save.php", {
                productId: Number(eligibility.product_id),
                productVersionId: Number(versionId),
                areaId: Number(areaId),
                unitPrice: byId("offerPrice").value,
                priceBasis: byId("offerBasis").value,
                effectiveFrom,
                effectiveTo,
                offerNote: byId("offerNote").value.trim()
            });
            const data = unwrap(response, "Unable to save supplier offer.");
            clearForm();
            await loadData(versionId);
            setStatus(data.message || "Supplier offer saved.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to save supplier offer.", "error");
        } finally {
            state.busy = false;
            byId("saveOffer").disabled = false;
            byId("saveOffer").textContent = "Publish offer";
        }
    }

    async function endOffer(button) {
        if (state.busy) return;
        const offerId = button.dataset.endOffer;
        const dateInput = document.querySelector(`[data-end-date-for="${offerId}"]`);
        if (!dateInput || !dateInput.value) {
            setStatus("Choose an end date for this offer.", "error");
            return;
        }
        state.busy = true;
        setStatus("Saving offer end date...", "");
        try {
            const response = await postJson("network/supplier-offer-end.php", { offerId: Number(offerId), effectiveTo: dateInput.value });
            const data = unwrap(response, "Unable to end supplier offer.");
            await loadData(byId("offerVersion").value);
            setStatus(data.message || "Offer end date saved.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to end supplier offer.", "error");
        } finally {
            state.busy = false;
        }
    }

    async function initialise() {
        try {
            state.account = await shellReady;
            if (!state.account) return;
            const type = state.account.organization.organization_type;
            const role = state.account.user.role;
            const allowed = ["SUPPLIER", "MANUFACTURER_SUPPLIER"].includes(type) && role === "ADMIN";
            if (!allowed) {
                byId("offerApp").hidden = true;
                byId("refreshOffers").disabled = true;
                setStatus("Supplier offers are available only to supplier organisation administrators.", "error");
                return;
            }
            byId("offerApp").hidden = false;
            byId("offerFrom").value = localToday();
            await loadData();
            setStatus("Supplier offer history and authorised product appointments loaded.");
        } catch (error) {
            setStatus(error.message || "Unable to initialise supplier offers.", "error");
        }
    }

    byId("offerVersion").addEventListener("change", populateAreas);
    byId("offerFrom").addEventListener("change", () => {
        byId("offerTo").min = byId("offerFrom").value;
        if (byId("offerTo").value && byId("offerTo").value < byId("offerFrom").value) byId("offerTo").value = "";
    });
    byId("offerForm").addEventListener("submit", saveOffer);
    byId("clearOffer").addEventListener("click", clearForm);
    byId("refreshOffers").addEventListener("click", async () => {
        try {
            await loadData(byId("offerVersion").value);
            setStatus("Supplier offers refreshed.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to refresh supplier offers.", "error");
        }
    });
    byId("offerRows").addEventListener("click", event => {
        const button = event.target.closest("[data-end-offer]");
        if (button) endOffer(button);
    });
    initialise();
}());
