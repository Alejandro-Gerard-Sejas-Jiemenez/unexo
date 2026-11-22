import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import cliente from '../api/cliente';
import { claseControl } from '../componentes/Campo';

const FASES = [
    { valor: '', etiqueta: 'Todas' },
    { valor: 'en_curso', etiqueta: 'En curso' },
    { valor: 'proximos', etiqueta: 'Próximos' },
    { valor: 'finalizados', etiqueta: 'Finalizados' },
];

/**
 * Mis grupos (docente) y Mis cursos (estudiante). Es la misma pantalla: lo que
 * cambia es de dónde vienen los datos y si cada fila enlaza a su detalle.
 */
export default function MisClases({ modo }) {
    const esDocente = modo === 'docente';
    const ruta = esDocente ? '/mis-grupos' : '/mis-cursos';

    const [parametros, setParametros] = useSearchParams();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    const clave = parametros.toString();

    const cargar = useCallback(
        async (query) => {
            setCargando(true);
            try {
                const { data } = await cliente.get(`${ruta}?${query}`);
                setDatos(data);
                setAviso(null);
            } catch (error) {
                setAviso(error.message);
            } finally {
                setCargando(false);
            }
        },
        [ruta],
    );

    useEffect(() => {
        cargar(clave);
    }, [cargar, clave]);

    function cambiar(campo, valor) {
        const siguiente = new URLSearchParams(parametros);
        valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
        setParametros(siguiente);
    }

    if (cargando) return <p role="status" className="text-[var(--color-texto-suave)]">Cargando…</p>;
    if (aviso) return <p role="alert" className="text-sm text-[var(--color-error)]">{aviso}</p>;

    const filas = datos?.data ?? [];
    const vacio = esDocente ? !datos?.hay_grupos : !datos?.hay_inscripciones;

    return (
        <section>
            <h1 className="mb-1 text-2xl font-semibold">
                {esDocente ? 'Mis grupos' : 'Mis cursos'}
            </h1>
            <p className="mb-4 text-sm text-[var(--color-texto-suave)]">
                {esDocente
                    ? 'Los grupos que tenés a cargo, con su horario y su enlace o aula.'
                    : 'Los grupos en los que estás inscrito.'}
            </p>

            {!esDocente && (datos?.pendientes_de_pago ?? []).length > 0 && (
                <div className="superficie mb-4 p-4">
                    <h2 className="mb-2 text-sm font-semibold">Matrículas pendientes de pago</h2>
                    <ul className="list-none space-y-1 p-0 text-sm">
                        {datos.pendientes_de_pago.map((m) => (
                            <li key={m.id}>
                                {m.edicion}
                                {m.programa ? ` — ${m.programa}` : ''} · matriculada el{' '}
                                {m.fecha_matricula} · {m.comprobantes} comprobante(s) subido(s)
                            </li>
                        ))}
                    </ul>
                    <Link to="/app/pagos" className="mt-2 inline-block text-sm">
                        Ver mis métodos de pago
                    </Link>
                </div>
            )}

            {vacio ? (
                <p className="superficie p-6 text-center text-[var(--color-texto-suave)]">
                    {esDocente
                        ? 'Todavía no tenés grupos asignados.'
                        : 'Todavía no estás inscrito en ningún grupo.'}
                </p>
            ) : (
                <>
                    <div className="mb-4 flex flex-wrap items-center gap-3">
                        <div role="group" aria-label="Filtrar por fase" className="flex gap-2">
                            {FASES.map((f) => (
                                <button key={f.valor} type="button"
                                    aria-pressed={(parametros.get('fase') ?? '') === f.valor}
                                    onClick={() => cambiar('fase', f.valor)}
                                    className={
                                        'rounded-lg border px-3 py-1.5 text-sm ' +
                                        ((parametros.get('fase') ?? '') === f.valor
                                            ? 'border-[var(--color-acento)] bg-[var(--color-acento)] text-[var(--color-acento-texto)]'
                                            : 'border-[var(--color-borde)] bg-transparent')
                                    }>
                                    {f.etiqueta}
                                    {f.valor && datos?.conteo?.[f.valor] != null
                                        ? ` (${datos.conteo[f.valor]})`
                                        : ''}
                                </button>
                            ))}
                        </div>

                        <label className="flex-1">
                            <span className="sr-only">Buscar</span>
                            <input type="search" placeholder="Buscar por módulo o grupo"
                                className={claseControl} defaultValue={parametros.get('q') ?? ''}
                                onChange={(e) => cambiar('q', e.target.value)} />
                        </label>
                    </div>

                    {filas.length === 0 ? (
                        <p className="superficie p-6 text-center text-[var(--color-texto-suave)]">
                            Nada coincide con el filtro.
                        </p>
                    ) : (
                        <ul className="list-none space-y-3 p-0">
                            {filas.map((fila) => {
                                const g = esDocente ? fila : fila.grupo;

                                return (
                                    <li key={fila.id} className="superficie p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <p className="font-medium">{g.modulo?.nombre ?? g.nombre}</p>
                                                <p className="text-sm text-[var(--color-texto-suave)]">
                                                    {g.nombre} · {g.modalidad_label} · {g.estado_label}
                                                    {g.cohorte?.nombre ? ` · ${g.cohorte.nombre}` : ''}
                                                </p>
                                                {(g.horarios ?? []).length > 0 && (
                                                    <p className="mt-1 text-xs text-[var(--color-texto-suave)]">
                                                        {g.horarios
                                                            .map((h) => `${h.dia_label} ${h.hora_inicio}–${h.hora_fin}`)
                                                            .join(' · ')}
                                                    </p>
                                                )}
                                                {g.aula && (
                                                    <p className="mt-1 text-xs">Aula: {g.aula}</p>
                                                )}
                                                {g.enlace_videoconferencia && (
                                                    <a href={g.enlace_videoconferencia}
                                                        target="_blank" rel="noreferrer"
                                                        className="mt-1 inline-block text-xs">
                                                        Entrar a la videoconferencia
                                                    </a>
                                                )}
                                            </div>

                                            {esDocente && (
                                                <Link to={`/app/mis-grupos/${g.id}`} className="text-sm">
                                                    Ver estudiantes
                                                </Link>
                                            )}
                                        </div>
                                    </li>
                                );
                            })}
                        </ul>
                    )}
                </>
            )}
        </section>
    );
}
