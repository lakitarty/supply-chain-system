const relationshipSelect = field("relationshipSelect");
const relationshipDetail = field("relationshipDetail");
const relationshipMessage = field("relationshipMessage");
const relationshipActions = field("relationshipActions");
const acceptButton = field("acceptButton");
const declineButton = field("declineButton");
const endButton = field("endButton");
const connectForm = field("connectForm");
const connectMessage = field("connectMessage");
const findButton = field("findButton");
const requestButton = field("requestButton");
const foundOrganisation = field("foundOrganisation");

let relationships = [];
let myOrganizationId = 0;

const iRequested = relationship => Number(relationship.requested_by_organization_id) === myOrganizationId;
const awaitingMe = relationship => relationship.status === "PENDING" && !iRequested(relationship);
const selectedRelationship = () => relationships.find(relationship => String(relationship.relationship_id) === relationshipSelect.value);

function directionText(relationship) {
    return Number(relationship.i_supply) === 1 ? "You supply them" : "They supply you";
}

function statusText(relationship) {
    if (relationship.status === "PENDING") {
        return awaitingMe(relationship) ? "Awaiting your answer" : "Waiting for them to answer";
    }

    return relationship.status === "ACTIVE" ? "Active" : "Ended";
}

function describeRelationship(relationship) {
    return `${relationship.partner_name}  |  ${directionText(relationship)}  |  ${statusText(relationship)}`;
}

function detailRows(relationship) {
    return [
        ["Organisation", relationship.partner_name],
        ["Type", TYPE_LABELS[relationship.partner_type]],
        ["Direction", directionText(relationship)],
        ["Status", statusText(relationship)],
        ["Since", relationship.start_date],
        ...(relationship.end_date ? [["Ended on", relationship.end_date]] : [])
    ];
}

function renderActions(relationship) {
    const answering = relationship && awaitingMe(relationship);
    const endable = relationship && (relationship.status === "ACTIVE" || (relationship.status === "PENDING" && iRequested(relationship)));

    acceptButton.hidden = !answering;
    declineButton.hidden = !answering;
    endButton.hidden = !endable;
    endButton.textContent = relationship?.status === "PENDING" ? "Withdraw request" : "End relationship";
    relationshipActions.hidden = !answering && !endable;
}

function renderSelected() {
    const relationship = selectedRelationship();

    renderDetailList(relationshipDetail, relationship ? detailRows(relationship) : []);
    renderActions(relationship);
}

function renderRelationships(selectedId) {
    relationshipSelect.replaceChildren(
        ...(relationships.length === 0
            ? [option("", "No relationships yet")]
            : relationships.map(relationship => option(String(relationship.relationship_id), describeRelationship(relationship))))
    );
    relationshipSelect.disabled = relationships.length === 0;

    if (selectedId !== undefined) {
        relationshipSelect.value = String(selectedId);
    }

    renderSelected();
}

async function loadRelationships(selectedId) {
    const { ok, data } = await getJson("network/list.php");

    if (!ok) {
        showMessage(relationshipMessage, data.error || "Unable to load the supply network.", "error");
        return;
    }

    relationships = data.relationships;
    myOrganizationId = Number(data.organization_id);
    renderRelationships(selectedId);
}

async function runAction(button, path, payload) {
    const relationship = selectedRelationship();

    const data = await sendForm({
        form: relationshipActions,
        button,
        message: relationshipMessage,
        path,
        payload: { relationshipId: relationship.relationship_id, ...payload }
    });

    if (data !== null) {
        await loadRelationships(relationship.relationship_id);
        showMessage(relationshipMessage, data.message, "success");
    }
}

async function findOrganisation() {
    showMessage(connectMessage, "", "");
    clearFieldErrors(connectForm);
    field("partnerId").value = "";
    foundOrganisation.textContent = "";

    const { ok, data } = await getJson(`network/find.php?term=${encodeURIComponent(fieldValue("term"))}`);

    if (!ok) {
        showMessage(connectMessage, data.error || "Unable to search.", "error");
        return;
    }

    field("partnerId").value = data.organization.organization_id;
    foundOrganisation.textContent = `Found: ${data.organization.organization_name} (${TYPE_LABELS[data.organization.organization_type]})`;
}

async function submitRequest(event) {
    event.preventDefault();

    const data = await sendForm({
        form: connectForm,
        button: requestButton,
        message: connectMessage,
        path: "network/request.php",
        payload: { partnerId: fieldValue("partnerId"), direction: fieldValue("direction") }
    });

    if (data !== null) {
        connectForm.reset();
        foundOrganisation.textContent = "";
        await loadRelationships(data.relationship_id);
        showMessage(connectMessage, data.message, "success");
    }
}

acceptButton.addEventListener("click", () => runAction(acceptButton, "network/respond.php", { decision: "ACCEPT" }));
declineButton.addEventListener("click", () => runAction(declineButton, "network/respond.php", { decision: "DECLINE" }));
endButton.addEventListener("click", () => runAction(endButton, "network/end.php", {}));
relationshipSelect.addEventListener("change", renderSelected);
findButton.addEventListener("click", findOrganisation);
connectForm.addEventListener("submit", submitRequest);
clearErrorOnInput(connectForm);

shellReady.then(account => {
    if (account) {
        loadRelationships();
    }
});