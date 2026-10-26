const settingsRefreshButton = field("settingsRefreshButton");
const settingsSignOutButton = field("settingsSignOutButton");
const priceProtectionToggle = document.getElementById("priceProtectionToggle");
const priceProtectionSaveButton = document.getElementById("priceProtectionSaveButton");
const priceProtectionStatus = document.getElementById("priceProtectionStatus");

let priceProtectionCanManage = false;
let savedPriceProtectionValue = false;

async function loadPriceProtectionSetting() {
    priceProtectionToggle.disabled = true;
    priceProtectionSaveButton.disabled = true;
    priceProtectionStatus.textContent = "Loading organisation setting...";

    try {
        const response = await getJson("settings/price-protection.php");
        const data = response.data || {};

        if (!response.ok || data.success !== true) {
            throw new Error(data.message || data.error || "Unable to load the setting.");
        }

        savedPriceProtectionValue = data.enabled === true;
        priceProtectionCanManage = data.can_manage === true;
        priceProtectionToggle.checked = savedPriceProtectionValue;
        priceProtectionToggle.disabled = !priceProtectionCanManage;
        priceProtectionSaveButton.disabled = !priceProtectionCanManage;
        priceProtectionSaveButton.hidden = !priceProtectionCanManage;

        priceProtectionStatus.textContent = priceProtectionCanManage
            ? (savedPriceProtectionValue
                ? "Enabled. Eligible upstream price changes can automatically update your organisation's own prices. Scheduled or incomplete pricing may require review."
                : "Disabled. Your organisation's prices will not be changed automatically by this feature.")
            : "Only your organisation administrator can change this setting.";
    } catch (error) {
        priceProtectionToggle.disabled = true;
        priceProtectionSaveButton.disabled = true;
        priceProtectionStatus.textContent =
            error.message || "Unable to load Price Change Protection.";
    }
}

priceProtectionSaveButton.addEventListener("click", async () => {
    if (!priceProtectionCanManage) return;

    priceProtectionToggle.disabled = true;
    priceProtectionSaveButton.disabled = true;
    priceProtectionSaveButton.textContent = "Saving...";

    try {
        const response = await postJson("settings/price-protection.php", {
            enabled: priceProtectionToggle.checked
        });
        const data = response.data || {};

        if (!response.ok || data.success !== true) {
            throw new Error(data.message || data.error || "Unable to save the setting.");
        }

        savedPriceProtectionValue = data.enabled === true;
        priceProtectionToggle.checked = savedPriceProtectionValue;
        priceProtectionStatus.textContent = savedPriceProtectionValue
            ? "Setting saved. Eligible upstream price changes can now trigger automatic repricing for your organisation. Scheduled or incomplete pricing may require review."
            : "Setting saved. Price Change Protection is disabled for your organisation.";
    } catch (error) {
        priceProtectionToggle.checked = savedPriceProtectionValue;
        priceProtectionStatus.textContent =
            error.message || "Unable to save Price Change Protection.";
    } finally {
        priceProtectionToggle.disabled = !priceProtectionCanManage;
        priceProtectionSaveButton.disabled = !priceProtectionCanManage;
        priceProtectionSaveButton.textContent = "Save setting";
    }
});

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
        settingsRefreshButton.textContent = "Refresh";
        window.alert("Unable to refresh Nexora MSRC. Close and reopen the desktop app.");
    }
});

settingsSignOutButton.addEventListener("click", signOut);

loadPriceProtectionSetting();