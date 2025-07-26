  const dadosPenitenciaria = JSON.parse(localStorage.getItem("dadosPenitenciaria") || "{}");
  const totalCarrinho = Number(localStorage.getItem("totalCarrinho")) || 0;


  export function montarPayloadFrete() {
  // Se o usuário escolheu penitenciária:
  const enviarParaPenitenciaria = document.getElementById('btnRadioPenitenciaria')?.checked;


 

  // Campo de frete selecionado (ajuste conforme sua lógica)
  let forma_envio = null, frete_valor = null;
  document.querySelectorAll('input[name="freteMetodo"]').forEach(radio => {
    if(radio.checked) {
      forma_envio = radio.value;
      frete_valor = Number(radio.dataset.valor) || null; // coloque o valor no data-valor ou pegue de onde já estiver salvo
    }
  });

  // Monta remetente (dados do visitante)
  const remetente = montarPayloadVisitante()

  // Monta destinatário
  let destinatario = {};
  if (!enviarParaPenitenciaria) {
    destinatario = {
      nome: document.getElementById("destinatario")?.value || "",
      logradouro: document.getElementById("logradouroDestinatario")?.value || "",
      numero: document.getElementById("numeroDestinatario")?.value || "",
      bairro: document.getElementById("bairroDestinatario")?.value || "",
      cidade: document.getElementById("cidadeDestinatario")?.value || "",
      estado: document.getElementById("estadoDestinatario")?.value || "",
      cep: document.getElementById("cepDestinatario")?.value || "",
      complemento: document.getElementById("complementoDestinatario")?.value || "",
    };
  } else {
    destinatario = {
      nome: dadosPenitenciaria.nome || "",
      logradouro: dadosPenitenciaria.logradouro || "",
      numero: dadosPenitenciaria.numero || "",
      bairro: dadosPenitenciaria.bairro || "",
      cidade: dadosPenitenciaria.cidade || "",
      estado: dadosPenitenciaria.estado || "",
      cep: dadosPenitenciaria.cep || "",
      complemento: dadosPenitenciaria.complemento || "",
    };
  }
  const payload= {
    envio: {
      slug_penitenciaria: dadosPenitenciaria.slug || "",
      nome_penitenciaria: dadosPenitenciaria.nome || "",
      peso_carrinho: totalCarrinho.peso,
      enviar_para_penitenciaria: !!enviarParaPenitenciaria,
      forma_envio: forma_envio,
      frete_valor: frete_valor,
      valor_carrinho: totalCarrinho.valorTotal, 
      //com a taxa de 10% inclusa, mas sem o frete
      destinatario,
      remetente,
    }
  }
  console.log(payload);

  return payload;
}

export function montarPayloadDetento(){
 const detento =  {
      nome: document.getElementById("nomeDetento")?.value || "",
      matricula: document.getElementById("matriculaDetento")?.value || "",
      raio: document.getElementById("raioDetento")?.value || "",
      cela: document.getElementById("celaDetento")?.value || "",
      nome_penitenciaria: dadosPenitenciaria.nome || "",
      slug_penitenciaria: dadosPenitenciaria.slug || "",
 }
 return detento;
} 


export function montarPayloadVisitante(){
    const remetente = {
    nome: document.getElementById("nomeVisitante")?.value || "",
    logradouro: document.getElementById("ruaVisitante")?.value || "",
    numero: document.getElementById("numeroVisitante")?.value || "",
    bairro: document.getElementById("bairroVisitante")?.value || "",

    cidade: document.getElementById("cidadeVisitante")?.value || "",
    estado: document.getElementById("estadoVisitante")?.value || "",
    cep: document.getElementById("cepVisitante")?.value || "",
    complemento:  document.getElementById("complementoVisitante")?.value || "",
  };
  return remetente;
}