// Orquestador de navegación asíncrona en tiempo real
import { iniciarCarga, terminarCarga, configurarCancelacionLoader } from './loader';
import { actualizarDOM } from './dom';

export function iniciarNavegacion() {
    configurarCancelacionLoader();

    // Petición asíncrona coordinada con el loader
    const cargarPagina = async (url, actualizarHistorial = true) => {
        iniciarCarga();
        try {
            const respuesta = await fetch(url, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });

            // Si es archivo o no-HTML, delegamos al navegador
            const contentType = respuesta.headers.get('content-type') || '';
            if (!contentType.includes('text/html')) {
                terminarCarga();
                location.href = url;
                return;
            }

            const html = await respuesta.text();
            const exito = actualizarDOM(html, url);

            if (!exito) {
                location.href = url;
                return;
            }

            if (actualizarHistorial) {
                history.pushState({ url }, '', url);
            }
        } catch {
            location.href = url;
        } finally {
            terminarCarga();
        }
    };

    // Interceptar enlaces internos
    document.addEventListener('click', (e) => {
        const a = e.target.closest('a[href]');
        if (!a || e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        if (a.target === '_blank' || a.hasAttribute('download')) return;

        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin) return;
        if (url.pathname === location.pathname && url.search === location.search) return;
        if (url.pathname.includes('/pdf') || url.pathname.includes('/excel')) return;

        e.preventDefault();
        cargarPagina(a.href);
    });

    // Soporte para botón atrás/adelante
    window.addEventListener('popstate', (e) => {
        const urlDestino = e.state?.url || location.href;
        cargarPagina(urlDestino, false);
    });

    window.addEventListener('pageshow', terminarCarga);
}
