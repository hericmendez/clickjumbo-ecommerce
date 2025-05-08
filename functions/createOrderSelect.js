// functions/createOrderSelect.js
export function createOrderSelect(container, currentOrder, onChangeCallback) {
    if (!container) return;
  
    container.innerHTML = `
      <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
        <label for="orderBySelect" class="form-label mb-0 fw-semibold">Ordenar por:</label>
        <select id="orderBySelect" class="form-select form-select-sm" style="width: auto;">
          <option value="asc">Subcategoria (A–Z)</option>
          <option value="desc">Subcategoria (Z–A)</option>
        </select>
      </div>
    `;
  
    const orderBySelect = document.getElementById("orderBySelect");
    if (orderBySelect) {
      orderBySelect.value = currentOrder;
      orderBySelect.addEventListener("change", (e) => {
        onChangeCallback(e.target.value);
      });
    }
  }
  