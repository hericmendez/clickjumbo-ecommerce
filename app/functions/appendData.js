import { createOrderSelect } from './createOrderSelect.js'


function renderSubcategories (
  container,
  subcats,
  handleAddToCart,
  handleRemoveOne,
  dadosCarrinho
) {
container.innerHTML = '' // limpa o conteúdo anterior
console.log('renderSubcategories subcats:', subcats)

  Object.entries(subcats).forEach(([subcatName, items]) => {
const subHeader = document.createElement('div')

    subHeader.style =
'display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem;'
subHeader.className = 'sub-header'

const heading = document.createElement('h3')
heading.textContent = subcatName
heading.style = 'margin: 0;'
const toggleBtn = document.createElement('button')
toggleBtn.innerHTML = '<i class="bi bi-chevron-up"></i>'


toggleBtn.setAttribute('aria-expanded', 'true')
toggleBtn.classList.add('btn', 'btn-sm', 'btn-link', 'p-0')
toggleBtn.style = 'font-size: 1.2rem;'


subHeader.appendChild(heading)
subHeader.appendChild(toggleBtn)
container.appendChild(subHeader)


const hr = document.createElement('hr')
hr.style = 'margin-top: 4px; margin-bottom: 12px;'
container.appendChild(hr)


const tableWrapper = document.createElement('div')

    tableWrapper.className =
'table-responsive d-none d-md-block table-transition'


const table = document.createElement('table')
table.classList.add('table', 'table-bordered', 'align-middle')


const thead = document.createElement('thead')

    thead.innerHTML = `
      <tr>
        <th>Produto</th>
        <th>Preço</th>
        <th>Peso</th>
        <th>Qtde</th>
        <th style="width: 1%; white-space: nowrap; text-align: center;">Ações</th>
      </tr>
    `
    table.appendChild(thead)

const tbody = document.createElement('tbody')

const mobileList = document.createElement('div')

    mobileList.classList.add(
      'toggle-section',

      'd-md-none'
    )

    items.forEach(item => {
      // mesmo código de itens (tabela + mobile)
const { id, nome, thumb, preco, peso, maximo_por_cliente } = item
const carrinhoItem = dadosCarrinho.find(prod => prod.id === id)

      console.log(carrinhoItem)
const qtdeAtual = carrinhoItem ? carrinhoItem.qtde : 0
const fallbackThumb = 'https://placehold.co/400x400/orange/white'


const tr = document.createElement('tr')
const tdProduto = document.createElement('td')

      tdProduto.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <img style="width: 100px;" src=${
              thumb || fallbackThumb
            } alt="${nome}"/>
          <span style="font-weight: 600;">${nome.toUpperCase()}</span>
        </div>
      `
      const tdPreco = document.createElement('td')
      tdPreco.textContent = `R$ ${preco.toFixed(2).replace('.', ',')}`
      const tdPeso = document.createElement('td')
      tdPeso.textContent = `${peso}kg`
      const tdQtd = document.createElement('td')
      tdQtd.textContent = `${qtdeAtual} / ${maximo_por_cliente}`

const tdAcoes = document.createElement('td')
tdAcoes.style.whiteSpace = 'nowrap'
tdAcoes.style.textAlign = 'center'


const addBtn = document.createElement('button')
addBtn.textContent = '+'
addBtn.setAttribute('class', 'btn btn-success mx-1')
addBtn.style = 'font-size: 1.25rem; padding: 0.5rem 1rem;'
addBtn.disabled = maximo_por_cliente <= qtdeAtual
addBtn.addEventListener('click', () => handleAddToCart(item))


const removeBtn = document.createElement('button')
removeBtn.textContent = '-'
removeBtn.setAttribute('class', 'btn btn-danger mx-1')
removeBtn.style = 'font-size: 1.25rem; padding: 0.5rem 1rem;'
removeBtn.disabled = qtdeAtual <= 0
removeBtn.addEventListener('click', () => handleRemoveOne(item))


tdAcoes.append(removeBtn, addBtn)
tr.append(tdProduto, tdPreco, tdPeso, tdQtd, tdAcoes)
tbody.appendChild(tr)


      // mobile card
const card = document.createElement('div')
card.className = 'border p-2 rounded d-flex'
card.style = 'gap: 0.75rem; align-items: center;'
const img = document.createElement('img')
img.src = thumb
img.alt = nome
img.style = 'width: 80px; height: 80px; object-fit: cover;'
const info = document.createElement('div')
info.style = 'flex: 1;'

      info.innerHTML = `
        <div style="font-peso: 600;">${nome.toUpperCase()} (${peso}kg)</div>
;<div>R$ ${preco.toFixed(2).replace('.', ',')}</div>

        <div>Qtd: ${qtdeAtual} / ${maximo_por_cliente}</div>
      `
      const actions = document.createElement('div')
      actions.className = 'd-flex flex-column gap-1'
      const addBtnMobile = addBtn.cloneNode(true)
      addBtnMobile.addEventListener('click', () => handleAddToCart(item))
      const removeBtnMobile = removeBtn.cloneNode(true)
      removeBtnMobile.addEventListener('click', () => handleRemoveOne(item))

      actions.append(addBtnMobile, removeBtnMobile)
      card.append(img, info, actions)
      mobileList.appendChild(card)
    })

table.appendChild(tbody)
tableWrapper.appendChild(table)
container.appendChild(tableWrapper)
container.appendChild(mobileList)


    toggleBtn.addEventListener('click', () => {
      const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true'
      tableWrapper.classList.toggle('table-transition-collapsed', isExpanded)
      mobileList.classList.toggle('toggle-section-collapsed', isExpanded)

      toggleBtn.innerHTML = isExpanded
        ? '<i class="bi bi-chevron-down"></i>'
        : '<i class="bi bi-chevron-up"></i>'
      toggleBtn.setAttribute('aria-expanded', isExpanded ? 'false' : 'true')
    })
  })
}

export const appendData = (
  data,
  parent,
  handleAddToCart,
  handleRemoveOne,
  dadosCarrinho = []
) => {
  parent.innerHTML = null
  console.log('appendData items:', data)
  const hr = document.createElement('hr')
  hr.style = 'margin-top: 2rem; margin-bottom: 2rem;width: 100%;'
  const categoryMap = {}
  data.forEach(item => {
    const { categoria, subcategoria } = item
    if (!categoryMap[categoria]) categoryMap[categoria] = {}
    if (!categoryMap[categoria][subcategoria])
      categoryMap[categoria][subcategoria] = []
    categoryMap[categoria][subcategoria].push(item)
  })

  Object.entries(categoryMap).forEach(([categoria, subcats]) => {
    // Cria o container para título + select
const categoryHeaderContainer = document.createElement('div')

    categoryHeaderContainer.className =
'd-flex justify-content-between align-items-center mt-4 mb-2 flex-wrap gap-2'
const dadosPenitenciaria = JSON.parse(
  localStorage.getItem('dadosPenitenciaria')
)

    // Título da categoria
const categoryHeading = document.createElement('h2')
categoryHeading.textContent = `${categoria} na ${dadosPenitenciaria?.nome}`
categoryHeading.className = 'm-0'
categoryHeading.style.fontSize = '2rem'
const hr = document.createElement('hr')

    // Select de ordenação
const selectContainer = document.createElement('div')


    // Adiciona os dois elementos ao container

parent.appendChild(categoryHeaderContainer)
categoryHeaderContainer.appendChild(categoryHeading)
categoryHeaderContainer.appendChild(selectContainer)

    // Adiciona o container ao DOM

function validarString (str, separator) {
  if (str === 'não cadastrado' || str === '') {
    return ''
  }
  return `${str}${separator || ''}`
}

const div = document.createElement('div')


    div.innerHTML = dadosPenitenciaria
      ? `


      <div class="p-2 border border-rounded-4 flex flex-column">
      <p class='fw-bold'>Endereço de ${dadosPenitenciaria?.nome}</p>
  <p>${validarString(dadosPenitenciaria?.logradouro, ', ')} ${validarString(
          dadosPenitenciaria?.numero,
          ', '
        )}${validarString(dadosPenitenciaria.bairro, ', ')}
  <br/>
  ${validarString(dadosPenitenciaria.referencia, ', ') + '<br/>' || ''}
  ${validarString(dadosPenitenciaria?.cidade, ' - ')}${validarString(
          dadosPenitenciaria?.estado
        )}
  <br/>
  CEP ${dadosPenitenciaria.cep}
  <br/>
    ${dadosPenitenciaria.telefone || ''}
  </p>
</div>
    `
      : 'Endereço indisponível.'
    parent.appendChild(div)

parent.appendChild(hr)


    // Container específico dos subcats
const subcatContainer = document.createElement('div')
parent.appendChild(subcatContainer)


    // Select ordenação
    createOrderSelect(selectContainer, 'asc', selectedOrder => {
      const sorted = Object.entries(subcats).sort(([a], [b]) =>
        selectedOrder === 'asc' ? a.localeCompare(b) : b.localeCompare(a)
      )
      renderSubcategories(
        subcatContainer,
        Object.fromEntries(sorted),
        handleAddToCart,
        handleRemoveOne,
        dadosCarrinho
      )
    })

    // Render inicial (ordem A–Z)
    const initialSorted = Object.entries(subcats).sort(([a], [b]) =>
      a.localeCompare(b)
    )
    renderSubcategories(
      subcatContainer,
      Object.fromEntries(initialSorted),
      handleAddToCart,
      handleRemoveOne,
      dadosCarrinho
    )

    const hrBottom = document.createElement('hr')
    parent.appendChild(hrBottom)
  })
}
