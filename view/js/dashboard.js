const balanceDialog = document.getElementById("balance-dialog");
const balanceForm = document.getElementById("balance-form");
const openingBalance = document.getElementById("opening-balance");
const balanceError = document.getElementById("balance-error");
const balanceFeedback = document.getElementById("balance-feedback");
let balanceDialogOpener = null;

function closeBalanceDialog() {
    if (balanceDialog.open) balanceDialog.close();
}

document.getElementById("open-balance-dialog")?.addEventListener("click", (event) => {
    balanceDialogOpener = event.currentTarget;
    balanceDialog.showModal();
    openingBalance.focus();
});

["close-balance-dialog", "cancel-balance-dialog"].forEach((id) => {
    document.getElementById(id).addEventListener("click", closeBalanceDialog);
});

balanceDialog.addEventListener("cancel", (event) => event.preventDefault());
balanceDialog.addEventListener("close", () => {
    openingBalance.value = "";
    openingBalance.setAttribute("aria-invalid", "false");
    openingBalance.parentElement.setAttribute("aria-invalid", "false");
    balanceError.textContent = "";
    balanceFeedback.textContent = "";
    balanceDialogOpener?.focus();
    balanceDialogOpener = null;
});

openingBalance.addEventListener("input", () => {
    openingBalance.setAttribute("aria-invalid", "false");
    openingBalance.parentElement.setAttribute("aria-invalid", "false");
    balanceError.textContent = "";
    const value = openingBalance.value.trim();
    const normalized = value.replace(/^0+(?=\d)/, "");
    balanceFeedback.textContent = /^\d+$/.test(value) ? `Pratinjau: Rp${normalized.replace(/\B(?=(\d{3})+(?!\d))/g, ".")}` : "";
});

balanceForm.addEventListener("submit", (event) => {
    const valid = /^\d+$/.test(openingBalance.value.trim());
    openingBalance.setAttribute("aria-invalid", String(!valid));
    openingBalance.parentElement.setAttribute("aria-invalid", String(!valid));
    balanceError.textContent = valid ? "" : "Masukkan nominal berupa bilangan bulat minimal Rp0.";
    balanceFeedback.textContent = "";
    if (!valid) {
        event.preventDefault();
        openingBalance.focus();
        return;
    }
});
