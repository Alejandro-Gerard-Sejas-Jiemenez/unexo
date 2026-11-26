import { useCallback, useEffect, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useAuth } from '../../auth/AuthContext';
import cliente from '../../api/cliente';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';

const DIAS = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

function formatoDiaCorto(iso) {
    if (!iso) return '—';
    const d = new Date(`${iso}T00:00:00`);
    if (Number.isNaN(d.getTime())) return iso;
    return d.toLocaleDateString('es-BO', { weekday: 'short', day: 'numeric' });
}

export default function Calendario() {
    const { usuario } = useAuth();
    const [parametros, setParametros] = useSearchParams();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    const mes = parametros.get('mes') ?? '';

    const cargar = useCallback(async (m) => {
        setCargando(true);
        try {
            const { data } = await cliente.get('/calendario', { params: m ? { mes: m } : {} });
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

    const irMes = (m) => setParametros(m ? { mes: m } : {});
    const subtituloRol =
        usuario?.rol === 'docente'
            ? 'Fechas de inicio y fin de tus grupos.'
            : 'Fechas de inicio y fin de tus cursos.';

    const nombreMesSolo = datos?.mes_label?.split(' de ')[0] ?? 'este mes';

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo="Calendario de clases"
                subtitulo={subtituloRol}
            />

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-4 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            {!cargando && datos && (
                <>
                    <div className="superficie mb-10 overflow-hidden rounded-xl shadow-sm">
                        <div className="flex items-center justify-between border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-4">
                            <button
                                type="button"
                                onClick={() => irMes(datos.mes_anterior)}
                                className="inline-flex items-center gap-1.5 rounded-md border border-(--color-borde) bg-(--color-superficie) px-3 py-1.5 text-sm font-medium text-(--color-texto-suave) shadow-sm transition-colors hover:bg-(--color-superficie-2) hover:text-(--color-acento)"
                            >
                                <Icono nombre="volver" className="h-4 w-4" />
                                <span className="hidden sm:inline">Mes anterior</span>
                            </button>

                            <h2 className="text-lg font-bold capitalize">{datos.mes_label}</h2>

                            <button
                                type="button"
                                onClick={() => irMes(datos.mes_siguiente)}
                                className="inline-flex items-center gap-1.5 rounded-md border border-(--color-borde) bg-(--color-superficie) px-3 py-1.5 text-sm font-medium text-(--color-texto-suave) shadow-sm transition-colors hover:bg-(--color-superficie-2) hover:text-(--color-acento)"
                            >
                                <span className="hidden sm:inline">Mes siguiente</span>
                                <Icono nombre="siguiente" className="h-4 w-4" />
                            </button>
                        </div>

                        <div className="flex flex-wrap gap-6 border-b border-(--color-borde) bg-(--color-superficie) px-6 py-3 text-sm">
                            <span className="flex items-center gap-2 font-medium text-(--color-texto-suave)">
                                <span
                                    className="h-3 w-3 rounded-full border-2 border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-(--color-exito)"
                                    aria-hidden="true"
                                />
                                Inicio de clases
                            </span>
                            <span className="flex items-center gap-2 font-medium text-(--color-texto-suave)">
                                <span
                                    className="h-3 w-3 rounded-full border-2 border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-(--color-error)"
                                    aria-hidden="true"
                                />
                                Fin de clases
                            </span>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[700px] table-fixed border-collapse text-left">
                                <caption className="sr-only">{datos.mes_label}</caption>
                                <thead>
                                    <tr className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) text-xs font-bold tracking-wider text-(--color-texto-suave) uppercase">
                                        {DIAS.map((d) => (
                                            <th
                                                key={d}
                                                scope="col"
                                                className="w-[14.28%] border-r border-(--color-borde) px-2 py-3 text-center last:border-r-0"
                                            >
                                                {d}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-(--color-borde) bg-(--color-superficie)">
                                    {datos.semanas.map((semana, s) => (
                                        <tr key={s}>
                                            {semana.map((dia, d) => (
                                                <td
                                                    key={d}
                                                    className={
                                                        'relative h-28 border-r border-(--color-borde) align-top last:border-r-0 sm:h-32 ' +
                                                        (dia.del_mes
                                                            ? dia.es_hoy
                                                                ? 'bg-(--color-acento-suave)/35'
                                                                : 'bg-(--color-superficie)'
                                                            : 'bg-(--color-superficie-cabecera)/80')
                                                    }
                                                >
                                                    {dia.del_mes && (
                                                        <div className="flex h-full flex-col p-2">
                                                            <div className="mb-1 flex items-center justify-between">
                                                                <span
                                                                    className={
                                                                        'inline-flex h-7 w-7 items-center justify-center text-sm font-semibold tabular-nums ' +
                                                                        (dia.es_hoy
                                                                            ? 'rounded-full bg-(--color-acento) text-(--color-acento-texto) shadow-sm'
                                                                            : '')
                                                                    }
                                                                >
                                                                    {dia.numero}
                                                                </span>
                                                            </div>

                                                            <div className="flex-1 space-y-1.5 overflow-y-auto pr-1">
                                                                {dia.eventos.map((e, i) => {
                                                                    const esInicio = e.tipo === 'inicio';
                                                                    return (
                                                                        <div
                                                                            key={i}
                                                                            title={e.texto}
                                                                            className={
                                                                                'truncate rounded border px-2 py-1 text-xs font-medium ' +
                                                                                (esInicio
                                                                                    ? 'border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-(--color-exito-fondo) text-(--color-exito)'
                                                                                    : 'border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-(--color-error-fondo) text-(--color-error)')
                                                                            }
                                                                        >
                                                                            {e.texto}
                                                                        </div>
                                                                    );
                                                                })}
                                                            </div>
                                                        </div>
                                                    )}
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div className="mb-6 flex items-center gap-3">
                        <h2 className="text-2xl font-bold capitalize">Eventos de {nombreMesSolo}</h2>
                        <span className="inline-flex items-center justify-center rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-sm font-medium text-(--color-texto-suave) tabular-nums">
                            {datos.agenda.length}
                        </span>
                    </div>

                    {datos.agenda.length === 0 ? (
                        <div className="superficie max-w-3xl rounded-xl p-8 text-center shadow-sm">
                            <div className="mb-3 inline-flex h-12 w-12 items-center justify-center rounded-full bg-(--color-superficie-2) text-(--color-texto-suave)">
                                <Icono nombre="calendario" className="h-6 w-6" />
                            </div>
                            <h3 className="mb-1 text-lg font-medium">No hay clases este mes</h3>
                            <p className="text-sm text-(--color-texto-suave)">
                                Usa «Mes anterior» o «Mes siguiente» para ver otras fechas.
                            </p>
                        </div>
                    ) : (
                        <div className="superficie mb-10 max-w-4xl overflow-hidden rounded-xl shadow-sm">
                            <ul className="list-none divide-y divide-(--color-borde) p-0">
                                {datos.agenda.map((e, i) => {
                                    const esInicio = e.tipo === 'inicio';
                                    return (
                                        <li
                                            key={i}
                                            className="flex flex-col gap-3 p-4 transition-colors hover:bg-(--color-superficie-2)/60 sm:flex-row sm:items-center sm:gap-6 sm:p-5"
                                        >
                                            <div className="flex w-28 shrink-0 items-center gap-2 font-semibold capitalize text-(--color-texto-suave)">
                                                <Icono nombre="calendario" className="h-4 w-4 opacity-75" />
                                                <span>{formatoDiaCorto(e.fecha)}</span>
                                            </div>

                                            <div className="shrink-0">
                                                <span
                                                    className={
                                                        'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-bold tracking-wider uppercase ' +
                                                        (esInicio
                                                            ? 'border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-(--color-exito-fondo) text-(--color-exito)'
                                                            : 'border-[color-mix(in_srgb,var(--color-error)_35%,transparent)] bg-(--color-error-fondo) text-(--color-error)')
                                                    }
                                                >
                                                    {esInicio ? 'Inicio' : 'Fin'}
                                                </span>
                                            </div>

                                            <div className="flex-1 font-medium">{e.texto}</div>
                                        </li>
                                    );
                                })}
                            </ul>
                        </div>
                    )}
                </>
            )}
        </section>
    );
}
