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

function postJson(path, body = {}) {
    return request(path, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(body)
    });
}