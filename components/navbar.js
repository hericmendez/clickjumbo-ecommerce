export const navigationBar = (token, name) => {
  return `
    <style>
      nav a.nav-link:hover {
        color: #ffcc2a !important;
        text-decoration: underline;
          transform: scale(1.1);
                  transition: transform 0.2s;

      }
      nav a.nav-icon:hover, nav i:hover {
        color: #ffcc2a !important;
        transform: scale(1.1);
        transition: transform 0.2s;
      }
    </style>

    <nav class="navbar navbar-expand-lg" style="background-color: #003399;">
      <div class="container-fluid">
        
        <!-- Logo -->
        <a style="color:#ffc200;" class="navbar-brand fs-2 fw-bold d-flex align-items-center" href="index.html"> 
          <img src="assets/logo_transparent.png" alt="Logo" width="50" height="50" class="d-inline-block align-text-top" />
          ClickJumbo
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
          aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Collapsible Menu -->
        <div class="collapse navbar-collapse justify-content-between" id g="navbarContent">
          
          <!-- Left Links -->
          <div class="navbar-nav     fs-4">
            <a style="color: #fff; font-weight: bold;" class="nav-link" href="index.html">Produtos</a>
            <a style="color: #fff; font-weight: bold;" class="nav-link" href="comingSoon.html">Sobre</a>
            <a style="color: #fff; font-weight: bold;" class="nav-link" href="comingSoon.html">Blog</a>
            <a style="color: #fff; font-weight: bold;" class="nav-link" href="comingSoon.html">Contato</a>
          </div>

          <!-- Right Icons -->
          <div class="navbar-nav align-items-center fs-5">
            <a style="color: #fff;" class="nav-icon nav-link" href="cart.html" title="Carrinho">
              <i class="fas fa-shopping-cart"></i>
            </a>
            <a style="color: #ffcc2a; display: ${
              !token ? "block" : "none"
            };" class="nav-icon nav-link " href="../login.html" title="Entrar">
              <i class="fas fa-sign-in-alt"></i>
            </a>
            <li style="display: ${
              token ? "block" : "none"
            };" class="nav-item dropdown">
              <a style="color: #ffcc2a; font-weight: bold;" class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button"
                data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user"></i> ${name}
              </a>
              <ul class="dropdown-menu" aria-labelledby="navbarDropdownMenuLink">
                <li><a class="dropdown-item" href="#">Minha Conta</a></li>
                <li><a class="dropdown-item" id="logoutBtn" href="#">Sair</a></li>
              </ul>
            </li>
          </div>

        </div>
      </div>
    </nav>
  `;
};
