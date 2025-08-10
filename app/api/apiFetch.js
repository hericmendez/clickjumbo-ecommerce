import { getItem } from '../functions/localStorage.js';
import {API_URL} from './baseUrl.js';
export async function apiFetch(endpoint, options = {}) {
  const token = getItem('token'); // Ou sessionStorage/cookie
  console.log("token apiFetch ==> ", token);
  const headers = {
    ...(options.headers || {}),
    ...(token ? { 'Authorization': `Bearer ${token}` } : {})
        };
   const url = API_URL+endpoint;
   console.log("chamada na url ==> ", url);
        const resp = await fetch(url, {
    ...options,
    headers,

  });

  if (resp.status === 401 || resp.status === 403) {
    // Usuário não autenticado ou token inválido
    localStorage.removeItem('token');
    alert('Sua sessão expirou! Faça login novamente.'); // Opcional
    window.location.href = '/app/login.html'; // Troque para sua rota de login
    return Promise.reject('Sessão expirada');
  }

  return resp;
}
