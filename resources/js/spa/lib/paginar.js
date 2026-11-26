/** Mismo tamaño que `limites.por_pagina` del servidor. */
export const POR_PAGINA = 10;

/** Opciones del selector; igual que `limites.tamanos_pagina` del servidor. */
export const TAMANOS = [5, 10, 25, 50];

/** Con muy pocos registros no hay nada que paginar ni que elegir. */
export function mostrarControl(meta) {
    return Boolean(meta) && meta.total > TAMANOS[0];
}

/** Corta una lista ya cargada. `meta` tiene la forma que espera el componente Paginacion. */
export function paginar(filas, pagina, porPagina = POR_PAGINA) {
    const total = filas.length;
    const ultima = Math.max(1, Math.ceil(total / porPagina));
    const actual = Math.min(Math.max(1, pagina), ultima);
    const inicio = (actual - 1) * porPagina;
    const visibles = filas.slice(inicio, inicio + porPagina);

    return {
        visibles,
        meta: {
            current_page: actual,
            last_page: ultima,
            per_page: porPagina,
            total,
            from: total === 0 ? 0 : inicio + 1,
            to: inicio + visibles.length,
        },
    };
}
