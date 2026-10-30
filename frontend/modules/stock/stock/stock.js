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
        const branchField = select.closest(".stock-field");
        if (branchField) {
            branchField.hidden = state.branches.length === 0;
        }

        const branchPicker = byId("stockBranchPicker");
        if (branchPicker) {
            branchPicker.style.display = state.branches.length > 1 ? "" : "none";
        }
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
        state.selectedKey = null;
        state.salesContext = null;
        state.contextKey = null;
        state.contextBranchId = null;
        state.contextRequest += 1;

        const label = byId("selectedProductLabel");
        if (label) label.textContent = "Select a product to begin.";

        ["unitsSold", "salesAmount", "salesAmountBasis"].forEach(id => {
            const field = byId(id);
            if (field) field.value = "";
        });

        const override = byId("salesAmountOverride");
        if (override) {
            override.checked = false;
            override.disabled = true;
        }

        const context = byId("salesTaxContext");
        if (context) context.textContent = "";

        renderStockProductDetails(null);
        updateFormAvailability(null);
        renderSalesAmountHint();
    }

    function renderRows() {
        const rows = state.rows;
        const previousKey = state.selectedKey;

        if (
            previousKey &&
            !rows.some(row =>
                `${row.product_id}:${row.product_version_id}` === previousKey
            )
        ) {
            state.selectedKey = null;
            clearSelectedProductForm();
        }

        const selector = byId("stockProductSelect");

        if (selector) {
            selector.replaceChildren();

            const placeholder = document.createElement("option");
            placeholder.value = "";
            placeholder.textContent = rows.length ? "Choose a product/package" : "No product packages found in Catalogue";
            selector.appendChild(placeholder);

            rows.forEach(row => {
                const option = document.createElement("option");
                option.value = `${row.product_id}:${row.product_version_id}`;

                const packageName =
                    row.pack_size ||
                    row.packaging_description ||
                    `Version ${row.version_number || "?"}`;

                option.textContent = [
                    row.product_name || "Unnamed product",
                    packageName,
                    `Version ${row.version_number || "?"}`
                ].filter(Boolean).join(" · ");

                selector.appendChild(option);
            });

            selector.value = state.selectedKey || "";
            selector.style.display = "";
            selector.removeAttribute("aria-hidden");
        }

        const list = byId("stockProductSummary");
        if (list) {
            list.replaceChildren();
            list.style.display = "none";
        }
    }

    function prepareSalesOnlyLayout() {
        const productPanel = document.querySelector(".stock-product-panel");
        const selector = byId("stockProductSelect");

        if (productPanel && selector) {
            let picker = byId("stockProductPicker");

            if (!picker) {
                picker = document.createElement("div");
                picker.id = "stockProductPicker";
                picker.className = "stock-product-picker";
                productPanel.prepend(picker);
            }

            let productLabel = byId("stockProductPickerLabel");

            if (!productLabel) {
                productLabel = document.createElement("label");
                productLabel.id = "stockProductPickerLabel";
                productLabel.htmlFor = "stockProductSelect";
                productLabel.textContent = "Product / package";
            }

            const oldProductLabel =
                document.querySelector('label[for="stockProductSelect"]');

            if (oldProductLabel && oldProductLabel !== productLabel) {
                oldProductLabel.style.display = "none";
            }

            let branchGroup = byId("stockBranchPicker");

            if (!branchGroup) {
                branchGroup = document.createElement("div");
                branchGroup.id = "stockBranchPicker";
                branchGroup.className = "stock-branch-picker";
            }

            const branchSelect = byId("stockBranch");
            let branchLabel = byId("stockBranchPickerLabel");

            if (branchSelect) {
                if (!branchLabel) {
                    branchLabel = document.createElement("label");
                    branchLabel.id = "stockBranchPickerLabel";
                    branchLabel.htmlFor = "stockBranch";
                    branchLabel.textContent = "Branch";
                }

                const oldBranchLabel =
                    document.querySelector('label[for="stockBranch"]');

                if (oldBranchLabel && oldBranchLabel !== branchLabel) {
                    oldBranchLabel.style.display = "none";
                }

                branchGroup.replaceChildren(branchLabel, branchSelect);

                const branchCount = Array.isArray(state.branches)
                    ? state.branches.length
                    : 0;

                branchGroup.style.display = branchCount > 1 ? "" : "none";
                branchSelect.style.display = "";
            }

            picker.replaceChildren();

            if (branchSelect) picker.appendChild(branchGroup);

            picker.append(productLabel, selector);

            Array.from(productPanel.children).forEach(child => {
                if (child !== picker) child.style.display = "none";
            });
        }

        const toolbar = document.querySelector(".stock-toolbar");
        if (toolbar) toolbar.style.display = "none";

        const metrics = byId("stockMetrics");
        if (metrics) metrics.style.display = "none";

        const search = byId("stockSearch");
        if (search) search.style.display = "none";

        const statusFilter = byId("stockStatusFilter");
        if (statusFilter) statusFilter.style.display = "none";

        const countForm = byId("stockCountForm");
        if (countForm) {
            countForm.style.display = "none";

            const editor = document.querySelector(".stock-editor");
            let ancestor = countForm.parentElement;

            while (ancestor && ancestor !== editor) {
                const forms = ancestor.querySelectorAll("form");

                if (forms.length === 1 && forms[0] === countForm) {
                    ancestor.style.display = "none";
                    break;
                }

                ancestor = ancestor.parentElement;
            }
        }

        const saveButton = byId("saveStockButton");
        if (saveButton) saveButton.style.display = "none";

        // These values still exist internally, but are no longer extra
        // user-facing controls. Sale date remains today's date automatically.
        function hideFieldGroup(id) {
            const field = byId(id);
            if (!field) return;

            field.style.display = "none";

            const label = document.querySelector(`label[for="${id}"]`);
            if (label) label.style.display = "none";

            let group = field.parentElement;
            const form = field.closest("form");

            for (let i = 0; group && group !== form && i < 4; i++) {
                const controls = group.querySelectorAll(
                    "input, select, textarea, button"
                );

                if (controls.length === 1 && controls[0] === field) {
                    group.style.display = "none";
                    group = group.parentElement;
                } else {
                    break;
                }
            }
        }

        hideFieldGroup("saleDate");
        hideFieldGroup("salesAmountBasis");
        hideFieldGroup("salesAmountOverride");

        ["salesAmountHint", "salesAmountOverrideHint", "salesTaxContext"].forEach(id => {
            const element = byId(id);
            if (element) element.style.display = "none";
        });

        const override = byId("salesAmountOverride");
        if (override) {
            override.checked = false;
            override.disabled = true;
        }

        const amount = byId("salesAmount");
        if (amount) amount.readOnly = true;

        const saleDate = byId("saleDate");
        if (saleDate && !saleDate.value) {
            saleDate.value = localDateValue();
        }
    }

    function updateFormAvailability(row) {
        const hasSelection = Boolean(row);
        const button = byId("recordSaleButton");
        const unitsText = byId("unitsSold").value.trim();
        const unitsValid = /^\d{1,9}$/.test(unitsText);
        const saleDate = byId("saleDate").value;
        const priceInfo = state.salesContext && state.salesContext.price;

        const hasPrice = Boolean(
            priceInfo &&
            priceInfo.status === "EFFECTIVE" &&
            Number.isFinite(Number(priceInfo.selling_price)) &&
            Number(priceInfo.selling_price) >= 0 &&
            ["VAT_INCLUSIVE", "VAT_EXCLUSIVE"].includes(priceInfo.price_basis)
        );

        if (byId("saveStockButton")) {
            byId("saveStockButton").disabled = true;
        }

        button.disabled =
            !hasSelection ||
            state.busy ||
            !hasPrice ||
            !unitsValid ||
            !saleDate ||
            saleDate > localDateValue();
    }

    function formatSalesMoney(value) {
        const amount = Number(value);
        return new Intl.NumberFormat("en-KE", {
            style: "currency", currency: "KES",
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }).format(Number.isFinite(amount) ? amount : 0);
    }

    function renderStockProductDetails(row) {
        let panel = byId("stockProductDetails");
        const form = byId("stockSaleForm");
        const editor = document.querySelector(".stock-editor");

        if (!panel) {
            panel = document.createElement("div");
            panel.id = "stockProductDetails";
            panel.className = "stock-product-details";
        }

        if (form && form.parentElement) {
            form.parentElement.insertBefore(panel, form);
        } else if (editor && panel.parentElement !== editor) {
            editor.prepend(panel);
        }

        panel.replaceChildren();

        if (!row) {
            panel.style.display = "none";
            return;
        }

        const context = state.salesContext || {};
        const priceInfo = context.price || {};
        const taxInfo = context.tax || {};

        const priceIsEffective =
            priceInfo.status === "EFFECTIVE" &&
            Number.isFinite(Number(priceInfo.selling_price));

        const stockAmount = Number(row.on_hand);
        const stockText = !row.stock_recorded
            ? "Not recorded"
            : Number.isFinite(stockAmount)
                ? `${new Intl.NumberFormat("en-KE", {
                    maximumFractionDigits: 2
                }).format(stockAmount)} units`
                : "Not recorded";

        const priceText = priceIsEffective
            ? `${formatSalesMoney(priceInfo.selling_price)} / unit`
            : "Not set";

        const taxCode = String(
            taxInfo.tax_category ||
            taxInfo.tax_category_code ||
            taxInfo.category_code ||
            taxInfo.category ||
            ""
        ).toUpperCase();

        const taxLabels = {
            STANDARD_RATED: "Standard-rated",
            VAT_STANDARD: "Standard-rated",
            ZERO_RATED: "Zero-rated",
            VAT_ZERO_RATED: "Zero-rated",
            EXEMPT: "Exempt",
            VAT_EXEMPT: "Exempt",
            NON_TAXABLE: "Non-taxable",
            OUT_OF_SCOPE: "Out of scope"
        };

        const taxText = taxCode
            ? (taxLabels[taxCode] ||
                taxCode.toLowerCase().replace(/_/g, " ").replace(/\b[a-z]/g, c => c.toUpperCase()))
            : "Not declared";

        const details = [
            ["Available stock", stockText],
            ["Selling price", priceText],
            ["Tax category", taxText]
        ];

        details.forEach(([labelText, valueText]) => {
            const item = document.createElement("div");
            item.className = "stock-product-detail";

            const label = document.createElement("span");
            label.className = "stock-product-detail-label";
            label.textContent = labelText;

            const value = document.createElement("strong");
            value.className = "stock-product-detail-value";
            value.textContent = valueText;

            item.append(label, value);
            panel.appendChild(item);
        });

        panel.style.display = "";
    }

    function renderSalesAmountHint() {
        const amount = byId("salesAmount");
        const unitsText = byId("unitsSold").value.trim();
        const priceInfo = state.salesContext && state.salesContext.price;
        const basis = byId("salesAmountBasis");
        const override = byId("salesAmountOverride");

        if (override) {
            override.checked = false;
            override.disabled = true;
        }

        const hasPrice = Boolean(
            priceInfo &&
            priceInfo.status === "EFFECTIVE" &&
            Number.isFinite(Number(priceInfo.selling_price)) &&
            Number(priceInfo.selling_price) >= 0 &&
            ["VAT_INCLUSIVE", "VAT_EXCLUSIVE"].includes(priceInfo.price_basis)
        );

        amount.readOnly = true;
        basis.value = hasPrice ? priceInfo.price_basis : "";

        if (!hasPrice) {
            amount.value = "";
            updateFormAvailability(selectedRow());
            return;
        }

        if (!/^\d{1,9}$/.test(unitsText)) {
            amount.value = "";
            updateFormAvailability(selectedRow());
            return;
        }

        const price = Number(priceInfo.selling_price);
        const total = Math.round(Number(unitsText) * price * 100) / 100;

        amount.value = total.toFixed(2);
        updateFormAvailability(selectedRow());
    }

    async function loadSalesPricingContext(row, setDefaultBasis) {
        const requestId = ++state.contextRequest;
        const message = byId("salesTaxContext");
        const saleDate = byId("saleDate").value || localDateValue();

        state.salesContext = null;

        if (!row || !state.branch || !saleDate) {
            if (message) message.textContent = "";
            renderStockProductDetails(row);
            renderSalesAmountHint();
            return;
        }

        renderStockProductDetails(row);
        renderSalesAmountHint();

        const query = new URLSearchParams({
            branchId: String(state.branch.branch_id),
            productId: String(row.product_id),
            productVersionId: String(row.product_version_id),
            saleDate
        });

        try {
            const response = await getJson(
                `stock/sales-pricing-context.php?${query.toString()}`
            );

            const data = unwrapApiResponse(
                response,
                "Unable to load selling price."
            );

            if (requestId !== state.contextRequest) return;

            state.salesContext = data;

            if (data.price && data.price.status === "EFFECTIVE") {
                byId("salesAmountBasis").value = data.price.price_basis;
            } else {
                byId("salesAmountBasis").value = "";
            }

            if (message) message.textContent = "";

            renderStockProductDetails(row);
            renderSalesAmountHint();
        } catch (error) {
            if (requestId !== state.contextRequest) return;

            state.salesContext = null;

            if (message) message.textContent = "";

            renderStockProductDetails(row);
            renderSalesAmountHint();
            setEditorStatus("Selling price could not be loaded. Refresh and try again.", "error");
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
        byId("salesAmount").value = "";
        byId("salesAmountOverride").checked = false;
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
    function setStockWorkflowMessage(message, type) {
        let host = byId("stockProductPicker") ||
            document.querySelector(".stock-product-panel");

        if (!host) host = byId("stockStatus");
        if (!host) return;

        let note = byId("stockWorkflowMessage");

        if (!note) {
            note = document.createElement("p");
            note.id = "stockWorkflowMessage";
            note.className = "stock-workflow-message";
            host.appendChild(note);
        }

        note.textContent = message || "";
        note.dataset.state = type || "";
        note.style.display = message ? "" : "none";
    }

    async function loadCatalogueStockRows(stockRows) {
        try {
            const response = await getJson("products/list.php");

            const candidates = [
                response,
                response && response.data,
                response && response.data && response.data.data,
                response && response.payload,
                response && response.payload && response.payload.data,
                response && response.result,
                response && response.result && response.result.data
            ];

            const data = candidates.find(
                candidate => candidate &&
                    Array.isArray(candidate.products)
            );

            if (!data) {
                const shapes = candidates
                    .filter(candidate => candidate && typeof candidate === "object")
                    .map(candidate => Object.keys(candidate).join(", "))
                    .filter(Boolean);

                throw new Error(
                    "Catalogue response has no products array. Response fields: " +
                    (shapes.join(" | ") || "none")
                );
            }

            const stockByKey = new Map(
                (Array.isArray(stockRows) ? stockRows : []).map(row => [
                    `${Number(row.product_id)}:${Number(row.product_version_id)}`,
                    row
                ])
            );

            const catalogueRows = [];

            data.products.forEach(product => {
                const productId = Number(product.product_id);
                const versions = Array.isArray(product.versions)
                    ? product.versions
                    : [];

                versions.forEach(version => {
                    const versionId = Number(version.product_version_id);
                    if (!productId || !versionId) return;

                    const key = `${productId}:${versionId}`;
                    const actual = stockByKey.get(key) || null;

                    catalogueRows.push({
                        ...product,
                        ...version,
                        ...(actual || {}),
                        product_id: productId,
                        product_version_id: versionId,
                        product_name: product.product_name,
                        category: product.category || "",
                        manufacturer_name: product.manufacturer_name || "",
                        version_number: version.version_number,
                        barcode: version.barcode || "",
                        pack_size: version.pack_size || "",
                        packaging_description: version.packaging_description || "",
                        on_hand: actual ? Number(actual.on_hand || 0) : 0,
                        reorder_level: actual ? Number(actual.reorder_level || 0) : 0,
                        stock_recorded: actual
                            ? Boolean(actual.stock_recorded || actual.retailer_stock_id)
                            : false,
                        last_updated: actual ? actual.last_updated : null,
                        sold_recently: actual ? Number(actual.sold_recently || 0) : 0,
                        status: actual ? actual.status : "NOT_TRACKED"
                    });
                });
            });

            if (catalogueRows.length === 0) {
                const productsCount = data.products.length;
                setStockWorkflowMessage(
                    productsCount === 0
                        ? "The Catalogue returned no products. Open Catalogue and confirm products are visible for this account."
                        : `The Catalogue returned ${productsCount} products, but none has a package version available.`,
                    "error"
                );
            } else {
                setStockWorkflowMessage("", "");
            }

            return catalogueRows;
        } catch (error) {
            setStockWorkflowMessage(
                `Could not load Catalogue products: ${apiMessage(error)}`,
                "error"
            );
            throw error;
        }
    }
    async function loadStock() {
        setStatus("Loading stock...", "");
        try {
            setStockWorkflowMessage("Loading Catalogue products...", "");
            state.rows = await loadCatalogueStockRows([]);
            renderRows();

            const selectedBranchId = byId("stockBranch").value;
            const path = "stock/list.php" + (selectedBranchId ? `?branchId=${encodeURIComponent(selectedBranchId)}` : "");
            const response = await getJson(path);
            const data = unwrapApiResponse(response, "Unable to load stock.");
            state.branch = data.branch || null;
            state.branches = Array.isArray(data.branches) ? data.branches : [];
            prepareSalesOnlyLayout();
            const stockRows = Array.isArray(data.stock) ? data.stock : [];
            state.rows = await loadCatalogueStockRows(stockRows);
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
            setStockWorkflowMessage(
                `Stock loading failed: ${apiMessage(error)}`,
                "error"
            );
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

        const unitsSold = byId("unitsSold").value.trim();
        const saleDate = byId("saleDate").value || localDateValue();
        const priceInfo = state.salesContext && state.salesContext.price;

        if (!/^\d{1,9}$/.test(unitsSold)) {
            setEditorStatus("Enter units sold as a whole number.", "error");
            byId("unitsSold").focus();
            return;
        }

        if (
            !priceInfo ||
            priceInfo.status !== "EFFECTIVE" ||
            !Number.isFinite(Number(priceInfo.selling_price)) ||
            Number(priceInfo.selling_price) < 0
        ) {
            setEditorStatus("An effective selling price is required before recording sales.", "error");
            return;
        }

        if (!["VAT_INCLUSIVE", "VAT_EXCLUSIVE"].includes(priceInfo.price_basis)) {
            setEditorStatus("The selling price's VAT basis is not configured.", "error");
            return;
        }

        if (!saleDate || saleDate > localDateValue()) {
            setEditorStatus("The sale date cannot be in the future.", "error");
            return;
        }

        const salesAmount = (
            Math.round(Number(unitsSold) * Number(priceInfo.selling_price) * 100) / 100
        ).toFixed(2);

        byId("salesAmount").value = salesAmount;
        byId("salesAmountBasis").value = priceInfo.price_basis;

        state.busy = true;
        updateFormAvailability(row);
        setEditorStatus("Recording sales...", "");

        try {
            const response = await postJson("stock/sale.php", {
                productId: Number(row.product_id),
                productVersionId: Number(row.product_version_id),
                branchId: Number(state.branch.branch_id),
                unitsSold: Number(unitsSold),
                salesAmount: Number(salesAmount),
                salesAmountBasis: priceInfo.price_basis,
                saleDate,
                salesAmountOverride: false
            });

            const data = unwrapApiResponse(response, "Unable to record sales.");

            byId("unitsSold").value = "";
            byId("salesAmount").value = "";

            await loadStockAfterAction();

            let toastStatus =
                data.tax_calculation_status === "CALCULATED" ? "success" : "warning";

            if (data.stock_warning) toastStatus = "warning";

            setEditorStatus(
                (data.message || "Sales recorded.") + " Stock refreshed.",
                toastStatus
            );
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
        byId("salesAmount").value = "";
        byId("salesAmountOverride").checked = false;
        state.salesContext = null;
        renderSalesAmountHint();
        updateFormAvailability(selectedRow());
        loadSalesPricingContext(selectedRow(), false);
    });
    byId("unitsSold").addEventListener("input", () => { renderSalesAmountHint(); updateFormAvailability(selectedRow()); });
    byId("salesAmount").addEventListener("input", renderSalesAmountHint);
    byId("salesAmountOverride").addEventListener("change", renderSalesAmountHint);
    byId("salesAmountBasis").addEventListener("change", renderSalesAmountHint);
    prepareSalesOnlyLayout();
    loadStock();
}());
