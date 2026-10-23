const branchesPicker = field("branchesPicker");
const selectBranchButton = field("selectBranchButton");
const selectedBranchLabel = field("selectedBranchLabel");
const branchList = field("branchList");
const branchDetails = field("branchDetails");
const branchesMessage = field("branchesMessage");
const addBranchButton = field("addBranchButton");
const editorHost = field("editorHost");
const branchEditorPanel = field("branchEditorPanel");
const branchEditorTitle = field("branchEditorTitle");
const closeEditorButton = field("closeEditorButton");
const recordForm = field("recordForm");
const recordSelect = field("recordSelect");
const saveButton = field("saveButton");

let branches = [];
let selectedBranchId = null;
let editingBranchId = null;

function makeElement(tag, className, text) {
    const node = document.createElement(tag);

    if (className) {
        node.className = className;
    }

    if (text !== undefined && text !== null) {
        node.textContent = String(text);
    }

    return node;
}

function addDetail(list, label, value) {
    list.append(
        makeElement("dt", "", label),
        makeElement("dd", "", value || "Not provided")
    );
}

function getBranch(branchId) {
    return branches.find(
        branch => String(branch.branch_id) === String(branchId)
    );
}

function isMainBranch(branch) {
    return Number(branch.is_main_branch) === 1;
}

function readableStatus(status) {
    return status === "ACTIVE" ? "Active" : "Inactive";
}

function setDropdownOpen(open) {
    branchList.hidden = !open;
    selectBranchButton.setAttribute("aria-expanded", String(open));
}

function syncRecordSelect() {
    recordSelect.replaceChildren(
        option("new", "Add a new branch"),
        ...branches.map(branch =>
            option(String(branch.branch_id), branch.branch_name)
        )
    );

    recordSelect.value = selectedBranchId ?? "new";
}

function renderBranchOptions() {
    branchList.replaceChildren();

    if (branches.length === 0) {
        branchList.append(
            makeElement("p", "branches-empty", "No branches found. Use + to add one.")
        );
        return;
    }

    for (const branch of branches) {
        const id = String(branch.branch_id);
        const button = makeElement("button", "branch-option");
        const identity = makeElement("span", "branch-option-identity");
        const label = isMainBranch(branch)
            ? "Main branch"
            : branch.status === "INACTIVE"
                ? "Inactive"
                : "";

        button.type = "button";
        button.setAttribute("role", "option");
        button.setAttribute("aria-selected", String(id === selectedBranchId));

        identity.append(
            makeElement("span", "branch-name", branch.branch_name),
            makeElement("span", "branch-option-location", branch.location || "Location not provided")
        );

        button.append(identity);

        if (label) {
            button.append(makeElement("span", "branch-main-label", label));
        }

        button.addEventListener("click", () => {
            selectedBranchId = id;
            recordSelect.value = id;
            selectedBranchLabel.textContent = branch.branch_name;

            setDropdownOpen(false);
            syncRecordSelect();
            renderBranchOptions();
            renderBranchDetails();
            showMessage(branchesMessage, "", "");
        });

        branchList.append(button);
    }
}

function renderBranchDetails() {
    branchDetails.replaceChildren();

    const branch = getBranch(selectedBranchId);

    if (!branch) {
        branchDetails.hidden = true;
        return;
    }

    branchDetails.hidden = false;

    const heading = makeElement("div", "branch-details-heading");
    const identity = makeElement("div", "branch-details-identity");

    identity.append(
        makeElement("h2", "branch-name", branch.branch_name),
        makeElement("p", "branch-location", branch.location || "Location not provided")
    );

    const editButton = makeElement("button", "secondary-button", "Edit branch");
    editButton.type = "button";
    editButton.addEventListener("click", () => openEditor(branch));

    heading.append(identity, editButton);

    const details = makeElement("dl", "branch-detail-list");

    addDetail(details, "Branch type", isMainBranch(branch) ? "Main branch" : "Additional branch");
    addDetail(details, "Status", readableStatus(branch.status));

    branchDetails.append(heading, details);
}

async function loadBranches(preferredId = selectedBranchId) {
    const { ok, data } = await getJson("branches/list.php");

    if (!ok) {
        throw new Error(data.error || "Unable to load branches.");
    }

    branches = Array.isArray(data.branches) ? data.branches : [];

    const preferred = preferredId === null || preferredId === undefined
        ? null
        : String(preferredId);

    selectedBranchId = branches.some(
        branch => String(branch.branch_id) === preferred
    )
        ? preferred
        : branches.length > 0
            ? String(branches[0].branch_id)
            : null;

    const branch = getBranch(selectedBranchId);

    selectedBranchLabel.textContent = branch
        ? branch.branch_name
        : "Select branch";

    syncRecordSelect();
    renderBranchOptions();
    renderBranchDetails();
}

function openEditor(branch = null) {
    editingBranchId = branch ? String(branch.branch_id) : null;

    branchEditorTitle.textContent = branch ? "Edit branch" : "Add branch";
    saveButton.textContent = branch ? "Save changes" : "Create branch";

    field("branchName").value = branch?.branch_name ?? "";
    field("location").value = branch?.location ?? "";
    field("status").value = branch?.status ?? "ACTIVE";

    field("statusGroup").hidden = !branch;
    field("branchAdminFields").hidden = Boolean(branch);

    ["firstName", "lastName", "email", "password"].forEach(id => {
        field(id).value = "";
    });

    recordSelect.value = editingBranchId ?? "new";

    showMessage(field("formMessage"), "", "");
    clearFieldErrors(recordForm);

    editorHost.hidden = false;
    document.body.classList.add("branch-modal-open");

    branchEditorPanel.focus();
    field("branchName").focus();
}

function closeEditor() {
    editorHost.hidden = true;
    document.body.classList.remove("branch-modal-open");

    showMessage(field("formMessage"), "", "");
    clearFieldErrors(recordForm);
}

async function saveBranch(event) {
    event.preventDefault();

    const message = field("formMessage");
    const branchName = fieldValue("branchName");
    const location = fieldValue("location");
    const isCreating = editingBranchId === null;

    showMessage(message, "", "");
    clearFieldErrors(recordForm);

    if (!branchName) {
        showMessage(message, "Enter a branch name.", "error");
        field("branchName").focus();
        return;
    }

    if (!location) {
        showMessage(message, "Enter the branch location.", "error");
        field("location").focus();
        return;
    }

    const payload = { branchName, location };

    if (isCreating) {
        const firstName = fieldValue("firstName");
        const lastName = fieldValue("lastName");
        const email = fieldValue("email");
        const password = fieldValue("password");

        if (!firstName) {
            showMessage(message, "Enter the branch administrator's first name.", "error");
            field("firstName").focus();
            return;
        }

        if (!lastName) {
            showMessage(message, "Enter the branch administrator's last name.", "error");
            field("lastName").focus();
            return;
        }

        if (!email || !field("email").checkValidity()) {
            showMessage(message, "Enter a valid administrator email address.", "error");
            field("email").focus();
            return;
        }

        if (!password) {
            showMessage(message, "Enter a temporary password for the administrator.", "error");
            field("password").focus();
            return;
        }

        Object.assign(payload, {
            status: "ACTIVE",
            firstName,
            lastName,
            email,
            password
        });
    } else {
        payload.branchId = Number(editingBranchId);
        payload.status = fieldValue("status");
    }

    setLoading(saveButton, true, "Saving...");

    let result;

    try {
        result = await postJson(
            isCreating ? "branches/create.php" : "branches/update.php",
            payload
        );
    } catch (error) {
        showMessage(
            message,
            "Unable to reach the server. Check that Apache and MySQL are running.",
            "error"
        );
        setLoading(saveButton, false);
        return;
    }

    setLoading(saveButton, false);

    if (!result.ok) {
        showServerError(message, result.data);
        return;
    }

    const savedId = isCreating
        ? String(result.data.branch_id)
        : editingBranchId;

    closeEditor();

    try {
        await loadBranches(savedId);
        showMessage(
            branchesMessage,
            result.data.message || "Branch saved.",
            "success"
        );
    } catch (error) {
        showMessage(
            branchesMessage,
            error.message || "The branch was saved, but the list could not refresh.",
            "error"
        );
    }
}

async function initialiseBranches() {
    try {
        await loadBranches();
    } catch (error) {
        showMessage(
            branchesMessage,
            error.message || "Unable to load branches.",
            "error"
        );
    }
}

selectBranchButton.addEventListener("click", () => {
    setDropdownOpen(branchList.hidden);
});

addBranchButton.addEventListener("click", () => {
    openEditor();
});

closeEditorButton.addEventListener("click", closeEditor);

recordForm.addEventListener("submit", saveBranch);
clearErrorOnInput(recordForm);

editorHost.addEventListener("click", event => {
    if (event.target === editorHost) {
        closeEditor();
    }
});

document.addEventListener("click", event => {
    if (!branchesPicker.contains(event.target)) {
        setDropdownOpen(false);
    }
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape") {
        if (!editorHost.hidden) {
            closeEditor();
        } else {
            setDropdownOpen(false);
        }
    }
});

shellReady.then(account => {
    if (account) {
        initialiseBranches();
    }
});