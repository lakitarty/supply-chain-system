let adminPasswordDialog = null;

function buildAdminPasswordDialog() {
    const dialog = document.createElement("dialog");
    dialog.className = "admin-password-dialog";
    dialog.setAttribute("aria-labelledby", "adminPasswordTitle");
    dialog.innerHTML = `
        <div class="admin-password-content">
            <h2 id="adminPasswordTitle">Verify administrator password</h2>
            <p class="admin-password-message"></p>
            <label class="admin-password-label" for="adminPasswordInput">Administrator password</label>
            <div class="admin-password-field">
                <input id="adminPasswordInput" class="admin-password-input" type="password" autocomplete="current-password">
                <button type="button" class="admin-password-toggle" aria-pressed="false">Show</button>
            </div>
            <div class="admin-password-actions">
                <button type="button" class="admin-password-cancel">Cancel</button>
                <button type="button" class="admin-password-confirm">Verify and continue</button>
            </div>
        </div>
    `;
    document.body.appendChild(dialog);
    return dialog;
}

function requestAdminPassword(message) {
    if (typeof HTMLDialogElement === "undefined") {
        alert("Administrator password verification is unavailable in this browser.");
        return Promise.resolve(null);
    }

    adminPasswordDialog ??= buildAdminPasswordDialog();

    const dialog = adminPasswordDialog;
    const input = dialog.querySelector(".admin-password-input");
    const text = dialog.querySelector(".admin-password-message");
    const toggle = dialog.querySelector(".admin-password-toggle");
    const cancel = dialog.querySelector(".admin-password-cancel");
    const proceed = dialog.querySelector(".admin-password-confirm");

    text.textContent = message;
    input.value = "";
    input.type = "password";
    toggle.textContent = "Show";
    toggle.setAttribute("aria-pressed", "false");

    return new Promise(resolve => {
        const listeners = new AbortController();
        const options = { signal: listeners.signal };

        const finish = password => {
            listeners.abort();
            input.value = "";
            if (dialog.open) dialog.close();
            resolve(password);
        };

        const submit = () => {
            if (!input.value) {
                text.textContent = "Enter your administrator password.";
                input.focus();
                return;
            }

            finish(input.value);
        };

        cancel.addEventListener("click", () => finish(null), options);
        proceed.addEventListener("click", submit, options);

        dialog.addEventListener("cancel", event => {
            event.preventDefault();
            finish(null);
        }, options);

        toggle.addEventListener("click", () => {
            const reveal = input.type === "password";
            input.type = reveal ? "text" : "password";
            toggle.textContent = reveal ? "Hide" : "Show";
            toggle.setAttribute("aria-pressed", String(reveal));
            input.focus();
        }, options);

        input.addEventListener("keydown", event => {
            if (event.key === "Enter") {
                event.preventDefault();
                submit();
            }
        }, options);

        dialog.showModal();
        input.focus();
    });
}
