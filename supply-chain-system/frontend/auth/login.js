const loginForm = document.getElementById("loginForm");
const emailInput = document.getElementById("email");
const passwordInput = document.getElementById("password");
const formMessage = document.getElementById("formMessage");
const loginButton = document.getElementById("loginButton");

function showMessage(message, type) {
    formMessage.textContent = message;
    formMessage.className = `form-message ${type}`;
}

loginForm.addEventListener("submit", async function (event) {
    event.preventDefault();

    const email = emailInput.value.trim();
    const password = passwordInput.value;

    formMessage.className = "form-message";
    formMessage.textContent = "";

    if (!email || !password) {
        showMessage("Email and password are required.", "error");
        return;
    }

    loginButton.disabled = true;
    loginButton.textContent = "Signing in...";

    try {
        const response = await fetch("../../Backend/auth/login.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            credentials: "include",
            body: JSON.stringify({
                email,
                password
            })
        });

        const result = await response.json();

        if (!response.ok) {
            showMessage(
                result.error || result.message || "Unable to sign in.",
                "error"
            );
            return;
        }

        if (result.status !== "success" || !result.data?.organization_type) {
            showMessage(
                "Your organization could not be identified.",
                "error"
            );
            return;
        }

        showMessage("Login successful.", "success");

        setTimeout(() => {
            window.location.replace("../portals/gateway.html");
        }, 300);

    } catch (error) {
        showMessage("Unable to connect to the server.", "error");
    } finally {
        loginButton.disabled = false;
        loginButton.textContent = "Sign In";
    }
});