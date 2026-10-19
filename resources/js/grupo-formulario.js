// Formulario de grupo: fechas según la edición y enlace/aula según la modalidad.
// Delegado en document por la misma razón que horarios.js. El servidor renderiza
// el estado inicial; aquí solo se reacciona a los cambios del usuario.

function mostrar(bloque, visible, visibleDisplay) {
    bloque.style.display = visible ? visibleDisplay : 'none';
    // Un campo deshabilitado no viaja: el servidor no recibe lo que el usuario no ve.
    bloque.querySelectorAll('input, select, textarea').forEach((campo) => {
        campo.disabled = !visible;
    });
}

function alternarFechas(selector) {
    const formulario = selector.closest('form');
    const bloque = formulario?.querySelector('[data-bloque-fechas]');
    if (bloque) mostrar(bloque, selector.value === '', 'grid');
}

function alternarModalidad(selector) {
    const formulario = selector.closest('form');
    const bloqueEnlace = formulario?.querySelector('#campo-virtual');
    const bloqueAula = formulario?.querySelector('#campo-presencial');
    if (!bloqueEnlace || !bloqueAula) return;

    const modalidad = selector.value;
    mostrar(bloqueEnlace, modalidad === 'virtual' || modalidad === 'mixta', 'block');
    mostrar(bloqueAula, modalidad === 'presencial' || modalidad === 'mixta', 'grid');
}

export function iniciarGrupoFormulario() {
    document.addEventListener('change', (e) => {
        if (e.target.matches('#cohorte_id')) alternarFechas(e.target);
        if (e.target.matches('form select[name="modalidad"]')) alternarModalidad(e.target);
    });
}
