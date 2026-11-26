import { useCallback, useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import BarraHerramientas from '../../componentes/BarraHerramientas';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Insignia, { tonoEstado } from '../../componentes/Insignia';
import { useToast } from '../../componentes/ToastContext';
import ZonaArchivo from '../../componentes/ZonaArchivo';
import { gradienteDeCodigo } from '../../lib/fechas';

const FASES = [
    { valor: '', etiqueta: 'Todos' },
    { valor: 'por-iniciar', etiqueta: 'Por iniciar' },
    { valor: 'en-curso', etiqueta: 'En curso' },
    { valor: 'finalizado', etiqueta: 'Finalizados' },
];

export default function MisClases({ modo }) {
    const esDocente = modo === 'docente';
    const ruta = esDocente ? '/mis-grupos' : '/mis-cursos';
    const { mostrar } = useToast();

    const [parametros, setParametros] = useSearchParams();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [busqueda, setBusqueda] = useState(parametros.get('q') ?? '');
    const [archivos, setArchivos] = useState({});
    const [erroresSubida, setErroresSubida] = useState({});
    const [subiendoId, setSubiendoId] = useState(null);

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

    async function subirComprobante(e, matriculaId) {
        e.preventDefault();
        const archivo = archivos[matriculaId];
        if (!archivo) {
            setErroresSubida((p) => ({ ...p, [matriculaId]: 'Elige un archivo de comprobante antes de enviarlo.' }));
            return;
        }

        setErroresSubida((p) => ({ ...p, [matriculaId]: null }));
        setSubiendoId(matriculaId);

        try {
            const fd = new FormData();
            fd.append('archivo', archivo);
            const { data } = await cliente.post(`/matriculas/${matriculaId}/comprobantes`, fd);
            mostrar(data.mensaje);
            setArchivos((p) => ({ ...p, [matriculaId]: null }));
            await cargar(clave);
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErroresSubida((p) => ({ ...p, [matriculaId]: error.errores.archivo?.[0] ?? error.message }));
            } else {
                setErroresSubida((p) => ({ ...p, [matriculaId]: error.message }));
            }
        } finally {
            setSubiendoId(null);
        }
    }

    const filas = datos?.data ?? [];
    const tieneBase = esDocente ? Boolean(datos?.hay_grupos) : Boolean(datos?.hay_inscripciones);
    const pendientes = datos?.pendientes_de_pago ?? [];
    const faseActual = parametros.get('fase') ?? '';

    const pildoras = FASES.map((f) => {
        const count = f.valor === '' ? datos?.total : datos?.conteo?.[f.valor];
        return {
            valor: f.valor,
            etiqueta: count != null ? `${f.etiqueta} (${count})` : f.etiqueta,
        };
    });

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo={esDocente ? 'Mis grupos' : 'Mis cursos'}
                subtitulo={
                    esDocente
                        ? 'Los grupos que tienes asignados y los estudiantes inscritos en cada uno.'
                        : 'Los módulos y programas en los que estás inscrito.'
                }
                acciones={
                    <Link to="/catalogo" className="btn btn-secundario">
                        <Icono nombre="catalogo" className="h-4 w-4 opacity-75" />
                        Ver catálogo
                    </Link>
                }
            />

            {!esDocente &&
                pendientes.map((m) => (
                    <section
                        key={m.id}
                        className="mb-6 rounded-xl border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-(--color-aviso-fondo) p-5"
                    >
                        <div className="flex flex-wrap items-center justify-between gap-2">
                            <h2 className="font-semibold text-(--color-aviso)">
                                Pago pendiente: {m.programa ? `${m.programa} — ${m.edicion}` : m.edicion}
                            </h2>
                            <Link to="/pagos" className="text-xs font-semibold text-(--color-acento) hover:underline">
                                Pagar en línea o ver cuotas
                            </Link>
                        </div>

                        {m.ultimo_comprobante && (
                            <p className="mt-1 text-sm text-(--color-aviso)">
                                Último comprobante: {m.ultimo_comprobante.estado_label} ({m.ultimo_comprobante.creado}).
                            </p>
                        )}

                        {(!m.ultimo_comprobante || m.ultimo_comprobante.estado !== 'pendiente') && (
                            <form onSubmit={(e) => subirComprobante(e, m.id)} className="mt-3 space-y-3">
                                <ZonaArchivo
                                    etiqueta="Subir comprobante (JPG, PNG o PDF)"
                                    ayuda="Tamaño máximo 4 MB."
                                    accept="image/jpeg,image/png,application/pdf"
                                    maxMb={4}
                                    esImagen={Boolean(archivos[m.id]?.type?.startsWith('image/'))}
                                    archivo={archivos[m.id] ?? null}
                                    alCambiar={(f) => setArchivos((p) => ({ ...p, [m.id]: f }))}
                                    error={erroresSubida[m.id] ?? null}
                                />
                                <button
                                    type="submit"
                                    disabled={subiendoId === m.id}
                                    className="btn btn-primario min-h-9! text-xs"
                                >
                                    <Icono nombre="subir-archivo" className="h-4 w-4" />
                                    {subiendoId === m.id ? 'Subiendo…' : 'Subir comprobante'}
                                </button>
                            </form>
                        )}
                    </section>
                ))}

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-4 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            {!cargando && tieneBase && (
                <BarraHerramientas
                    pildoras={pildoras}
                    pildoraActiva={faseActual}
                    alSeleccionarPildora={(val) => cambiar('fase', val)}
                    busqueda={busqueda}
                    placeholder="Buscar por módulo o grupo…"
                    alCambiarBusqueda={(val) => setBusqueda(val)}
                    alBuscar={(val) => cambiar('q', val)}
                    alLimpiarBusqueda={() => {
                        setBusqueda('');
                        cambiar('q', '');
                    }}
                />
            )}

            {!cargando && (
                <div className="mb-12 grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
                    {filas.length === 0 ? (
                        <div className="col-span-1 md:col-span-2 xl:col-span-3">
                            <div className="superficie mx-auto max-w-3xl rounded-xl p-12 text-center shadow-sm">
                                <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-(--color-superficie-2) text-(--color-texto-suave)">
                                    <Icono nombre={esDocente ? 'usuarios' : 'catalogo'} className="h-6 w-6" />
                                </div>
                                {tieneBase ? (
                                    <>
                                        <h3 className="mb-1 text-lg font-medium">
                                            No hay {esDocente ? 'grupos' : 'cursos'} con ese filtro
                                        </h3>
                                        <p className="text-sm text-(--color-texto-suave)">
                                            Prueba con otra búsqueda o muestra todos tus registros.
                                        </p>
                                    </>
                                ) : (
                                    <>
                                        <h3 className="mb-1 text-lg font-medium">
                                            {esDocente
                                                ? 'Todavía no tienes grupos asignados'
                                                : 'Todavía no estás inscrito en ningún curso'}
                                        </h3>
                                        <p className="mb-4 text-sm text-(--color-texto-suave)">
                                            {esDocente
                                                ? 'Cuando la administración te asigne un grupo, aparecerá aquí con sus estudiantes.'
                                                : 'Cuando la administración registre tu matrícula, tus cursos aparecerán aquí.'}
                                        </p>
                                        {!esDocente && (
                                            <Link to="/catalogo" className="btn btn-primario">
                                                Revisar el catálogo
                                            </Link>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    ) : (
                        filas.map((fila) => {
                            const g = esDocente ? fila : fila.grupo;
                            const esRetirado = !esDocente && fila.matricula_estado === 'retirado';
                            const estaPendiente = !esDocente && fila.matricula_estado === 'pendiente';
                            const activa = !esDocente && fila.matricula_estado === 'activo';
                            const enCurso = g.fase === 'por-iniciar' || g.fase === 'en-curso';

                            return (
                                <article
                                    key={fila.id}
                                    className={
                                        'superficie relative flex flex-col overflow-hidden rounded-xl shadow-sm transition-shadow hover:shadow-md ' +
                                        (esRetirado ? 'opacity-75 grayscale-[0.5]' : '')
                                    }
                                >
                                    <div className={`${gradienteDeCodigo(g.modulo?.codigo ?? g.nombre)} p-5`}>
                                        <span className="mb-3 inline-block rounded-md bg-white/20 px-2.5 py-1 text-xs font-bold tracking-wider backdrop-blur-sm">
                                            {g.cohorte
                                                ? `${g.cohorte.programa ? `${g.cohorte.programa} · ` : ''}${g.cohorte.nombre}`
                                                : 'Módulo suelto'}
                                        </span>
                                        <h3 className="text-xl leading-tight font-bold">
                                            {g.modulo?.nombre ?? g.nombre}
                                        </h3>
                                    </div>

                                    <div className="flex flex-1 flex-col p-5">
                                        <dl className="mb-5 grid grid-cols-1 gap-y-3 text-sm">
                                            {esDocente ? (
                                                <>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Grupo</dt>
                                                        <dd className="mt-0.5 font-medium">{g.nombre}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Fechas</dt>
                                                        <dd className="mt-0.5 tabular-nums">{g.periodo_texto || '—'}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Modalidad</dt>
                                                        <dd className="mt-0.5">
                                                            {g.modalidad_label}
                                                            {g.aula ? ` · ${g.aula}` : ''}
                                                        </dd>
                                                    </div>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Estudiantes</dt>
                                                        <dd className="mt-0.5">
                                                            <span className="inline-flex items-center rounded-full border border-(--color-borde) bg-(--color-superficie-2) px-2 py-0.5 text-xs font-bold">
                                                                {g.inscripciones_count ?? 0} inscritos
                                                            </span>
                                                        </dd>
                                                    </div>
                                                </>
                                            ) : (
                                                <>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Docente</dt>
                                                        <dd className="mt-0.5">{g.docente ?? 'Por asignar'}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Período</dt>
                                                        <dd className="mt-0.5 tabular-nums">{g.periodo_texto || '—'}</dd>
                                                    </div>
                                                    <div>
                                                        <dt className="font-medium text-(--color-texto-suave)">Modalidad</dt>
                                                        <dd className="mt-0.5">
                                                            {g.modalidad_label}
                                                            {g.aula ? ` · ${g.aula}` : ''}
                                                        </dd>
                                                    </div>
                                                </>
                                            )}
                                        </dl>

                                        {!esDocente && (
                                            <div className="mt-auto space-y-3 border-t border-(--color-borde) pt-4">
                                                {!esRetirado && (
                                                    <Link to={`/mis-cursos/${g.id}`} className="btn btn-secundario w-full">
                                                        Ver detalle<span className="sr-only"> de {g.modulo?.nombre}</span>
                                                    </Link>
                                                )}

                                                {activa && enCurso && g.enlace_videoconferencia ? (
                                                    <a
                                                        href={g.enlace_videoconferencia}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="btn btn-primario w-full"
                                                    >
                                                        <Icono nombre="video" className="h-4 w-4" />
                                                        Entrar a la clase
                                                    </a>
                                                ) : (
                                                    estaPendiente && (
                                                        <div className="flex gap-2 rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_30%,transparent)] bg-(--color-aviso-fondo) p-3 text-sm text-(--color-aviso)">
                                                            <Icono nombre="alerta" className="h-5 w-5 shrink-0" />
                                                            <p>El enlace de la clase se habilitará cuando se confirme tu pago.</p>
                                                        </div>
                                                    )
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    <div className="flex flex-wrap items-center justify-between gap-2 border-t border-(--color-borde) bg-(--color-superficie-cabecera) p-4">
                                        {esDocente ? (
                                            <>
                                                <Insignia tono={tonoEstado(g.fase)}>{g.fase_etiqueta}</Insignia>
                                                <BotonAccion to={`/mis-grupos/${g.id}`} icono="usuarios">
                                                    Ver estudiantes
                                                </BotonAccion>
                                            </>
                                        ) : (
                                            <>
                                                <Insignia tono={tonoEstado(fila.matricula_estado)}>
                                                    {fila.matricula_estado_label}
                                                </Insignia>
                                                <Insignia tono={tonoEstado(g.fase)}>{g.fase_etiqueta}</Insignia>
                                            </>
                                        )}
                                    </div>
                                </article>
                            );
                        })
                    )}
                </div>
            )}
        </section>
    );
}
