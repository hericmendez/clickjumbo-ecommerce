export const footer = () => {
  return `
  <footer id="footer" class="py-4 mt-5 text-white"
    style="
      /* overlay escurecedor + gradiente invertido da navbar */
      background-image:
        linear-gradient(0deg, rgba(0,0,0,.18), rgba(0,0,0,.18)),
        linear-gradient(85deg, rgba(0,0,0,.24) 0%, rgba(110,130,207,1) 40%, rgba(0,50,154,1) 100%);
      background-blend-mode: multiply, normal;
    ">
    <style>
      #footer .social-btn{
        width:44px;height:44px;
        display:inline-flex;align-items:center;justify-content:center;
        border-radius:50%;
        transition:transform .2s ease, color .2s ease, border-color .2s ease, background-color .2s ease;
        font-size:1.1rem;
        border:1px solid rgba(255,255,255,.6);
        color:#fff;
      }
      #footer .social-btn:hover{ transform:translateY(-2px) scale(1.05); border-color:#fff; }
      #footer .facebook:hover  { color:#1877F2; background:rgba(24,119,242,.08); }
      #footer .instagram:hover { color:#E4405F; background:rgba(228,64,95,.08); }
      #footer .whatsapp:hover  { color:#25D366; background:rgba(37,211,102,.08); }
      #footer p { margin-bottom:.75rem; }
      #footer { border-top:1px solid rgba(0,0,0,.25); }
    </style>

    <p class="text-center mb-3">© 2025 ClickJumbo. Todos os direitos reservados.</p>

    <div class="d-flex justify-content-center gap-2">
      <a href="#" target="_blank" rel="noopener" class="social-btn facebook" aria-label="Facebook">
        <i class="fab fa-facebook-f"></i>
      </a>
      <a href="#" target="_blank" rel="noopener" class="social-btn instagram" aria-label="Instagram">
        <i class="fab fa-instagram"></i>
      </a>
      <a href="#" target="_blank" rel="noopener" class="social-btn whatsapp" aria-label="WhatsApp">
        <i class="fab fa-whatsapp"></i>
      </a>
    </div>
  </footer>`;
};
