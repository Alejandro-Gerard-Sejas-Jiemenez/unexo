// Módulo para actualizar el DOM con el nuevo contenido HTML recibido por fetch
export function actualizarDOM(html, urlDestino) {
    const doc = new DOMParser().parseFromString(html, 'text/html');

    // Actualizar <title>
    const nuevoTitulo = doc.querySelector('title');
    if (nuevoTitulo) document.title = nuevoTitulo.innerText;

    // Actualizar <h1> en el header
    const tituloHeader = document.querySelector('header h1');
    const nuevoTituloHeader = doc.querySelector('header h1');
    if (tituloHeader && nuevoTituloHeader) {
        tituloHeader.innerHTML = nuevoTituloHeader.innerHTML;
    }

    // Actualizar contenedor principal #contenido
    const contenidoActual = document.getElementById('contenido');
    const nuevoContenido = doc.getElementById('contenido');

    if (!contenidoActual || !nuevoContenido) {
        return false;
    }

    contenidoActual.innerHTML = nuevoContenido.innerHTML;
    contenidoActual.scrollTop = 0;
    reejecutarScripts(contenidoActual);
    actualizarMenuActivo(urlDestino);

    return true;
}

function reejecutarScripts(contenedor) {
    contenedor.querySelectorAll('script').forEach((viejoScript) => {
        const nuevoScript = document.createElement('script');
        Array.from(viejoScript.attributes).forEach((attr) => {
            nuevoScript.setAttribute(attr.name, attr.value);
        });
        nuevoScript.appendChild(document.createTextNode(viejoScript.innerHTML));
        viejoScript.parentNode.replaceChild(nuevoScript, viejoScript);
    });
}

function actualizarMenuActivo(urlDestino) {
    const rutaDestino = new URL(urlDestino, location.href).pathname;
    document.querySelectorAll('.sidebar a[href]').forEach((enlace) => {
        const rutaEnlace = new URL(enlace.href, location.href).pathname;
        const esActivo = (rutaEnlace === rutaDestino);

        if (enlace.classList.contains('sb-sub-fila')) {
            enlace.classList.toggle('activo', esActivo);
            if (esActivo) enlace.setAttribute('aria-current', 'page');
            else enlace.removeAttribute('aria-current');
        } else if (enlace.classList.contains('sb-fila')) {
            const icon = enlace.querySelector('.sb-icono svg');
            if (esActivo) {
                enlace.classList.add('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-600/20');
                enlace.classList.remove('text-slate-600', 'hover:bg-slate-100', 'hover:text-slate-900');
                enlace.setAttribute('aria-current', 'page');
                icon?.classList.add('text-white');
                icon?.classList.remove('text-slate-400');
            } else {
                enlace.classList.remove('bg-indigo-600', 'text-white', 'shadow-md', 'shadow-indigo-600/20');
                enlace.classList.add('text-slate-600', 'hover:bg-slate-100', 'hover:text-slate-900');
                enlace.removeAttribute('aria-current');
                icon?.classList.remove('text-white');
                icon?.classList.add('text-slate-400');
            }
        }
    });
}
