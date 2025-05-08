fetch("http://clickjumbo.local/wp-json/clickjumbo/v1/produtos")
  .then(async (res) => {
    console.log("Status da resposta:", res.status);
    console.log("Status da resposta:", res.statusText);
    if (!res.ok) {
      throw new Error(`Erro HTTP: ${res.status}`);
    }

    const text = await res.text();

    try {
      const json = JSON.parse(text);
      if (json.status !== 200) {
        throw new Error(`Erro da API: ${json.message}`);
      }
      return json.content; // apenas os produtos
    } catch (err) {
      throw new Error("Erro ao parsear o JSON: " + err.message);
    }
  })
  .then((produtos) => {
    console.log("Produtos recebidos:", produtos);
    // aqui você manipula diretamente o array de produtos
  })
  .catch((err) => {
    console.error("Erro ao buscar produtos:", err);
  });
