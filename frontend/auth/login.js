const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const formMessage = document.getElementById("formMessage");
const loginButton = document.getElementById("loginButton");

clearErrorOnInput(loginForm);

/* If the person is already signed in, skip the login page. */
(async function redirectIfSignedIn() {
    try {
        const response = await fetch(`${API_BASE}/auth/session.php`, {
            credentials: "include",
            cache: "no-store"
        });
        const result = await response.json();

        if (result.authenticated === true) {
            window.location.replace(GATEWAY_URL);
        }
    } catch (error) {
        // Not signed in, or server not reachable: stay on this page.
    }
})();

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    showMessage(formMessage, "", "");
    clearFieldErrors(loginForm);

    let valid = true;

    if (!email) {
        setFieldError(emailInput, "Enter your email address.");
        valid = false;
    } else if (!emailInput.checkValidity()) {
        setFieldError(emailInput, "Enter a valid email address.");
        valid = false;
    }

    if (!password) {
        setFieldError(passwordInput, "Enter your password.");
        valid = false;
    }

    if (!valid) {
        loginForm.querySelector(".has-error input").focus();
        return;
    }

    setLoading(loginButton, true, "Signing in...", "Sign In");

    try {
        const { ok, data } = await postJson("auth/login.php", { email, password });

        if (!ok) {
            showMessage(formMessage, data.error || "Unable to sign in.", "error");
            passwordInput.value = "";
            passwordInput.focus();
            setLoading(loginButton, false, "Signing in...", "Sign In");
            return;
        }

        showMessage(formMessage, "Signed in. Opening your portal...", "success");

        // The gateway reads the session and sends the person to the right portal.
        window.location.replace(GATEWAY_URL);

    } catch (error) {
        showMessage(
            formMessage,
            "Unable to reach the server. Check that Apache and MySQL are running.",
            "error"
        );
        setLoading(loginButton, false, "Signing in...", "Sign In");
    }
});
