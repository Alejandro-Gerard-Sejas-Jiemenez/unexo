// Filas dinámicas de horarios del grupo. Delegado en document: el panel
// reemplaza #contenido por fetch y un DOMContentLoaded no volvería a ejecutarse.

const SELECTOR_BLOQUE = '[data-horarios]';

function maximo(bloque) {
    return parseInt(bloque.dataset.max, 10) || 14;
}

function filas(bloque) {
    return bloque.querySelectorAll('[data-horario-fila]');
}

function siguienteIndice(bloque) {
    const guardado = parseInt(bloque.dataset.siguienteIndice, 10);
    if (!Number.isNaN(guardado)) return guardado;

    const indices = [...filas(bloque)].map((fila) => parseInt(fila.dataset.indice, 10) || 0);
    return indices.length ? Math.max(...indices) + 1 : 0;
}

function anunciar(bloque, mensaje) {
    const region = bloque.querySelector('[data-horarios-anuncio]');
    if (region) region.textContent = mensaje;
}

function renumerar(bloque) {
    filas(bloque).forEach((fila, posicion) => {
        fila.querySelectorAll('[data-horario-numero]').forEach((el) => {
            el.textContent = posicion + 1;
        });
    });
}

function actualizarBotonAgregar(bloque) {
    const boton = bloque.querySelector('[data-horario-agregar]');
    if (boton) boton.disabled = filas(bloque).length >= maximo(bloque);
}

function agregar(bloque) {
    const plantilla = document.getElementById('plantilla-horario');
    const lista = bloque.querySelector('[data-horarios-lista]');
    if (!plantilla || !lista || filas(bloque).length >= maximo(bloque)) return;

    const indice = siguienteIndice(bloque);
    const numero = filas(bloque).length + 1;
    const html = plantilla.innerHTML
        .replaceAll('__INDICE__', indice)
        .replaceAll('__NUMERO__', numero);

    lista.insertAdjacentHTML('beforeend', html);
    bloque.dataset.siguienteIndice = indice + 1;

    renumerar(bloque);
    actualizarBotonAgregar(bloque);

    const nueva = lista.lastElementChild;
    nueva?.querySelector('select')?.focus();

    anunciar(bloque, filas(bloque).length >= maximo(bloque)
        ? `Horario ${numero} agregado. Llegaste al máximo de ${maximo(bloque)} horarios.`
        : `Horario ${numero} agregado.`);
}

function quitar(bloque, fila) {
    fila.remove();
    renumerar(bloque);
    actualizarBotonAgregar(bloque);
    anunciar(bloque, `Horario quitado. Quedan ${filas(bloque).length}.`);
    bloque.querySelector('[data-horario-agregar]')?.focus();
}

export function iniciarHorarios() {
    document.addEventListener('click', (e) => {
        const botonAgregar = e.target.closest('[data-horario-agregar]');
        if (botonAgregar) {
            const bloque = botonAgregar.closest(SELECTOR_BLOQUE);
            if (bloque) agregar(bloque);
            return;
        }

        const botonQuitar = e.target.closest('[data-horario-quitar]');
        if (botonQuitar) {
            const bloque = botonQuitar.closest(SELECTOR_BLOQUE);
            const fila = botonQuitar.closest('[data-horario-fila]');
            if (bloque && fila) quitar(bloque, fila);
        }
    });
}
