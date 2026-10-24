(function () {
    "use strict";

    const byId = id => document.getElementById(id);
    const state = { branches: [], branch: null, alerts: [], dataIssues: null, busy: false };

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
        if (error && error.data && typeof error.data.message === "string") return error.data.message;
        return "The request could not be completed. Please try again.";
    }

    function setStatus(message, kind) {
        const node = byId("stockAlertsStatus");
        node.textContent = message || "";
        node.classList.toggle("is-error", kind === "error");
        node.classList.toggle("is-success", kind === "success");
    }

    function formatDate(value) {
        if (!value) return "Not recorded";
        const text = String(value);
        const date = text.slice(0, 10).split("-");
        const formatted = date.length === 3 ? `${date[2]}/${date[1]}/${date[0]}` : text;
        const timeMatch = text.match(/\s(\d{2}:\d{2})/);
        return timeMatch ? `${formatted} ${timeMatch[1]}` : formatted;
    }

    function renderBranches() {
        const select = byId("alertsBranch");
        select.innerHTML = state.branches.map(branch => {
            const area = branch.area_name ? ` | ${branch.area_name}` : " | County not assigned";
            return `<option value="${escapeHtml(branch.branch_id)}">${escapeHtml(branch.branch_name + area)}</option>`;
        }).join("");
        if (state.branch) select.value = String(state.branch.branch_id);
        select.disabled = state.branches.length <= 1;
        select.closest(".stock-alerts-field").hidden = state.branches.length === 0;
    }

    function renderMetrics() {
        const low = state.alerts.filter(alert => alert.alert_type === "LOW_STOCK").length;
        const out = state.alerts.filter(alert => alert.alert_type === "OUT_OF_STOCK").length;
        const suggested = state.alerts.reduce((total, alert) => total + Number(alert.recommended_order_quantity || 0), 0);
        byId("stockAlertsMetrics").innerHTML = [
            ["Active alerts", state.alerts.length],
            ["Out of stock", out],
            ["Suggested units to order", suggested.toLocaleString()]
        ].map(([label, value]) => `<div class="stock-alert-metric"><div class="stock-alert-metric-label">${label}</div><div class="stock-alert-metric-value">${value}</div></div>`).join("");
        byId("activeAlertCount").textContent = `${state.alerts.length} alert${state.alerts.length === 1 ? "" : "s"} | ${low} low stock`;
    }

    function alertReviewMessage(issue) {
        const productName = escapeHtml(issue.product_name || "Product");
        if (issue.issue_code === "PRODUCT_VERSION_MISMATCH") {
            return `${productName}: active alert #${escapeHtml(issue.stock_alert_id)} refers to a version of ${escapeHtml(issue.version_product_name || "another product")}. It is retained for reconciliation.`;
        }
        if (issue.issue_code === "BRANCH_UNALLOCATED") {
            return `${productName}: active alert #${escapeHtml(issue.stock_alert_id)} has no branch assigned and is excluded from branch alert counts.`;
        }
        return `${productName}: active alert #${escapeHtml(issue.stock_alert_id)} has no verified matching product version and needs review.`;
    }

    function renderLegacyIssues() {
        const node = byId("legacyAlertReview");
        const issues = state.dataIssues || {};
        const stockIssues = Array.isArray(issues.stock_records_needing_review) ? issues.stock_records_needing_review : [];
        const salesCount = Number(issues.unallocated_or_unversioned_sales_count || 0);
        const legacyAlerts = Array.isArray(issues.active_legacy_alerts) ? issues.active_legacy_alerts : [];
        const messages = [];

        stockIssues.forEach(issue => {
            if (issue.issue_code === "PRODUCT_VERSION_MISMATCH") {
                messages.push(`${escapeHtml(issue.product_name)} stock record #${escapeHtml(issue.retailer_stock_id)} references a version belonging to ${escapeHtml(issue.version_product_name || "another product")}.`);
            } else if (issue.issue_code === "BRANCH_UNALLOCATED") {
                messages.push(`${escapeHtml(issue.product_name)} stock record #${escapeHtml(issue.retailer_stock_id)} has no branch assigned.`);
            } else {
                messages.push(`${escapeHtml(issue.product_name)} stock record #${escapeHtml(issue.retailer_stock_id)} needs review.`);
            }
        });
        legacyAlerts.forEach(issue => messages.push(alertReviewMessage(issue)));
        if (salesCount > 0) messages.push(`${salesCount} historical sale record(s) have no branch or product-version assignment. They remain preserved but are excluded from version-specific demand analysis.`);

        node.hidden = messages.length === 0;
        node.innerHTML = messages.length
            ? `<h2>Legacy records need review</h2><p>These records are preserved. They are not automatically assigned to a branch or product version.</p><ul>${messages.map(message => `<li>${message}</li>`).join("")}</ul>`
            : "";
    }

    function renderAlerts() {
        byId("activeAlertCount").textContent = `${state.alerts.length} active alert${state.alerts.length === 1 ? "" : "s"}`;
        if (state.alerts.length === 0) {
            byId("stockAlertList").innerHTML = `<p class="stock-alert-empty">No active stock alerts for this branch.</p>`;
            return;
        }

        byId("stockAlertList").innerHTML = state.alerts.map(alert => {
            const out = alert.alert_type === "OUT_OF_STOCK";
            const kind = out ? "out-of-stock" : "low-stock";
            const label = out ? "Out of stock" : "Low stock";
            const pack = [alert.pack_size, alert.barcode ? `Barcode ${alert.barcode}` : ""].filter(Boolean).join(" | ");
            return `<article class="stock-alert-item">
                <div class="stock-alert-main">
                    <h3>${escapeHtml(alert.product_name)}</h3>
                    <p>${escapeHtml(pack || `Version ${alert.version_number || "unknown"}`)} | Created ${escapeHtml(formatDate(alert.generated_at))}</p>
                    <span class="stock-alert-type ${kind}">${label}</span>
                    <div class="stock-alert-detail-grid">
                        <span class="stock-alert-detail">On hand: <strong>${Number(alert.current_stock).toLocaleString()}</strong></span>
                        <span class="stock-alert-detail">Reorder level: <strong>${Number(alert.reorder_level).toLocaleString()}</strong></span>
                        <span class="stock-alert-detail">Avg. daily sales: <strong>${Number(alert.average_daily_sales).toFixed(2)}</strong></span>
                        <span class="stock-alert-detail">Lead time: <strong>${Number(alert.lead_time_days)} days</strong></span>
                        <span class="stock-alert-detail">Expected lead-time demand: <strong>${Number(alert.expected_demand).toFixed(2)}</strong></span>
                        <span class="stock-alert-detail">Suggested order: <strong>${Number(alert.recommended_order_quantity).toLocaleString()} units</strong></span>
                    </div>
                </div>
            </article>`;
        }).join("");
    }

    async function loadAlerts() {
        if (state.busy) return;
        state.busy = true;
        byId("refreshStockAlerts").disabled = true;
        setStatus("Loading stock alerts...", "");
        try {
            const selectedBranchId = byId("alertsBranch").value;
            const path = "stock/alerts.php" + (selectedBranchId ? `?branchId=${encodeURIComponent(selectedBranchId)}` : "");
            const response = await getJson(path);
            if (!response.ok || !response.data || response.data.success !== true) {
                const message = response.data && (response.data.message || response.data.error);
                throw new Error(message || `Unable to load stock alerts (HTTP ${response.status}).`);
            }
            const data = response.data;
            state.branch = data.branch || null;
            state.branches = Array.isArray(data.branches) ? data.branches : [];
            state.alerts = Array.isArray(data.alerts) ? data.alerts : [];
            state.dataIssues = data.data_issues || null;
            renderBranches();
            renderMetrics();
            renderLegacyIssues();
            renderAlerts();
            setStatus(state.branch
                ? `Showing active alerts for ${state.branch.branch_name}${state.branch.area_name ? ` | ${state.branch.area_name}` : " | County not assigned"}.`
                : "No active branch is available.", "");
        } catch (error) {
            setStatus(apiMessage(error), "error");
            byId("stockAlertList").innerHTML = `<p class="stock-alert-empty">Alerts could not be loaded. Refresh to try again.</p>`;
        } finally {
            state.busy = false;
            byId("refreshStockAlerts").disabled = false;
        }
    }

    byId("refreshStockAlerts").addEventListener("click", loadAlerts);
    byId("alertsBranch").addEventListener("change", loadAlerts);
    loadAlerts();
}());
