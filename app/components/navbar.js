export const navigationBar = (token, name) => {
  return `
    <style>
      :root{
        --cj-drawer-w: 320px;
      }

      /* Hover da navbar (desktop) */
      nav a.nav-link:hover {
        color: #fdcc2c !important;
        text-decoration: underline;
        transform: scale(1.05);
        transition: transform .2s, color .2s;
      }
      nav a.nav-icon:hover, nav i:hover {
        color: #fdcc2c !important;
        transform: scale(1.1);
        transition: transform .2s, color .2s;
      }

      /* Toggler + melhor contraste no mobile */
      .navbar-dark .navbar-toggler { border-color: rgba(255,255,255,.25); }
      .navbar-toggler:active { transform: scale(.95); }
      .navbar-toggler.cj-active { transform: rotate(90deg); transition: transform .25s ease; }

      /* BACKDROP com blur */
      .offcanvas-backdrop.show {
        opacity: .35;
        backdrop-filter: blur(2px);
      }

      /* DRAWER estilizado */
      .offcanvas.cj-drawer {
        width: var(--cj-drawer-w);
        background: linear-gradient(200deg, rgba(8,18,66,1) 0%, rgba(10,31,95,1) 60%, rgba(0,50,154,1) 100%);
        color: #fff;
        border-right: 1px solid rgba(255,255,255,.08);
        box-shadow: 0 0 40px rgba(0,0,0,.25);
      }
      .cj-drawer .offcanvas-header {
        padding-top: calc(env(safe-area-inset-top, 0) + .75rem);
        background: linear-gradient(265deg,rgba(211,204,255,1) 0%, rgba(110,130,207,1) 40%, rgba(0,50,154,1) 100%);
      }
      .cj-drawer .list-group-item {
        background: transparent;
        color: rgba(255,255,255,.95);
        border: 0;
        border-bottom: 1px solid rgba(255,255,255,.08);
        padding: .9rem 1rem;
        display: flex; align-items: center; gap: .75rem;
        transition: transform .15s ease, background-color .15s ease, color .15s ease;
      }
      .cj-drawer .list-group-item i { opacity: .9; }
      .cj-drawer .list-group-item:hover {
        transform: translateX(6px);
        background-color: rgba(255,255,255,.06);
      }
      .cj-drawer .list-title {
        font-size: .8rem;
        letter-spacing: .06em;
        opacity: .7;
        padding: .75rem 1rem .25rem;
        text-transform: uppercase;
      }
      .cj-drawer .list-group-item.active {
        background: rgba(253,204,44,.15);
        color: #fdcc2c;
        border-bottom-color: rgba(253,204,44,.25);
      }

      /* EFEITO PUSH: empurra navbar + conteúdo quando drawer abre */
      .cj-push-target { transition: transform .35s cubic-bezier(.2,.8,.2,1), filter .35s; will-change: transform; }
      body.cj-shifted .navbar { transform: translateX(var(--cj-drawer-w)); }
      body.cj-shifted .cj-push-target { transform: translateX(var(--cj-drawer-w)); filter: saturate(.98) brightness(.98); }

      @media (prefers-reduced-motion: reduce) {
        .cj-push-target, .navbar, .navbar-toggler { transition: none !important; }
      }
    </style>

    <nav class="navbar navbar-expand-lg navbar-dark"
     style="  background-image:
                                      linear-gradient(0deg, rgba(0,0,0,.18), rgba(0,0,0,.18)),
                                      linear-gradient(265deg, rgba(211,204,255,1) 0%, rgba(110,130,207,1) 40%, rgb(0, 81, 255) 100%);
                                    background-blend-mode: multiply, normal; ">
      <div class="container-fluid">

        <!-- Logo -->
        <a class="navbar-brand fs-4 d-flex align-items-center" href="index.html">
          <img src="assets/logo_transparent.png" alt="Logo" width="50" height="50" class="d-inline-block align-text-top" /><span class='ms-2'>ClickJumbo</span>

        </a>

        <!-- HAMBURGER (só mobile) -->
        <button id="cjHamburger" class="navbar-toggler d-lg-none" type="button"
          data-bs-toggle="offcanvas" data-bs-target="#cjMobileMenu"
          aria-controls="cjMobileMenu" aria-label="Abrir menu">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- NAV DESKTOP (>= lg) -->
        <div class="collapse navbar-collapse justify-content-between d-none d-lg-flex" id="navbarContent">
          <!-- Left Links -->
          <div class="navbar-nav fs-5">
            <a class="nav-link text-white" href="/app/index.html">Produtos</a>
            <a class="nav-link text-white" href="/app/comingSoon.html">Sobre</a>
            <a class="nav-link text-white" href="/app/comingSoon.html">Blog</a>
            <a class="nav-link text-white" href="/app/comingSoon.html">Contato</a>
          </div>

          <!-- Right Icons -->
          <div class="navbar-nav align-items-center fs-5">
            <a class="nav-icon nav-link" style="color:#003399;" href="wizard.html" title="Carrinho">
              <i class="fas fa-shopping-cart"></i>
            </a>

            <a class="nav-icon nav-link" style="color:#003399; display: ${!token ? "block" : "none"};"
              href="/app/login.html" title="Entrar">
              <i class="fas fa-sign-in-alt"></i>
            </a>

            <li class="nav-item dropdown" style="display: ${token ? "block" : "none"};">
              <a class="nav-link dropdown-toggle" style="color:#003399; font-weight: bold;"
                 href="#" id="navbarDropdownMenuLink" role="button"
                 data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user"></i> ${name}
              </a>
              <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                <li><a class="dropdown-item" href="/app/orders.html">Minhas Compras</a></li>
                <li><a class="dropdown-item logoutBtn" id="logoutBtn" href="#">Sair</a></li>
              </ul>
            </li>
          </div>
        </div>
      </div>
    </nav>

    <!-- DRAWER MOBILE (offcanvas) -->
    <div class="offcanvas offcanvas-start cj-drawer d-lg-none" tabindex="-1" id="cjMobileMenu" aria-labelledby="cjMobileMenuLabel">
      <div class="offcanvas-header">
        <h5 class="offcanvas-title text-white d-flex align-items-center" id="cjMobileMenuLabel">
          <img src="assets/logo_transparent.png" alt="Logo" width="36" height="36" class="me-2" />
          ClickJumbo
        </h5>
        <button type="button" class="btn-close btn-close-white"  aria-label="Fechar"></button>
      </div>

      <div class="offcanvas-body p-0 d-flex flex-column">
        <div class="list-title">Navegação</div>
        <nav class="list-group list-group-flush">
          <a href="/app/index.html" class="list-group-item list-group-item-action" >
            <i class="fas fa-store"></i> Produtos
          </a>
          <a href="/app/comingSoon.html" class="list-group-item list-group-item-action" >
            <i class="fas fa-info-circle"></i> Sobre
          </a>
          <a href="/app/comingSoon.html" class="list-group-item list-group-item-action" >
            <i class="fas fa-blog"></i> Blog
          </a>
          <a href="/app/comingSoon.html" class="list-group-item list-group-item-action" >
            <i class="fas fa-envelope"></i> Contato
          </a>
        </nav>

        <div class="list-title">Conta</div>
        <div class="px-3 pb-3">
          <a href="/app/wizard.html" class="btn btn-outline-light w-100 mb-2" >
            <i class="fas fa-shopping-cart me-2"></i>Carrinho
          </a>

          ${!token ? `
            <a href="/app/login.html" class="btn btn-warning w-100" >
              <i class="fas fa-sign-in-alt me-2"></i>Entrar
            </a>
          ` : `
            <div class="dropdown w-100">
              <button class="btn btn-warning dropdown-toggle w-100" type="button"
                      data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user me-2"></i>${name}
              </button>
              <ul class="dropdown-menu dropdown-menu-end w-100 mt-1">
                <li><a class="dropdown-item" href="/app/orders.html" >Minhas Compras</a></li>
                <li><a class="dropdown-item logoutBtn" id="logoutBtn" href="#" >Sair</a></li>
              </ul>
            </div>
          `}
        </div>

;<div class='mt-auto small text-center text-white-50 py-2'>
  © ${new Date().getFullYear()} ClickJumbo
</div>

      </div>
    </div>

    <script>
      (function() {
        // 1) detectar/definir alvo do "push"
        const candidates = ['#cjContent','main','.page-wrapper','#app','#root','.container-fluid','.container'];
        let pushTarget = null;
        for (const sel of candidates) {
          const el = document.querySelector(sel);
          if (el) { pushTarget = el; el.classList.add('cj-push-target'); break; }
        }
        if (!pushTarget) {
          // fallback pro body (menos ideal, mas funciona)
          document.body.classList.add('cj-push-target');
        }

        // 2) refs
        const ocEl = document.getElementById('cjMobileMenu');
        const toggler = document.getElementById('cjHamburger');

        // 3) eventos de abrir/fechar pra aplicar classe no body (efeito push)
        ocEl.addEventListener('show.bs.offcanvas', () => {
          if (window.innerWidth < 992) {
            document.body.classList.add('cj-shifted');
            toggler?.classList.add('cj-active');
          }
        });
        ocEl.addEventListener('hidden.bs.offcanvas', () => {
          document.body.classList.remove('cj-shifted');
          toggler?.classList.remove('cj-active');
        });

        // 4) fechar ao clicar nos links (data-bs-dismiss já cobre, mas garantimos)
        ocEl.querySelectorAll('a[]').forEach(a => {
          a.addEventListener('click', () => {
            const inst = bootstrap.Offcanvas.getInstance(ocEl) || new bootstrap.Offcanvas(ocEl);
            inst.hide();
          });
        });

        // 5) destacar link ativo (desktop + mobile)
        const path = location.pathname.replace(/\\/+/g,'/').split('/').pop() || 'index.html';
        const allLinks = document.querySelectorAll('a[href]');
        allLinks.forEach(a => {
          const href = a.getAttribute('href');
          if (!href) return;
          const file = href.split('/').pop();
          if (file === path) {
            a.classList.add('active');
          }
        });

        // 6) acessibilidade: fecha com ESC (bootstrap já faz), aqui só garantimos foco no primeiro link do drawer
        ocEl.addEventListener('shown.bs.offcanvas', () => {
          const first = ocEl.querySelector('.list-group-item, .btn');
          first && first.focus();
        });

        // 7) safe guard se o usuário rotacionar a tela com o menu aberto
        window.addEventListener('resize', () => {
          if (window.innerWidth >= 992) {
            // se voltar pro desktop, limpa shift
            document.body.classList.remove('cj-shifted');
          }
        });
      })();
    </script>
  `;
};
