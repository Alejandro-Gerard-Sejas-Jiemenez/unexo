const almacen = new Map();
const MAX_ENTRADAS = 120;
const TTL_DEFECTO_MS = 60_000;

export function claveCache(ruta, params = '') {
    if (!params) return ruta;
    if (typeof params === 'string') {
        const clean = new URLSearchParams(params);
        clean.sort();
        const qs = clean.toString();
        return qs ? `${ruta}?${qs}` : ruta;
    }
    const clean = new URLSearchParams();
    Object.keys(params)
        .sort()
        .forEach((k) => {
            const v = params[k];
            if (v !== undefined && v !== null && v !== '') {
                clean.set(k, String(v));
            }
        });
    const qs = clean.toString();
    return qs ? `${ruta}?${qs}` : ruta;
}

export function leerCache(clave, ttlMs = TTL_DEFECTO_MS) {
    const entrada = almacen.get(clave);
    if (!entrada) return null;

    if (Date.now() - entrada.marca > ttlMs) {
        almacen.delete(clave);
        return null;
    }

    return entrada.datos;
}

export function guardarCache(clave, datos) {
    if (almacen.has(clave)) {
        almacen.delete(clave);
    } else if (almacen.size >= MAX_ENTRADAS) {
        const primera = almacen.keys().next().value;
        almacen.delete(primera);
    }

    almacen.set(clave, { datos, marca: Date.now() });
}

export function limpiarCache() {
    almacen.clear();
}
