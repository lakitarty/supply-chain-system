const organizationTypes = [
    {
        value: "MANUFACTURER",
        label: "Manufacturer"
    },
    {
        value: "SUPPLIER",
        label: "Supplier"
    },
    {
        value: "MANUFACTURER_SUPPLIER",
        label: "Manufacturer + Supplier"
    },
    {
        value: "RETAILER",
        label: "Retailer"
    }
];

const organizationTypeSelect = document.getElementById("organizationType");

organizationTypes.forEach(type => {
    const option = document.createElement("option");
    option.value = type.value;
    option.textContent = type.label;
    organizationTypeSelect.appendChild(option);
});

const registerForm = document.getElementById("registerForm");
const formMessage = document.getElementById("formMessage");
const registerButton = document.getElementById("registerButton");

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}
function getSelectedOrganizationType() {
    return document.getElementById("organizationType").value; 
}

registerForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const organizationType = getSelectedOrganizationType();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirmPassword").value;

    formMessage.className = "form-message";
    formMessage.textContent = "";

    if (!organizationType) {
        showMessage(
            "Please select an organization type.",
            "error"
        );
        return;
    }

    if (password !== confirmPassword) {
        showMessage(
            "Passwords do not match.",
            "error"
        );
        return;
    }

    if (password.length < 8) {
        showMessage(
            "Password must be at least 8 characters.",
            "error"
        );
        return;
    }

    const payload = {
        organizationName:
            document.getElementById("organizationName").value.trim(),

        organizationType,

        kraPin:
            document.getElementById("kraPin").value.trim(),

        organizationEmail:
            document.getElementById("organizationEmail").value.trim(),

        organizationPhone:
            document.getElementById("organizationPhone").value.trim(),

        organizationAddress:
            document.getElementById("organizationAddress").value.trim(),

        branchName:
            document.getElementById("branchName").value.trim(),

        branchLocation:
            document.getElementById("branchLocation").value.trim(),

        firstName:
            document.getElementById("firstName").value.trim(),

        lastName:
            document.getElementById("lastName").value.trim(),

        adminEmail:
            document.getElementById("adminEmail").value.trim(),

        password
    };

    registerButton.disabled = true;
    registerButton.textContent = "Creating organization...";

    try {
        const response = await fetch(
            "../../Backend/organizations/register.php",
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                credentials: "include",
                body: JSON.stringify(payload)
            }
        );

        const result = await response.json();

        if (!response.ok) {
            showMessage(
                result.error ||
                result.message ||
                "Registration failed.",
                "error"
            );
            return;
        }

        showMessage(
            "Organization created successfully. Redirecting to sign in...",
            "success"
        );

        setTimeout(() => {
            window.location.replace("login.html");
        }, 1000);

    } catch (error) {
        showMessage(
            "Unable to connect to the server.",
            "error"
        );
    } finally {
        registerButton.disabled = false;
        registerButton.textContent = "Create Organization";
    }
});