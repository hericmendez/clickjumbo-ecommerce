export const navigationBar = (token, name) => {
    return `<nav style="background-color: #003399;" class="navbar navbar-expand-lg ">
            <div class="container-fluid" id="navItems">
                <a style="color:#ffc200" class="navbar-brand" href="index.html">ClickJumbo</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavAltMarkup">
                    <div   class="navbar-nav">
                        <a style="    color: #ffcc2a; font-weight: bold;"  class="nav-link" href="index.html">Penitenciárias</a>
                        <a style="    color: #ffcc2a; font-weight: bold;" class="nav-link" href="html/cart.html">Carrinho</a> 
                        <a style="    color: #ffcc2a; font-weight: bold;" style="display:${
                          !token ? "block" : "none"
                        }" class="nav-link" href="../html/login.html">Login</a>
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

