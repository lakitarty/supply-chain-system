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

    const done = change.recipients.filter(recipient => recipient.notification_status === "ACKNOWLEDGED").length;

    return [
        ["Acknowledged", `${done} of ${change.recipients.length} organisations`],
        ...change.recipients.map(recipient => [
            recipient.organization_name,
            recipient.acknowledged_at ? `Acknowledged on ${recipient.acknowledged_at}` : "Not acknowledged yet"
        ])
    ];
}

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