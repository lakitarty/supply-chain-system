const homeScript = document.currentScript;
const frontendRoot = new URL("../", homeScript.src);
const apiRoot = new URL("../Backend/", frontendRoot);
const portalName = document.body.dataset.portal;

const portalByOrganizationType = {
    RETAILER: "retailer",
    SUPPLIER: "supplier",
    MANUFACTURER: "manufacturer",
    MANUFACTURER_SUPPLIER: "manufacturer-supplier"
};

const organizationTypeLabels = {
    RETAILER: "Retailer",
    SUPPLIER: "Supplier",
    MANUFACTURER: "Manufacturer",
    MANUFACTURER_SUPPLIER: "Manufacturer and supplier"
};

function goTo(path) {
    window.location.replace(new URL(path, frontendRoot).href);
}

function setText(id, value) {
    document.getElementById(id).textContent = value;
}

function showError(message) {
    const paragraph = document.createElement("p");
    paragraph.className = "portal-error";
    paragraph.textContent = message;
    document.body.replaceChildren(paragraph);
}

function capitalise(value) {
    return value.charAt(0) + value.slice(1).toLowerCase();
}

function fillDetails(session) {
    const { user, organization } = session;

    setText("organizationName", organization.organization_name);
    setText("organizationType", organizationTypeLabels[organization.organization_type]);
    setText("userName", `${user.first_name} ${user.last_name}`);
    setText("userRole", capitalise(user.role));
    setText("branchName", user.branch_name || "All branches");
    setText("welcomeText", `Signed in as ${user.email}`);
}

async function signOut() {
    try {
        await fetch(new URL("auth/logout.php", apiRoot), {
            method: "POST",
            credentials: "include"
        });
    } finally {
        goTo("auth/login.html");
    }
}

async function openHome() {
    try {
        const response = await fetch(new URL("auth/session.php", apiRoot), {
            credentials: "include",
            cache: "no-store"
        });
        const session = await response.json();

        if (session.authenticated !== true) {
            goTo("auth/login.html");
            return;
        }

        if (portalByOrganizationType[session.organization.organization_type] !== portalName) {
            goTo("portals/gateway.html");
            return;
        }

        fillDetails(session);
        document.getElementById("signOutButton").addEventListener("click", signOut);
        document.body.classList.add("portal-ready");
    } catch (error) {
        showError("Unable to reach the server. Check that Apache and MySQL are running.");
    }
}

openHome();
