const historyRows = field("historyRows");
const historyMessage = field("historyMessage");
const historySearch = field("historySearch");
const typeFilter = field("typeFilter");
const statusFilter = field("statusFilter");
const historySummary = field("historySummary");
const historyEmpty = field("historyEmpty");

let historyChanges = [];

function escapeHistoryText(value) {
    return String(value ?? "").replace(/[&<>"']/g, character => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;"
    })[character]);
}

function historyDate(value) {
    return value ? String(value).slice(0, 10) : "Not recorded";
}

function historyType(value) {
    return ({
        BARCODE_CHANGE: "Barcode change",
        PACKAGING_CHANGE: "Packaging change",
        PRODUCT_INFORMATION_CHANGE: "Product information"
    })[value] || readableHistory(value);
}

function readableHistory(value) {
    return String(value ?? "Unknown")
        .toLowerCase()
        .replaceAll("_", " ")
        .replace(/\b\w/g, letter => letter.toUpperCase());
}

function historyStatusClass(status) {
    if (status === "PUBLISHED") return "status-published";
    if (status === "PENDING_APPROVAL") return "status-pending";
    return "";
}

function historySearchText(change) {
    return [
        change.product_name,
        change.manufacturer_name,
        change.old_barcode,
        change.new_barcode,
        change.old_pack_size,
        change.new_pack_size,
        change.old_packaging,
        change.new_packaging,
        change.reason,
        change.change_type,
        change.status,
        change.created_by_name
    ].join(" ").toLowerCase();
}

function visibleHistory() {
    const query = historySearch.value.trim().toLowerCase();

    return historyChanges.filter(change => {
        if (query && !historySearchText(change).includes(query)) return false;
        if (typeFilter.value && change.change_type !== typeFilter.value) return false;
        if (statusFilter.value && change.status !== statusFilter.value) return false;
        return true;
    });
}

function versionDetails(change) {
    const oldVersion = [
        change.old_barcode || "No previous barcode",
        change.old_pack_size || "No previous pack size",
        change.old_packaging || "No previous packaging"
    ].join(" · ");

    const newVersion = [
        change.new_barcode || "No barcode",
        change.new_pack_size || "No pack size",
        change.new_packaging || "No packaging"
    ].join(" · ");

    return `<span class="history-muted">Previous: ${escapeHistoryText(oldVersion)}</span>
            <span>New: ${escapeHistoryText(newVersion)}</span>`;
}

function detailMarkup(change) {
    return `<div class="history-detail-content">
        <strong>Reason:</strong> ${escapeHistoryText(change.reason || "Not provided")}<br>
        <strong>Created:</strong> ${escapeHistoryText(change.created_at || "Not recorded")}<br>
        <strong>Effective date:</strong> ${escapeHistoryText(change.effective_date || "Not recorded")}<br>
        <strong>Published:</strong> ${escapeHistoryText(change.published_at || "Not published")}<br>
        <strong>Prepared by:</strong> ${escapeHistoryText(change.created_by_name || "Not recorded")}
    </div>`;
}

function renderHistory() {
    const records = visibleHistory();
    historySummary.textContent = `${records.length} of ${historyChanges.length} records`;

    historyRows.replaceChildren();

    for (const change of records) {
        const row = document.createElement("tr");
        const statusClass = historyStatusClass(change.status);

        row.innerHTML = `
            <td>${escapeHistoryText(historyDate(change.created_at))}</td>
            <td class="history-product">
                ${escapeHistoryText(change.product_name)}
                <span class="history-muted">ID ${escapeHistoryText(change.product_change_id)}</span>
            </td>
            <td>${escapeHistoryText(change.manufacturer_name)}</td>
            <td>${escapeHistoryText(historyType(change.change_type))}</td>
            <td class="history-version">${versionDetails(change)}</td>
            <td class="history-status ${statusClass}">${escapeHistoryText(readableHistory(change.status))}</td>
            <td><button type="button" class="history-detail-button">View details</button></td>
        `;

        const detailRow = document.createElement("tr");
        detailRow.className = "history-detail-row";
        detailRow.hidden = true;

        const detailCell = document.createElement("td");
        detailCell.colSpan = 7;
        detailCell.innerHTML = detailMarkup(change);
        detailRow.append(detailCell);

        const detailButton = row.querySelector(".history-detail-button");
        detailButton.setAttribute("aria-expanded", "false");

        detailButton.addEventListener("click", () => {
            detailRow.hidden = !detailRow.hidden;
            detailButton.textContent = detailRow.hidden ? "View details" : "Hide details";
            detailButton.setAttribute("aria-expanded", String(!detailRow.hidden));
        });

        historyRows.append(row, detailRow);
    }

    historyEmpty.hidden = records.length !== 0;

    if (records.length === 0) {
        const row = document.createElement("tr");
        const cell = document.createElement("td");
        cell.colSpan = 7;
        cell.textContent = "No matching records.";
        row.append(cell);
        historyRows.append(row);
    }
}

async function loadHistory() {
    showMessage(historyMessage, "", "");

    try {
        const { ok, data } = await getJson("changes/list.php");

        if (!ok) {
            showMessage(historyMessage, data.error || "Unable to load product history.", "error");
            historyRows.replaceChildren();
            return;
        }

        historyChanges = Array.isArray(data.changes) ? data.changes : [];
        renderHistory();
    } catch {
        showMessage(historyMessage, "Unable to reach the server. Check that Apache and MySQL are running.", "error");
        historyRows.replaceChildren();
    }
}

historySearch.addEventListener("input", renderHistory);
typeFilter.addEventListener("change", renderHistory);
statusFilter.addEventListener("change", renderHistory);

shellReady.then(account => {
    if (account) {
        loadHistory();
    }
});
