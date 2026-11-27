const centavos = (monto) => Math.round(Number(monto) * 100);

/** Mismo reparto que el servidor: la última cuota absorbe el redondeo. */
export function dividirEnCuotas(total, cuotas) {
    const enCentavos = centavos(total);
    const n = Number(cuotas);

    if (!Number.isInteger(n) || n < 2 || n > 36 || !(enCentavos >= n)) return [];

    const base = Math.floor(enCentavos / n);

    return Array.from({ length: n }, (_, i) => (i === n - 1 ? enCentavos - base * (n - 1) : base) / 100);
}

/**
 * Total, pagado y pendiente de una matrícula. Sin cargos, el total es el precio del
 * programa; si tampoco lo hay, queda sin definir.
 */
export function resumirPagos(cargos, precio) {
    const vigentes = cargos.filter((c) => c.estado !== 'cancelada');
    const sumar = (lista) => lista.reduce((acc, c) => acc + centavos(c.monto), 0);

    const total = vigentes.length > 0 ? sumar(vigentes) : precio ? centavos(precio) : null;
    const pagado = sumar(vigentes.filter((c) => c.estado === 'aprobada'));

    const porPagar = vigentes
        .filter((c) => !['aprobada', 'reembolsada'].includes(c.estado))
        .sort((a, b) => (a.numero_cuota ?? 0) - (b.numero_cuota ?? 0));

    let situacion = 'sin_cargos';
    if (vigentes.length > 0) situacion = porPagar.length > 0 ? 'por_pagar' : 'pagado';

    return {
        total: total === null ? null : total / 100,
        pagado: pagado / 100,
        pendiente: total === null ? null : Math.max(0, total - pagado) / 100,
        proximo: porPagar[0] ?? null,
        situacion,
    };
}
