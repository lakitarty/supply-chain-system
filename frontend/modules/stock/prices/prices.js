(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = { account: null, branches: [], versions: [], prices: [], busy: false };
    const basisLabels = { VAT_INCLUSIVE: "VAT inclusive", VAT_EXCLUSIVE: "VAT exclusive" };

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
        const node = byId("pricesStatus");
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

    function populateBranches(selectedValue) {
        const select = byId("priceBranch");
        select.replaceChildren(new Option(state.branches.length ? "Select branch" : "No active branches found", ""));
        state.branches.forEach(branch => {
            const area = branch.area_name ? ` | ${branch.area_name}` : " | County not assigned";
            const suffix = Number(branch.is_main_branch) === 1 ? " (Main branch)" : "";
            select.add(new Option(`${branch.branch_name}${suffix}${area}`, String(branch.branch_id)));
        });
        if (selectedValue && Array.from(select.options).some(option => option.value === String(selectedValue))) select.value = String(selectedValue);
        if (!select.value && state.branches.length) select.value = String(state.branches[0].branch_id);
        select.disabled = state.branches.length <= 1;
    }

    function populateVersions(selectedValue) {
        const select = byId("priceVersion");
        select.replaceChildren(new Option(state.versions.length ? "Select product package version" : "No visible manufacturer products found", ""));
        state.versions.forEach(item => select.add(new Option(versionLabel(item), String(item.product_version_id))));
        if (selectedValue && Array.from(select.options).some(option => option.value === String(selectedValue))) select.value = String(selectedValue);
        if (!select.value && state.versions.length) {
            const current = state.versions.find(item => Number(item.is_current) === 1);
            select.value = String((current || state.versions[0]).product_version_id);
        }
        select.disabled = state.versions.length === 0;
    }

    function priceStatus(row) {
        const today = localToday();
        if (row.status !== "ACTIVE") return "Inactive";
        if (row.effective_from > today) return "Scheduled";
        if (row.effective_to && row.effective_to < today) return "Ended";
        return "Effective";
    }

    function renderPrices() {
        const body = byId("priceRows");
        if (!state.prices.length) {
            body.innerHTML = '<tr><td colspan="6" class="pricing-empty">No branch selling prices have been recorded yet.</td></tr>';
            return;
        }
        body.innerHTML = state.prices.map(row => {
            const packageName = row.pack_size || row.packaging_description || "Package unspecified";
            const status = priceStatus(row);
            const canEnd = row.status === "ACTIVE" && !row.effective_to;
            const start = String(row.effective_from || "");
            const defaultEnd = start > localToday() ? start : localToday();
            const actions = canEnd
                ? `<div class="pricing-end-controls"><input class="pricing-end-date" type="date" data-end-date-for="${escapeHtml(row.retailer_branch_price_id)}" min="${escapeHtml(start)}" value="${escapeHtml(defaultEnd)}" aria-label="Selling price end date"><button class="pricing-end-button" type="button" data-end-price="${escapeHtml(row.retailer_branch_price_id)}">Save end date</button></div>`
                : (row.effective_to ? escapeHtml(row.effective_to) : "No action");
            return `<tr>
                <td><strong>${escapeHtml(row.branch_name || "Branch unavailable")}</strong><small>${escapeHtml(row.area_name || "County not assigned")}</small></td>
                <td><strong>${escapeHtml(row.product_name)}</strong><small>${escapeHtml(row.manufacturer_name)} | Version ${escapeHtml(row.version_number)} | ${escapeHtml(packageName)}${row.barcode ? ` | ${escapeHtml(row.barcode)}` : ""}</small></td>
                <td><strong>${escapeHtml(money(row.selling_price))}</strong><small>${escapeHtml(basisLabels[row.price_basis] || row.price_basis)}</small></td>
                <td>${escapeHtml(row.effective_from)}<small>to ${escapeHtml(row.effective_to || "No planned end")}</small></td>
                <td><span class="pricing-chip">${escapeHtml(status)}</span></td>
                <td>${actions}</td>
            </tr>`;
        }).join("");
    }

    async function loadData(preferredBranch, preferredVersion) {
        const response = await getJson("stock/branch-prices-list.php");
        const data = unwrap(response, "Unable to load branch selling prices.");
        state.branches = Array.isArray(data.branches) ? data.branches : [];
        state.versions = Array.isArray(data.versions) ? data.versions : [];
        state.prices = Array.isArray(data.prices) ? data.prices : [];
        populateBranches(preferredBranch);
        populateVersions(preferredVersion);
        renderPrices();
    }

    function clearForm() {
        byId("priceForm").reset();
        byId("priceFrom").value = localToday();
        populateBranches();
        populateVersions();
    }

    async function savePrice(event) {
        event.preventDefault();
        if (state.busy) return;
        const branchId = byId("priceBranch").value;
        const versionId = byId("priceVersion").value;
        const product = state.versions.find(item => String(item.product_version_id) === String(versionId));
        const effectiveFrom = byId("priceFrom").value;
        const effectiveTo = byId("priceTo").value;
        if (!branchId || !product || !effectiveFrom || !byId("priceAmount").value) {
            setStatus("Select a branch and product version, then enter a selling price and start date.", "error");
            return;
        }
        if (effectiveTo && effectiveTo < effectiveFrom) {
            setStatus("The selling-price end date cannot be earlier than its start date.", "error");
            return;
        }
        state.busy = true;
        byId("savePrice").disabled = true;
        byId("savePrice").textContent = "Saving price...";
        setStatus("Saving branch selling price...", "");
        try {
            const response = await postJson("stock/branch-price-save.php", {
                branchId: Number(branchId),
                productId: Number(product.product_id),
                productVersionId: Number(versionId),
                sellingPrice: byId("priceAmount").value,
                priceBasis: byId("priceBasis").value,
                effectiveFrom,
                effectiveTo
            });
            const data = unwrap(response, "Unable to save branch selling price.");
            clearForm();
            await loadData(branchId, versionId);
            setStatus(data.message || "Branch selling price saved.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to save branch selling price.", "error");
        } finally {
            state.busy = false;
            byId("savePrice").disabled = false;
            byId("savePrice").textContent = "Save selling price";
        }
    }

    async function endPrice(button) {
        if (state.busy) return;
        const priceId = button.dataset.endPrice;
        const dateInput = document.querySelector(`[data-end-date-for="${priceId}"]`);
        if (!dateInput || !dateInput.value) {
            setStatus("Choose an end date for this selling-price period.", "error");
            return;
        }
        state.busy = true;
        setStatus("Saving selling-price end date...", "");
        try {
            const response = await postJson("stock/branch-price-end.php", { priceId: Number(priceId), effectiveTo: dateInput.value });
            const data = unwrap(response, "Unable to end selling-price period.");
            await loadData(byId("priceBranch").value, byId("priceVersion").value);
            setStatus(data.message || "Selling-price end date saved.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to end selling-price period.", "error");
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
            const allowed = type === "RETAILER" && ["ADMIN", "BRANCH_ADMIN"].includes(role);
            if (!allowed) {
                byId("priceApp").hidden = true;
                byId("refreshPrices").disabled = true;
                setStatus("Branch selling prices are available only to retailer organisation administrators and branch administrators.", "error");
                return;
            }
            byId("priceApp").hidden = false;
            byId("priceFrom").value = localToday();
            await loadData();
            setStatus("Active branches, visible product versions, and selling-price history loaded.");
        } catch (error) {
            setStatus(error.message || "Unable to initialise branch selling prices.", "error");
        }
    }

    byId("priceFrom").addEventListener("change", () => {
        byId("priceTo").min = byId("priceFrom").value;
        if (byId("priceTo").value && byId("priceTo").value < byId("priceFrom").value) byId("priceTo").value = "";
    });
    byId("priceForm").addEventListener("submit", savePrice);
    byId("clearPrice").addEventListener("click", clearForm);
    byId("refreshPrices").addEventListener("click", async () => {
        try {
            await loadData(byId("priceBranch").value, byId("priceVersion").value);
            setStatus("Branch selling prices refreshed.", "success");
        } catch (error) {
            setStatus(error.message || "Unable to refresh branch selling prices.", "error");
        }
    });
    byId("priceRows").addEventListener("click", event => {
        const button = event.target.closest("[data-end-price]");
        if (button) endPrice(button);
    });
    initialise();
}());
