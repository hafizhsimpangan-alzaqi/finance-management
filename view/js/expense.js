const expenseDialogs = ["add-dialog", "detail-dialog", "edit-dialog", "delete-dialog"].map((id) => document.getElementById(id));
const dialogOpeners = new Map();
const attemptedForms = new Set();
function formatRupiah(value) { return "Rp" + value.replace(/\B(?=(\d{3})+(?!\d))/g, "."); }

function openExpenseDialog(dialog, opener, focusId) {
    dialogOpeners.set(dialog, opener);
    dialog.showModal();
    document.getElementById(focusId).focus();
}

function resetExpenseForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    form.reset();
    attemptedForms.delete(formId);
    form.querySelectorAll("input, textarea").forEach((field) => field.setAttribute("aria-invalid", "false"));
    form.querySelectorAll(".field-error").forEach((error) => { error.textContent = ""; });
    const summary = document.getElementById(formId.replace("-form", "-summary"));
    summary.hidden = true;
    document.getElementById(formId.replace("-form", "-summary-list")).replaceChildren();
    document.getElementById(formId.replace("-form", "-feedback")).textContent = "";
}

expenseDialogs.forEach((dialog) => {
    dialog.addEventListener("cancel", (event) => event.preventDefault());
    dialog.addEventListener("close", () => {
        if (dialog.id === "add-dialog" || dialog.id === "edit-dialog") resetExpenseForm(dialog.id.replace("-dialog", "-form"));
        const opener = dialogOpeners.get(dialog);
        opener?.focus();
        dialogOpeners.delete(dialog);
    });
    dialog.querySelectorAll("[data-close-dialog]").forEach((button) => button.addEventListener("click", () => dialog.close()));
});

document.getElementById("open-add-dialog").addEventListener("click", (event) => {
    resetExpenseForm("add-form");
    openExpenseDialog(document.getElementById("add-dialog"), event.currentTarget, "add-description");
});

const formRules = {
    "add-form": ["add-description", "add-amount", "add-note"],
    "edit-form": ["edit-description", "edit-amount", "edit-note"]
};

function fieldError(field) {
    const value = field.value.trim();
    if (field.id.endsWith("description") && !value) return "Keterangan wajib diisi.";
    if (field.id.endsWith("description") && Array.from(value).length > 100) return "Keterangan maksimal 100 karakter.";
    if (field.id.endsWith("amount") && (!/^\d+$/.test(value) || !/[1-9]/.test(value))) return "Nominal harus berupa bilangan bulat lebih dari Rp0.";
    if (field.id.endsWith("note") && Array.from(field.value).length > 500) return "Catatan maksimal 500 karakter.";
    return "";
}

function validateExpenseForm(formId) {
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
    return { fields, invalid, summary };
}

Object.entries(formRules).forEach(([formId]) => {
    const form = document.getElementById(formId);
    form.addEventListener("submit", (event) => {
        attemptedForms.add(formId);
        const feedback = document.getElementById(formId.replace("-form", "-feedback"));
        feedback.textContent = "";
        const { invalid, summary } = validateExpenseForm(formId);
        if (invalid.length) {
            event.preventDefault();
            summary.focus();
            return;
        }
    });
    form.querySelectorAll("input, textarea").forEach((field) => field.addEventListener("input", () => {
        document.getElementById(formId.replace("-form", "-feedback")).textContent = "";
        if (attemptedForms.has(formId)) validateExpenseForm(formId);
    }));
});

document.querySelectorAll("[data-action]").forEach((button) => button.addEventListener("click", (event) => {
    const action = event.currentTarget.dataset.action;
    const row = event.currentTarget.closest("tr");
    const opener = event.currentTarget.closest("details").querySelector("summary");
    event.currentTarget.closest("details").open = false;

    if (action === "detail") {
        document.getElementById("detail-date").textContent = row.dataset.date;
        document.getElementById("detail-description").textContent = row.dataset.description;
        document.getElementById("detail-note").textContent = row.dataset.note || "—";
        document.getElementById("detail-amount").textContent = formatRupiah(row.dataset.amount);
        openExpenseDialog(document.getElementById("detail-dialog"), opener, "close-detail-dialog");
    } else if (action === "edit") {
        resetExpenseForm("edit-form");
        document.getElementById("edit-transaction-id").value = row.dataset.id;
        document.getElementById("edit-description").value = row.dataset.description;
        document.getElementById("edit-amount").value = row.dataset.amount;
        document.getElementById("edit-note").value = row.dataset.note;
        openExpenseDialog(document.getElementById("edit-dialog"), opener, "edit-description");
    } else if (action === "delete") {
        document.getElementById("delete-transaction-id").value = row.dataset.id;
        document.getElementById("delete-transaction-name").textContent = row.dataset.description;
        openExpenseDialog(document.getElementById("delete-dialog"), opener, "cancel-delete-dialog");
    }
}));

