(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = {
        branch: null,
        branches: [],
        rows: [],
        alerts: [],
        alertRequest: 0,
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

    function filteredRows() {
        const query = byId("stockSearch").value.trim().toLowerCase();
        const filter = byId("stockStatusFilter").value;
        return state.rows.filter(row => {
            const searchable = [row.product_name, row.manufacturer_name, row.barcode, row.pack_size,
                row.packaging_description, row.version_number, row.supplier_name, row.area_name].join(" ").toLowerCase();
            return (!query || searchable.includes(query)) && (filter === "ALL" || row.status === filter);
        });
    }

    function clearSelectedProductForm() {
        state.salesContext = null;
        state.contextKey = null;
        state.contextBranchId = null;
        state.contextRequest += 1;

        byId("selectedProductLabel").textContent = "No product package selected.";
        ["countOnHand", "countReorderLevel", "unitsSold", "salesAmount", "salesAmountBasis"].forEach(id => {
            byId(id).value = "";
        });
        updateFormAvailability(null);
        setEditorStatus("Choose a product package to manage its stock.", "");
        byId("salesTaxContext").textContent = "Select a product version, branch, and sale date to check its effective branch price and manufacturer tax declaration.";
        renderSalesAmountHint();
    }

    function renderRows() {
        const rows = filteredRows();
        const previousKey = state.selectedKey;

        if (previousKey && !rows.some(row => `${row.product_id}:${row.product_version_id}` === previousKey)) {
            state.selectedKey = null;
            clearSelectedProductForm();
        }

        const select = byId("stockProductSelect");
        const options = rows.map(row => {
            const key = `${row.product_id}:${row.product_version_id}`;
            const packageName = row.pack_size || row.packaging_description || "Package not specified";
            const status = stockStatusLabels[row.status] || row.status || "Unknown";
            const label = `${row.product_name} | ${packageName} | Version ${row.version_number} | ${status}`;
            return `<option value="${escapeHtml(key)}">${escapeHtml(label)}</option>`;
        });
        select.innerHTML = `<option value="">Choose a product package</option>${options.join("")}`;
        select.value = state.selectedKey && rows.some(row => `${row.product_id}:${row.product_version_id}` === state.selectedKey)
            ? state.selectedKey
            : "";
        byId("stockRowCount").textContent = `${rows.length} matching package${rows.length === 1 ? "" : "s"}`;

        const summary = byId("stockProductSummary");
        const row = selectedRow();
        if (!row) {
            summary.innerHTML = `<div class="stock-product-empty">
                <strong>Choose a product to begin</strong>
                <p>Select a product and package version above. Its stock count, demand, supplier guidance, and status will appear here.</p>
                <small>${rows.length.toLocaleString("en-KE")} matching product package${rows.length === 1 ? "" : "s"} available.</small>
            </div>`;
            return;
        }

        const packageName = row.pack_size || row.packaging_description || "Package not specified";
        const onHand = row.stock_recorded ? Number(row.on_hand).toLocaleString("en-KE") : "Not counted";
        const reorder = Number(row.reorder_level || 0).toLocaleString("en-KE");
        const average = Number(row.average_daily_sales || 0).toFixed(2);
        const days = row.days_left === null || row.days_left === undefined
            ? "Not available"
            : `${Number(row.days_left).toFixed(1)} days`;
        const supplier = row.supplier_name || "No applicable regional supplier";
        const leadTime = `${Number(row.lead_time_days || 0)} day lead time${row.lead_time_is_estimate ? " (estimate)" : ""}`;
        const suggested = Number(row.recommended_quantity || 0);
        const suggestion = suggested > 0
            ? `${suggested.toLocaleString("en-KE")} units`
            : "No order suggested";
        const suggestionDetail = suggested > 0
            ? `MOQ ${Number(row.minimum_order_quantity || 0)} | Order multiple ${Number(row.order_multiple || 0)}`
            : row.status === "NOT_TRACKED"
                ? "Count stock to enable analysis"
                : row.status === "NORMAL"
                    ? "No reorder indicated"
                    : "Review stock and supplier availability";

        summary.innerHTML = `<div class="stock-product-overview">
            <div class="stock-product-overview-heading">
                <div class="stock-product-overview-copy">
                    <p class="stock-product-kicker">PRODUCT OVERVIEW</p>
                    <h3>${escapeHtml(row.product_name)}</h3>
                    <p>${escapeHtml(row.manufacturer_name || "Manufacturer not recorded")}</p>
                </div>
                ${statusChip(row.status)}
            </div>
            <dl class="stock-product-details">
                <div><dt>Package</dt><dd>${escapeHtml(packageName)}</dd></div>
                <div><dt>Version / barcode</dt><dd>Version ${escapeHtml(row.version_number)}<small>${escapeHtml(row.barcode || "No barcode")}</small></dd></div>
                <div><dt>Units on hand</dt><dd>${escapeHtml(onHand)}<small>Reorder level: ${escapeHtml(reorder)}</small></dd></div>
                <div><dt>Average daily sales</dt><dd>${escapeHtml(average)}<small>${escapeHtml(days)} remaining</small></dd></div>
            </dl>
            <div class="stock-product-guidance">
                <div><span>Regional supplier</span><strong>${escapeHtml(supplier)}</strong><small>${escapeHtml(leadTime)}</small></div>
                <div><span>Suggested order</span><strong>${escapeHtml(suggestion)}</strong><small>${escapeHtml(suggestionDetail)}</small></div>
            </div>
        </div>`;
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

    function renderInlineAlerts() {
        const alerts = state.alerts;
        const low = alerts.filter(alert => alert.alert_type === "LOW_STOCK").length;
        const out = alerts.filter(alert => alert.alert_type === "OUT_OF_STOCK").length;
        const suggested = alerts.reduce(
            (total, alert) => total + Number(alert.recommended_order_quantity || 0),
            0
        );

        byId("stockInlineAlertCount").textContent =
            `${alerts.length} active alert${alerts.length === 1 ? "" : "s"}`;

        byId("stockInlineAlertSummary").innerHTML = [
            ["Low stock", low],
            ["Out of stock", out],
            ["Suggested units", suggested.toLocaleString()]
        ].map(([label, value]) =>
            `<div class="stock-inline-alert-stat">
                <span>${escapeHtml(label)}</span>
                <strong>${escapeHtml(value)}</strong>
            </div>`
        ).join("");

        if (!alerts.length) {
            byId("stockInlineAlertList").innerHTML =
                `<p class="stock-inline-alert-empty">No active stock alerts for this branch.</p>`;
            return;
        }

        byId("stockInlineAlertList").innerHTML = alerts.map(alert => {
            const isOut = alert.alert_type === "OUT_OF_STOCK";
            const kind = isOut ? "out-of-stock" : "low-stock";
            const label = isOut ? "Out of stock" : "Low stock";
            const key = `${alert.product_id}:${alert.product_version_id}`;
            const canOpen = state.rows.some(row =>
                `${row.product_id}:${row.product_version_id}` === key
            );

            const packageText = [
                alert.pack_size || `Version ${alert.version_number || "unknown"}`,
                alert.barcode ? `Barcode ${alert.barcode}` : ""
            ].filter(Boolean).join(" | ");

            return `<article class="stock-inline-alert-item">
                <div class="stock-inline-alert-title">
                    <div>
                        <h4>${escapeHtml(alert.product_name || "Product")}</h4>
                        <p>${escapeHtml(packageText)}</p>
                    </div>
                    <span class="stock-inline-alert-badge ${kind}">${label}</span>
                </div>
                <div class="stock-inline-alert-details">
                    <div><span>On hand</span>
                        <strong>${Number(alert.current_stock || 0).toLocaleString()}</strong></div>
                    <div><span>Reorder level</span>
                        <strong>${Number(alert.reorder_level || 0).toLocaleString()}</strong></div>
                    <div><span>Suggested order</span>
                        <strong>${Number(alert.recommended_order_quantity || 0).toLocaleString()} units</strong></div>
                </div>
                <button class="stock-inline-alert-open" type="button"
                    data-stock-alert-key="${escapeHtml(key)}"
                    ${canOpen ? "" : "disabled"}>
                    ${canOpen ? "View product" : "Product unavailable"}
                </button>
            </article>`;
        }).join("");
    }

    async function loadInlineAlerts(branchId) {
        const requestId = ++state.alertRequest;
        const list = byId("stockInlineAlertList");

        if (!branchId) {
            state.alerts = [];
            renderInlineAlerts();
            return;
        }

        list.innerHTML =
            `<p class="stock-inline-alert-empty">Loading active alerts...</p>`;

        try {
            const response = await getJson(
                `stock/alerts.php?branchId=${encodeURIComponent(branchId)}`
            );
            const data = unwrapApiResponse(response, "Unable to load stock alerts.");

            if (requestId !== state.alertRequest) return;

            state.alerts = Array.isArray(data.alerts) ? data.alerts : [];
            renderInlineAlerts();
        } catch (error) {
            if (requestId !== state.alertRequest) return;

            byId("stockInlineAlertCount").textContent = "Unavailable";
            byId("stockInlineAlertSummary").innerHTML = "";
            list.innerHTML = `<p class="stock-inline-alert-empty">
                Alerts could not be retrieved. Use Refresh to try again.
            </p>`;
        }
    }
    async function loadStock() {
        setStatus("Loading stock...", "");
        try {
            const selectedBranchId = byId("stockBranch").value;
            const path = "stock/list.php" + (selectedBranchId ? `?branchId=${encodeURIComponent(selectedBranchId)}` : "");
            const response = await getJson(path);
            const data = unwrapApiResponse(response, "Unable to load stock.");
            state.branch = data.branch || null;
            state.branches = Array.isArray(data.branches) ? data.branches : [];
            state.rows = Array.isArray(data.stock) ? data.stock : [];
            void loadInlineAlerts(state.branch ? state.branch.branch_id : selectedBranchId);
            renderBranches();
            renderMetrics(state.rows);
            if (!selectedRow()) state.selectedKey = null;
            renderRows();
            if (state.selectedKey) selectRow(state.selectedKey);
            else clearSelectedProductForm();
            setStatus("", "");
        } catch (error) {
            setStatus(apiMessage(error), "error");
            byId("stockProductSummary").innerHTML = '<div class="stock-product-empty"><strong>Stock could not be loaded</strong><p>Refresh to try again.</p></div>';
        } finally {
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

    byId("stockBranch").addEventListener("change", () => {
        state.selectedKey = null;
        loadStock();
    });
    byId("stockProductSelect").addEventListener("change", () => {
        const key = byId("stockProductSelect").value;
        if (key) {
            selectRow(key);
        } else {
            state.selectedKey = null;
            clearSelectedProductForm();
            renderRows();
        }
    });
    byId("stockInlineAlertList").addEventListener("click", event => {
        const target = event.target instanceof Element
            ? event.target.closest("[data-stock-alert-key]")
            : null;

        if (!target || target.disabled) return;

        const key = target.getAttribute("data-stock-alert-key");
        const row = state.rows.find(item =>
            `${item.product_id}:${item.product_version_id}` === key
        );

        if (!row) {
            setStatus("That product package is unavailable in the current branch stock list.", "error");
            return;
        }

        byId("stockSearch").value = "";
        byId("stockStatusFilter").value = "ALL";
        state.selectedKey = key;
        renderRows();
        selectRow(key);
        byId("stockProductSelect").value = key;

        byId("stockProductSelect").scrollIntoView({
            behavior: "smooth",
            block: "center"
        });
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
