function renderBranchSelect(branches) {
    field("branchId").replaceChildren(
        option("", "No branch"),
        ...branches
            .filter(branch => branch.status === "ACTIVE")
            .map(branch => option(String(branch.branch_id), branch.branch_name))
    );
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
    describe: user =>
        `${user.first_name} ${user.last_name}  |  ${user.email}  |  ${user.role.toLowerCase()}, ${user.status.toLowerCase()}`,
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
    }
});

async function initialise(account) {

    try {
        const { data } = await getJson("branches/list.php");
        renderBranchSelect(data.branches ?? []);
        await editor.start();
    } catch (error) {
        showMessage(field("formMessage"), "Unable to load users.", "error");
    }
}

shellReady.then(account => {
    if (account) {
        initialise(account);
    }
});