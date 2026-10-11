shellReady.then(account => {
    if (account) {
        document.getElementById("pageSubtitle").textContent = account.organization.organization_name;
    }
});