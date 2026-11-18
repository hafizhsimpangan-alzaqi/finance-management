const registerForm = document.getElementById("register-form");
const registerUsername = document.getElementById("username");
const registerEmail = document.getElementById("email");
const registerPassword = document.getElementById("password");
const registerToggle = document.getElementById("toggle-password");
const registerSummary = document.getElementById("error-summary");
const registerSummaryLinks = document.getElementById("summary-links");
const registerStatus = document.getElementById("form-status");
let registerAttempted = false;

function validateRegisterField(field) {
    const error = document.getElementById(`${field.id}-error`);
    const value = field.value.trim();
    let message = "";

    if (field.id === "password" ? field.value === "" : !value) {
        message = `${field.id === "username" ? "Username" : field.id === "email" ? "Email" : "Password"} wajib diisi.`;
    } else if (field.id === "username" && Array.from(value).length > 50) {
        message = "Username maksimal 50 karakter.";
    } else if (field.id === "email" && !field.validity.valid) {
        message = "Masukkan alamat email dengan format yang valid.";
    } else if (field.id === "password" && Array.from(field.value).length < 8) {
        message = "Password minimal 8 karakter.";
    }

    field.setAttribute("aria-invalid", String(Boolean(message)));
    error.textContent = message;
    return message === "";
}

function renderRegisterErrors(fields) {
    const invalidFields = fields.filter((field) => field.getAttribute("aria-invalid") === "true");
    registerSummary.hidden = invalidFields.length === 0;
    registerSummaryLinks.replaceChildren();
    invalidFields.forEach((field) => {
        const item = document.createElement("li");
        const link = document.createElement("a");
        link.href = `#${field.id}`;
        link.textContent = document.getElementById(`${field.id}-error`).textContent;
        item.append(link);
        registerSummaryLinks.append(item);
    });
}

const registerFields = [registerUsername, registerEmail, registerPassword];
registerForm.addEventListener("submit", (event) => {
    registerAttempted = true;
    registerStatus.textContent = "";
    const isValid = registerFields.map(validateRegisterField).every(Boolean);
    renderRegisterErrors(registerFields);
    if (!isValid) {
        event.preventDefault();
        registerSummary.focus();
        return;
    }
});

registerFields.forEach((field) => {
    field.addEventListener("input", () => {
        registerStatus.textContent = "";
        if (registerAttempted) {
            validateRegisterField(field);
            renderRegisterErrors(registerFields);
        }
    });
});

registerToggle.addEventListener("click", () => {
    const showing = registerPassword.type === "password";
    registerPassword.type = showing ? "text" : "password";
    registerToggle.setAttribute("aria-label", showing ? "Sembunyikan password" : "Tampilkan password");
    registerToggle.setAttribute("aria-pressed", String(showing));
});
