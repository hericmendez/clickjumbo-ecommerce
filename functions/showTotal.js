export const showTotal = (arr, parent) => {

    parent.innerHTML = null;

    const total = document.createElement('h4');

    total.textContent = `Produtos [${arr.length}]`;

    parent.append(total);

};
