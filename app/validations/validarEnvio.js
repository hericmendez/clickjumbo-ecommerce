import { apiFetch } from '../api/apiFetch.js';
import { API_URL } from '../api/baseUrl.js'

const token = localStorage.getItem("token")

export async function validarFreteAPI (payload) {
console.log("payload validation ==> ", payload);
  try {
    const response = await apiFetch(`/validate-shipping`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Authorization: `Bearer ${token}`
      },
      body: JSON.stringify(payload)
    })
    const data = await response.json()

    console.log('response ==> ', data)
    return data.success;
  } catch (error) {
    console.log('error ==> ', error)
  }
}

export function validarEnvioForm(){
    const outroEnderecoSelecionado = document.getElementById('btnRadioOutro')?.checked;
    if (!outroEnderecoSelecionado) return true;

    // IDs dos campos obrigatórios
    const campos = [
        'destinatario',
        'logradouroDestinatario',
        'numeroDestinatario',
        'bairroDestinatario',
        'cidadeDestinatario',
        'estadoDestinatario',
        'cepDestinatario'
    ];
    let valido = true;

    campos.forEach(id => {
        const campo = document.getElementById(id);
        if (!campo || !campo.value.trim()) {
            campo?.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
        }
    });

    return valido;
}


