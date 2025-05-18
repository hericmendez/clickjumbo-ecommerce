const http = require('http');
const https = require('https');
const { URL } = require('url');

const API_URL = 'http://clickjumbo.local/wp-json/clickjumbo/v1';
let token = '';

function request(method, endpoint, data = null, auth = false) {
  return new Promise((resolve, reject) => {
    const url = new URL(endpoint, API_URL);
    const body = data ? JSON.stringify(data) : null;
    const isHttps = url.protocol === 'https:';
    const lib = isHttps ? https : http;

    const options = {
      method,
      hostname: url.hostname,
      path: url.pathname + url.search,
      port: url.port || (isHttps ? 443 : 80),
      headers: {
        'Content-Type': 'application/json',
        'Content-Length': body ? Buffer.byteLength(body) : 0
      }
    };

    if (auth) {
      options.headers['Authorization'] = `Bearer ${token}`;
    }

    const req = lib.request(options, (res) => {
      let chunks = '';
      res.on('data', chunk => chunks += chunk);
      res.on('end', () => {
        console.log("chunks ==> ", chunks);
        try {
          const json = JSON.parse(chunks);
     
          resolve(json);
        } catch (err) {
          reject(new Error('❌ Erro ao parsear JSON: ' + err.message));
        }
      });
    });

    req.on('error', reject);
    if (body) req.write(body);
    req.end();
  });
}

// 🔐 Login
async function login(email, password) {
  const res = await request('POST', '/login', { email, password });
  if (res.success && res.token) {
    token = res.token;
    console.log('✅ Login OK:', email);
  } else {
    throw new Error('❌ Falha no login');
  }
}

// 🛍️ Listar produtos
async function listarProdutos(penitenciaria) {
  const res = await request('GET', `/product-list?penitenciaria=${penitenciaria}`);
  console.log('🛍️ Produtos:', res.produtos.map(p => p.nome));
  return res.produtos;
}

// 🧾 Validar carrinho
async function validarCarrinho(carrinho) {
  const res = await request('POST', '/validate-cart', { itens: carrinho }, true);
  console.log('🧾 Total:', res.total);
  return res;
}

// 🚚 Calcular frete
async function calcularFrete() {
  const payload = {
    cep_origem: '01001-000',
    cep_destino: '20040-020',
    peso: 2.5,
    comprimento: 25,
    largura: 15,
    altura: 10
  };
  const res = await request('POST', '/calculate-shipping', payload);
  console.log('🚚 Frete PAC:', res.frete?.PAC?.valor);
  return res.frete?.PAC;
}

// 💳 Gerar Pix
async function gerarPix(valor) {
  const res = await request('POST', '/generate-pix', { valor, txid: 'pedido-node-001' });
  console.log('💳 Pix gerado. Código:', res.codigo_pix?.slice(0, 20) + '...');
  return res;
}

// 🧾 Processar pedido
async function processarPedido(carrinho, frete, total) {
  const payload = {
    cart: carrinho,
    shipping: {
      method: 'PAC',
      value: frete.valor
    },
    payment: {
      method: 'pix',
      value: total + frete.valor
    }
  };
  const res = await request('POST', '/process-order', payload, true);
  console.log('📦 Pedido finalizado:', res.message);
  return res;
}

// 🚀 Fluxo principal
(async () => {
  try {
    await login('heric.mendez00@gmail.com', 'admin123');

    const produtos = await listarProdutos('SP001');
    const carrinho = [
      { id: produtos[0].id, quantidade: 1 },
      { id: produtos[1].id, quantidade: 2 }
    ];

    const validados = await validarCarrinho(carrinho);
    const frete = await calcularFrete();
    await gerarPix(validados.total + frete.valor);
    await processarPedido(carrinho, frete, validados.total);

    console.log('✅ Compra simulada com sucesso via Node.js');
  } catch (err) {
    console.error(err.message);
  }
})();
