import { validarCarrinhoAPI } from './validarCarrinhoAPI.js'
import { validarEnvioForm, validarFreteAPI } from './validarEnvio.js'
import { validarPagamento } from './validarPagamento.js'
import { validarClienteForm } from './validarClienteForm.js'
import { montarPayloadFrete } from './montarPayload.js'

export {
  montarPayloadFrete,
  validarCarrinhoAPI,
  validarEnvioForm,
  validarFreteAPI,
  validarPagamento,
  validarClienteForm
}
