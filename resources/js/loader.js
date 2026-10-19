// Módulo para controlar la interfaz del loader y barra de progreso
let temporizadorVelo = null;

export function iniciarCarga() {
    const velo = document.getElementById('cargando');
    const barra = document.getElementById('cargando-barra');
    if (!barra) return;

    barra.classList.remove('activa');
    void barra.offsetWidth; // Forzar reflujo para reiniciar la animación CSS
    barra.classList.add('activa');

    clearTimeout(temporizadorVelo);
    if (velo) {
        temporizadorVelo = setTimeout(() => {
            velo.classList.add('activo');
            velo.setAttribute('aria-hidden', 'false');
        }, 300);
    }
}

export function terminarCarga() {
    const velo = document.getElementById('cargando');
    const barra = document.getElementById('cargando-barra');

    clearTimeout(temporizadorVelo);
    if (velo) {
        velo.classList.remove('activo');
        velo.setAttribute('aria-hidden', 'true');
    }
    if (barra) {
        barra.classList.remove('activa');
    }
}

export function configurarCancelacionLoader() {
    const velo = document.getElementById('cargando');
    velo?.addEventListener('click', terminarCarga);
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') terminarCarga();
    });
}
