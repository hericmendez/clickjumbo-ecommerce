// scripts/main-cart.js
import { cartMenu, initCartMenu } from "../components/cartMenu.js";

const cartMenuDiv = document.getElementById("cartMenuDiv");
cartMenuDiv.innerHTML = cartMenu();
initCartMenu(); 


import { clientForm } from "../components/clientForm.js";

const clientFormDiv = document.getElementById("clientFormDiv");
clientFormDiv.innerHTML = clientForm();



import { envioForm } from "../components/envioForm.js";
const envioFormDiv = document.getElementById("envioFormDiv");

envioFormDiv.innerHTML = envioForm();


import { pagamentoForm } from "../components/pagamentoForm.js";
const pagamentoFormDiv = document.getElementById("pagamentoFormDiv");

pagamentoFormDiv.innerHTML = pagamentoForm();