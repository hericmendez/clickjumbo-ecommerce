import { API_URL } from "./baseUrl";
import { setItem } from "../functions/localStorage.js";

const form = document.getElementById("form");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const username = form.querySelector("#username")?.value.trim();
  const email = form.querySelector("#email")?.value.trim();
  const password = form.querySelector("#password")?.value.trim();
  const confirm = form.querySelector("#confirm")?.value?.trim();

  if (!username || !password) {
    alert("Por favor, preencha todos os campos.");
    return;
  }

  if (password !== confirm) {
    alert("As senhas não coincidem.");
    return;
  }

  try {
    const response = await fetch(
      `${API_URL}/register`,
      {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ username, email, password }),
      }
    );

    const data = await response.json();

    if (!response.ok || !data.success || !data.token) {
      alert(data.message || "Erro ao criar conta.");
      return;
    }

    setItem("token", data.token);
    setItem("user", data.user); // se o backend retornar

    alert("Conta criada com sucesso!");
    window.location.href = "/index.html";
  } catch (err) {
    console.error("Erro na requisição:", err);
    alert("Erro ao criar conta. Tente novamente mais tarde.");
  }
});
