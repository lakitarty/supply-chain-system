const registerForm = document.getElementById("registerForm");
const formMessage = document.getElementById("formMessage");
const registerButton = document.getElementById("registerButton");
const passwordInput = document.getElementById("password");
const passwordHint = document.getElementById("passwordHint");

const KRA_PATTERN = /^[A-Z][0-9]{9}[A-Z]$/;
const PHONE_PATTERN = /^\+?[0-9][0-9\s\-()]{6,19}$/;

const REQUIRED_FIELDS = [
    ["organizationName", "Enter the organization name."],
    ["organizationType", "Choose an organization type."],
    ["branchName", "Enter the branch name."],
    ["branchLocation", "Enter the branch location."],
    ["firstName", "Enter the first name."],
    ["lastName", "Enter the last name."],
    ["adminEmail", "Enter the administrator email."],
    ["password", "Create a password."],
    ["confirmPassword", "Confirm the password."]
];

const PAYLOAD_FIELDS = [
    "organizationName",
    "organizationType",
    "kraPin",
    "organizationEmail",
    "organizationPhone",
    "organizationAddress",
    "branchName",
    "branchLocation",
    "firstName",
    "lastName",
    "adminEmail"
];
 

function isStrongPassword(value) {
    return value.length >= 8 && /[A-Za-z]/.test(value) && /[0-9]/.test(value);
}

function validateRegistration() {
    const errors = [];
    const add = (id, message) => errors.push({ id, message });
    const hasError = id => errors.some(error => error.id === id);

    REQUIRED_FIELDS.forEach(([id, message]) => {
        if (!fieldValue(id)) {
            add(id, message);
        }
    });

    if (!hasError("adminEmail") && !field("adminEmail").checkValidity()) {
        add("adminEmail", "Enter a valid email address.");
    }

    if (fieldValue("organizationEmail") && !field("organizationEmail").checkValidity()) {
        add("organizationEmail", "Enter a valid email address.");
    }

    if (fieldValue("kraPin") && !KRA_PATTERN.test(fieldValue("kraPin"))) {
        add("kraPin", "KRA PIN must look like A123456789Z.");
    }

    if (fieldValue("organizationPhone") && !PHONE_PATTERN.test(fieldValue("organizationPhone"))) {
        add("organizationPhone", "Enter a valid phone number.");
    }

    if (!hasError("password") && !isStrongPassword(passwordInput.value)) {
        add("password", "Use at least 8 characters, with a letter and a number.");
    }

    if (!hasError("confirmPassword") && passwordInput.value !== fieldValue("confirmPassword")) {
        add("confirmPassword", "Passwords do not match.");
    }

    return errors;
}

function collectPayload() {
    const payload = Object.fromEntries(PAYLOAD_FIELDS.map(id => [id, fieldValue(id)]));
    payload.password = passwordInput.value;
    return payload;
}

function showFieldErrors(errors) {
    errors.forEach(error => setFieldError(field(error.id), error.message));
    field(errors[0].id).focus();
}

async function submitRegistration(event) {
    event.preventDefault();

    showMessage(formMessage, "", "");
    clearFieldErrors(registerForm);

    const errors = validateRegistration();

    if (errors.length > 0) {
        showFieldErrors(errors);
        showMessage(formMessage, "Please fix the highlighted fields.", "error");
        return;
    }

    setLoading(registerButton, true, "Creating organization...");

    try {
        const { ok, data } = await postJson("organizations/register.php", collectPayload());

        if (!ok) {
            if (data.field && field(data.field)) {
                showFieldErrors([{ id: data.field, message: data.error }]);
            }

            showMessage(formMessage, data.error || "Registration failed.", "error");
            setLoading(registerButton, false);
            return;
        }

        showMessage(formMessage, "Organization created. Opening your portal...", "success");
        setTimeout(() => goTo(HOME_PATH), 800);

    } catch (error) {
        showMessage(
            formMessage,
            "Unable to reach the server. Check that Apache and MySQL are running.",
            "error"
        );
        setLoading(registerButton, false);
    }
}

passwordInput.addEventListener("input", () => {
    passwordHint.classList.toggle("met", isStrongPassword(passwordInput.value));
});

field("kraPin").addEventListener("input", event => {
    event.target.value = event.target.value.toUpperCase();
});

clearErrorOnInput(registerForm);
registerForm.addEventListener("submit", submitRegistration);