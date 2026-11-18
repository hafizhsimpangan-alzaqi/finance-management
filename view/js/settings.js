const attemptedForms = new Set();
const formRules = {
    "username-form": ["username"],
    "password-form": ["current-password", "new-password"],
    "balance-form": ["opening-balance"]
};

function fieldError(field) {
    const value = field.value;
    const trimmed = value.trim();
    if (field.id === "username") {
        if (!trimmed) return "Username wajib diisi.";
        if (Array.from(value).length > 50) return "Username maksimal 50 karakter.";
    }
    if (field.id === "current-password" && value === "") return "Password lama wajib diisi.";
    if (field.id === "new-password") {
        if (value === "") return "Password baru wajib diisi.";
        if (Array.from(value).length < 8) return "Password baru minimal 8 karakter.";
    }
    if (field.id === "opening-balance") {
        if (trimmed === "") return "Saldo awal wajib diisi.";
        if (trimmed.startsWith("-")) return "Saldo awal tidak boleh negatif.";
        if (!/^\d+$/.test(trimmed)) return "Saldo awal harus berupa bilangan bulat.";
    }
    return "";
}

function validateForm(formId) {
    const fields = formRules[formId].map((id) => document.getElementById(id));
    const invalid = [];
    fields.forEach((field) => {
        const message = fieldError(field);
        field.setAttribute("aria-invalid", String(Boolean(message)));
        document.getElementById(`${field.id}-error`).textContent = message;
        if (message) invalid.push({ field, message });
    });

    const summary = document.getElementById(formId.replace("-form", "-summary"));
    const list = document.getElementById(formId.replace("-form", "-summary-list"));
    list.replaceChildren();
    summary.hidden = invalid.length === 0;
    invalid.forEach(({ field, message }) => {
        const item = document.createElement("li");
        const link = document.createElement("a");
        link.href = `#${field.id}`;
        link.textContent = message;
        item.append(link);
        list.append(item);
    });
    return { invalid, summary };
}

Object.keys(formRules).forEach((formId) => {
    const form = document.getElementById(formId);
    if (!form) return;
    const feedback = document.getElementById(formId.replace("-form", "-feedback"));
    form.addEventListener("submit", (event) => {
        attemptedForms.add(formId);
        feedback.textContent = "";
        const { invalid, summary } = validateForm(formId);
        if (invalid.length) {
            event.preventDefault();
            summary.focus();
            return;
        }
    });

    formRules[formId].forEach((fieldId) => {
        document.getElementById(fieldId).addEventListener("input", () => {
            feedback.textContent = "";
            if (attemptedForms.has(formId)) validateForm(formId);
        });
    });
});

document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    button.addEventListener("click", () => {
        const field = document.getElementById(button.dataset.passwordToggle);
        const visible = field.type === "password";
        field.type = visible ? "text" : "password";
        button.textContent = visible ? "Sembunyikan" : "Tampilkan";
        button.setAttribute("aria-label", `${visible ? "Sembunyikan" : "Tampilkan"} ${field.id === "current-password" ? "password lama" : "password baru"}`);
        button.setAttribute("aria-pressed", String(visible));
    });
});

document.getElementById("opening-balance")?.addEventListener("input", (event) => {
    const value = event.currentTarget.value.trim();
    const preview = document.getElementById("balance-preview");
    if (/^\d+$/.test(value)) {
        const normalized = value.replace(/^0+(?=\d)/, "");
        preview.textContent = `Pratinjau tampilan: Rp${normalized.replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
    } else {
        preview.textContent = "Pratinjau tampilan: —";
    }
});
