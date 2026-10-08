const gatewayScript = document.currentScript;

const portalFolders = {
    RETAILER: "retailer",
    SUPPLIER: "supplier",
    MANUFACTURER: "manufacturer",
    MANUFACTURER_SUPPLIER: "manufacturer-supplier"
};

function goTo(path) {
    window.location.replace(new URL(path, gatewayScript.src).href);
}

async function openPortal() {
    try {
        const sessionUrl = new URL("../../Backend/auth/session.php", gatewayScript.src);
        const response = await fetch(sessionUrl, {
            credentials: "include",
            cache: "no-store"
        });
        const session = await response.json();

        if (session.authenticated !== true) {
            goTo("../auth/login.html");
            return;
        }

        const folder = portalFolders[session.organization.organization_type];

        if (!folder) {
            goTo("../auth/login.html");
            return;
        }

        goTo(`${folder}/index.html`);
    } catch (error) {
        goTo("../auth/login.html");
    }
}

openPortal();
