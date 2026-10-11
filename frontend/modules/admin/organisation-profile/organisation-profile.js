const PROFILE_COLUMNS = {
    organizationName: "organization_name",
    kraPin: "kra_pin",
    organizationEmail: "email",
    organizationPhone: "phone",
    organizationAddress: "address"
};

const profileForm = field("profileForm");
const formMessage = field("formMessage");
const saveButton = field("saveButton");

function fillProfile(organization) {
    Object.entries(PROFILE_COLUMNS).forEach(([id, column]) => {
        field(id).value = organization[column] ?? "";
    });

    field("organizationType").value = TYPE_LABELS[organization.organization_type];
}

function collectProfile() {
    return Object.fromEntries(Object.keys(PROFILE_COLUMNS).map(id => [id, fieldValue(id)]));
}

async function loadProfile() {
    const { ok, data } = await getJson("organizations/profile.php");

    if (!ok) {
        throw new Error(data.error || "Unable to load the profile.");
    }

    fillProfile(data.organization);
}

async function submitProfile(event) {
    event.preventDefault();

    const data = await sendForm({
        form: profileForm,
        button: saveButton,
        message: formMessage,
        path: "organizations/update-profile.php",
        payload: collectProfile()
    });

    if (data !== null) {
        await loadProfile();
        showMessage(formMessage, data.message, "success");
    }
}

async function initialise(account) {
    field("pageSubtitle").textContent = account.organization.organization_name;

    try {
        await loadProfile();
    } catch (error) {
        showMessage(formMessage, "Unable to load the profile.", "error");
    }
}

profileForm.addEventListener("submit", submitProfile);
clearErrorOnInput(profileForm);

shellReady.then(account => {
    if (account) {
        initialise(account);
    }
});