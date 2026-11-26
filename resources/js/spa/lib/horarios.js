/** Índice = día de la semana (1 = lunes). */
export const NOMBRES_DIA = ['', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];
export const DIAS_CORTOS = ['', 'Lu', 'Ma', 'Mi', 'Ju', 'Vi', 'Sá', 'Do'];

export const MAX_HORARIOS = 14;

/**
 * Suma un bloque (varios días, una misma hora) a la lista. Entra completo o no entra.
 * El servidor vuelve a validar al guardar.
 */
export function agregarBloque(actuales, bloque, maximo = MAX_HORARIOS) {
    const rechazar = (error) => ({ horarios: actuales, error });
    const { dias, hora_inicio: inicio, hora_fin: fin } = bloque;

    if (dias.length === 0) return rechazar('Marca al menos un día.');
    if (!inicio || !fin) return rechazar('Completa la hora de inicio y de fin.');
    if (fin <= inicio) return rechazar('La hora de fin debe ser posterior a la de inicio.');

    const nuevos = [];
    for (const dia of [...dias].map(Number).sort((a, b) => a - b)) {
        const delDia = actuales.filter((h) => Number(h.dia_semana) === dia);

        if (delDia.some((h) => h.hora_inicio === inicio && h.hora_fin === fin)) continue;
        // Dos horarios que solo se tocan no se solapan.
        if (delDia.some((h) => inicio < h.hora_fin && h.hora_inicio < fin)) {
            return rechazar(`En ${NOMBRES_DIA[dia]}, los horarios se solapan.`);
        }

        nuevos.push({ dia_semana: String(dia), hora_inicio: inicio, hora_fin: fin });
    }

    if (actuales.length + nuevos.length > maximo) {
        return rechazar(`Un grupo admite hasta ${maximo} horarios.`);
    }

    return { horarios: [...actuales, ...nuevos], error: null };
}
