const NEW_USER = "new";

const usersPicker = field("usersPicker");
const selectUserButton = field("selectUserButton");
const selectedUserLabel = field("selectedUserLabel");
const userList = field("userList");
const userDetails = field("userDetails");
const usersMessage = field("usersMessage");
const addUserButton = field("addUserButton");
const editorHost = field("editorHost");
const userEditorTitle = field("userEditorTitle");
const closeEditorButton = field("closeEditorButton");
const recordSelect = field("recordSelect");

let users = [];
let selectedUserId = null;
let currentAccount = null;

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

function formatUserDate(value) {
    if (!value) return "Not available";

    const date = new Date(String(value).replace(" ", "T"));

    return Number.isNaN(date.getTime())
        ? String(value)
        : new Intl.DateTimeFormat(undefined, { dateStyle: "medium" }).format(date);
}

function selectedUser() {
    return users.find(user => String(user.user_id) === String(selectedUserId));
}

function setDropdownOpen(open) {
    userList.hidden = !open;
    selectUserButton.setAttribute("aria-expanded", String(open));
}

function renderUserDetails() {
    userDetails.replaceChildren();

    const user = selectedUser();

    if (!user) {
        userDetails.hidden = true;
        return;
    }

    userDetails.hidden = false;

    const heading = makeElement("div", "user-details-heading");
    const identity = makeElement("div", "user-details-identity");

    identity.append(
        makeElement("h2", "user-name", `${user.first_name} ${user.last_name}`.trim()),
        makeElement("p", "user-email", user.email)
    );

    const editButton = makeElement("button", "secondary-button", "Edit user");
    editButton.type = "button";
    editButton.addEventListener("click", () => openEditUser(user));

    heading.append(identity, editButton);

    const list = makeElement("dl", "user-detail-list");

    addDetail(list, "Role", readable(user.role));
    addDetail(list, "Status", readable(user.status));
    addDetail(list, "Branch", user.branch_name || "No branch assigned");
    addDetail(list, "Created", formatUserDate(user.created_at));

    userDetails.append(heading, list);
}

function renderUserOptions() {
    userList.replaceChildren();

    if (users.length === 0) {
        userList.append(makeElement("p", "users-empty", "No users found."));
        return;
    }

    for (const user of users) {
        const id = String(user.user_id);
        const name = `${user.first_name} ${user.last_name}`.trim();
        const option = makeElement("button", "user-option");
        const identity = makeElement("span", "user-option-identity");

        option.type = "button";
        option.setAttribute("role", "option");
        option.setAttribute("aria-selected", String(id === selectedUserId));

        identity.append(
            makeElement("span", "user-name", name),
            makeElement("span", "user-email", user.email)
        );

        option.append(
            identity,
            makeElement("span", "user-option-role", readable(user.role))
        );

        option.addEventListener("click", () => {
            selectedUserId = id;
            selectedUserLabel.textContent = name;
            recordSelect.value = id;
            recordSelect.dispatchEvent(new Event("change"));
            setDropdownOpen(false);
            renderUserOptions();
            renderUserDetails();
        });

        userList.append(option);
    }
}

async function loadUsers(preferredId = selectedUserId) {
    const { ok, data } = await getJson("users/list.php");

    if (!ok) {
        throw new Error(data.error || "Unable to load users.");
    }

    users = Array.isArray(data.users) ? data.users : [];
    selectedUserId = users.some(user => String(user.user_id) === String(preferredId))
        ? String(preferredId)
        : null;

    const user = selectedUser();

    selectedUserLabel.textContent = user
        ? `${user.first_name} ${user.last_name}`.trim()
        : "Select user";

    renderUserOptions();
    renderUserDetails();
}

function renderBranchSelect(branches) {
    const select = field("branchId");
    const activeBranches = branches.filter(branch => branch.status === "ACTIVE");

    if (currentAccount?.user?.role === "BRANCH_ADMIN") {
        const ownBranchId = String(currentAccount.user.branch_id ?? "");

        select.replaceChildren(
            ...activeBranches.map(branch =>
                option(String(branch.branch_id), branch.branch_name)
            )
        );

        select.value = ownBranchId;
        select.disabled = true;
        return;
    }

    select.disabled = false;
    select.replaceChildren(
        option("", "No branch"),
        ...activeBranches.map(branch =>
            option(String(branch.branch_id), branch.branch_name)
        )
    );
}

function openEditor() {
    editorHost.hidden = false;
    document.body.classList.add("user-modal-open");
    editorHost.querySelector(".user-modal").focus();
}

function closeEditor() {
    editorHost.hidden = true;
    document.body.classList.remove("user-modal-open");
}

function openEditUser(user) {
    selectedUserId = String(user.user_id);
    recordSelect.value = selectedUserId;
    recordSelect.dispatchEvent(new Event("change"));
    userEditorTitle.textContent = "Edit user";
    openEditor();
    field("firstName").focus();
}

const editor = createRecordEditor({
    listPath: "users/list.php",
    listKey: "users",
    createPath: "users/create.php",
    updatePath: "users/update.php",
    idKey: "user_id",
    idField: "userId",
    newLabel: "Add a new user",
    addLabel: "Add user",
    fieldIds: ["firstName", "lastName", "email", "role", "branchId", "status", "password"],
    describe: user => `${user.first_name} ${user.last_name} | ${user.email}`,
    toForm: user => ({
        firstName: user?.first_name,
        lastName: user?.last_name,
        email: user?.email,
        role: user?.role ?? "STAFF",
        branchId: user?.branch_id ? String(user.branch_id) : "",
        status: user?.status ?? "ACTIVE",
        password: ""
    }),
    onMode: creating => {
        field("email").disabled = !creating;
        field("statusGroup").hidden = creating;
        field("passwordLabel").textContent = creating
            ? "Temporary password"
            : "New password (leave blank to keep the current one)";
        userEditorTitle.textContent = creating ? "Add user" : "Edit user";

        if (currentAccount?.user?.role === "BRANCH_ADMIN") {
            field("role").value = "STAFF";
            field("role").disabled = true;
            field("branchId").value = String(currentAccount.user.branch_id ?? "");
            field("branchId").disabled = true;
        } else {
            field("role").disabled = false;
            field("branchId").disabled = false;
        }
    },
    onSaved: async data => {
        const savedId = recordSelect.value;
        await loadUsers(savedId !== NEW_USER ? savedId : null);
        closeEditor();
        showMessage(usersMessage, data.message || "User saved.", "success");
    }
});

async function initialise(account) {
    currentAccount = account;

    const roleField = field("role");

    if (account.user.role === "BRANCH_ADMIN") {
        roleField.replaceChildren(option("STAFF", "Staff"));
        roleField.value = "STAFF";
        roleField.disabled = true;
    } else {
        roleField.disabled = false;
    }
    try {
        const { ok, data } = await getJson("branches/list.php");

        if (!ok) {
            throw new Error(data.error || "Unable to load branches.");
        }

        renderBranchSelect(data.branches ?? []);
        await editor.start();
        await loadUsers(null);
    } catch (error) {
        showMessage(usersMessage, error.message || "Unable to load users.", "error");
    }
}

selectUserButton.addEventListener("click", () => {
    setDropdownOpen(userList.hidden);
});

addUserButton.addEventListener("click", () => {
    recordSelect.value = NEW_USER;
    recordSelect.dispatchEvent(new Event("change"));
    openEditor();
    field("firstName").focus();
});

closeEditorButton.addEventListener("click", closeEditor);

editorHost.addEventListener("click", event => {
    if (event.target === editorHost) {
        closeEditor();
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
        initialise(account);
    }
});