(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = { account: null, versions: [], classifications: [], rates: [], busy: false };
    const labels = {
        STANDARD_RATED: "Standard-rated",
        ZERO_RATED: "Zero-rated",
        EXEMPT: "Exempt"
    };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function setStatus(message, kind = "") {
        const node = byId("taxStatus");
        node.textContent = message || "";
        node.className = "tax-status";
        if (kind) node.classList.add(`is-${kind}`);
    }

    function localToday() {
        const now = new Date();
        const pad = value => String(value).padStart(2, "0");
        return `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`;
    }

    function unwrap(response, fallback) {
        const data = response && response.data;
        if (!response || !response.ok || !data || data.success !== true) {
            const message = data && (data.error || data.message);
            throw new Error(typeof message === "string" && message.trim() ? message : fallback);
        }
        return data;
    }

    function versionLabel(version) {
        const pieces = [`Version ${version.version_number}`];
        if (version.pack_size) pieces.push(version.pack_size);
        if (version.barcode) pieces.push(`Barcode ${version.barcode}`);
        else pieces.push("No barcode");
        if (Number(version.is_current) === 1) pieces.push("Current");
        return pieces.join(" | ");
    }

    function populateProducts(selectedProductId, selectedVersionId) {
        const productSelect = byId("taxProduct");
        const productMap = new Map();
        for (const version of state.versions) {
            if (!productMap.has(String(version.product_id))) {
                productMap.set(String(version.product_id), version.product_name);
            }
        }

        productSelect.replaceChildren(new Option("Select a product", ""));
        for (const [id, name] of productMap.entries()) {
            productSelect.add(new Option(name, id));
        }

        const preferredProduct = selectedProductId || productSelect.options[1]?.value || "";
        if (preferredProduct) productSelect.value = preferredProduct;
        populateVersions(selectedVersionId);
    }

    function populateVersions(selectedVersionId) {
        const productId = byId("taxProduct").value;
        const versionSelect = byId("taxVersion");
        versionSelect.replaceChildren(new Option("Select a package version", ""));

        const matching = state.versions.filter(version => String(version.product_id) === String(productId));
        for (const version of matching) {
            versionSelect.add(new Option(versionLabel(version), String(version.product_version_id)));
        }

        const preferred = selectedVersionId || matching.find(version => Number(version.is_current) === 1)?.product_version_id || matching[0]?.product_version_id;
        if (preferred && Array.from(versionSelect.options).some(option => option.value === String(preferred))) {
            versionSelect.value = String(preferred);
        }
    }

    function periodStatus(row) {
        const today = localToday();
        if (row.effective_from > today) return { label: "Scheduled", css: "is-scheduled" };
        if (row.effective_to && row.effective_to < today) return { label: "Ended", css: "is-ended" };
        return { label: "Effective", css: "is-current" };
    }

    function renderClassifications() {
        const body = byId("taxRows");
        if (!state.classifications.length) {
            body.innerHTML = '<tr><td colspan="6" class="tax-empty">No tax classifications have been declared. Products remain unclassified until a manufacturer records one.</td></tr>';
            return;
        }

        body.innerHTML = state.classifications.map(row => {
            const status = periodStatus(row);
            const packageText = [row.pack_size, row.barcode ? `Barcode ${row.barcode}` : "No barcode"].filter(Boolean).join(" | ");
            const rateText = row.rate_percent_at_start === null || row.rate_percent_at_start === "" ? "Rate not configured" : `${escapeHtml(row.rate_percent_at_start)}%`;
            const end = row.effective_to || "No planned end";
            return `<tr>
                <td><strong>${escapeHtml(row.product_name)}</strong><small>Version ${escapeHtml(row.version_number)}${packageText ? ` | ${escapeHtml(packageText)}` : ""}</small></td>
                <td><span class="tax-category">${escapeHtml(labels[row.tax_category] || row.tax_category)}</span></td>
                <td>${rateText}<small>${Number(row.taxable_supply_at_start) === 0 ? "Not a taxable supply" : row.taxable_supply_at_start === null ? "Schedule not available" : "Taxable supply"}</small></td>
                <td>${escapeHtml(row.effective_from)}<small>to ${escapeHtml(end)}</small></td>
                <td><span class="tax-period-status ${status.css}">${status.label}</span></td>
                <td>${escapeHtml(row.declaration_note || "No note")}</td>
            </tr>`;
        }).join("");
    }

    function renderRates() {
        const body = byId("taxRateRows");
        if (!state.rates.length) {
            body.innerHTML = '<tr><td colspan="5" class="tax-empty">No tax-rate schedule entries were found.</td></tr>';
            return;
        }
        body.innerHTML = state.rates.map(row => `<tr>
            <td>${escapeHtml(labels[row.tax_category] || row.tax_category)}</td>
            <td>${escapeHtml(row.rate_percent)}%</td>
            <td>${Number(row.taxable_supply) === 1 ? "Yes" : "No"}</td>
            <td>${escapeHtml(row.effective_from)}</td>
            <td>${escapeHtml(row.effective_to || "No planned end")}</td>
        </tr>`).join("");
    }

    function updateRateHint() {
        const category = byId("taxCategory").value;
        const date = byId("effectiveFrom").value;
        if (!category || !date) {
            byId("taxRateHint").textContent = "Rates come from the configured effective-dated tax schedule.";
            return;
        }
        const match = state.rates
            .filter(row => row.tax_category === category && row.effective_from <= date && (!row.effective_to || row.effective_to >= date))
            .sort((a, b) => b.effective_from.localeCompare(a.effective_from))[0];
        byId("taxRateHint").textContent = match
            ? `${labels[category]}: ${match.rate_percent}% in the configured schedule from ${match.effective_from}. ${Number(match.taxable_supply) === 0 ? "Exempt is not the same as zero-rated." : "Confirm the category is legally applicable to this product."}`
            : "No rate is configured for this category on that date. The declaration cannot be saved until the schedule covers it.";
    }

    async function loadData(preferredProductId, preferredVersionId) {
        const response = await getJson("products/tax-classifications-list.php");
        const data = unwrap(response, "Unable to load product tax classifications.");
        state.versions = Array.isArray(data.versions) ? data.versions : [];
        state.classifications = Array.isArray(data.classifications) ? data.classifications : [];
        state.rates = Array.isArray(data.tax_rates) ? data.tax_rates : [];
        populateProducts(preferredProductId, preferredVersionId);
        renderClassifications();
        renderRates();
        updateRateHint();
    }

    function clearForm() {
        byId("taxForm").reset();
        byId("effectiveFrom").value = localToday();
        const firstProduct = state.versions[0];
        if (firstProduct) {
            byId("taxProduct").value = String(firstProduct.product_id);
            populateVersions();
        } else {
            populateProducts();
        }
        updateRateHint();
    }

    async function saveDeclaration(event) {
        event.preventDefault();
        if (state.busy) return;

        const versionId = byId("taxVersion").value;
        const category = byId("taxCategory").value;
        const effectiveFrom = byId("effectiveFrom").value;
        const effectiveTo = byId("effectiveTo").value;
        const note = byId("declarationNote").value.trim();

        if (!versionId || !category || !effectiveFrom) {
            setStatus("Select a product version, tax category, and effective-from date.", "error");
            return;
        }
        if (effectiveTo && effectiveTo < effectiveFrom) {
            setStatus("The effective-to date cannot be earlier than the effective-from date.", "error");
            return;
        }

        state.busy = true;
        byId("saveTax").disabled = true;
        byId("saveTax").textContent = "Saving declaration...";
        setStatus("Saving tax declaration...", "");

        try {
            const response = await postJson("products/tax-classification-save.php", {
                productVersionId: Number(versionId),
                taxCategory: category,
                effectiveFrom,
                effectiveTo,
                declarationNote: note,
                closePrevious: byId("closePrevious").checked
            });
            const data = unwrap(response, "Unable to save the tax declaration.");
            await loadData(byId("taxProduct").value, versionId);
            byId("taxCategory").value = "";
            byId("effectiveFrom").value = localToday();
            byId("effectiveTo").value = "";
            byId("declarationNote").value = "";
            byId("closePrevious").checked = false;
            updateRateHint();
            setStatus(data.message || "Product tax classification saved.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to save the tax declaration.", "error");
        } finally {
            state.busy = false;
            byId("saveTax").disabled = false;
            byId("saveTax").textContent = "Save tax declaration";
        }
    }

    async function initialise() {
        try {
            state.account = await shellReady;
            if (!state.account) return;

            const organizationType = state.account.organization.organization_type;
            const role = state.account.user.role;
            const allowed = ["MANUFACTURER", "MANUFACTURER_SUPPLIER"].includes(organizationType) && role === "ADMIN";

            if (!allowed) {
                byId("taxApp").hidden = true;
                byId("refreshTax").disabled = true;
                setStatus("Product tax declarations are available only to manufacturer organisation administrators.", "error");
                return;
            }

            byId("taxApp").hidden = false;
            byId("effectiveFrom").value = localToday();
            await loadData();
            setStatus("Product versions and tax declaration history loaded.");
        } catch (error) {
            setStatus(error.message || "Unable to initialise the tax classification page.", "error");
        }
    }

    byId("taxProduct").addEventListener("change", () => populateVersions());
    byId("taxCategory").addEventListener("change", updateRateHint);
    byId("effectiveFrom").addEventListener("change", updateRateHint);
    byId("taxForm").addEventListener("submit", saveDeclaration);
    byId("clearTax").addEventListener("click", clearForm);
    byId("refreshTax").addEventListener("click", async () => {
        try {
            await loadData(byId("taxProduct").value, byId("taxVersion").value);
            setStatus("Tax declarations refreshed.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to refresh tax declarations.", "error");
        }
    });

    initialise();
}());
