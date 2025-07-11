export const notify = (type, message) => {
  const uniqueId = `toast-${Date.now()}`;

  // Cria o elemento do Toast
  const toast = document.createElement("div");
  toast.className =
    "toast align-items-center text-white bg-" + type + " border-0";
  toast.id = uniqueId;
  toast.role = "alert";
  toast.setAttribute("aria-live", "assertive");
  toast.setAttribute("aria-atomic", "true");

  toast.innerHTML = `
    <div class="d-flex">
      <div class="toast-body">
        ${message}
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  `;

  // Encontra (ou cria) o container
  let container = document.getElementById("toastContainer");
  if (!container) {
    container = document.createElement("div");
    container.id = "toastContainer";
    container.className = "toast-container position-fixed bottom-0 end-0 p-3 ";
    container.style.marginBottom = "100px"; // Define a largura do container

    document.body.appendChild(container);
  }

  // Adiciona o toast ao container
  container.appendChild(toast);

  // Exibe o toast
  const bsToast = new bootstrap.Toast(toast, {
    delay: 3000, // fecha sozinho após 3 segundos
  });
  bsToast.show();
};
