export function validarClienteForm() {
    const campos = [
        "nomeVisitante",
        "emailVisitante",
        "telefoneVisitante",
        "numeroCarteirinha",
        "ruaVisitante",
        "numeroVisitante",
        "cidadeVisitante",
        "estadoVisitante",
        "cepVisitante",
        "nomeDetento",
        "matriculaDetento",
        "raioDetento",
        "celaDetento"
    ];

    let valido = true;

    campos.forEach(id => {
        const campo = document.getElementById(id);
        // Considera vazio se não existe ou se valor em branco
        if (!campo || !campo.value.trim()) {
            campo?.classList.add('is-invalid');
            valido = false;
        } else {
            campo.classList.remove('is-invalid');
        }
    });

    return valido;
}

console.log(validarClienteForm())

