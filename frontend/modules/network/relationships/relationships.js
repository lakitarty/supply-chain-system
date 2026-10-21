(() => {
const SUPPLYING_TYPES = ["MANUFACTURER", "SUPPLIER", "MANUFACTURER_SUPPLIER"];
const SUPPLIED_TYPES = ["SUPPLIER", "RETAILER", "MANUFACTURER_SUPPLIER"];

const TYPE_LABELS = {
    MANUFACTURER: "Manufacturer",
    SUPPLIER: "Supplier",
    MANUFACTURER_SUPPLIER: "Manufacturer and Supplier",
    RETAILER: "Retailer"
};

const TYPE_ORDER = [
    "MANUFACTURER",
    "SUPPLIER",
    "MANUFACTURER_SUPPLIER",
    "RETAILER"
];

const DIRECTION_LABELS = {
    THEY_SUPPLY_ME: "They supply me",
    I_SUPPLY_THEM: "I supply them"
};

const GROUP_ORDER = [
    "Awaiting your answer",
    "Requests you sent",
    "Organisations that supply you",
    "Organisations you supply",
    "Connected through your partners",
    "Ended"
];

const relationshipSelect = field("relationshipSelect");
const detailOverlay = field("detailOverlay");
const detailBackdrop = field("detailBackdrop");
const detailCloseButton = field("closeDetail");
const relationshipDetail = field("relationshipDetail");
const relationshipMessage = field("relationshipMessage");
const relationshipActions = field("relationshipActions");
const acceptButton = field("acceptButton");
const declineButton = field("declineButton");
const endButton = field("endButton");
const registerOrganizationButton = field("registerOrganizationButton");
const registerOrganisationNote = field("registerOrganisationNote");
const closeDirectoryButton = field("closeDirectoryButton");
const openDirectoryButton = field("openDirectoryButton");
const directoryModalBackdrop = field("directoryModalBackdrop");
const searchSection = field("searchSection");
const searchTerm = field("searchTerm");
const searchCount = field("searchCount");
const organizationTypeFilter = field("organizationTypeFilter");
const directoryResults = field("directoryResults");
const selectedOrganization = field("selectedOrganization");
const connectForm = field("connectForm");
const connectMessage = field("connectMessage");
const requestButton = field("requestButton");
const direction = field("direction");
const directionHint = field("directionHint");
const eligibilityMessage = field("eligibilityMessage");
const eligibilityTitle = field("eligibilityTitle");
const eligibilityText = field("eligibilityText");
const networkList = field("networkList");
const networkFilter = field("networkFilter");
const networkCount = field("networkCount");
const networkEmptyState = field("networkEmptyState");
const networkDetailContent = field("networkDetailContent");
const detailPartnerAvatar = field("detailPartnerAvatar");
const detailPartnerName = field("detailPartnerName");
const detailPartnerType = field("detailPartnerType");
const detailStatus = field("detailStatus");

let entries = [];
let directory = [];
let myOrganizationId = 0;
let myType = "";
let networkFilterMode = "ALL";

const isDirect = entry => entry.kind === "DIRECT";
const iRequested = entry => Number(entry.requested_by_organization_id) === myOrganizationId;
const awaitingMe = entry => isDirect(entry) && entry.status === "PENDING" && !iRequested(entry);
const selectedEntry = () => entries.find(entry => entry.key === relationshipSelect.value);

function openDirectoryModal() {
    searchSection.hidden = false;
    directoryModalBackdrop.hidden = false;
    document.body.classList.add("directory-modal-open");
    renderDirectory();
    searchTerm.focus();
}

function closeDirectoryModal() {
    searchSection.hidden = true;
    directoryModalBackdrop.hidden = true;
    document.body.classList.remove("directory-modal-open");
    openDirectoryButton.focus();
}

function openDetailPanel() {
    if (!selectedEntry()) {
        return;
    }

    detailOverlay.hidden = false;
    document.body.classList.add("detail-panel-open");
    detailCloseButton.focus();
}

function closeDetailPanel() {
    detailOverlay.hidden = true;
    document.body.classList.remove("detail-panel-open");
    networkFilter.focus();
}

function organizationTypeLabel(type) {
    return TYPE_LABELS[type] || "Unknown organisation type";
}

function initials(name) {
    return String(name || "")
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(word => word.charAt(0))
        .join("")
        .toUpperCase() || "?";
}

function makeElement(tag, className, text) {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    if (text !== undefined) {
        node.textContent = text;
    }

    return node;
}

function logoImageUrl(path) {
    if (
        typeof path !== "string" ||
        !/^uploads\/organization-logos\/org-\d+-[a-f0-9]{32}\.(jpg|png|webp)$/.test(path)
    ) {
        return null;
    }

    return new URL("../../../../" + path, window.location.href).href;
}

function populateAvatar(container, name, logoPath) {
    const source = logoImageUrl(logoPath);

    container.replaceChildren();
    container.classList.toggle("has-logo", Boolean(source));

    if (!source) {
        container.textContent = initials(name);
        return;
    }

    const image = document.createElement("img");
    image.src = source;
    image.alt = "";
    image.loading = "lazy";

    image.addEventListener("error", () => {
        container.classList.remove("has-logo");
        container.textContent = initials(name);
    }, { once: true });

    container.append(image);
}

function makeAvatar(name, large = false, logoPath = null) {
    const avatar = makeElement(
        "span",
        large ? "partner-avatar partner-avatar-large" : "partner-avatar"
    );

    populateAvatar(avatar, name, logoPath);

    return avatar;
}

function validDirections(partnerType) {
    const partnerSuppliesMe =
        SUPPLYING_TYPES.includes(partnerType) &&
        SUPPLIED_TYPES.includes(myType) &&
        !(partnerType === "MANUFACTURER" && myType === "RETAILER");

    const I_supplyThem =
        SUPPLYING_TYPES.includes(myType) &&
        SUPPLIED_TYPES.includes(partnerType) &&
        !(myType === "MANUFACTURER" && partnerType === "RETAILER");

    return [
        ...(partnerSuppliesMe ? ["THEY_SUPPLY_ME"] : []),
        ...(I_supplyThem ? ["I_SUPPLY_THEM"] : [])
    ];
}

function groupOf(entry) {
    if (!isDirect(entry)) {
        return "Connected through your partners";
    }

    if (entry.status === "INACTIVE") {
        return "Ended";
    }

    if (entry.status === "PENDING") {
        return awaitingMe(entry) ? "Awaiting your answer" : "Requests you sent";
    }

    return Number(entry.i_supply) === 1
        ? "Organisations you supply"
        : "Organisations that supply you";
}

function directionText(entry) {
    const text = Number(entry.i_supply) === 1
        ? "You supply them"
        : "They supply you";

    return isDirect(entry) ? text : `${text}, through ${entry.via}`;
}

function statusText(entry) {
    if (!isDirect(entry)) {
        return "Through partner";
    }

    if (entry.status === "PENDING") {
        return awaitingMe(entry) ? "Awaiting your answer" : "Waiting for them";
    }

    return entry.status === "ACTIVE" ? "Active" : "Ended";
}

function statusClass(entry) {
    if (!isDirect(entry)) {
        return "status-indirect";
    }

    if (entry.status === "PENDING") {
        return "status-pending";
    }

    return entry.status === "ACTIVE" ? "status-active" : "status-ended";
}

function detailRows(entry) {
    const contact = [
        ["Email", entry.partner_email],
        ["Phone", entry.partner_phone],
        ["Address", entry.partner_address],
        ["KRA PIN", entry.partner_kra_pin]
    ].filter(([, value]) => value);

    return [
        ["Organisation type", organizationTypeLabel(entry.partner_type)],
        ["Relationship", directionText(entry)],
        ["Status", statusText(entry)],
        ...(isDirect(entry) && entry.start_date ? [["Since", entry.start_date]] : []),
        ...(entry.end_date ? [["Ended on", entry.end_date]] : []),
        ...contact
    ];
}

function renderActions(entry) {
    const answering = Boolean(entry && awaitingMe(entry));
    const endable = Boolean(
        entry &&
        isDirect(entry) &&
        (
            entry.status === "ACTIVE" ||
            (entry.status === "PENDING" && iRequested(entry))
        )
    );

    acceptButton.hidden = !answering;
    declineButton.hidden = !answering;
    endButton.hidden = !endable;
    endButton.textContent = entry?.status === "PENDING"
        ? "Withdraw request"
        : "End relationship";

    relationshipActions.hidden = !answering && !endable;
}

function renderSelected() {
    const entry = selectedEntry();

    renderNetworkList();

    if (!entry) {
        networkEmptyState.hidden = false;
        networkDetailContent.hidden = true;
        relationshipActions.hidden = true;
        relationshipDetail.replaceChildren();
        detailPartnerName.textContent = "";
        detailPartnerType.textContent = "";
        detailPartnerAvatar.textContent = "?";
        detailStatus.textContent = "";
        detailStatus.className = "relationship-status";
        return;
    }

    networkEmptyState.hidden = true;
    networkDetailContent.hidden = false;

    detailPartnerName.textContent = entry.partner_name || "Organisation";
    detailPartnerType.textContent = organizationTypeLabel(entry.partner_type);
    populateAvatar(detailPartnerAvatar, entry.partner_name, entry.partner_logo_path);
    detailStatus.textContent = statusText(entry);
    detailStatus.className = `relationship-status ${statusClass(entry)}`;

    renderDetailList(relationshipDetail, detailRows(entry));
    renderActions(entry);
}

function matchesNetworkFilter(entry) {
    switch (networkFilterMode) {
        case "PENDING":
            return isDirect(entry) && entry.status === "PENDING";
        case "DIRECT":
            return isDirect(entry) && entry.status !== "INACTIVE";
        case "INDIRECT":
            return !isDirect(entry);
        case "ENDED":
            return isDirect(entry) && entry.status === "INACTIVE";
        default:
            return true;
    }
}

function filteredNetworkEntries() {
    const term = networkFilter.value.trim().toLowerCase();

    return entries.filter(entry => {
        const text = [
            entry.partner_name,
            entry.partner_type,
            entry.via,
            directionText(entry),
            statusText(entry)
        ].filter(Boolean).join(" ").toLowerCase();

        return matchesNetworkFilter(entry) && text.includes(term);
    });
}

function renderNetworkList() {
    const filtered = filteredNetworkEntries();
    const selectedKey = relationshipSelect.value;

    networkCount.textContent = `${filtered.length} ${filtered.length === 1 ? "entry" : "entries"}`;
    networkList.replaceChildren();

    if (filtered.length === 0) {
        const empty = makeElement("div", "network-list-empty");
        const title = makeElement(
            "strong",
            "",
            entries.length === 0 ? "Your network starts here" : "No matching organisations"
        );
        const description = makeElement(
            "p",
            "",
            entries.length === 0
                ? "Use Find an organisation to discover eligible partners and request a connection."
                : "Try another search term or relationship filter."
        );

        empty.append(title, description);
        networkList.append(empty);
        return;
    }

    filtered.forEach(entry => {
        const button = makeElement(
            "button",
            `network-item${entry.key === selectedKey ? " is-selected" : ""}`
        );

        button.type = "button";
        button.setAttribute("aria-pressed", String(entry.key === selectedKey));

        const copy = makeElement("span", "network-item-copy");
        const name = makeElement("span", "network-item-name", entry.partner_name);
        const meta = makeElement(
            "span",
            "network-item-meta",
            `${organizationTypeLabel(entry.partner_type)} · ${directionText(entry)}`
        );
        const status = makeElement(
            "span",
            `relationship-status ${statusClass(entry)}`,
            statusText(entry)
        );

        copy.append(name, meta);
        button.append(makeAvatar(entry.partner_name, false, entry.partner_logo_path), copy, status);

        button.addEventListener("click", () => {
            relationshipSelect.value = entry.key;
            renderSelected();
            openDetailPanel();
        });

        networkList.append(button);
    });
}


function renderNetwork(selectedKey) {
    const previousKey = relationshipSelect.value;

    entries.sort((a, b) => {
        const aGroup = GROUP_ORDER.indexOf(groupOf(a));
        const bGroup = GROUP_ORDER.indexOf(groupOf(b));

        return aGroup - bGroup ||
            String(a.partner_name).localeCompare(String(b.partner_name));
    });

    relationshipSelect.replaceChildren(
        option("", "Select a relationship"),
        ...entries.map(entry => option(entry.key, entry.partner_name))
    );

    const keyToKeep = selectedKey && entries.some(entry => entry.key === selectedKey)
        ? selectedKey
        : previousKey && entries.some(entry => entry.key === previousKey)
            ? previousKey
            : entries[0]?.key || "";

    relationshipSelect.value = keyToKeep;
    renderNetworkList();
    renderSelected();
}

async function loadNetwork(selectedKey) {
    const { ok, data } = await getJson("network/list.php");

    if (!ok) {
        showMessage(relationshipMessage, data.error || "Unable to load the supply network.", "error");
        return;
    }

    myOrganizationId = Number(data.organization_id);

    entries = [
        ...data.relationships.map(relationship => ({
            ...relationship,
            kind: "DIRECT",
            key: `direct-${relationship.relationship_id}`
        })),
        ...data.indirect.map(connection => ({
            ...connection,
            kind: "INDIRECT",
            key: `via-${connection.partner_id}`
        }))
    ];

    renderNetwork(selectedKey);
}

function renderSelectedOrganization(partner) {
    selectedOrganization.replaceChildren();

    if (!partner) {
        const placeholder = makeElement("div", "selection-placeholder");
        const mark = makeElement("span", "placeholder-mark", "+");
        mark.setAttribute("aria-hidden", "true");

        const copy = makeElement("span");
        copy.append(
            makeElement("strong", "", "No organisation selected"),
            makeElement("small", "", "Choose a result from the directory.")
        );

        placeholder.append(mark, copy);
        selectedOrganization.append(placeholder);
        return;
    }

    const card = makeElement("div", "selected-organization-card");
    const copy = makeElement("div", "selected-organization-copy");

    copy.append(
        makeElement("strong", "", partner.organization_name),
        makeElement("small", "", organizationTypeLabel(partner.organization_type))
    );

    card.append(makeAvatar(partner.organization_name, false, partner.logo_path), copy);
    selectedOrganization.append(card);
}

function explainIneligibility(partner) {
    if (myType === "RETAILER" && partner.organization_type === "MANUFACTURER") {
        return [
            "Direct supply connection unavailable",
            `${partner.organization_name} is registered as a Manufacturer. Manufacturers cannot establish direct supply relationships with Retailers under MSRC rules. To supply retailers directly, this organisation must register as Manufacturer and Supplier. Product updates and promotions are separate from supply relationships.`
        ];
    }

    if (myType === "MANUFACTURER" && partner.organization_type === "RETAILER") {
        return [
            "Your organisation cannot supply retailers directly",
            "Your organisation is registered as a Manufacturer. To supply retailers directly, it must register as Manufacturer and Supplier. Product updates and promotions are separate from supply relationships."
        ];
    }

    return [
        "This supply relationship is not permitted",
        "The registered organisation types do not permit this supply relationship. Review the permitted organisation types before continuing."
    ];
}

function syncRequestButton(directions) {
    requestButton.disabled = !directions.includes(direction.value);
}

function renderDirections() {
    const partner = directory.find(
        organization => String(organization.organization_id) === field("partnerId").value
    );

    const previousDirection = direction.value;
    const directions = partner ? validDirections(partner.organization_type) : [];

    direction.replaceChildren(
        option("", partner ? "Choose a relationship direction" : "Choose an organisation first"),
        ...directions.map(value => option(value, DIRECTION_LABELS[value]))
    );

    direction.disabled = directions.length === 0;

    if (directions.includes(previousDirection)) {
        direction.value = previousDirection;
    } else {
        direction.value = "";
    }

    renderSelectedOrganization(partner);
    eligibilityMessage.hidden = true;
    eligibilityTitle.textContent = "";
    eligibilityText.textContent = "";

    if (!partner) {
        directionHint.textContent = "The available direction depends on both registered organisation types.";
        requestButton.disabled = true;
        renderDirectorySelection();
        return;
    }

    if (directions.length === 0) {
        const [title, message] = explainIneligibility(partner);

        eligibilityTitle.textContent = title;
        eligibilityText.textContent = message;
        eligibilityMessage.hidden = false;
        directionHint.textContent = "Choose another organisation to continue.";
    } else {
        directionHint.textContent = "Choose the direction that reflects the actual supply arrangement.";
    }

    syncRequestButton(directions);
    renderDirectorySelection();
}

function renderDirectorySelection() {
    const selectedId = field("partnerId").value;

    directoryResults.querySelectorAll(".directory-result").forEach(button => {
        const isSelected = button.dataset.organizationId === selectedId;

        button.classList.toggle("is-selected", isSelected);
        button.setAttribute("aria-pressed", String(isSelected));
    });
}

function renderDirectory() {
    const term = searchTerm.value.trim().toLowerCase();
    const selectedType = organizationTypeFilter.value;

    const matches = directory
        .filter(organization => {
            const nameMatches = organization.organization_name.toLowerCase().includes(term);
            const typeMatches = !selectedType || organization.organization_type === selectedType;

            return nameMatches && typeMatches;
        })
        .sort((a, b) => {
            const typeDifference =
                TYPE_ORDER.indexOf(a.organization_type) - TYPE_ORDER.indexOf(b.organization_type);

            return typeDifference ||
                a.organization_name.localeCompare(b.organization_name);
        });

    const oldSelectedId = field("partnerId").value;

    field("partnerId").replaceChildren(
        option("", "Select an organisation"),
        ...matches.map(organization =>
            option(String(organization.organization_id), organization.organization_name)
        )
    );

    if (matches.some(organization => String(organization.organization_id) === oldSelectedId)) {
        field("partnerId").value = oldSelectedId;
    } else {
        field("partnerId").value = "";
    }

    searchCount.textContent =
        `${matches.length} ${matches.length === 1 ? "organisation" : "organisations"} found`;

    directoryResults.replaceChildren();

    if (matches.length === 0) {
        directoryResults.append(
            makeElement(
                "div",
                "directory-empty",
                "No registered organisations match these filters. Try another name or organisation type."
            )
        );
    } else {
        matches.forEach(organization => {
            const eligible = validDirections(organization.organization_type).length > 0;
            const button = makeElement(
                "button",
                `directory-result${String(organization.organization_id) === field("partnerId").value ? " is-selected" : ""}`
            );

            button.type = "button";
            button.dataset.organizationId = String(organization.organization_id);
            button.setAttribute(
                "aria-pressed",
                String(String(organization.organization_id) === field("partnerId").value)
            );

            const copy = makeElement("span", "directory-result-copy");
            copy.append(
                makeElement("span", "directory-result-name", organization.organization_name),
                makeElement("span", "directory-result-type", organizationTypeLabel(organization.organization_type))
            );

            const eligibility = makeElement(
                "span",
                `directory-eligibility${eligible ? "" : " unavailable"}`,
                eligible ? "Compatible" : "Not eligible"
            );

            button.append(makeAvatar(organization.organization_name, false, organization.logo_path), copy, eligibility);

            button.addEventListener("click", () => {
                field("partnerId").value = String(organization.organization_id);
                renderDirections();
            });

            directoryResults.append(button);
        });
    }

    renderDirections();
}

async function loadDirectory() {
    const { ok, data } = await getJson("network/directory.php");

    if (!ok) {
        showMessage(connectMessage, data.error || "Unable to load organisations.", "error");
        return;
    }

    directory = data.organizations || [];
    renderDirectory();
}

async function runAction(button, path, payload) {
    const entry = selectedEntry();

    if (!entry) {
        return;
    }

    const data = await sendForm({
        form: relationshipActions,
        button,
        message: relationshipMessage,
        path,
        payload: { relationshipId: entry.relationship_id, ...payload }
    });

    if (data !== null) {
        await Promise.all([
            loadNetwork(entry.key),
            loadDirectory()
        ]);

        showMessage(relationshipMessage, data.message, "success");
    }
}

async function submitRequest(event) {
    event.preventDefault();

    const partnerId = field("partnerId").value;
    const selectedPartner = directory.find(
        organization => String(organization.organization_id) === partnerId
    );

    if (!selectedPartner || !validDirections(selectedPartner.organization_type).includes(direction.value)) {
        renderDirections();
        return;
    }

    const data = await sendForm({
        form: connectForm,
        button: requestButton,
        message: connectMessage,
        path: "network/request.php",
        payload: { partnerId, direction: direction.value }
    });

    if (data !== null) {
        searchTerm.value = "";
        organizationTypeFilter.value = "";

        await Promise.all([
            loadNetwork(`direct-${data.relationship_id}`),
            loadDirectory()
        ]);

        showMessage(connectMessage, data.message, "success");
    }
}


function setNetworkFilter(button) {
    networkFilterMode = button.dataset.networkFilter || "ALL";

    document.querySelectorAll("[data-network-filter]").forEach(tab => {
        const active = tab === button;

        tab.classList.toggle("is-active", active);
        tab.setAttribute("aria-pressed", String(active));
    });

    renderNetworkList();
}

acceptButton.addEventListener(
    "click",
    () => runAction(acceptButton, "network/respond.php", { decision: "ACCEPT" })
);

declineButton.addEventListener(
    "click",
    () => runAction(declineButton, "network/respond.php", { decision: "DECLINE" })
);

endButton.addEventListener(
    "click",
    () => runAction(endButton, "network/end.php", {})
);

relationshipSelect.addEventListener("change", renderSelected);
detailCloseButton.addEventListener("click", closeDetailPanel);
detailBackdrop.addEventListener("click", closeDetailPanel);
document.addEventListener("keydown", event => {
    if (event.key === "Escape" && !detailOverlay.hidden) {
        closeDetailPanel();
    }
});
openDirectoryButton.addEventListener("click", openDirectoryModal);

searchTerm.addEventListener("input", renderDirectory);

closeDirectoryButton.addEventListener("click", closeDirectoryModal);

directoryModalBackdrop.addEventListener("click", closeDirectoryModal);

document.addEventListener("keydown", event => {
    if (event.key === "Escape" && !searchSection.hidden) {
        closeDirectoryModal();
    }
});

registerOrganizationButton.addEventListener("click", () => {
    const target = registerOrganizationButton.dataset.registrationUrl;

    if (target) {
        window.location.href = target;
        return;
    }

    registerOrganisationNote.textContent = "An existing organisation registration page was not found. Registration needs to be connected to the Admin organisation-management workflow.";
    registerOrganisationNote.hidden = false;
});
organizationTypeFilter.addEventListener("change", renderDirectory);
field("partnerId").addEventListener("change", renderDirections);

direction.addEventListener("change", () => {
    syncRequestButton(validDirections(
        directory.find(
            organization => String(organization.organization_id) === field("partnerId").value
        )?.organization_type
    ));
});

networkFilter.addEventListener("input", renderNetworkList);

document.querySelectorAll("[data-network-filter]").forEach(button => {
    button.addEventListener("click", () => setNetworkFilter(button));
});

connectForm.addEventListener("submit", submitRequest);
clearErrorOnInput(connectForm);

shellReady.then(async account => {
    if (account) {
        myType = account.organization.organization_type;

        await Promise.all([
            loadNetwork(),
            loadDirectory()
        ]);
    }
});
})();
