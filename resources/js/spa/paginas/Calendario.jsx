import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import cliente from '../api/cliente';

const DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

/** Agenda mensual de clases del docente y del estudiante. */
export default function Calendario() {
    const [parametros, setParametros] = useSearchParams();
    const mes = parametros.get('mes') ?? '';

    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    const cargar = useCallback(async (valor) => {
        setCargando(true);
        try {
            const { data } = await cliente.get('/calendario', { params: valor ? { mes: valor } : {} });
            setDatos(data);
            setAviso(null);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar(mes);
    }, [cargar, mes]);

    function irA(valor) {
        const siguiente = new URLSearchParams(parametros);
        siguiente.set('mes', valor);
        setParametros(siguiente);
    }

    if (cargando) return <p role="status" className="text-[var(--color-texto-suave)]">Cargando…</p>;
    if (aviso) return <p role="alert" className="text-sm text-[var(--color-error)]">{aviso}</p>;

    return (
        <section>
            <div className="mb-4 flex items-center justify-between">
                <h1 className="text-2xl font-semibold capitalize">{datos.mes_label}</h1>
                <div className="flex gap-2">
                    <button type="button" onClick={() => irA(datos.mes_anterior)}
                        className="superficie px-3 py-1.5 text-sm">
                        Mes anterior
                    </button>
                    <button type="button" onClick={() => irA(datos.mes_siguiente)}
                        className="superficie px-3 py-1.5 text-sm">
                        Mes siguiente
                    </button>
                </div>
            </div>

            <div className="superficie mb-6 overflow-x-auto p-2">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">
                        Calendario de {datos.mes_label} con los inicios y fines de tus grupos
                    </caption>
                    <thead>
                        <tr>
                            {DIAS.map((d) => (
                                <th key={d} scope="col"
                                    className="p-2 text-center text-xs font-semibold text-[var(--color-texto-suave)]">
                                    {d}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody>
                        {datos.semanas.map((semana, i) => (
                            <tr key={i}>
                                {semana.map((dia, j) => (
                                    <td key={j}
                                        className={
                                            'h-24 w-[14.28%] border border-[var(--color-borde)] p-1 align-top ' +
                                            (dia.del_mes ? '' : 'opacity-40')
                                        }>
                                        <span
                                            className={
                                                'inline-block rounded px-1 text-xs tabular-nums ' +
                                                (dia.es_hoy
                                                    ? 'bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                                    : 'text-[var(--color-texto-suave)]')
                                            }>
                                            {dia.numero}
                                        </span>
                                        {dia.eventos.map((e, k) => (
                                            <p key={k} className="mt-1 text-xs leading-tight">
                                                {/* El tipo se dice con una palabra, no solo con color. */}
                                                <b>{e.tipo === 'inicio' ? 'Inicia' : 'Termina'}:</b>{' '}
                                                {e.texto.replace(/^(Inicia|Termina): /, '')}
                                            </p>
                                        ))}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <h2 className="mb-2 text-lg font-semibold">Agenda del mes</h2>
            {datos.agenda.length === 0 ? (
                <p className="superficie p-4 text-sm text-[var(--color-texto-suave)]">
                    No hay eventos este mes.
                </p>
            ) : (
                <ul className="list-none space-y-2 p-0">
                    {datos.agenda.map((e, i) => (
                        <li key={i} className="superficie flex gap-3 p-3 text-sm">
                            <span className="tabular-nums">{e.fecha}</span>
                            <span>{e.texto}</span>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}
