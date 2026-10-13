const editor = createRecordEditor({
    listPath: "branches/list.php",
    listKey: "branches",
    createPath: "branches/create.php",
    updatePath: "branches/update.php",
    idKey: "branch_id",
    idField: "branchId",
    newLabel: "Add a new branch",
    addLabel: "Add branch",
    fieldIds: ["branchName", "location", "status"],
    describe: branch => {
        const main = Number(branch.is_main_branch) === 1 ? "main, " : "";
        return `${branch.branch_name}  |  ${branch.location}  |  ${main}${branch.status.toLowerCase()}`;
    },
    toForm: branch => ({
        branchName: branch?.branch_name,
        location: branch?.location,
        status: branch?.status ?? "ACTIVE"
    }),
    onMode: creating => {
        field("statusGroup").hidden = creating;
    }
});

async function initialise(account) {
    field("pageSubtitle").textContent = account.organization.organization_name;

    try {
        await editor.start();
    } catch (error) {
        showMessage(field("formMessage"), "Unable to load branches.", "error");
    }
}

shellReady.then(account => {
    if (account) {
        initialise(account);
    }
});