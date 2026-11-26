export function formatoFecha(iso) {
    if (!iso || typeof iso !== 'string') return '—';
    const [anio, mes, dia] = iso.slice(0, 10).split('-');
    if (!anio || !mes || !dia) return iso;
    return `${dia}/${mes}/${anio}`;
}

export function semanasEntre(inicio, fin) {
    if (!inicio || !fin) return null;
    const ms = new Date(`${fin}T00:00:00`) - new Date(`${inicio}T00:00:00`);
    if (Number.isNaN(ms) || ms < 0) return null;
    return Math.max(1, Math.round(ms / (7 * 24 * 60 * 60 * 1000)));
}

export function gradienteDeCodigo(codigo = '') {
    let hash = 0;
    for (let i = 0; i < codigo.length; i++) {
        hash = (hash * 31 + codigo.charCodeAt(i)) | 0;
    }
    return `grad-card-${Math.abs(hash) % 6}`;
}

export function gradienteNeonAleatorio() {
    return `grad-card-${Math.floor(Math.random() * 6)}`;
}
