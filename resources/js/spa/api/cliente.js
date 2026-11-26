import axios from 'axios';
import { conBase } from '../base';
import { limpiarCache } from './cache';

/**
 * Punto único de salida hacia la API. Todo lo que la SPA pida al servidor pasa
 * por aquí: así la cookie CSRF, el manejo de 401 y la traducción de los errores
 * de validación se resuelven en un solo lugar.
 */
const cliente = axios.create({
    baseURL: conBase('/api/panel'),
    withCredentials: true,
    withXSRFToken: true,
    headers: { Accept: 'application/json' },
});

/** Laravel exige la cookie XSRF antes de cualquier petición que escriba. */
export async function prepararCsrf() {
    await axios.get(conBase('/sanctum/csrf-cookie'), { withCredentials: true });
}

/**
 * Error de validación del servidor (422) con los mensajes en español,
 * ya agrupados por campo para pintarlos junto a cada input.
 */
export class ErrorDeValidacion extends Error {
    constructor(mensaje, errores) {
        super(mensaje);
        this.name = 'ErrorDeValidacion';
        this.errores = errores;
    }
}

/** Se dispara cuando la sesión caducó, para que la app vuelva al login. */
export const SESION_CADUCADA = 'sesion-caducada';

cliente.interceptors.response.use(
    (respuesta) => {
        const metodo = respuesta.config?.method?.toLowerCase();
        const url = String(respuesta.config?.url ?? '');
        if (metodo && metodo !== 'get' && !url.endsWith('/visitas')) {
            limpiarCache();
        }
        return respuesta;
    },
    async (error) => {
        const estado = error.response?.status;
        let payload = error.response?.data;

        if (payload instanceof Blob && payload.type?.includes('json')) {
            try {
                payload = JSON.parse(await payload.text());
            } catch {
                payload = null;
            }
        }

        if (estado === 422) {
            const datos = payload ?? {};
            return Promise.reject(
                new ErrorDeValidacion(
                    datos.message ?? 'Revisa los datos del formulario.',
                    datos.errors ?? {},
                ),
            );
        }

        if (estado === 401) {
            window.dispatchEvent(new CustomEvent(SESION_CADUCADA));
            return Promise.reject(new Error('Tu sesión expiró. Vuelve a iniciar sesión.'));
        }

        if (estado === 403) {
            return Promise.reject(
                new Error(payload?.message ?? 'No tienes permiso para esta acción.'),
            );
        }

        if (estado === 419) {
            return Promise.reject(
                new Error('La página estuvo inactiva demasiado tiempo. Recárgala e inténtalo de nuevo.'),
            );
        }

        return Promise.reject(
            new Error(
                payload?.message ??
                    'No pudimos conectarnos con el servidor. Revisa tu conexión e inténtalo de nuevo.',
            ),
        );
    },
);

export default cliente;
