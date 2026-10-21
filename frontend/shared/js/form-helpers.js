function field(id) {
    return document.getElementById(id);
}

function fieldValue(id) {
    const input = field(id);
    return input.type === "password" ? input.value : input.value.trim();
}

function option(value, text) {
    const node = document.createElement("option");
    node.value = value;
    node.textContent = text;
    return node;
}
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = message ? `form-message ${type}` : "form-message";
}

function setFieldError(input, message) {
    const group = input.closest(".form-group");
    if (!group) {
        if (!message) {
            input.removeAttribute("aria-invalid");
            input.removeAttribute("aria-describedby");
        }
        return;
    }
    let error = group.querySelector(".field-error");

    if (!message) {
        group.classList.remove("has-error");
        input.removeAttribute("aria-invalid");
        error?.remove();
        return;
    }

    if (!error) {
        error = document.createElement("p");
        error.className = "field-error";
        error.id = `${input.id}-error`;
        group.appendChild(error);
    }

    error.textContent = message;
    group.classList.add("has-error");
    input.setAttribute("aria-invalid", "true");
    input.setAttribute("aria-describedby", error.id);
}

function clearFieldErrors(form) {
    form.querySelectorAll("input, select").forEach(input => setFieldError(input, ""));
}

function clearErrorOnInput(form) {
    form.addEventListener("input", event => {
        if (event.target.closest(".form-group")) {
            setFieldError(event.target, "");
        }
    });
}

function setLoading(button, loading, loadingText) {
    if (loading) {
        button.dataset.label = button.textContent;
    }

    button.textContent = loading ? loadingText : button.dataset.label;
    button.disabled = loading;
}
function showServerError(message, data) {
    if (data.field && field(data.field)) {
        setFieldError(field(data.field), data.error);
        field(data.field).focus();
    }

    showMessage(message, data.error || "Unable to save.", "error");
}

async function sendForm({ form, button, message, path, payload }) {
    showMessage(message, "", "");
    clearFieldErrors(form);
    setLoading(button, true, "Saving...");

    try {
        const { ok, data } = await postJson(path, payload);

        if (!ok) {
            showServerError(message, data);
            return null;
        }

        return data;

    } catch (error) {
        showMessage(message, "Unable to reach the server. Check that Apache and MySQL are running.", "error");
        return null;

    } finally {
        setLoading(button, false);
    }
}
function setupPasswordToggles() {
    document.querySelectorAll(".toggle-password").forEach(button => {
        button.addEventListener("click", () => {
            const input = document.getElementById(button.dataset.target);
            const showing = input.type === "text";

            input.type = showing ? "password" : "text";
            button.textContent = showing ? "Show" : "Hide";
            button.setAttribute("aria-pressed", String(!showing));
        });
    });
}

setupPasswordToggles();