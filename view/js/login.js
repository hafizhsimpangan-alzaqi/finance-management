const loginForm = document.getElementById("login-form");
const loginIdentity = document.getElementById("identity");
const loginPassword = document.getElementById("password");
const loginToggle = document.getElementById("toggle-password");
const loginSummary = document.getElementById("error-summary");
const loginSummaryMessage = document.getElementById("summary-message");
const loginSummaryLinks = document.getElementById("summary-links");
const loginStatus = document.getElementById("form-status");
let loginAttempted = false;

function validateLoginField(field) {
    const error = document.getElementById(`${field.id}-error`);
    const invalid = field.id === "password" ? field.value === "" : field.value.trim() === "";
    field.setAttribute("aria-invalid", String(invalid));
    error.textContent = invalid ? "Field ini wajib diisi." : "";
    return !invalid;
}

function renderLoginErrors(validFields) {
    const invalidFields = validFields.filter((field) => field.getAttribute("aria-invalid") === "true");
    loginSummary.hidden = invalidFields.length === 0;
    loginSummaryMessage.textContent = invalidFields.length ? "Semua field wajib diisi." : "";
    loginSummaryLinks.replaceChildren();
    invalidFields.forEach((field) => {
        const item = document.createElement("li");
        const link = document.createElement("a");
        link.href = `#${field.id}`;
        link.textContent = field.id === "identity" ? "Username atau Email" : "Password";
        item.append(link);
        loginSummaryLinks.append(item);
    });
}

loginForm.addEventListener("submit", (event) => {
    loginAttempted = true;
    loginStatus.textContent = "";
    const validFields = [loginIdentity, loginPassword];
    const isValid = validFields.map(validateLoginField).every(Boolean);
    renderLoginErrors(validFields);
    if (!isValid) {
        event.preventDefault();
        loginSummary.focus();
        return;
    }
});

[loginIdentity, loginPassword].forEach((field) => {
    field.addEventListener("input", () => {
        loginStatus.textContent = "";
        if (loginAttempted) {
            validateLoginField(field);
            renderLoginErrors([loginIdentity, loginPassword]);
        }
    });
});

loginToggle.addEventListener("click", () => {
    const showing = loginPassword.type === "password";
    loginPassword.type = showing ? "text" : "password";
    loginToggle.setAttribute("aria-label", showing ? "Sembunyikan password" : "Tampilkan password");
    loginToggle.setAttribute("aria-pressed", String(showing));
});
