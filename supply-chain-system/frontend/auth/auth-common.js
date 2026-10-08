

const API_BASE = "../../Backend";
const GATEWAY_URL = "../portals/gateway.html";
function showMessage(element, message, type) {
    element.textContent = message;
    element.className = message ? `form-message ${type}` : "form-message";
}

function setFieldError(input, message) {
    const group = input.closest(".form-group");
    let error = group.querySelector(".field-error");

    if (!message) {
        group.classList.remove("has-error");
        input.removeAttribute("aria-invalid");
        if (error) {
            error.remove();
        }
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

async function postJson(path, body) {
    const response = await fetch(`${API_BASE}/${path}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify(body)
    });

    let data = null;

    try {
        data = await response.json();
    } catch (error) {
        
        data = { error: "The server sent an unexpected response." };
    }

    return { ok: response.ok, status: response.status, data };
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

function setLoading(button, loading, loadingText, normalText) {
    button.disabled = loading;
    button.textContent = loading ? loadingText : normalText;
}

setupPasswordToggles();
