export const navigationBar = (token, name) => {
  return `<nav style="background-color: #003399;" class="navbar navbar-expand-lg ">
            <div class="container-fluid" id="navItems">
                <a style="color:#ffc200;" class="navbar-brand fs-2 fw-bold d-flex justify-content-center align-items-center" href="index.html"> 
                    <img src="assets/logo_transparent.png" alt="Logo" width="50" height="50" class="d-inline-block align-text-top" />ClickJumbo
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div   class="navbar-nav fs-5">
                        <a style="    color: #ffcc2a; font-weight: bold;"  class="nav-link" href="index.html">Produtos</a>
                        <a style="    color: #ffcc2a; font-weight: bold;" class="nav-link" href="cart.html">Carrinho</a> 
                        <a style="    color: #ffcc2a; font-weight: bold;" style="display:${
                          !token ? "block" : "none"
                        }" class="nav-link" href="../login.html">Login</a>
                        <li style="display:${
                          token ? "block" : "none"
                        }" class="nav-item dropdown">
                        <a style="    color: #ffcc2a; font-weight: bold;" class="nav-link dropdown-toggle" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            ${name}
                        </a>
                      
                        </li>
                    </div>
                </div>
            </div>
        </nav>`;
};
