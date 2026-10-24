(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = {
        branch: null,
        branches: [],
        rows: [],
        dataIssues: null,
        selectedKey: null,
        busy: false,
        salesContext: null,
        contextKey: null,
        contextBranchId: null,
        contextRequest: 0
    };

    const stockStatusLabels = {
        NOT_TRACKED: "Not tracked",
        NORMAL: "Normal",
        LOW_STOCK: "Low stock",
        OUT_OF_STOCK: "Out of stock"
    };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function apiMessage(error) {
        if (error && typeof error.message === "string" && error.message.trim()) {
            return error.message;
        }
        if (error && error.data && typeof error.data.message === "string") {
            return error.data.message;
        }
        return "The request could not be completed. Please try again.";
    }

    function localDateValue() {
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, "0");
        const day = String(now.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    function formatDate(value) {
        if (!value) return "Not recorded";
        const date = String(value).slice(0, 10);
        const parts = date.split("-");
        return parts.length === 3 ? `${parts[2]}/${parts[1]}/${parts[0]}` : escapeHtml(value);
    }

    function statusClass(status) {
        return {
            NORMAL: "normal",
            LOW_STOCK: "low-stock",
            OUT_OF_STOCK: "out-of-stock",
            NOT_TRACKED: "not-tracked"
        }[status] || "not-tracked";
    }

    function statusChip(status) {
        return `<span class="stock-chip ${statusClass(status)}">${escapeHtml(stockStatusLabels[status] || status || "Unknown")}</span>`;
    }

    function setStatus(message, kind) {
        const node = byId("stockStatus");
        node.textContent = message || "";
        node.classList.toggle("is-error", kind === "error");
        node.classList.toggle("is-success", kind === "success");
    }

    function setEditorStatus(message, kind) {
        const node = byId("stockEditorStatus");
        node.textContent = message || "";
        node.classList.toggle("is-error", kind === "error");
        node.classList.toggle("is-success", kind === "success");
        node.classList.toggle("is-warning", kind === "warning");
    }

    function selectedRow() {
        return state.rows.find(row => `${row.product_id}:${row.product_version_id}` === state.selectedKey) || null;
    }

    function renderBranches() {
        const select = byId("stockBranch");
        const priorValue = select.value;
        select.innerHTML = state.branches.map(branch => {
            const county = branch.area_name ? ` | ${branch.area_name}` : " | County not assigned";
            return `<option value="${escapeHtml(branch.branch_id)}">${escapeHtml(branch.branch_name + county)}</option>`;
        }).join("");

        if (state.branch) select.value = String(state.branch.branch_id);
        else if (priorValue && state.branches.some(branch => String(branch.branch_id) === priorValue)) select.value = priorValue;

        select.disabled = state.branches.length <= 1;
        select.closest(".stock-field").hidden = state.branches.length === 0;
    }

    function renderMetrics(rows) {
        const tracked = rows.filter(row => row.stock_recorded).length;
        const low = rows.filter(row => row.status === "LOW_STOCK").length;
        const out = rows.filter(row => row.status === "OUT_OF_STOCK").length;
        const untracked = rows.filter(row => row.status === "NOT_TRACKED").length;
        const metrics = [
            ["Tracked versions", tracked],
            ["Low stock", low],
            ["Out of stock", out],
            ["Not tracked", untracked]
        ];
        byId("stockMetrics").innerHTML = metrics.map(([label, value]) =>
            `<div class="stock-metric"><div class="stock-metric-label">${label}</div><div class="stock-metric-value">${value}</div></div>`
        ).join("");
    }

    function issueDescription(issue) {
        const product = escapeHtml(issue.product_name || "Product");
        if (issue.issue_code === "PRODUCT_VERSION_MISMATCH") {
            return `${product}: stock record #${escapeHtml(issue.retailer_stock_id)} points to a version belonging to ${escapeHtml(issue.version_product_name || "another product")}. It is excluded from branch stock calculations until reconciled.`;
        }
        if (issue.issue_code === "BRANCH_UNALLOCATED") {
            return `${product}: stock record #${escapeHtml(issue.retailer_stock_id)} has no branch assigned. Its physical location must be confirmed before it can be used for branch inventory.`;
        }
        if (issue.issue_code === "MISSING_PRODUCT_VERSION") {
            return `${product}: stock record #${escapeHtml(issue.retailer_stock_id)} references a missing product version. Reconciliation is required.`;
        }
        return `${product}: stock record #${escapeHtml(issue.retailer_stock_id)} needs review.`;
    }

    function renderIntegrity() {
        const node = byId("stockIntegrity");
        const issues = state.dataIssues || {};
        const records = Array.isArray(issues.stock_records_needing_review) ? issues.stock_records_needing_review : [];
        const unversionedSales = Number(issues.unallocated_or_unversioned_sales_count || 0);
        const legacyAlerts = Array.isArray(issues.active_legacy_alerts) ? issues.active_legacy_alerts : [];
        const messages = [];

        records.forEach(issue => messages.push(issueDescription(issue)));
        if (unversionedSales > 0) {
            messages.push(`${unversionedSales} historical sale record(s) have no branch or product version assigned. They have been retained and are excluded from version-specific demand calculations.`);
        }
        legacyAlerts.forEach(alert => {
            const reason = alert.issue_code === "PRODUCT_VERSION_MISMATCH"
                ? `version belongs to ${alert.version_product_name || "another product"}`
                : alert.issue_code === "BRANCH_UNALLOCATED"
                    ? "has no branch assigned"
                    : "has no verified product-version assignment";
            messages.push(`${alert.product_name}: active legacy alert #${alert.stock_alert_id} ${reason}. Review it before resolving or reassigning it.`);
        });

        node.hidden = messages.length === 0;
        if (messages.length === 0) {
            node.innerHTML = "";
            return;
        }

        node.innerHTML = `<h2>Inventory records needing review</h2>
            <p>Legacy data is preserved. These records are not automatically assigned to a branch or product version.</p>
            <ul>${messages.map(message => `<li>${message}</li>`).join("")}</ul>`;
    }

    function filteredRows() {
        const query = byId("stockSearch").value.trim().toLowerCase();
        const filter = byId("stockStatusFilter").value;
        return state.rows.filter(row => {
            const searchable = [row.product_name, row.manufacturer_name, row.barcode, row.pack_size,
                row.packaging_description, row.version_number, row.supplier_name, row.area_name].join(" ").toLowerCase();
            return (!query || searchable.includes(query)) && (filter === "ALL" || row.status === filter);
        });
    }

    function renderRows() {
        const rows = filteredRows();
        byId("stockRowCount").textContent = `${rows.length} version${rows.length === 1 ? "" : "s"}`;

        if (rows.length === 0) {
            byId("stockRows").innerHTML = `<tr><td colspan="8" class="stock-empty-cell">No products match these filters.</td></tr>`;
            return;
        }

        byId("stockRows").innerHTML = rows.map(row => {
            const key = `${row.product_id}:${row.product_version_id}`;
            const selected = key === state.selectedKey ? " is-selected" : "";
            const onHand = row.stock_recorded ? Number(row.on_hand).toLocaleString() : "Not counted";
            const average = Number(row.average_daily_sales || 0).toFixed(2);
            const days = row.days_left === null || row.days_left === undefined ? "Not available" : `${Number(row.days_left).toFixed(1)} days`;
            const packageName = row.pack_size || row.packaging_description || "Package not specified";
            const supplier = row.supplier_name
                ? `${escapeHtml(row.supplier_name)}<small>${Number(row.lead_time_days)} day lead time${row.lead_time_is_estimate ? " (estimate)" : ""}</small>`
                : `No applicable regional supplier<small>Default lead time: ${Number(row.lead_time_days)} days</small>`;
            const suggestion = Number(row.recommended_quantity || 0) > 0
                ? `${Number(row.recommended_quantity).toLocaleString()} units<small>MOQ ${Number(row.minimum_order_quantity)} | Multiple ${Number(row.order_multiple)}</small>`
                : `None<small>${row.status === "NORMAL" ? "No reorder indicated" : row.status === "NOT_TRACKED" ? "Count stock to enable analysis" : "Review supply availability"}</small>`;
            return `<tr class="${selected.trim()}" data-row-key="${escapeHtml(key)}">
                <td><strong>${escapeHtml(row.product_name)}</strong><small>${escapeHtml(row.manufacturer_name || "Manufacturer not recorded")}</small></td>
                <td><strong>${escapeHtml(packageName)}</strong><small>Version ${escapeHtml(row.version_number)} | ${escapeHtml(row.barcode || "No barcode")}</small></td>
                <td class="numeric"><strong>${escapeHtml(onHand)}</strong><small>Reorder at ${Number(row.reorder_level).toLocaleString()}</small></td>
                <td class="numeric">${average}<small>${escapeHtml(days)} left</small></td>
                <td>${supplier}</td>
                <td class="numeric">${suggestion}</td>
                <td>${statusChip(row.status)}</td>
                <td><button type="button" class="secondary-button stock-manage-button" data-manage-key="${escapeHtml(key)}">Manage</button></td>
            </tr>`;
        }).join("");

        byId("stockRows").querySelectorAll("[data-manage-key]").forEach(button => {
            button.addEventListener("click", () => selectRow(button.getAttribute("data-manage-key")));
        });
    }

    function updateFormAvailability(row) {
        const hasSelection = Boolean(row);
        byId("saveStockButton").disabled = !hasSelection || state.busy;
        const saleDate = byId("saleDate").value;
        const historicalSale = saleDate && saleDate < localDateValue();
        byId("recordSaleButton").disabled = !hasSelection || state.busy || (!row.stock_recorded && !historicalSale);
    }

    function formatSalesMoney(value) {
        const amount = Number(value);
        return new Intl.NumberFormat("en-KE", {
            style: "currency", currency: "KES",
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }).format(Number.isFinite(amount) ? amount : 0);
    }

    function renderSalesAmountHint() {
        const hint = byId("salesAmountHint");
        const context = state.salesContext;
        const unitsText = byId("unitsSold").value;
        if (!context || !context.price || context.price.status !== "EFFECTIVE") {
            hint.textContent = "Enter the actual total for the selected day, including any promotions or discounts. A current branch selling price is not available for this date.";
            return;
        }
        const price = Number(context.price.selling_price);
        const basis = context.price.price_basis === "VAT_EXCLUSIVE" ? "VAT-exclusive" : "VAT-inclusive";
        const units = Number(unitsText);
        if (unitsText !== "" && Number.isFinite(units) && units >= 0 && Number.isFinite(price)) {
            hint.textContent = `Reference only: ${units.toLocaleString("en-KE")} units at the current listed price would total ${formatSalesMoney(units * price)} (${basis}), before promotions or discounts. Enter the actual daily total.`;
        } else {
            hint.textContent = `Current listed price is ${formatSalesMoney(price)} per unit (${basis}). Enter the actual daily total, including any promotions or discounts.`;
        }
    }

    async function loadSalesPricingContext(row, setDefaultBasis) {
        const requestId = ++state.contextRequest;
        const node = byId("salesTaxContext");
        if (!row || !state.branch || !byId("saleDate").value) {
            state.salesContext = null;
            node.textContent = "Select a product version, branch, and sale date to check its effective price and tax declaration.";
            renderSalesAmountHint();
            return;
        }

        node.textContent = "Checking the effective branch selling price and manufacturer tax declaration...";
        const query = new URLSearchParams({
            branchId: String(state.branch.branch_id),
            productId: String(row.product_id),
            productVersionId: String(row.product_version_id),
            saleDate: byId("saleDate").value
        });

        try {
            const response = await getJson(`stock/sales-pricing-context.php?${query.toString()}`);
            const data = unwrapApiResponse(response, "Unable to check the effective price and tax declaration.");
            if (requestId !== state.contextRequest) return;
            state.salesContext = data;

            if (data.price && data.price.status === "EFFECTIVE") {
                if (setDefaultBasis || !byId("salesAmountBasis").value) {
                    byId("salesAmountBasis").value = data.price.price_basis;
                }
            } else if (setDefaultBasis) {
                byId("salesAmountBasis").value = "";
            }

            const parts = [];
            if (data.price && data.price.status === "EFFECTIVE") {
                const basisLabel = data.price.price_basis === "VAT_EXCLUSIVE" ? "VAT-exclusive" : "VAT-inclusive";
                const endLabel = data.price.effective_to ? ` through ${data.price.effective_to}` : " with no planned end date";
                parts.push(`Effective branch selling price: ${formatSalesMoney(data.price.selling_price)} per unit (${basisLabel}), from ${data.price.effective_from}${endLabel}.`);
            } else if (data.price && data.price.status === "AMBIGUOUS_PRICE") {
                parts.push("Price review needed: more than one active selling price period overlaps this date. The listed price cannot be chosen safely.");
            } else {
                parts.push("No effective branch selling price is recorded for this product version and date. You can still record the actual sales total, but there is no price reference.");
            }

            const categories = {
                STANDARD_RATED: "Standard-rated",
                ZERO_RATED: "Zero-rated",
                EXEMPT: "Exempt"
            };
            if (data.tax && data.tax.status === "CALCULATED") {
                parts.push(`Manufacturer declaration: ${categories[data.tax.tax_category] || data.tax.tax_category}, rate ${Number(data.tax.rate_percent).toFixed(2)}%. The server will calculate and snapshot tax for this sale date.`);
            } else if (data.tax && data.tax.status === "AMBIGUOUS_CLASSIFICATION") {
                parts.push("Tax review needed: multiple manufacturer declarations overlap this date. Tax and net sales will be left uncalculated until the declarations are reconciled.");
            } else if (data.tax && data.tax.status === "AMBIGUOUS_RATE") {
                parts.push("Tax review needed: multiple tax-rate periods overlap this date. Tax and net sales will be left uncalculated until the rate schedule is reconciled.");
            } else if (data.tax && data.tax.status === "MISSING_RATE") {
                parts.push(`Manufacturer category ${categories[data.tax.tax_category] || data.tax.tax_category} exists, but no effective tax rate is configured for this date.`);
            } else {
                parts.push("No effective manufacturer tax declaration exists for this date. Nexora will save the sales amount but leave tax and net sales uncalculated.");
            }
            node.textContent = parts.join(" ");
            renderSalesAmountHint();
        } catch (error) {
            if (requestId !== state.contextRequest) return;
            state.salesContext = null;
            node.textContent = `Price and tax context could not be loaded: ${apiMessage(error)} You may still enter an amount basis explicitly.`;
            renderSalesAmountHint();
        }
    }
    function selectRow(key) {
        state.selectedKey = key;
        const row = selectedRow();
        if (!row) return;

        const branchKey = state.branch ? String(state.branch.branch_id) : null;
        const contextChanged = state.contextKey !== key || state.contextBranchId !== branchKey;
        if (contextChanged) {
            state.contextKey = key;
            state.contextBranchId = branchKey;
            byId("salesAmountBasis").value = "";
        }

        byId("selectedProductLabel").textContent = `${row.product_name} | Version ${row.version_number} | ${row.pack_size || row.packaging_description || "Package unspecified"} | ${row.barcode || "No barcode"}`;
        byId("countOnHand").value = row.stock_recorded ? row.on_hand : 0;
        byId("countReorderLevel").value = row.reorder_level || 0;
        byId("unitsSold").value = "";
        setEditorStatus(row.stock_recorded ? `Last counted: ${formatDate(row.last_updated)}.` : "This branch/version is not tracked yet. Save an opening count before recording today's sales.");
        updateFormAvailability(row);
        renderRows();
        loadSalesPricingContext(row, contextChanged);
    }

    function unwrapApiResponse(response, fallbackMessage) {
        if (!response || typeof response !== "object" || typeof response.ok !== "boolean") {
            throw new Error("The server returned an invalid API response.");
        }

        const payload = response.data;
        if (!response.ok || !payload || payload.success === false) {
            const message = payload && (payload.message || payload.error);
            const detail = typeof message === "string" && message.trim()
                ? message.trim()
                : fallbackMessage;
            const status = !response.ok && response.status ? ` (HTTP ${response.status})` : "";
            throw new Error(detail + status);
        }

        return payload;
    }

    async function loadStock() {
        setStatus("Loading stock...", "");
        byId("refreshStock").disabled = true;
        try {
            const selectedBranchId = byId("stockBranch").value;
            const path = "stock/list.php" + (selectedBranchId ? `?branchId=${encodeURIComponent(selectedBranchId)}` : "");
            const response = await getJson(path);
            const data = unwrapApiResponse(response, "Unable to load stock.");
            state.branch = data.branch || null;
            state.branches = Array.isArray(data.branches) ? data.branches : [];
            state.rows = Array.isArray(data.stock) ? data.stock : [];
            state.dataIssues = data.data_issues || null;
            renderBranches();
            renderMetrics(state.rows);
            renderIntegrity();

            if (!selectedRow()) {
                const preferred = state.rows.find(row => row.is_current === 1 && row.status !== "NOT_TRACKED") || state.rows.find(row => row.is_current === 1) || state.rows[0];
                state.selectedKey = preferred ? `${preferred.product_id}:${preferred.product_version_id}` : null;
            }
            renderRows();
            if (state.selectedKey) selectRow(state.selectedKey);
            else {
                byId("selectedProductLabel").textContent = "No product version is available for this branch.";
                updateFormAvailability(null);
            }
            setStatus(state.branch
                ? `Showing inventory for ${state.branch.branch_name}${state.branch.area_name ? ` | ${state.branch.area_name}` : " | County not assigned"}.`
                : "No active branch is available.", "");
        } catch (error) {
            setStatus(apiMessage(error), "error");
            byId("stockRows").innerHTML = `<tr><td colspan="8" class="stock-empty-cell">Stock could not be loaded. Refresh to try again.</td></tr>`;
        } finally {
            byId("refreshStock").disabled = false;
        }
    }

    async function submitStockCount(event) {
        event.preventDefault();
        const row = selectedRow();
        if (!row || state.busy) return;

        const onHand = byId("countOnHand").value;
        const reorderLevel = byId("countReorderLevel").value;
        if (!/^\d{1,9}$/.test(onHand) || !/^\d{1,9}$/.test(reorderLevel)) {
            setEditorStatus("Enter whole-number quantities from 0 to 999999999.", "error");
            return;
        }

        state.busy = true;
        updateFormAvailability(row);
        setEditorStatus("Saving stock count...", "");
        try {
            const response = await postJson("stock/update.php", {
                productId: Number(row.product_id),
                productVersionId: Number(row.product_version_id),
                branchId: Number(state.branch.branch_id),
                onHand: Number(onHand),
                reorderLevel: Number(reorderLevel)
            });
            const data = unwrapApiResponse(response, "Unable to save stock count.");
            await loadStockAfterAction();
            setEditorStatus((data.message || "Stock count saved.") + " Current stock refreshed.", "success");
        } catch (error) {
            setEditorStatus(apiMessage(error), "error");
        } finally {
            state.busy = false;
            updateFormAvailability(selectedRow());
        }
    }

    async function submitSale(event) {
        event.preventDefault();
        const row = selectedRow();
        if (!row || state.busy) return;

        const unitsSold = byId("unitsSold").value;
        const salesAmount = byId("salesAmount").value;
        const salesAmountBasis = byId("salesAmountBasis").value;
        const saleDate = byId("saleDate").value;
        if (!/^\d{1,9}$/.test(unitsSold)) {
            setEditorStatus("Enter total units sold as a whole number from zero upwards.", "error");
            return;
        }
        if (!/^\d{1,10}(?:\.\d{1,2})?$/.test(salesAmount)) {
            setEditorStatus("Enter the total sales amount in KSh, with no more than two decimal places.", "error");
            return;
        }
        if (!["VAT_INCLUSIVE", "VAT_EXCLUSIVE"].includes(salesAmountBasis)) {
            setEditorStatus("Choose whether the entered sales amount includes or excludes VAT.", "error");
            byId("salesAmountBasis").focus();
            return;
        }
        if (!saleDate || saleDate > localDateValue()) {
            setEditorStatus("Choose a valid sale date that is not in the future.", "error");
            return;
        }
        if (saleDate === localDateValue() && !row.stock_recorded) {
            setEditorStatus("Save an opening stock count before recording today's sale.", "error");
            return;
        }


        state.busy = true;
        updateFormAvailability(row);
        setEditorStatus("Saving daily sales summary...", "");
        try {
            const response = await postJson("stock/sale.php", {
                productId: Number(row.product_id),
                productVersionId: Number(row.product_version_id),
                branchId: Number(state.branch.branch_id),
                unitsSold: Number(unitsSold),
                salesAmount: Number(salesAmount),
                salesAmountBasis,
                saleDate
            });
            const data = unwrapApiResponse(response, "Unable to record sale.");
            byId("unitsSold").value = "";
            byId("salesAmount").value = "";
            await loadStockAfterAction();
            setEditorStatus((data.message || "Daily sales summary saved.") + " Stock refreshed.", data.tax_calculation_status === "CALCULATED" ? "success" : "warning");
        } catch (error) {
            setEditorStatus(apiMessage(error), "error");
        } finally {
            state.busy = false;
            updateFormAvailability(selectedRow());
        }
    }

    async function loadStockAfterAction() {
        const selectedKey = state.selectedKey;
        await loadStock();
        if (selectedKey && state.rows.some(row => `${row.product_id}:${row.product_version_id}` === selectedKey)) {
            selectRow(selectedKey);
        }
    }

    byId("refreshStock").addEventListener("click", loadStock);
    byId("stockBranch").addEventListener("change", () => {
        state.selectedKey = null;
        loadStock();
    });
    byId("stockSearch").addEventListener("input", renderRows);
    byId("stockStatusFilter").addEventListener("change", renderRows);
    byId("stockCountForm").addEventListener("submit", submitStockCount);
    byId("stockSaleForm").addEventListener("submit", submitSale);
    byId("saleDate").value = localDateValue();
    byId("saleDate").addEventListener("change", () => {
        updateFormAvailability(selectedRow());
        loadSalesPricingContext(selectedRow(), false);
    });
    byId("unitsSold").addEventListener("input", renderSalesAmountHint);
    byId("salesAmountBasis").addEventListener("change", renderSalesAmountHint);

    loadStock();
}());
