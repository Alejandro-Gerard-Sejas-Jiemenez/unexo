// Zona de portada del programa: arrastrar y soltar, botón de selección y vista previa.
// Delegado en document por la misma razón que horarios.js: la navegación del panel
// reemplaza #contenido y no vuelve a disparar DOMContentLoaded. El servidor repite la validación.

const TIPOS_VALIDOS = ['image/jpeg', 'image/png', 'image/webp'];
const MENSAJE_INVALIDA = 'La portada debe ser una imagen JPG, PNG o WebP de hasta 2 MB.';
const MAXIMO_KB_POR_DEFECTO = 2048;

function esValida(archivo, maximoKb) {
    return TIPOS_VALIDOS.includes(archivo.type) && archivo.size <= maximoKb * 1024;
}

function mostrarMensaje(zona, texto) {
    const estado = zona.querySelector('[data-portada-estado]');
    if (estado) estado.textContent = texto;
}

function liberarVistaPrevia(zona) {
    if (zona.portadaUrl) {
        URL.revokeObjectURL(zona.portadaUrl);
        zona.portadaUrl = null;
    }
}

// Sin portada actual la imagen queda oculta; con ella, se vuelve a mostrar.
function restaurarVistaPrevia(zona) {
    liberarVistaPrevia(zona);
    const vista = zona.querySelector('[data-portada-vista]');
    if (!vista) return;
    const actual = zona.dataset.portadaActual;
    if (actual) {
        vista.src = actual;
        vista.hidden = false;
    } else {
        vista.removeAttribute('src');
        vista.hidden = true;
    }
}

function mostrarVistaPrevia(zona, archivo) {
    const vista = zona.querySelector('[data-portada-vista]');
    if (!vista) return;
    liberarVistaPrevia(zona);
    zona.portadaUrl = URL.createObjectURL(archivo);
    vista.src = zona.portadaUrl;
    vista.hidden = false;
}

function rechazar(zona, input) {
    input.value = '';
    restaurarVistaPrevia(zona);
    mostrarMensaje(zona, MENSAJE_INVALIDA);
}

function aceptar(zona, archivo) {
    mostrarVistaPrevia(zona, archivo);
    mostrarMensaje(zona, 'Imagen lista para guardar.');
}

function procesar(zona, input, archivo) {
    const maximoKb = Number(zona.dataset.portadaMaxKb) || MAXIMO_KB_POR_DEFECTO;
    if (esValida(archivo, maximoKb)) aceptar(zona, archivo);
    else rechazar(zona, input);
}

function marcarArrastre(zona, activo) {
    zona.classList.toggle('portada-arrastrando', activo);
}

function soltar(zona, archivos) {
    const input = zona.querySelector('input[type="file"]');
    if (!input || archivos.length === 0) return;

    const archivo = archivos[0];
    if (!esValida(archivo, Number(zona.dataset.portadaMaxKb) || MAXIMO_KB_POR_DEFECTO)) {
        // Un archivo inválido no se asigna: se conserva lo que el input ya tenía.
        restaurarVistaPrevia(zona);
        mostrarMensaje(zona, MENSAJE_INVALIDA);
        return;
    }

    try {
        const transferencia = new DataTransfer();
        transferencia.items.add(archivo);
        input.files = transferencia.files;
    } catch {
        // Si el navegador no deja asignar el archivo, se avisa en lugar de mostrar una vista que no viajaría.
        mostrarMensaje(zona, 'No se pudo tomar la imagen soltada. Usa el botón «Elegir imagen».');
        return;
    }
    aceptar(zona, archivo);
}

export function iniciarPortada() {
    document.addEventListener('dragenter', (e) => {
        const zona = e.target.closest?.('[data-portada]');
        if (!zona) return;
        e.preventDefault();
        marcarArrastre(zona, true);
    });

    document.addEventListener('dragover', (e) => {
        const zona = e.target.closest?.('[data-portada]');
        if (!zona) return;
        e.preventDefault(); // necesario para que el navegador permita soltar aquí
        marcarArrastre(zona, true);
    });

    document.addEventListener('dragleave', (e) => {
        const zona = e.target.closest?.('[data-portada]');
        // relatedTarget dentro de la zona: el puntero pasó a un hijo, no salió.
        if (!zona || zona.contains(e.relatedTarget)) return;
        marcarArrastre(zona, false);
    });

    document.addEventListener('drop', (e) => {
        const zona = e.target.closest?.('[data-portada]');
        if (!zona) return;
        e.preventDefault();
        marcarArrastre(zona, false);
        soltar(zona, e.dataTransfer?.files ?? []);
    });

    document.addEventListener('change', (e) => {
        if (!e.target.matches('[data-portada] input[type="file"]')) return;
        const zona = e.target.closest('[data-portada]');
        const archivo = e.target.files[0];
        if (archivo) procesar(zona, e.target, archivo);
    });

    document.addEventListener('click', (e) => {
        const boton = e.target.closest?.('[data-portada-elegir]');
        if (!boton) return;
        boton.closest('[data-portada]')?.querySelector('input[type="file"]')?.click();
    });
}
