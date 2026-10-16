const CHANGE_FIELD_IDS = ["productId", "changeType", "barcode", "packSize", "packaging", "effectiveDate", "reason"];

const changeSelect = field("changeSelect");
const changeDetail = field("changeDetail");
const changeMessage = field("changeMessage");
const changeActions = field("changeActions");
const actionButton = field("actionButton");
const actionNote = field("actionNote");
const prepareSection = field("prepareSection");
const prepareForm = field("prepareForm");
const formMessage = field("formMessage");
const saveButton = field("saveButton");

const POLL_MILLISECONDS = 4000;

const threadSection = field("threadSection");
const threadLog = field("threadLog");
const messageForm = field("messageForm");
const messageMessage = field("messageMessage");
const sendButton = field("sendButton");

let changes = [];
let threadChangeId = null;
let lastMessageId = 0;
let permissions = { can_create: false, can_publish: false };

const acknowledgementActions = document.createElement("div");
acknowledgementActions.className = "form-actions acknowledgement-actions";
acknowledgementActions.hidden = true;

const acknowledgementViewButton = document.createElement("button");
acknowledgementViewButton.type = "button";
acknowledgementViewButton.className = "acknowledgement-view-button";
acknowledgementViewButton.textContent = "View acknowledgements";

acknowledgementActions.append(acknowledgementViewButton);
changeDetail.insertAdjacentElement("afterend", acknowledgementActions);

const isIncoming = change => change.direction === "INCOMING";
const orNone = value => value ?? "None";
const selectedChange = () => changes.find(change => String(change.product_change_id) === changeSelect.value);

function describeChange(change) {
    const origin = isIncoming(change) ? `From ${change.manufacturer_name}` : "Your change";

    return `${change.created_at.slice(0, 10)}  |  ${origin}  |  ${change.product_name}  |  ${readable(change.status)}`;
}

function acknowledgementRows(change) {
    if (isIncoming(change) || change.status !== "PUBLISHED") {
        return [];
    }

    const recipients = change.recipients || [];
    const done = recipients.filter(
        recipient => recipient.notification_status === "ACKNOWLEDGED"
    ).length;

    return [
        ["Acknowledgements", `${done} of ${recipients.length} organisations acknowledged`]
    ];
}

function showAcknowledgements(change) {
    if (!change || isIncoming(change) || change.status !== "PUBLISHED") {
        return;
    }

    const recipients = change.recipients || [];
    const acknowledged = recipients.filter(
        recipient => recipient.notification_status === "ACKNOWLEDGED"
    ).length;

    const overlay = document.createElement("div");
    overlay.className = "acknowledgement-overlay";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");
    overlay.setAttribute("aria-labelledby", "acknowledgementDialogTitle");

    const panel = document.createElement("section");
    panel.className = "acknowledgement-dialog";

    const heading = element("h2", "acknowledgement-dialog-title",
        "Organisation acknowledgements");

    heading.id = "acknowledgementDialogTitle";

    const summary = element(
        "p",
        "acknowledgement-dialog-summary",
        `${acknowledged} of ${recipients.length} organisations acknowledged receipt`
    );

    const closeButton = element(
        "button",
        "acknowledgement-close-button",
        "Close"
    );
    closeButton.type = "button";

    const list = document.createElement("ul");
    list.className = "acknowledgement-list";

    for (const recipient of recipients) {
        const done = recipient.notification_status === "ACKNOWLEDGED";
        const row = document.createElement("li");
        row.className = "acknowledgement-row";

        const organizationName = element(
            "span",
            "acknowledgement-organization",
            recipient.organization_name
        );

        const status = element(
            "span",
            done ? "acknowledgement-status is-acknowledged" : "acknowledgement-status is-pending",
            done ? "Acknowledged" : "Awaiting acknowledgement"
        );

        row.append(organizationName, status);

        if (done && recipient.acknowledged_at) {
            row.append(element(
                "span",
                "acknowledgement-date",
                `Received on ${recipient.acknowledged_at}`
            ));
        }

        list.append(row);
    }

    const dialogHeader = document.createElement("div");
    dialogHeader.className = "acknowledgement-dialog-header";
    dialogHeader.append(heading, closeButton);

    panel.append(dialogHeader, summary, list);
    overlay.append(panel);

    function closeDialog() {
        document.removeEventListener("keydown", handleDialogKey);
        overlay.remove();

        if (acknowledgementViewButton.isConnected) {
            acknowledgementViewButton.focus();
        }
    }

    function handleDialogKey(event) {
        if (event.key === "Escape") {
            closeDialog();
        }
    }

    closeButton.addEventListener("click", closeDialog);
    overlay.addEventListener("click", event => {
        if (event.target === overlay) {
            closeDialog();
        }
    });

    document.addEventListener("keydown", handleDialogKey);
    document.body.append(overlay);
    closeButton.focus();
}

acknowledgementViewButton.addEventListener("click", () => {
    showAcknowledgements(selectedChange());
});

function detailRows(change) {
    const yourResponse = isIncoming(change)
        ? [["Your response", change.my_acknowledged_at ? `Acknowledged on ${change.my_acknowledged_at}` : "Not acknowledged yet"]]
        : [];

    return [
        ["Product", change.product_name],
        ["Manufacturer", change.manufacturer_name],
        ["Change type", readable(change.change_type)],
        ["Barcode", `${orNone(change.old_barcode)}  to  ${change.new_barcode}`],
        ["Pack size", `${orNone(change.old_pack_size)}  to  ${orNone(change.new_pack_size)}`],
        ["Packaging", `${orNone(change.old_packaging)}  to  ${orNone(change.new_packaging)}`],
        ["Effective date", change.effective_date],
        ["Reason", change.reason],
        ["Status", readable(change.status)],
        ["Prepared by", change.created_by_name],
        ...yourResponse,
        ...acknowledgementRows(change)
    ];
}

function actionFor(change) {
    if (isIncoming(change)) {
        return change.my_acknowledged_at ? null : { label: "Acknowledge this change", path: "changes/acknowledge.php" };
    }

    if (change.status === "PENDING_APPROVAL" && permissions.can_publish) {
        return { label: "Publish to downstream organisations", path: "changes/publish.php" };
    }

    return null;
}

function noteFor(change) {
    return !isIncoming(change) && change.status === "PENDING_APPROVAL" && !permissions.can_publish
        ? "An administrator must publish this change before anyone else sees it."
        : "";
}

function messageLine(message) {
    const line = element("div", "message-line");
    line.append(
        element("span", "message-meta", `${message.sender_name}  |  ${message.organization_name}  |  ${message.sent_at.slice(11, 16)}`),
        element("span", "", message.message_text)
    );

    return line;
}

async function pollMessages() {
    const change = selectedChange();

    if (!change || change.status !== "PUBLISHED") {
        return;
    }

    const { ok, data } = await getJson(`messages/list.php?changeId=${change.product_change_id}&after=${lastMessageId}`);

    if (!ok || data.messages.length === 0 || selectedChange()?.product_change_id !== change.product_change_id) {
        return;
    }

    threadLog.append(...data.messages.map(messageLine));
    lastMessageId = Number(data.messages.at(-1).message_id);
    threadLog.scrollTop = threadLog.scrollHeight;
}

function renderThread(change) {
    const shown = change?.status === "PUBLISHED";

    threadSection.hidden = !shown;

    if (shown && change.product_change_id !== threadChangeId) {
        threadChangeId = change.product_change_id;
        lastMessageId = 0;
        threadLog.replaceChildren();
        pollMessages();
    }
}

function renderSelectedChange() {
    const change = selectedChange();
    const action = change ? actionFor(change) : null;

    renderThread(change);

    renderDetailList(changeDetail, change ? detailRows(change) : []);

    const canViewAcknowledgements = Boolean(
        change &&
        !isIncoming(change) &&
        change.status === "PUBLISHED" &&
        Array.isArray(change.recipients) &&
        change.recipients.length > 0
    );

    acknowledgementActions.hidden = !canViewAcknowledgements;

    if (canViewAcknowledgements) {
        const done = change.recipients.filter(
            recipient => recipient.notification_status === "ACKNOWLEDGED"
        ).length;

        acknowledgementViewButton.textContent =
            `View acknowledgements (${done}/${change.recipients.length})`;
    }

    changeActions.hidden = action === null;
    actionButton.textContent = action?.label ?? "";
    actionNote.textContent = change ? noteFor(change) : "";
}

function renderChanges(selectedId) {
    changeSelect.replaceChildren(
        ...(changes.length === 0
            ? [option("", "No product changes yet")]
            : changes.map(change => option(String(change.product_change_id), describeChange(change))))
    );
    changeSelect.disabled = changes.length === 0;

    if (selectedId !== undefined) {
        changeSelect.value = String(selectedId);
    }

    renderSelectedChange();
}

async function loadChanges(selectedId) {
    const { ok, data } = await getJson("changes/list.php");

    if (!ok) {
        showMessage(changeMessage, data.error || "Unable to load the changes.", "error");
        return;
    }

    changes = data.changes;
    permissions = data;
    prepareSection.hidden = !data.can_create;
    renderChanges(selectedId);
}

async function runAction() {
    const change = selectedChange();

    const data = await sendForm({
        form: changeActions,
        button: actionButton,
        message: changeMessage,
        path: actionFor(change).path,
        payload: { changeId: change.product_change_id }
    });

    if (data !== null) {
        await loadChanges(change.product_change_id);
        showMessage(changeMessage, data.message, "success");
    }
}

async function submitChange(event) {
    event.preventDefault();

    const data = await sendForm({
        form: prepareForm,
        button: saveButton,
        message: formMessage,
        path: "changes/create.php",
        payload: Object.fromEntries(CHANGE_FIELD_IDS.map(id => [id, fieldValue(id)]))
    });

    if (data !== null) {
        prepareForm.reset();
        await loadChanges(data.change_id);
        showMessage(changeMessage, data.message, "success");
    }
}

async function loadOwnProducts(account) {
    const { data } = await getJson("products/list.php");
    const own = (data.products ?? []).filter(product => Number(product.manufacturer_id) === account.organization.organization_id);

    field("productId").replaceChildren(...own.map(product => option(String(product.product_id), product.product_name)));
}

async function submitMessage(event) {
    event.preventDefault();

    const data = await sendForm({
        form: messageForm,
        button: sendButton,
        message: messageMessage,
        path: "messages/send.php",
        payload: { changeId: selectedChange().product_change_id, text: fieldValue("text") }
    });

    if (data !== null) {
        messageForm.reset();
        await pollMessages();
    }
}

actionButton.addEventListener("click", runAction);
messageForm.addEventListener("submit", submitMessage);
clearErrorOnInput(messageForm);
setInterval(() => {
    if (!document.hidden) {
        pollMessages();
    }
}, POLL_MILLISECONDS);
changeSelect.addEventListener("change", renderSelectedChange);
prepareForm.addEventListener("submit", submitChange);
clearErrorOnInput(prepareForm);

shellReady.then(async account => {
    if (account) {
        await loadChanges();
        await loadOwnProducts(account);
    }
});