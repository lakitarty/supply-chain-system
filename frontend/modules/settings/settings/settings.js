const settingsRefreshButton = field("settingsRefreshButton");
const settingsSignOutButton = field("settingsSignOutButton");

settingsRefreshButton.addEventListener("click", async () => {
    if (!window.confirm("Refresh Nexora MSRC? Any unsaved changes may be lost.")) {
        return;
    }

    settingsRefreshButton.disabled = true;
    settingsRefreshButton.textContent = "Refreshing...";

    try {
        if (!window.nexoraDesktop?.refreshSystem) {
            throw new Error("Desktop refresh is unavailable.");
        }

        await window.nexoraDesktop.refreshSystem();
    } catch (error) {
        settingsRefreshButton.disabled = false;
        settingsRefreshButton.textContent = "Refresh system";
        window.alert("Unable to refresh Nexora MSRC. Close and reopen the desktop app.");
    }
});

settingsSignOutButton.addEventListener("click", signOut);