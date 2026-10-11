const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const formMessage = document.getElementById("formMessage");
const loginButton = document.getElementById("loginButton");

async function redirectIfSignedIn() {
    try {
        const { data } = await getJson("auth/session.php");

        if (data.authenticated === true) {
            goTo(HOME_PATH);
        }
    } catch (error) {
        return;
    }
}

function validateLogin(email, password) {
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

    return valid;
}

async function submitLogin(event) {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    showMessage(formMessage, "", "");
    clearFieldErrors(loginForm);

    if (!validateLogin(email, password)) {
        loginForm.querySelector(".has-error input").focus();
        return;
    }

    setLoading(loginButton, true, "Signing in...");

    try {
        const { ok, data } = await postJson("auth/login.php", { email, password });

        if (!ok) {
            showMessage(formMessage, data.error || "Unable to sign in.", "error");
            passwordInput.value = "";
            passwordInput.focus();
            setLoading(loginButton, false);
            return;
        }

        showMessage(formMessage, "Signed in. Opening your portal...", "success");
        goTo(HOME_PATH);

    } catch (error) {
        showMessage(
            formMessage,
            "Unable to reach the server. Check that Apache and MySQL are running.",
            "error"
        );
        setLoading(loginButton, false);
    }
}

clearErrorOnInput(loginForm);
loginForm.addEventListener("submit", submitLogin);
redirectIfSignedIn();