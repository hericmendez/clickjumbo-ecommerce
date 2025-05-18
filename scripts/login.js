import { setItem } from "../functions/localStorage.js";

const form = document.getElementById("form");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const username = form.querySelector("#email").value.trim();
  const password = form.querySelector("#password").value.trim();

  if (!username || !password) {
    alert("Por favor, preencha todos os campos.");
    return;
  }

  try {
    const response = await fetch(
      "http://clickjumbo.local/wp-json/clickjumbo/v1/login",
      {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({ username, password }),
      }
    );

    const data = await response.json();

    if (!response.ok || !data.success || !data.token) {
      alert("Email ou senha inválidos.");
      return;
    }

    setItem("token", data.token);
    setItem("user", data.user); // se `user` for retornado pelo backend

    alert("Login realizado com sucesso!");
    window.location.href = "index.html";
  } catch (err) {
    console.error("Erro na requisição:", err);
    alert("Erro ao tentar logar. Tente novamente mais tarde.");
  }
});
