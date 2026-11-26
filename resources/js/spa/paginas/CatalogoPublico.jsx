import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import { claveCache, guardarCache, leerCache } from '../api/cache';
import cliente from '../api/cliente';
import { useAuth } from '../auth/AuthContext';
import { conBase } from '../base';
import BarraHerramientas from '../componentes/BarraHerramientas';
import Cargador from '../componentes/Cargador';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Icono from '../componentes/Icono';
import { formatoFecha, gradienteNeonAleatorio } from '../lib/fechas';

function PortadaPrograma({ programa }) {
    const [falloImagen, setFalloImagen] = useState(false);
    const [gradienteNeon] = useState(() => gradienteNeonAleatorio());

    useEffect(() => {
        setFalloImagen(false);
    }, [programa.imagen_portada_url]);

    if (programa.imagen_portada_url && !falloImagen) {
        return (
            <div className="relative">
                <img
                    src={conBase(programa.imagen_portada_url)}
                    alt={programa.nombre}
                    onError={() => setFalloImagen(true)}
                    className="h-36 w-full object-cover"
                />
                <div className="border-b border-[var(--color-borde)] p-5">
                    <span className="inline-block rounded bg-[var(--color-acento-suave)] px-2 py-0.5 font-mono text-xs font-bold text-[var(--color-acento)]">
                        {programa.codigo} · v{programa.version}
                    </span>
                    <h3 className="mt-2 line-clamp-2 text-lg leading-snug font-bold">
                        {programa.nombre}
                    </h3>
                    {programa.precio != null && (
                        <div className="mt-1.5 text-sm font-semibold text-[var(--color-acento)]">
                            Bs.{' '}
                            {Number(programa.precio).toLocaleString('es-BO', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2,
                            })}
                        </div>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className={`${gradienteNeon} p-5`}>
            <div className="mb-2 flex items-center justify-between gap-2">
                <span className="rounded bg-white/20 px-2 py-0.5 font-mono text-xs font-bold tracking-wider backdrop-blur-sm">
                    {programa.codigo} · v{programa.version}
                </span>
            </div>
            <h3 className="line-clamp-2 text-lg leading-snug font-bold drop-shadow-sm">
                {programa.nombre}
            </h3>
            {programa.precio != null && (
                <div className="mt-2 text-sm font-semibold opacity-95">
                    Bs.{' '}
                    {Number(programa.precio).toLocaleString('es-BO', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2,
                    })}
                </div>
            )}
        </div>
    );
}

export default function CatalogoPublico() {
    const { usuario } = useAuth();
    const esAdmin = usuario?.rol === 'administrador';
    const [parametros, setParametros] = useSearchParams();
    const tab = parametros.get('tab') ?? 'todos';
    const q = parametros.get('q') ?? '';

    const [textoBusqueda, setTextoBusqueda] = useState(q);
    const cacheInicial = leerCache(claveCache('/catalogo-publico', { q }));
    const [datos, setDatos] = useState(() => cacheInicial ?? { programas: [], modulos: [] });
    const [cargando, setCargando] = useState(() => !cacheInicial);
    const [aviso, setAviso] = useState(null);

    useEffect(() => {
        setTextoBusqueda(q);
    }, [q]);

    const cargar = useCallback(async (busqueda) => {
        const llave = claveCache('/catalogo-publico', { q: busqueda });
        const enCache = leerCache(llave);
        if (enCache) {
            setDatos(enCache);
            setCargando(false);
        } else {
            setCargando(true);
        }
        try {
            const { data } = await cliente.get('/catalogo-publico', { params: { q: busqueda } });
            guardarCache(llave, data);
            setDatos(data);
            setAviso(null);
        } catch (error) {
            if (!enCache) setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        const id = setTimeout(() => cargar(q), 0);
        return () => clearTimeout(id);
    }, [cargar, q]);

    function cambiar(campo, valor) {
        const siguiente = new URLSearchParams(parametros);
        valor ? siguiente.set(campo, valor) : siguiente.delete(campo);
        setParametros(siguiente);
    }

    const verProgramas = tab === 'programas' || tab === 'todos';
    const verModulos = tab === 'modulos' || tab === 'todos';

    const pildoras = [
        { valor: 'programas', etiqueta: `Programas (${datos.programas.length})` },
        { valor: 'modulos', etiqueta: `Módulos Libres (${datos.modulos.length})` },
        { valor: 'todos', etiqueta: `Ver Todos (${datos.programas.length + datos.modulos.length})` },
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Catálogo Académico"
                subtitulo="Oferta abierta de programas y módulos independientes disponibles para inscripción y CRM."
                acciones={
                    esAdmin && (
                        <>
                            <Link to="/programas/nuevo" className="btn btn-primario">
                                <Icono nombre="mas" className="h-4 w-4" />
                                Nuevo programa
                            </Link>
                            <Link to="/matriculas/nueva" className="btn btn-secundario">
                                <Icono nombre="matricula" className="h-4 w-4 text-[var(--color-acento)]" />
                                Registrar matrícula
                            </Link>
                        </>
                    )
                }
            />

            <BarraHerramientas
                pildoras={pildoras}
                pildoraActiva={tab}
                alSeleccionarPildora={(val) => cambiar('tab', val)}
                busqueda={textoBusqueda}
                placeholder="Buscar en catálogo…"
                mostrarBotonBuscar={false}
                alCambiarBusqueda={(val) => {
                    setTextoBusqueda(val);
                    cambiar('q', val);
                }}
                alLimpiarBusqueda={() => {
                    setTextoBusqueda('');
                    cambiar('q', '');
                }}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            {!cargando && verProgramas && (
                <section className="mb-12">
                    <div className="mb-5 flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg border border-[var(--color-borde)] bg-[var(--color-acento-suave)] text-[var(--color-acento)]">
                                <Icono nombre="programa" className="h-4 w-4" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold">Programas Académicos</h2>
                                <p className="text-xs text-[var(--color-texto-suave)]">
                                    Estructuras modulares con certificación y ediciones periódicas.
                                </p>
                            </div>
                        </div>
                        <span className="rounded-full bg-[var(--color-superficie-2)] px-2.5 py-1 text-xs font-semibold text-[var(--color-texto-suave)]">
                            {datos.programas.length} {datos.programas.length === 1 ? 'programa' : 'programas'}
                        </span>
                    </div>

                    {datos.programas.length === 0 ? (
                        <div className="superficie rounded-xl p-8 text-center text-sm text-[var(--color-texto-suave)] shadow-sm">
                            No se encontraron programas académicos con los criterios seleccionados.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {datos.programas.map((p) => {
                                const proxima = p.ediciones?.[0] ?? null;

                                return (
                                    <article
                                        key={p.id}
                                        className="superficie flex flex-col overflow-hidden rounded-xl shadow-sm transition-shadow hover:shadow-md"
                                    >
                                        <PortadaPrograma programa={p} />

                                        <div className="flex flex-1 flex-col justify-between p-5">
                                            {p.descripcion && (
                                                <p className="mb-4 line-clamp-3 text-xs leading-relaxed text-[var(--color-texto-suave)]">
                                                    {p.descripcion}
                                                </p>
                                            )}

                                            {proxima ? (
                                                <div className="mb-4 rounded-lg border border-[color-mix(in_srgb,var(--color-acento)_20%,transparent)] bg-[var(--color-acento-suave)]/70 p-3 text-xs">
                                                    <div className="flex items-center justify-between gap-2 font-semibold">
                                                        <span className="truncate">Próxima: {proxima.nombre}</span>
                                                        <span className="shrink-0 rounded bg-[var(--color-superficie)] px-1.5 py-0.5 text-[10px] font-bold text-[var(--color-acento)] uppercase">
                                                            {proxima.estado_label}
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 flex items-center gap-1.5 text-[var(--color-acento)]">
                                                        <Icono nombre="calendario" className="h-3.5 w-3.5 shrink-0" />
                                                        <span>
                                                            Inicia: <strong>{formatoFecha(proxima.fecha_inicio)}</strong>
                                                        </span>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="mb-4 flex items-center gap-2 rounded-lg border border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] p-2.5 text-xs text-[var(--color-texto-suave)]">
                                                    <Icono nombre="info" className="h-3.5 w-3.5 shrink-0 opacity-75" />
                                                    <span>Sin ediciones vigentes programadas.</span>
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between border-t border-[var(--color-borde)] pt-3 text-xs font-medium text-[var(--color-texto-suave)]">
                                                <span className="flex items-center gap-1.5">
                                                    <Icono nombre="modulos" className="h-3.5 w-3.5 opacity-70" />
                                                    {p.modulos.length} módulos
                                                </span>
                                                {(p.duracion_total_horas ?? 0) > 0 && (
                                                    <span>{p.duracion_total_horas} hrs</span>
                                                )}
                                            </div>
                                        </div>

                                        <details className="group border-t border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)]">
                                            <summary className="flex cursor-pointer list-none items-center justify-between px-5 py-2.5 text-xs font-semibold text-[var(--color-acento)] transition-colors select-none hover:bg-[var(--color-superficie-2)] [&::-webkit-details-marker]:hidden">
                                                <span className="flex items-center gap-1.5">
                                                    <Icono
                                                        nombre="chevron"
                                                        className="h-3.5 w-3.5 transition-transform group-open:rotate-180"
                                                    />
                                                    Ver plan de módulos ({p.modulos.length})
                                                </span>
                                                <span className="text-[11px] font-normal text-[var(--color-texto-suave)]">
                                                    Orden secuencial
                                                </span>
                                            </summary>

                                            <div className="space-y-2 border-t border-[var(--color-borde)] px-5 py-3">
                                                {p.modulos.length === 0 ? (
                                                    <p className="text-xs text-[var(--color-texto-suave)]">
                                                        Sin módulos abiertos por ahora.
                                                    </p>
                                                ) : (
                                                    p.modulos.map((mod, indice) => (
                                                        <div key={mod.id} className="flex items-start justify-between gap-2 text-xs">
                                                            <div className="flex items-start gap-2">
                                                                <span className="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-[var(--color-superficie-2)] text-[10px] font-bold text-[var(--color-texto-suave)]">
                                                                    {indice + 1}
                                                                </span>
                                                                <span className="font-medium">{mod.nombre}</span>
                                                            </div>
                                                            <span className="shrink-0 text-[var(--color-texto-suave)] tabular-nums">
                                                                {mod.duracion_horas ? `${mod.duracion_horas}h` : ''}
                                                            </span>
                                                        </div>
                                                    ))
                                                )}
                                            </div>
                                        </details>

                                        {esAdmin && (
                                            <div className="flex items-center justify-end gap-2 border-t border-[var(--color-borde)] bg-[var(--color-superficie)] px-5 py-3 text-xs font-medium">
                                                <Link
                                                    to={`/cohortes/nueva?programa_id=${p.id}`}
                                                    className="text-[var(--color-acento)] no-underline hover:underline"
                                                >
                                                    + Nueva edición
                                                </Link>
                                                <span className="opacity-30">·</span>
                                                <Link
                                                    to={`/programas/${p.id}/ver`}
                                                    className="text-[var(--color-texto-suave)] no-underline hover:text-[var(--color-texto)] hover:underline"
                                                >
                                                    Gestionar
                                                </Link>
                                            </div>
                                        )}
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            )}

            {!cargando && verModulos && (
                <section className="mb-12">
                    <div className="mb-5 flex items-center justify-between gap-3">
                        <div className="flex items-center gap-2.5">
                            <div className="flex h-8 w-8 items-center justify-center rounded-lg border border-[color-mix(in_srgb,var(--color-realce)_30%,transparent)] bg-[var(--color-exito-fondo)] text-[var(--color-exito)]">
                                <Icono nombre="modulos" className="h-4 w-4" />
                            </div>
                            <div>
                                <h2 className="text-xl font-bold">Módulos Independientes y Cursos Cortos</h2>
                                <p className="text-xs text-[var(--color-texto-suave)]">
                                    Módulos que pueden cursarse de manera suelta con grupos activos.
                                </p>
                            </div>
                        </div>
                        <span className="rounded-full bg-[var(--color-superficie-2)] px-2.5 py-1 text-xs font-semibold text-[var(--color-texto-suave)]">
                            {datos.modulos.length} {datos.modulos.length === 1 ? 'módulo' : 'módulos'}
                        </span>
                    </div>

                    {datos.modulos.length === 0 ? (
                        <div className="superficie rounded-xl p-8 text-center text-sm text-[var(--color-texto-suave)] shadow-sm">
                            No se encontraron módulos con los criterios seleccionados.
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {datos.modulos.map((m) => {
                                const primerGrupo = m.grupos_sueltos?.[0] ?? null;

                                return (
                                    <article
                                        key={m.id}
                                        className="superficie flex flex-col overflow-hidden rounded-xl shadow-sm transition-shadow hover:shadow-md"
                                    >
                                        <div className="border-b border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] p-4">
                                            <div className="mb-1.5 flex items-center justify-between gap-2">
                                                <span className="rounded border border-[var(--color-borde)] bg-[var(--color-superficie)] px-2 py-0.5 font-mono text-xs font-bold">
                                                    {m.codigo}
                                                </span>
                                            </div>
                                            <h3 className="text-base leading-snug font-bold">{m.nombre}</h3>
                                        </div>

                                        <div className="flex flex-1 flex-col justify-between p-4">
                                            {m.descripcion && (
                                                <p className="mb-3 line-clamp-2 text-xs leading-relaxed text-[var(--color-texto-suave)]">
                                                    {m.descripcion}
                                                </p>
                                            )}

                                            {m.programas?.length > 0 && (
                                                <div className="mb-3">
                                                    <span className="mb-1 block text-[11px] font-semibold tracking-wider text-[var(--color-texto-suave)] uppercase">
                                                        Disponible en:
                                                    </span>
                                                    <div className="flex flex-wrap gap-1">
                                                        {m.programas.map((nombreProg, idx) => (
                                                            <span
                                                                key={idx}
                                                                className="rounded bg-[var(--color-superficie-2)] px-2 py-0.5 text-[11px] font-medium"
                                                            >
                                                                {nombreProg}
                                                            </span>
                                                        ))}
                                                    </div>
                                                </div>
                                            )}

                                            {primerGrupo && (
                                                <div className="mb-3 rounded-lg border border-[color-mix(in_srgb,var(--color-exito)_30%,transparent)] bg-[var(--color-exito-fondo)] p-2.5 text-xs text-[var(--color-exito)]">
                                                    <div className="flex items-center justify-between gap-2 font-semibold">
                                                        <span>Grupo abierto: {primerGrupo.nombre}</span>
                                                        <span className="text-[10px] font-bold uppercase">
                                                            {primerGrupo.modalidad_label}
                                                        </span>
                                                    </div>
                                                    {primerGrupo.periodo_inicio && (
                                                        <div className="mt-1">
                                                            Inicia: <strong>{formatoFecha(primerGrupo.periodo_inicio)}</strong>
                                                        </div>
                                                    )}
                                                </div>
                                            )}

                                            <div className="flex items-center justify-between border-t border-[var(--color-borde)] pt-3 text-xs text-[var(--color-texto-suave)]">
                                                <span className="flex items-center gap-1">
                                                    <Icono nombre="calendario" className="h-3.5 w-3.5 opacity-70" />
                                                    {m.duracion_horas ? `${m.duracion_horas} horas` : 'Duración flexible'}
                                                </span>
                                                {m.pdf_temario_url && (
                                                    <a
                                                        href={m.pdf_temario_url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="flex items-center gap-1 font-medium text-[var(--color-acento)] hover:underline"
                                                    >
                                                        <Icono nombre="documento" className="h-3.5 w-3.5" />
                                                        Temario PDF
                                                    </a>
                                                )}
                                            </div>
                                        </div>

                                        {esAdmin && (
                                            <div className="flex items-center justify-end gap-2 border-t border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] px-4 py-2.5 text-xs font-medium">
                                                <Link
                                                    to={`/grupos/nuevo?modulo_id=${m.id}`}
                                                    className="text-[var(--color-acento)] no-underline hover:underline"
                                                >
                                                    + Aperturar grupo
                                                </Link>
                                            </div>
                                        )}
                                    </article>
                                );
                            })}
                        </div>
                    )}
                </section>
            )}
        </section>
    );
}
