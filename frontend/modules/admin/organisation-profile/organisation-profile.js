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
const logoFile = field("logoFile");
const logoPreview = field("logoPreview");
const logoFallback = field("logoFallback");
const chooseLogoButton = field("chooseLogoButton");
const uploadLogoButton = field("uploadLogoButton");
const removeLogoButton = field("removeLogoButton");
const logoMessage = field("logoMessage");

let savedLogoPath = null;
let localLogoPreviewUrl = null;

function logoUrl(path) {
    if (!/^uploads\/organization-logos\/org-\d+-[a-f0-9]{32}\.(jpg|png|webp)$/.test(path || "")) {
        return null;
    }

    return new URL("../../../../" + path, window.location.href).href + "?v=" + Date.now();
}

function setLogoPreview(path, organizationName, localUrl = null) {
    if (localLogoPreviewUrl && localLogoPreviewUrl !== localUrl) {
        URL.revokeObjectURL(localLogoPreviewUrl);
        localLogoPreviewUrl = null;
    }

    const source = localUrl || (path ? logoUrl(path) : null);

    logoFallback.textContent = String(organizationName || "?")
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map(word => word.charAt(0).toUpperCase())
        .join("") || "?";

    logoPreview.onerror = () => {
        logoPreview.hidden = true;
        logoFallback.hidden = false;
    };

    if (source) {
        logoPreview.src = source;
        logoPreview.hidden = false;
        logoFallback.hidden = true;
    } else {
        logoPreview.removeAttribute("src");
        logoPreview.hidden = true;
        logoFallback.hidden = false;
    }
}

function handleLogoSelection() {
    const file = logoFile.files?.[0];

    showMessage(logoMessage, "", "");

    if (!file) {
        uploadLogoButton.disabled = true;
        return;
    }

    const allowedTypes = ["image/png", "image/jpeg", "image/webp"];

    if (!allowedTypes.includes(file.type)) {
        logoFile.value = "";
        uploadLogoButton.disabled = true;
        showMessage(logoMessage, "Choose a PNG, JPEG or WebP image.", "error");
        return;
    }

    if (file.size < 1 || file.size > 2 * 1024 * 1024) {
        logoFile.value = "";
        uploadLogoButton.disabled = true;
        showMessage(logoMessage, "Choose an image no larger than 2 MB.", "error");
        return;
    }

    if (localLogoPreviewUrl) {
        URL.revokeObjectURL(localLogoPreviewUrl);
    }

    localLogoPreviewUrl = URL.createObjectURL(file);
    setLogoPreview(null, field("organizationName").value, localLogoPreviewUrl);
    uploadLogoButton.disabled = false;
}

async function uploadOrganizationLogo() {
    const file = logoFile.files?.[0];

    if (!file) {
        showMessage(logoMessage, "Choose an image first.", "error");
        return;
    }

    const body = new FormData();
    body.append("logo", file);

    chooseLogoButton.disabled = true;
    uploadLogoButton.disabled = true;
    removeLogoButton.disabled = true;
    uploadLogoButton.textContent = "Uploading...";

    try {
        const response = await fetch(apiUrl("organizations/upload-logo.php"), {
            method: "POST",
            credentials: "include",
            cache: "no-store",
            body
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data.success) {
            throw new Error(data.error || "Unable to upload the logo.");
        }

        savedLogoPath = data.logo_path || null;
        logoFile.value = "";
        setLogoPreview(savedLogoPath, field("organizationName").value);
        removeLogoButton.hidden = !savedLogoPath;
        showMessage(logoMessage, data.message || "Organisation logo uploaded.", "success");
    } catch (error) {
        showMessage(logoMessage, error.message || "Unable to upload the logo.", "error");
        uploadLogoButton.disabled = false;
    } finally {
        chooseLogoButton.disabled = false;
        removeLogoButton.disabled = false;
        uploadLogoButton.textContent = "Upload logo";
    }
}

async function removeOrganizationLogo() {
    if (!savedLogoPath) {
        return;
    }

    if (!window.confirm("Remove this organisation logo?")) {
        return;
    }

    chooseLogoButton.disabled = true;
    uploadLogoButton.disabled = true;
    removeLogoButton.disabled = true;

    try {
        const { ok, data } = await postJson("organizations/remove-logo.php", {});

        if (!ok || !data.success) {
            throw new Error(data.error || "Unable to remove the logo.");
        }

        savedLogoPath = null;
        logoFile.value = "";
        setLogoPreview(null, field("organizationName").value);
        removeLogoButton.hidden = true;
        showMessage(logoMessage, data.message || "Organisation logo removed.", "success");
    } catch (error) {
        showMessage(logoMessage, error.message || "Unable to remove the logo.", "error");
    } finally {
        chooseLogoButton.disabled = false;
        uploadLogoButton.disabled = !logoFile.files?.length;
        removeLogoButton.disabled = false;
    }
}
function fillProfile(organization) {
    Object.entries(PROFILE_COLUMNS).forEach(([id, column]) => {
        field(id).value = organization[column] ?? "";
    });

    field("organizationType").value = TYPE_LABELS[organization.organization_type];
    savedLogoPath = organization.logo_path || null;
    setLogoPreview(savedLogoPath, organization.organization_name);
    removeLogoButton.hidden = !savedLogoPath;
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

    try {
        await loadProfile();
    } catch (error) {
        showMessage(formMessage, "Unable to load the profile.", "error");
    }
}

profileForm.addEventListener("submit", submitProfile);
chooseLogoButton.addEventListener("click", () => logoFile.click());
logoFile.addEventListener("change", handleLogoSelection);
uploadLogoButton.addEventListener("click", uploadOrganizationLogo);
removeLogoButton.addEventListener("click", removeOrganizationLogo);
clearErrorOnInput(profileForm);

shellReady.then(account => {
    if (account) {
        initialise(account);
    }
});