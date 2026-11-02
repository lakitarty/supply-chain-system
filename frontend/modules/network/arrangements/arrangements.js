const statusFilter = document.getElementById("arrangementStatusFilter");
const arrangementSelect = document.getElementById("arrangementSelect");
const arrangementContent = document.getElementById("arrangementContent");
const arrangementDetail = document.getElementById("arrangementDetail");
const arrangementMessage = document.getElementById("arrangementMessage");
const arrangementSummary = document.getElementById("arrangementSummary");
const acceptButton = document.getElementById("acceptArrangement");
const declineButton = document.getElementById("declineArrangement");
const refreshButton = document.getElementById("refreshArrangements");

const detailFields = {
    product: document.getElementById("detailProduct"),
    supplier: document.getElementById("detailSupplier"),
    area: document.getElementById("detailArea"),
    period: document.getElementById("detailPeriod"),
    decision: document.getElementById("detailDecision")
};

const DECISION_LABELS = {
    PENDING: "Awaiting my decision",
    ACCEPTED: "Accepted",
    DECLINED: "Declined"
};

const DECISION_ORDER = { PENDING: 0, ACCEPTED: 1, DECLINED: 2 };

let arrangements = [];
let busy = false;

function showMessage(text, type = "error") {
    arrangementMessage.textContent = text;
    arrangementMessage.className = `arrangement-message ${type}`;
}

function periodText(arrangement) {
    return arrangement.end_date
        ? `${arrangement.start_date} to ${arrangement.end_date}`
        : `From ${arrangement.start_date}, no planned end date`;
}

function optionLabel(arrangement) {
    return `${arrangement.product_name} | ${arrangement.supplier_name} | ${arrangement.area_name} | ${DECISION_LABELS[arrangement.decision]}`;
}

function selectedArrangement() {
    return arrangements.find(item => String(item.product_supplier_id) === arrangementSelect.value) ?? null;
}

function renderDetail() {
    const arrangement = selectedArrangement();
    arrangementDetail.hidden = arrangement === null;

    if (!arrangement) {
        return;
    }

    detailFields.product.textContent = arrangement.product_name;
    detailFields.supplier.textContent = arrangement.supplier_name;
    detailFields.area.textContent = arrangement.area_name;
    detailFields.period.textContent = periodText(arrangement);
    detailFields.decision.textContent = DECISION_LABELS[arrangement.decision];

    acceptButton.disabled = busy || arrangement.decision === "ACCEPTED";
    declineButton.disabled = busy || arrangement.decision === "DECLINED";
}

function renderSelect(keepId) {
    const filter = statusFilter.value;
    const items = arrangements.filter(item => filter === "ALL" || item.decision === filter);

    arrangementSelect.replaceChildren(
        new Option(items.length ? "Select a supply arrangement" : "No arrangements to show", "")
    );

    for (const item of items) {
        arrangementSelect.add(new Option(optionLabel(item), String(item.product_supplier_id)));
    }

    arrangementSelect.disabled = items.length === 0;

    if (keepId && items.some(item => String(item.product_supplier_id) === String(keepId))) {
        arrangementSelect.value = String(keepId);
    }

    renderDetail();
}

async function loadArrangements(keepId) {
    const result = await getJson("network/supply-arrangements-list.php");
    const data = result?.data || {};

    if (!result?.ok || data.success !== true) {
        throw new Error(data.error || data.message || "Unable to load supply arrangements.");
    }

    arrangements = [...data.arrangements].sort((a, b) =>
        DECISION_ORDER[a.decision] - DECISION_ORDER[b.decision]
        || a.product_name.localeCompare(b.product_name)
    );

    const pending = arrangements.filter(item => item.decision === "PENDING").length;
    arrangementSummary.textContent = arrangements.length
        ? `${pending} awaiting your decision, ${arrangements.length} in total.`
        : "No supply arrangements concern your organisation at the moment.";

    renderSelect(keepId);
}

async function decide(decision) {
    const arrangement = selectedArrangement();

    if (!arrangement || busy) {
        return;
    }

    if (
        decision === "DECLINED"
        && !window.confirm(`Decline the supply of ${arrangement.product_name} by ${arrangement.supplier_name} in ${arrangement.area_name}?`)
    ) {
        return;
    }

    busy = true;
    renderDetail();
    showMessage("Saving your decision...", "info");

    try {
        const result = await postJson("network/supply-arrangement-decide.php", {
            appointmentId: Number(arrangement.product_supplier_id),
            decision
        });
        const data = result?.data || {};

        if (!result?.ok || data.success !== true) {
            throw new Error(data.error || data.message || "Unable to save your decision.");
        }

        await loadArrangements(arrangement.product_supplier_id);
        showMessage(data.message || "Decision saved.", "success");
    } catch (error) {
        showMessage(error.message || "Unable to save your decision.");
    } finally {
        busy = false;
        renderDetail();
    }
}

async function refresh() {
    showMessage("");

    try {
        await loadArrangements(arrangementSelect.value);
    } catch (error) {
        showMessage(error.message || "Unable to load supply arrangements.");
    }
}

async function initialiseArrangements() {
    try {
        const account = await shellReady;

        if (!account) {
            return;
        }

        const allowed = account.organization.organization_type === "RETAILER" && account.user.role === "ADMIN";

        if (!allowed) {
            arrangementContent.hidden = true;
            showMessage("Supply arrangements are available only to retailer organisation administrators.");
            return;
        }

        await loadArrangements();
    } catch (error) {
        showMessage(error.message || "Unable to load supply arrangements.");
    }
}

statusFilter.addEventListener("change", () => renderSelect(arrangementSelect.value));
arrangementSelect.addEventListener("change", renderDetail);
acceptButton.addEventListener("click", () => decide("ACCEPTED"));
declineButton.addEventListener("click", () => decide("DECLINED"));
refreshButton.addEventListener("click", refresh);

initialiseArrangements();
