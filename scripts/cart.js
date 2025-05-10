import { getItem } from "../functions/localStorage.js";
import { appendCartData, appendCartTotal, getTotalOrderAmount } from "../functions/appendCartData.js";
import { notify } from "../components/notify.js";
import { shippingForm } from "../functions/shippingForm.js";
import { getCoupon } from "../functions/getCoupon.js";


const display = document.getElementById("display");
const totalAmount = document.getElementById("totalAmount");
const notifyDiv1 = document.getElementById("notifyDiv1");
const notifyDiv2 = document.getElementById("notifyDiv2");
const form = document.getElementById("form");
const couponInput = document.getElementById("couponInput");
const applyCoupon = document.getElementById("applyCoupon");

notifyDiv1.innerHTML = notify('danger', 'Produto removido do carrinho!');

let cartData = getItem("cartData") || [];
console.log("cartData on cart ==> ", cartData);

getTotalOrderAmount(cartData, totalAmount);

const cartTotal = getItem("cartTotal");

appendCartData(cartData, display, totalAmount);

appendCartTotal(cartTotal, totalAmount);

applyCoupon.addEventListener("click", () => {
  const discountPercent = getCoupon(couponInput.value);
  if (discountPercent) {
    alert(`Coupon applied successfully, you got ${discountPercent}% discount`);
    getTotalOrderAmount(cartData, totalAmount, discountPercent);
  } else {
    alert(`Invalid coupon code`);
  }
});

form.addEventListener("submit", (e) => {
  e.preventDefault();
  /* 
  if (!getItem("token")) {
    alert("Please login first");
    window.location.href = "login.html";
    return;
  } */

  // NOVA LÓGICA: verifica o peso total do carrinho
  const totalWeight = cartData.reduce(
    (acc, curr) => acc + (curr.weight || 0),
    0
  );
  console.log("totalWeight ==> ", totalWeight);
  if (totalWeight > 12) {
    alert(
      `Peso total do pedido (${totalWeight.toFixed(
        2
      )}kg) excede o limite de 12kg.`
    );
    return;
  }

  const user = shippingForm(form);
  notifyDiv2.innerHTML = notify("info", user.isFilled().message, "liveToast2");
  /*   const toastLiveExample = document.getElementById("liveToast2");
  const toast = new bootstrap.Toast(toastLiveExample);
  toast.show(); */

  if (user.isFilled().status) {
    const cartItemsToSend = cartData.map((item) => ({
      id: item.id,
      qty: item.qty,
    }));

    console.log("Dados enviados ao backend:", cartItemsToSend);
    console.log("user shipping data ==> ", user);
    setTimeout(() => {
      // window.location.href = "orderPlaced.html";
      window.alert("Order placed successfully!");
      // Aqui você pode adicionar a lógica para redirecionar o usuário ou mostrar uma mensagem de sucesso
    }, 2000);
  }
});

