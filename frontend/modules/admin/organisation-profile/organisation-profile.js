const PROFILE_COLUMNS = {
    organizationName: "organization_name",
    kraPin: "kra_pin",
    organizationEmail: "email",
    organizationPhone: "phone",
    organizationAddress: "address"
};

const profileForm = field("profileForm");
const formMessage = field("formMessage");
const profileFormMessage = field("profileFormMessage");
const saveButton = field("saveButton");
const profileModal = field("profileModal");
const editProfileButton = field("editProfileButton");
const closeProfileModalButton = field("closeProfileModal");
const cancelProfileButton = field("cancelProfileButton");
const organizationSummary = field("organizationSummary");
const organizationDisplayName = field("organizationDisplayName");
const organizationDisplayType = field("organizationDisplayType");

const logoFile = field("logoFile");
const logoPreview = field("logoPreview");
const logoFallback = field("logoFallback");
const chooseLogoButton = field("chooseLogoButton");
const uploadLogoButton = field("uploadLogoButton");
const cancelLogoButton = field("cancelLogoButton");
const removeLogoButton = field("removeLogoButton");
const logoMessage = field("logoMessage");

let savedLogoPath = null;
let localLogoPreviewUrl = null;
let selectedLogoFile = null;
let currentOrganization = null;

function logoUrl(path) {
    if (!/^uploads\/organization-logos\/org-\d+-[a-f0-9]{32}\.(jpg|png|webp)$/.test(path || "")) {
        return null;
    }

    return new URL("../../../../" + path, window.location.href).href + "?v=" + Date.now();
}

function setLogoPreview(path, organizationName, localUrl = null) {
    if (localLogoPreviewUrl && localLogoPreviewUrl !== localUrl) {
        URL.revokeObjectURL(localLogoPreviewUrl);
    }

    localLogoPreviewUrl = localUrl;

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

    const source = localUrl || (path ? logoUrl(path) : null);

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

function renderOrganizationSummary(organization) {
    const type = TYPE_LABELS[organization.organization_type] || organization.organization_type || "Not provided";

    const details = [
        ["Organisation name", organization.organization_name],
        ["Organisation type", type],
        ["KRA PIN", organization.kra_pin],
        ["Email", organization.email],
        ["Phone", organization.phone],
        ["Address", organization.address],
        ["Status", organization.status]
    ];

    organizationSummary.replaceChildren();

    details.forEach(([label, rawValue]) => {
        const row = document.createElement("div");
        row.className = "profile-detail-row";

        const term = document.createElement("dt");
        term.textContent = label;

        const description = document.createElement("dd");
        description.textContent = String(rawValue ?? "").trim() || "Not provided";

        row.append(term, description);
        organizationSummary.append(row);
    });
}

function fillProfile(organization) {
    currentOrganization = { ...organization };

    Object.entries(PROFILE_COLUMNS).forEach(([id, column]) => {
        field(id).value = organization[column] ?? "";
    });

    const type = TYPE_LABELS[organization.organization_type] || organization.organization_type || "";
    field("organizationType").value = type;

    organizationDisplayName.textContent = organization.organization_name || "Organisation";
    organizationDisplayType.textContent = type;

    savedLogoPath = organization.logo_path || null;
    selectedLogoFile = null;
    logoFile.value = "";

    setLogoPreview(savedLogoPath, organization.organization_name);
    renderOrganizationSummary(organization);

    uploadLogoButton.disabled = true;
    uploadLogoButton.textContent = "Save photo";
    cancelLogoButton.hidden = true;
    removeLogoButton.hidden = !savedLogoPath;
    removeLogoButton.disabled = false;
}

function collectProfile() {
    return Object.fromEntries(
        Object.keys(PROFILE_COLUMNS).map(id => [id, fieldValue(id)])
    );
}

async function loadProfile() {
    const { ok, data } = await getJson("organizations/profile.php");

    if (!ok) {
        throw new Error(data.error || "Unable to load the organisation profile.");
    }

    fillProfile(data.organization);
}

function openProfileModal() {
    if (currentOrganization) {
        fillProfile(currentOrganization);
    }

    showMessage(profileFormMessage, "", "");
    profileModal.hidden = false;
    document.body.classList.add("profile-modal-open");
    profileModal.querySelector(".profile-modal").focus();
}

function closeProfileModal() {
    profileModal.hidden = true;
    document.body.classList.remove("profile-modal-open");

    if (currentOrganization) {
        fillProfile(currentOrganization);
    }

    showMessage(profileFormMessage, "", "");
    editProfileButton.focus();
}

async function submitProfile(event) {
    event.preventDefault();

    if (!field("organizationName").value.trim()) {
        showMessage(profileFormMessage, "Organisation name is required.", "error");
        field("organizationName").focus();
        return;
    }

    const data = await sendForm({
        form: profileForm,
        button: saveButton,
        message: profileFormMessage,
        path: "organizations/update-profile.php",
        payload: collectProfile()
    });

    if (data === null) {
        return;
    }

    try {
        await loadProfile();
        closeProfileModal();
        showMessage(formMessage, data.message || "Organisation profile updated.", "success");
    } catch (error) {
        showMessage(profileFormMessage, error.message || "The profile was saved, but could not be refreshed.", "error");
    }
}

function cancelLogoSelection() {
    selectedLogoFile = null;
    logoFile.value = "";

    setLogoPreview(savedLogoPath, currentOrganization?.organization_name);

    uploadLogoButton.disabled = true;
    uploadLogoButton.textContent = "Save photo";
    cancelLogoButton.hidden = true;
    removeLogoButton.hidden = !savedLogoPath;
    removeLogoButton.disabled = false;
    showMessage(logoMessage, "", "");
}

function handleLogoSelection() {
    const file = logoFile.files?.[0];

    if (!file) {
        return;
    }

    showMessage(logoMessage, "", "");

    const allowedTypes = ["image/png", "image/jpeg", "image/webp"];

    if (!allowedTypes.includes(file.type)) {
        cancelLogoSelection();
        showMessage(logoMessage, "Choose a PNG, JPEG or WebP image.", "error");
        return;
    }

    if (file.size < 1 || file.size > 2 * 1024 * 1024) {
        cancelLogoSelection();
        showMessage(logoMessage, "Choose an image no larger than 2 MB.", "error");
        return;
    }

    selectedLogoFile = file;
    setLogoPreview(null, currentOrganization?.organization_name, URL.createObjectURL(file));

    uploadLogoButton.disabled = false;
    uploadLogoButton.textContent = "Save photo";
    cancelLogoButton.hidden = false;
    removeLogoButton.disabled = true;
}

async function uploadOrganizationLogo() {
    if (!selectedLogoFile) {
        showMessage(logoMessage, "Choose a photo first.", "error");
        return;
    }

    const body = new FormData();
    body.append("logo", selectedLogoFile);

    chooseLogoButton.disabled = true;
    uploadLogoButton.disabled = true;
    cancelLogoButton.disabled = true;
    removeLogoButton.disabled = true;
    uploadLogoButton.textContent = "Saving...";

    try {
        const response = await fetch(apiUrl("organizations/upload-logo.php"), {
            method: "POST",
            credentials: "include",
            cache: "no-store",
            body
        });

        const data = await response.json().catch(() => ({}));

        if (!response.ok || !data.success) {
            throw new Error(data.error || "Unable to upload the organisation photo.");
        }

        savedLogoPath = data.logo_path || null;
        selectedLogoFile = null;
        logoFile.value = "";

        setLogoPreview(savedLogoPath, currentOrganization?.organization_name);

        uploadLogoButton.disabled = true;
        uploadLogoButton.textContent = "Save photo";
        cancelLogoButton.hidden = true;
        removeLogoButton.hidden = !savedLogoPath;

        showMessage(logoMessage, data.message || "Organisation photo updated.", "success");
    } catch (error) {
        showMessage(logoMessage, error.message || "Unable to upload the organisation photo.", "error");
        uploadLogoButton.disabled = !selectedLogoFile;
    } finally {
        chooseLogoButton.disabled = false;
        cancelLogoButton.disabled = false;
        removeLogoButton.disabled = false;
        uploadLogoButton.textContent = "Save photo";
    }
}

async function removeOrganizationLogo() {
    if (!savedLogoPath || selectedLogoFile) {
        return;
    }

    if (!window.confirm("Remove this organisation photo?")) {
        return;
    }

    chooseLogoButton.disabled = true;
    uploadLogoButton.disabled = true;
    removeLogoButton.disabled = true;

    try {
        const { ok, data } = await postJson("organizations/remove-logo.php", {});

        if (!ok || !data.success) {
            throw new Error(data.error || "Unable to remove the organisation photo.");
        }

        savedLogoPath = null;
        selectedLogoFile = null;
        logoFile.value = "";

        setLogoPreview(null, currentOrganization?.organization_name);

        uploadLogoButton.disabled = true;
        cancelLogoButton.hidden = true;
        removeLogoButton.hidden = true;

        showMessage(logoMessage, data.message || "Organisation photo removed.", "success");
    } catch (error) {
        showMessage(logoMessage, error.message || "Unable to remove the organisation photo.", "error");
    } finally {
        chooseLogoButton.disabled = false;
        uploadLogoButton.disabled = Boolean(selectedLogoFile);
        removeLogoButton.disabled = false;
    }
}

async function initialise() {
    try {
        await loadProfile();
    } catch (error) {
        showMessage(formMessage, error.message || "Unable to load the organisation profile.", "error");
    }
}

editProfileButton.addEventListener("click", openProfileModal);
closeProfileModalButton.addEventListener("click", closeProfileModal);
cancelProfileButton.addEventListener("click", closeProfileModal);

profileModal.addEventListener("click", event => {
    if (event.target === profileModal) {
        closeProfileModal();
    }
});

document.addEventListener("keydown", event => {
    if (event.key === "Escape" && !profileModal.hidden) {
        closeProfileModal();
    }
});

profileForm.addEventListener("submit", submitProfile);
chooseLogoButton.addEventListener("click", () => logoFile.click());
logoFile.addEventListener("change", handleLogoSelection);
uploadLogoButton.addEventListener("click", uploadOrganizationLogo);
cancelLogoButton.addEventListener("click", cancelLogoSelection);
removeLogoButton.addEventListener("click", removeOrganizationLogo);

clearErrorOnInput(profileForm);

shellReady.then(account => {
    if (account) {
        initialise();
    }
});