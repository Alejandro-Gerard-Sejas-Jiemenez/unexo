/**
 * Prefijo bajo el que está montada la aplicación.
 *
 * En local es cadena vacía; en el servidor de la FICCT es algo como
 * "/inf513/grupo06sa/proyecto2". Lo resuelve el servidor en spa.blade.php a
 * partir de APP_URL, así que aquí nunca se adivina ni se escribe a mano.
 */
export const BASE =
    document.querySelector('meta[name="base-path"]')?.content?.replace(/\/$/, '') ?? '';

/** Antepone el prefijo a una ruta absoluta del servidor. */
export function conBase(ruta) {
    return `${BASE}${ruta}`;
}
