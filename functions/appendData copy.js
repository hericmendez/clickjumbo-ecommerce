export const appendData = (
  data,
  parent,
  handleAddToCart,
  handleRemoveOne,
  cartData = []
) => {
  parent.innerHTML = null;

  const fallbackThumb = "https://placehold.co/400x400/orange/white";

  const categoryMap = {};
  data.forEach((item) => {
    const { category, subcategory } = item;
    if (!categoryMap[category]) categoryMap[category] = {};
    if (!categoryMap[category][subcategory])
      categoryMap[category][subcategory] = [];
    categoryMap[category][subcategory].push(item);
  });

  Object.entries(categoryMap).forEach(([category, subcats]) => {
    const categoryHeading = document.createElement("h2");
    categoryHeading.textContent = category;
    categoryHeading.className = "text-center";
    categoryHeading.style = "margin-top: 2rem; font-size: 2rem;";
    parent.appendChild(categoryHeading); //categoria vai aqui
    //createOrderSelect iria aqui
    const hr = document.createElement("hr");
    parent.appendChild(hr);

    Object.entries(subcats).forEach(([subcatName, items]) => {
      const subHeader = document.createElement("div");
      subHeader.style =
        "display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;";
      subHeader.className = "sub-header";

      const heading = document.createElement("h3");
      heading.textContent = subcatName;
      heading.style = "margin: 0;";
      const toggleBtn = document.createElement("button");
      toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i>';
      toggleBtn.setAttribute("aria-expanded", "true");
      toggleBtn.classList.add("btn", "btn-sm", "btn-link", "p-0");
      toggleBtn.style = "font-size: 1.2rem;";

      subHeader.appendChild(heading);
      subHeader.appendChild(toggleBtn);
      parent.appendChild(subHeader);

      const hr = document.createElement("hr");
      hr.style = "margin-top: 4px; margin-bottom: 12px;";
      parent.appendChild(hr);

      // Tabela desktop
      const tableWrapper = document.createElement("div");
      tableWrapper.className =
        "table-responsive d-none d-md-block table-transition";

      const table = document.createElement("table");
      table.classList.add("table", "table-bordered", "align-middle");

      const thead = document.createElement("thead");
      thead.innerHTML = `
        <tr>
          <th>Produto</th>
          <th>Preço</th>
          <th>Peso</th>
          <th>Qtde</th>
          <th style="width: 1%; white-space: nowrap; text-align: center;">Ações</th>
        </tr>
      `;
      table.appendChild(thead);

      const tbody = document.createElement("tbody");

      // Lista mobile
      const mobileList = document.createElement("div");
      mobileList.classList.add(
        "toggle-section",
        "toggle-section-collapsed",
        "d-md-none"
      );

      // mobileList.style = "display: flex; flex-direction: column; gap: 1rem; ";

      items.forEach((item) => {
        const { id, name, thumb, price, weight, maxUnitsPerClient } = item;
        const carrinhoItem = cartData.find((prod) => prod.id === id);
        const qtdeAtual = carrinhoItem ? carrinhoItem.qty : 0;
        const thumbUrl = thumb && thumb.trim() ? thumb.trim() : fallbackThumb;
        const urlImage = "https://placehold.co/400x400";

        // === Tabela (desktop)
        const tr = document.createElement("tr");

        const tdProduto = document.createElement("td");
        tdProduto.innerHTML = `
          <div class="d-flex align-items-center gap-2">
            <img src="${fallbackThumb}" alt="${name}" style="width:80px; height:80px; object-fit:cover;">
            <span style="font-weight: 600;">${name.toUpperCase()}</span>
          </div>
        `;

        const tdPreco = document.createElement("td");
        tdPreco.textContent = `R$ ${price.toFixed(2).replace(".", ",")}`;

        const tdPeso = document.createElement("td");
        tdPeso.textContent = `${weight}kg`;

        const tdQtd = document.createElement("td");
        tdQtd.textContent = `${qtdeAtual} / ${maxUnitsPerClient}`;

        const tdAcoes = document.createElement("td");
        tdAcoes.style.whiteSpace = "nowrap";
        tdAcoes.style.textAlign = "center";

        const addBtn = document.createElement("button");
        addBtn.textContent = "+";
        addBtn.setAttribute("class", "btn btn-success mx-1");
        addBtn.style = "font-size: 1.25rem; padding: 0.5rem 1rem;";
        addBtn.disabled = maxUnitsPerClient <= qtdeAtual;
        addBtn.addEventListener("click", () => handleAddToCart(item));

        const removeBtn = document.createElement("button");
        removeBtn.textContent = "-";
        removeBtn.setAttribute("class", "btn btn-danger mx-1");
        removeBtn.style = "font-size: 1.25rem; padding: 0.5rem 1rem;";
        removeBtn.disabled = qtdeAtual <= 0;
        removeBtn.addEventListener("click", () => handleRemoveOne(item));

        tdAcoes.append(removeBtn, addBtn);
        tr.append(tdProduto, tdPreco, tdPeso, tdQtd, tdAcoes);
        tbody.appendChild(tr);

        // === Card mobile
        const card = document.createElement("div");
        card.className = "border p-2 rounded d-flex";
        card.style = "gap: 0.75rem; align-items: center;";

        const img = document.createElement("img");
        img.src = fallbackThumb;
        img.alt = name;
        img.style = "width: 80px; height: 80px; object-fit: cover;";

        const info = document.createElement("div");
        info.style = "flex: 1;";
        info.innerHTML = `
          <div style="font-weight: 600;">${name.toUpperCase()} (${weight}kg)</div>
          <div>R$ ${price.toFixed(2).replace(".", ",")}</div>
          <div>Qtd: ${qtdeAtual} / ${maxUnitsPerClient}</div>
        `;

        const actions = document.createElement("div");
        actions.className = "d-flex flex-column gap-1";

        const addBtnMobile = document.createElement("button");
        addBtnMobile.textContent = "+";
        addBtnMobile.setAttribute("class", "btn btn-success");
        addBtnMobile.style = "font-size: 1.25rem; padding: 0.5rem 1rem;";
        addBtnMobile.disabled = maxUnitsPerClient <= qtdeAtual;
        addBtnMobile.addEventListener("click", () => handleAddToCart(item));

        const removeBtnMobile = removeBtn.cloneNode(true);
        removeBtnMobile.addEventListener("click", () => handleRemoveOne(item));
        mobileList.classList.toggle("toggle-section-collapsed", false);

        actions.append(addBtnMobile, removeBtnMobile);

        card.append(img, info, actions);
        mobileList.appendChild(card);
      });

      table.appendChild(tbody);
      tableWrapper.appendChild(table);
      parent.appendChild(tableWrapper);
      parent.appendChild(mobileList);

      toggleBtn.addEventListener("click", () => {
        const isExpanded = toggleBtn.getAttribute("aria-expanded") === "true";
        tableWrapper.classList.toggle("table-transition-collapsed", isExpanded);
        mobileList.classList.toggle("toggle-section-collapsed", isExpanded);

        toggleBtn.innerHTML = isExpanded
          ? '<i class="bi bi-chevron-down"></i>'
          : '<i class="bi bi-chevron-up"></i>';
        toggleBtn.setAttribute("aria-expanded", isExpanded ? "false" : "true");
      });
    });
  });
};
