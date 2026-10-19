// Control del menú lateral: expandir/fijar en escritorio y cajón en móvil
export function iniciarSidebar() {
    const sidebar = document.getElementById('sidebar-menu');
    const fondo = document.getElementById('sidebar-fondo');
    const abrir = document.getElementById('abrir-sidebar');
    const cerrar = document.getElementById('cerrar-sidebar');
    const toggle = document.getElementById('toggle-sidebar');
    if (!sidebar) return;

    const escritorio = window.matchMedia('(min-width: 48rem)');

    // Activa las transiciones solo después del primer pintado.
    requestAnimationFrame(() => requestAnimationFrame(() => sidebar.classList.remove('sin-animacion')));

    // Escritorio: fijar / liberar y recordar el estado.
    const fijar = (e) => {
        if (e) e.preventDefault();
        sidebar.classList.toggle('is-pinned');
        try { localStorage.setItem('sidebar-pinned', sidebar.classList.contains('is-pinned')); } catch (err) { }
    };
    toggle?.addEventListener('click', (e) => { if (escritorio.matches) fijar(e); });

    // Escritorio colapsado: al hacer clic en un submenú, expandir primero a 18rem
    sidebar.querySelectorAll('details.sb-grupo summary').forEach((sum) => {
        sum.addEventListener('click', () => {
            if (escritorio.matches && !sidebar.classList.contains('is-pinned')) {
                sidebar.classList.add('is-pinned');
                try { localStorage.setItem('sidebar-pinned', 'true'); } catch (err) {}
            }
        });
    });

    // Móvil: cajón con fondo.
    const mostrar = (v) => {
        sidebar.classList.toggle('abierto', v);
        fondo?.classList.toggle('abierto', v);
        abrir?.setAttribute('aria-expanded', v);
        if (v) cerrar?.focus(); else if (!escritorio.matches) abrir?.focus();
    };
    abrir?.addEventListener('click', () => mostrar(true));
    cerrar?.addEventListener('click', () => mostrar(false));
    fondo?.addEventListener('click', () => mostrar(false));
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && sidebar.classList.contains('abierto')) mostrar(false); });
    sidebar.querySelectorAll('nav a').forEach((a) => a.addEventListener('click', () => sidebar.classList.remove('abierto')));
    escritorio.addEventListener('change', () => { sidebar.classList.remove('abierto'); fondo?.classList.remove('abierto'); });
}
