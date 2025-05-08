import { fetchedData } from "../mock/fetchedData.js";
  
const datalist = document.getElementById("penitenciariaOptions");
const input = document.getElementById("penitenciariaInput");
const buscarBtn = document.getElementById("buscarBtn");

function populatePenitenciariasList(data) {
const penitenciarias = new Set(
data.content
  .map((item) => item.prison?.trim())
  .filter((prison) => prison) // remove falsy (null, undefined, "")
);

datalist.innerHTML = "";
penitenciarias.forEach((prison) => {
const option = document.createElement("option");
option.value = prison;
datalist.appendChild(option);
});
}


populatePenitenciariasList(fetchedData);

buscarBtn.addEventListener("click", () => {
  const selected = input.value.trim();
  if (selected) {
    const encoded = encodeURIComponent(selected);
    window.location.href = `shop.html?penitenciaria=${encoded}`;
  } else {
    alert("Por favor, selecione uma penitenciária.");
  }
});