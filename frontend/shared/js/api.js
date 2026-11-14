const apiScript = document.currentScript;
const frontendRoot = new URL("../../", apiScript.src);
const apiRoot = new URL("../Backend/", frontendRoot);

const HOME_PATH = "dashboard/dashboard.html";
const LOGIN_PATH = "auth/login.html";
function apiUrl(path) {
    return new URL(path, apiRoot).href;
}

function pageUrl(path) {
    return new URL(path, frontendRoot).href;
}

function goTo(path) {
    window.location.replace(pageUrl(path));
}

async function readJson(response) {
    try {
        return await response.json();
    } catch (error) {
        return { success: false, error: "The server sent an unexpected response." };
    }
}

async function request(path, options) {
    const response = await fetch(apiUrl(path), {
        credentials: "include",
        cache: "no-store",
        ...options
    });

    return { ok: response.ok, status: response.status, data: await readJson(response) };
}

function getJson(path) {
    return request(path, { method: "GET" });
}

let csrfTokenPromise = null;

function loadCsrfToken() {
    csrfTokenPromise ??= fetch(apiUrl("auth/csrf.php"), { credentials: "include", cache: "no-store" })
        .then(readJson)
        .then(data => {
            if (data.success !== true) {
                throw new Error(data.error || "Unable to start a secure session.");
            }

            return data.csrf_token;
        })
        .catch(error => {
            csrfTokenPromise = null;
            throw error;
        });

    return csrfTokenPromise;
}

async function csrfHeaders() {
    return { "X-CSRF-Token": await loadCsrfToken() };
}

async function postJson(path, body = {}) {
    const security = await csrfHeaders().catch(() => ({}));

    return request(path, {
        method: "POST",
        headers: { "Content-Type": "application/json", ...security },
        body: JSON.stringify(body)
    });
}