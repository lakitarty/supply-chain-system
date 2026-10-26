(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = { branches: [], branch: null, rows: [], allBranches: false, busy: false };

    function escapeHtml(value) {
        return String(value === null || value === undefined ? "" : value)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function apiMessage(error) {
        if (error && typeof error.message === "string" && error.message.trim()) return error.message;
        return "The request could not be completed. Please try again.";
    }

    function unwrapApiResponse(response, fallbackMessage) {
        if (!response || typeof response !== "object" || typeof response.ok !== "boolean") {
            throw new Error("The server returned an invalid API response.");
        }
        const payload = response.data;
        if (!response.ok || !payload || payload.success === false) {
            const message = payload && (payload.message || payload.error);
            const detail = typeof message === "string" && message.trim() ? message.trim() : fallbackMessage;
            const status = !response.ok && response.status ? ` (HTTP ${response.status})` : "";
            throw new Error(detail + status);
        }
        return payload;
    }

    function setStatus(message, kind) {
        const node = byId("salesReportStatus");
        node.textContent = message || "";
        node.classList.toggle("is-error", kind === "error");
        node.classList.toggle("is-success", kind === "success");
    }

    function money(value) {
        const amount = Number(value);
        return new Intl.NumberFormat("en-KE", {
            style: "currency", currency: "KES",
            minimumFractionDigits: 2, maximumFractionDigits: 2
        }).format(Number.isFinite(amount) ? amount : 0);
    }

    function number(value) {
        return Number(value || 0).toLocaleString("en-KE");
    }

    function renderBranches(preferredValue) {
        const select = byId("salesReportBranch");
        const choices = [];
        if (state.allBranches) choices.push('<option value="ALL">All branches</option>');
        state.branches.forEach(branch => {
            const area = branch.area_name ? ` | ${branch.area_name}` : " | County not assigned";
            choices.push(`<option value="${escapeHtml(branch.branch_id)}">${escapeHtml(branch.branch_name + area)}</option>`);
        });
        select.innerHTML = choices.join("");
        const desired = preferredValue || (state.allBranches ? "ALL" : state.branch ? String(state.branch.branch_id) : "");
        if (desired && Array.from(select.options).some(option => option.value === desired)) select.value = desired;
        select.disabled = state.branches.length <= 1 && !state.allBranches;
    }

    function renderMetrics(totals) {
        const metrics = [
            ["Customer total incl. VAT (calculated)", money(totals.customer_total_incl_vat)],
            ["Tax calculated", money(totals.tax_calculated_total)],
            ["Net sales calculated", money(totals.net_sales_total)],
            ["Units sold", number(totals.units_sold)]
        ];
        byId("salesReportMetrics").innerHTML = metrics.map(([label, value]) =>
            `<div class="stock-sales-metric"><div class="stock-sales-metric-label">${escapeHtml(label)}</div><div class="stock-sales-metric-value">${escapeHtml(value)}</div></div>`
        ).join("");

        const missingAmount = Number(totals.entries_missing_amount || 0);
        const missingTax = Number(totals.entries_missing_tax_calculation || 0);
        const legacy = Number(byId("salesReportRows").dataset.excludedLegacy || 0);
        const messages = [];
        if (missingAmount > 0) {
            messages.push(`${missingAmount} daily record(s) have no sales amount. They are excluded from amount totals.`);
        }
        if (missingTax > 0) {
            messages.push(`${missingTax} record(s) have no complete tax calculation. These amounts remain saved, but are excluded from calculated tax, net sales, and customer total including VAT. Check the amount basis, effective manufacturer tax declaration, and rate schedule.`);
        }
        if (legacy > 0) {
            messages.push(`${legacy} legacy sale record(s) lack a valid branch or product-version assignment and are excluded from this report breakdown. They have not been reassigned automatically.`);
        }
        const notice = byId("salesRevenueNotice");
        notice.hidden = messages.length === 0;
        notice.textContent = messages.join(" ");
    }

    function renderRows() {
        const rows = state.rows;
        const body = byId("salesReportRows");
        if (!rows.length) {
            body.innerHTML = '<tr><td colspan="8" class="stock-sales-empty">No valid daily sales summaries were recorded for this period.</td></tr>';
            return;
        }
        body.innerHTML = rows.map(row => {
            const packageName = row.pack_size || row.packaging_description || "Package not specified";
            const version = `Version ${row.version_number} | ${row.barcode || "No barcode"}`;
            const missingAmount = Number(row.entries_missing_amount || 0);
            const missingTax = Number(row.entries_missing_tax_calculation || 0);
            const taxCount = Number(row.entries_tax_calculated || 0);
            let taxReview = "Calculated";
            if (missingTax > 0 && taxCount > 0) taxReview = `Partial: ${missingTax} need review`;
            else if (missingTax > 0) taxReview = `Needs review: ${missingTax}`;
            const enteredAmount = `${escapeHtml(money(row.amount_entered))}${missingAmount ? `<small>${missingAmount} amount missing</small>` : ""}`;
            const taxAmount = `${escapeHtml(money(row.tax_calculated))}${missingTax ? `<small>Known entries only</small>` : ""}`;
            const netSales = `${escapeHtml(money(row.net_sales_calculated))}${missingTax ? `<small>Known entries only</small>` : ""}`;
            return `<tr>
                <td>${escapeHtml(row.branch_name || "Branch not recorded")}</td>
                <td><strong>${escapeHtml(row.product_name)}</strong><small>${escapeHtml(packageName)} | ${escapeHtml(version)}</small></td>
                <td class="numeric">${escapeHtml(number(row.units_sold))}</td>
                <td class="numeric">${enteredAmount}</td>
                <td class="numeric">${taxAmount}</td>
                <td class="numeric">${netSales}</td>
                <td>${escapeHtml(taxReview)}</td>
                <td class="numeric">${missingAmount ? escapeHtml(number(missingAmount)) : "0"}</td>
            </tr>`;
        }).join("");
    }

    async function loadReport() {
        if (state.busy) return;
        state.busy = true;
        setStatus("Loading sales report...", "");
        try {
            const selectedBranch = byId("salesReportBranch").value;
            const period = byId("salesReportPeriod").value || "TODAY";
            const query = new URLSearchParams({ period });
            if (selectedBranch) query.set("branchId", selectedBranch);
            const response = await getJson(`stock/sales-report.php?${query.toString()}`);
            const data = unwrapApiResponse(response, "Unable to load sales reports.");
            state.branch = data.branch || null;
            state.branches = Array.isArray(data.branches) ? data.branches : [];
            state.rows = Array.isArray(data.rows) ? data.rows : [];
            state.allBranches = data.all_branches === true;
            renderBranches(selectedBranch || (state.allBranches ? "ALL" : state.branch ? String(state.branch.branch_id) : ""));
            byId("salesReportRows").dataset.excludedLegacy = String(Number(data.excluded_legacy_entries || 0));
            renderMetrics(data.totals || {});
            renderRows();
            byId("salesReportRange").textContent = `${data.period_label || period} | ${data.start_date || "start date unavailable"} to ${data.end_date || "end date unavailable"}`;

            setStatus("", "");
        } catch (error) {
            setStatus(apiMessage(error), "error");
            byId("salesReportRows").innerHTML = '<tr><td colspan="8" class="stock-sales-empty">Sales reports could not be loaded. Reload the page to try again.</td></tr>';
            byId("salesReportMetrics").innerHTML = "";
        } finally {
            state.busy = false;
        }
    }


    byId("salesReportBranch").addEventListener("change", loadReport);
    byId("salesReportPeriod").addEventListener("change", loadReport);
    loadReport();
}());
