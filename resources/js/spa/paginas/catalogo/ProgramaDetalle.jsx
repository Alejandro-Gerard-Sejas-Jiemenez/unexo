import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
import cliente from '../../api/cliente';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import Icono from '../../componentes/Icono';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';

export default function ProgramaDetalle() {
    const { id } = useParams();
    const { mostrar } = useToast();

    const [programa, setPrograma] = useState(null);
    const [opciones, setOpciones] = useState({ modulos_disponibles: [], modulos_disponibles_total: 0, limites: {} });
    const [aAgregar, setAAgregar] = useState('');
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);

    const cargar = useCallback(async () => {
        try {
            const [resP, resO] = await Promise.all([
                cliente.get(`/catalogo/programas/${id}`),
                cliente.get('/catalogo/opciones', { params: { programa: id } }),
            ]);
            setPrograma(resP.data.data);
            setOpciones(resO.data);
            setAviso(null);
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, [id]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    async function operarPlan(peticion) {
        setAviso(null);
        try {
            const { data } = await peticion();
            mostrar(data.mensaje);
            await cargar();
        } catch (error) {
            setAviso(error.errores?.modulo_id?.[0] ?? error.message);
        }
    }

    if (cargando) return <Cargador texto="Cargando" />;
    if (!programa) {
        return (
            <p role="alert" className="superficie border-(--color-error) p-4 text-sm text-(--color-error)">
                {aviso ?? 'No se encontró el programa.'}
            </p>
        );
    }

    const plan = programa.modulos ?? [];
    const limiteSelect = opciones.limites?.opciones_select ?? 100;

    return (
        <section>
            <div className="mb-4 flex flex-col justify-between gap-4 md:flex-row md:items-start">
                <div>
                    <h1 className="flex flex-wrap items-center gap-3 text-3xl font-bold tracking-tight">
                        <span>{programa.nombre}</span>
                        <span className="rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-sm font-semibold text-(--color-texto-suave) ring-1 ring-(--color-borde) ring-inset">
                            v{programa.version}
                        </span>
                    </h1>
                    <p className="mt-2 flex flex-wrap items-center gap-2 font-medium text-(--color-texto-suave)">
                        <span className="font-bold text-(--color-acento)">{programa.codigo}</span>
                        {programa.descripcion && (
                            <>
                                <span className="opacity-40">&bull;</span>
                                <span>{programa.descripcion}</span>
                            </>
                        )}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <span
                        className={
                            'inline-flex items-center rounded-full px-3 py-1 text-sm font-semibold ring-1 ring-inset ' +
                            (programa.estado_comercial === 'abierto'
                                ? 'bg-(--color-exito-fondo) text-(--color-exito) ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]'
                                : 'bg-(--color-superficie-2) text-(--color-texto-suave) ring-(--color-borde)')
                        }
                    >
                        {programa.estado_label}
                    </span>
                    <BotonAccion to={`/programas/${programa.id}`} icono="editar" className="px-4! py-2! text-sm! rounded-lg!">
                        Editar
                    </BotonAccion>
                    <BotonAccion to="/programas" icono="volver" className="px-4! py-2! text-sm! rounded-lg!">
                        Volver
                    </BotonAccion>
                </div>
            </div>

            {aviso && (
                <p role="alert" className="superficie my-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <div className="mt-10 mb-4 flex items-center justify-between">
                <h2 className="text-xl font-bold">Módulos del programa (en orden)</h2>
            </div>

            <div className="mb-10">
                <TablaDatos
                    caption="Módulos del programa en orden secuencial"
                    columnas={[
                        { etiqueta: '#' },
                        { etiqueta: 'Código' },
                        { etiqueta: 'Módulo' },
                        { etiqueta: 'Horas' },
                        { etiqueta: 'Estado' },
                        { etiqueta: 'Acciones', alinear: 'derecha' },
                    ]}
                    vacio={plan.length === 0}
                    vacioIcono="modulos"
                    vacioTitulo="Este programa todavía no tiene módulos"
                    vacioDescripcion="Agrega módulos desde el selector inferior para armar el plan de estudios."
                >
                    {plan.map((m, i) => (
                        <tr key={m.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                            <td className="px-6 py-4 text-sm font-bold text-(--color-texto-suave) whitespace-nowrap tabular-nums">
                                {m.orden ?? i + 1}
                            </td>
                            <td className="px-6 py-4 text-sm font-medium text-(--color-texto-suave) whitespace-nowrap">
                                {m.codigo}
                            </td>
                            <td className="px-6 py-4 text-sm font-medium">{m.nombre}</td>
                            <td className="px-6 py-4 text-sm text-(--color-texto-suave) whitespace-nowrap tabular-nums">
                                {m.duracion_horas ?? '—'}
                            </td>
                            <td className="px-6 py-4 whitespace-nowrap">
                                <span
                                    className={
                                        'inline-flex rounded-md px-2.5 py-1 text-xs font-medium ' +
                                        (m.estado_comercial === 'abierto'
                                            ? 'bg-(--color-exito-fondo) text-(--color-exito)'
                                            : 'bg-(--color-superficie-2) text-(--color-texto-suave)')
                                    }
                                >
                                    {m.estado_label}
                                </span>
                            </td>
                            <td className="px-6 py-4 text-right whitespace-nowrap">
                                <div className="flex items-center justify-end gap-1.5">
                                    {i > 0 && (
                                        <button
                                            type="button"
                                            title="Subir"
                                            onClick={() =>
                                                operarPlan(() =>
                                                    cliente.patch(`/catalogo/programas/${id}/modulos/${m.id}/mover`, {
                                                        direccion: 'subir',
                                                    }),
                                                )
                                            }
                                            className="rounded-md border-0 bg-transparent p-1.5 text-(--color-texto-suave) transition-colors hover:bg-(--color-acento-suave) hover:text-(--color-acento)"
                                        >
                                            <Icono nombre="subir" className="h-4 w-4" />
                                        </button>
                                    )}

                                    {i < plan.length - 1 && (
                                        <button
                                            type="button"
                                            title="Bajar"
                                            onClick={() =>
                                                operarPlan(() =>
                                                    cliente.patch(`/catalogo/programas/${id}/modulos/${m.id}/mover`, {
                                                        direccion: 'bajar',
                                                    }),
                                                )
                                            }
                                            className="rounded-md border-0 bg-transparent p-1.5 text-(--color-texto-suave) transition-colors hover:bg-(--color-acento-suave) hover:text-(--color-acento)"
                                        >
                                            <Icono nombre="bajar" className="h-4 w-4" />
                                        </button>
                                    )}

                                    <button
                                        type="button"
                                        title="Quitar"
                                        onClick={() =>
                                            operarPlan(() => cliente.delete(`/catalogo/programas/${id}/modulos/${m.id}`))
                                        }
                                        className="ml-1 rounded-md border-0 bg-transparent p-1.5 text-(--color-error) transition-colors hover:bg-(--color-error-fondo)"
                                    >
                                        <Icono nombre="cerrar" className="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </TablaDatos>
            </div>

            <h2 className="mb-3 text-lg font-bold">Agregar un módulo</h2>
            <div className="rounded-xl border border-(--color-borde) bg-(--color-superficie-cabecera) p-5">
                {opciones.modulos_disponibles.length === 0 ? (
                    <div className="flex flex-wrap items-center gap-2 text-sm text-(--color-texto-suave)">
                        <Icono nombre="alerta" className="h-5 w-5 text-(--color-aviso)" />
                        <span>No quedan módulos por agregar.</span>
                        <Link to="/modulos/nuevo" className="font-medium text-(--color-acento) hover:underline">
                            Crear un módulo nuevo
                        </Link>
                    </div>
                ) : (
                    <>
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <div className="campo-caja max-w-lg flex-1">
                                <select
                                    aria-label="Módulo a agregar"
                                    className="campo-control"
                                    value={aAgregar}
                                    onChange={(e) => setAAgregar(e.target.value)}
                                >
                                    <option value="">Elige un módulo para agregar…</option>
                                    {opciones.modulos_disponibles.map((m) => (
                                        <option key={m.id} value={m.id}>
                                            {m.codigo} • {m.nombre}
                                        </option>
                                    ))}
                                </select>
                                <Icono nombre="chevron" className="campo-flecha" />
                            </div>
                            <button
                                type="button"
                                disabled={!aAgregar}
                                onClick={async () => {
                                    await operarPlan(() =>
                                        cliente.post(`/catalogo/programas/${id}/modulos`, {
                                            modulo_id: aAgregar,
                                        }),
                                    );
                                    setAAgregar('');
                                }}
                                className="btn btn-primario"
                            >
                                <Icono nombre="mas" className="h-4 w-4" />
                                Agregar al final
                            </button>
                        </div>
                        {opciones.modulos_disponibles_total > limiteSelect && (
                            <p className="mt-2 text-xs text-(--color-aviso)">
                                Se muestran los primeros {opciones.modulos_disponibles.length} de{' '}
                                {opciones.modulos_disponibles_total} módulos disponibles.
                            </p>
                        )}
                    </>
                )}
            </div>
        </section>
    );
}
