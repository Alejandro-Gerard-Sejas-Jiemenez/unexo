import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { claveCache, guardarCache, leerCache } from '../api/cache';
import cliente from '../api/cliente';
import { useAuth } from '../auth/AuthContext';
import { conBase } from '../base';
import Cargador from '../componentes/Cargador';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Icono from '../componentes/Icono';
import Insignia, { tonoEstado } from '../componentes/Insignia';
import Tarjeta from '../componentes/graficos/Tarjeta';

function iniciales(u) {
    return ((u?.nombres?.[0] ?? '') + (u?.apellidos?.[0] ?? '')).toUpperCase() || 'NX';
}

export default function Panel() {
    const { usuario } = useAuth();
    const llave = claveCache('/inicio');
    const [datos, setDatos] = useState(() => leerCache(llave));
    const [aviso, setAviso] = useState(null);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/inicio')
            .then(({ data }) => {
                guardarCache(llave, data);
                if (vigente) setDatos(data);
            })
            .catch((e) => vigente && !leerCache(llave) && setAviso(e.message));

        return () => {
            vigente = false;
        };
    }, [llave]);

    const esAdmin = datos?.rol === 'administrador';
    const esDocente = datos?.rol === 'docente';
    const esEstudiante = datos?.rol === 'estudiante';

    const subtituloRol = () => {
        if (esAdmin) return 'Resumen del catálogo académico de NEXO SIS.';
        if (esDocente) return 'Bienvenido al panel principal de docencia.';
        if (esEstudiante) return 'Bienvenido a tu portal estudiantil.';
        return 'Sistema de Administración Académica de NEXO SIS.';
    };

    const tarjetas = () => {
        const t = datos.totales ?? {};
        const o = datos.oferta ?? {};
        const p = o.programas ?? 0;
        const m = o.modulos ?? 0;

        return [
            {
                clave: 'crm',
                etiqueta: 'Visible para CRM',
                valor: p,
                detalle: `${p === 1 ? 'programa abierto' : 'programas abiertos'} · ${m} ${m === 1 ? 'módulo' : 'módulos'}`,
                icono: 'ojo',
                ruta: '/catalogo',
                destacado: true,
            },
            {
                clave: 'programas',
                etiqueta: 'Programas',
                valor: t.programas ?? 0,
                detalle: 'en el catálogo',
                icono: 'programa',
                ruta: '/programas',
            },
            {
                clave: 'modulos',
                etiqueta: 'Módulos',
                valor: t.modulos ?? 0,
                detalle: 'en el catálogo',
                icono: 'modulos',
                ruta: '/modulos',
            },
            {
                clave: 'usuarios',
                etiqueta: 'Usuarios',
                valor: t.usuarios ?? 0,
                detalle: `${t.docentes ?? 0} docentes · ${t.estudiantes ?? 0} estudiantes`,
                icono: 'usuarios',
                ruta: '/usuarios',
            },
        ];
    };

    const rutaAviso = (ruta) => (ruta?.startsWith('/') ? ruta : '/');

    const accionHeader = () => {
        if (esAdmin) {
            return (
                <Link to="/programas/nuevo" className="btn btn-primario">
                    <Icono nombre="mas" className="h-4 w-4" />
                    Nuevo programa
                </Link>
            );
        }
        if (esDocente) {
            return (
                <Link to="/mis-grupos" className="btn btn-primario">
                    <Icono nombre="usuarios" className="h-4 w-4" />
                    Ver mis grupos
                </Link>
            );
        }
        if (esEstudiante) {
            return (
                <Link to="/mis-cursos" className="btn btn-primario">
                    <Icono nombre="catalogo" className="h-4 w-4" />
                    Ver mis cursos
                </Link>
            );
        }
        return null;
    };

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo={`Hola, ${usuario?.nombres ?? ''}`}
                subtitulo={subtituloRol()}
                acciones={accionHeader()}
            />

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-4 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {!datos && !aviso && <Cargador texto="Cargando resumen" />}

            {esAdmin && (
                <>
                    <div className="mb-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {tarjetas().map((t) => (
                            <Tarjeta
                                key={t.clave}
                                etiqueta={t.etiqueta}
                                valor={t.valor}
                                detalle={t.detalle}
                                icono={t.icono}
                                ruta={t.ruta}
                                destacado={Boolean(t.destacado)}
                            />
                        ))}
                    </div>

                    <section aria-labelledby="titulo-atencion" className="superficie overflow-hidden rounded-xl shadow-sm">
                        <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                            <h2 id="titulo-atencion" className="text-lg font-bold">
                                Requiere atención
                            </h2>
                        </div>

                        <div className="p-6">
                            {(datos.avisos ?? []).length === 0 ? (
                                <div className="flex items-center gap-4 rounded-xl border border-[color-mix(in_srgb,var(--color-exito)_35%,transparent)] bg-(--color-exito-fondo) p-6 text-(--color-exito)">
                                    <Icono nombre="check" className="h-8 w-8 shrink-0" />
                                    <div>
                                        <h3 className="text-base font-semibold">Todo al día</h3>
                                        <p className="mt-1 text-sm opacity-90">
                                            El catálogo está al día: no hay nada pendiente de revisar.
                                        </p>
                                    </div>
                                </div>
                            ) : (
                                <ul className="list-none space-y-4 p-0">
                                    {datos.avisos.map((a, i) => (
                                        <li key={i}>
                                            <Link
                                                to={rutaAviso(a.ruta)}
                                                className="group flex flex-wrap items-center justify-between gap-3 rounded-xl border border-[color-mix(in_srgb,var(--color-aviso)_35%,transparent)] bg-(--color-aviso-fondo) p-4 text-(--color-aviso) no-underline transition-all hover:brightness-95"
                                            >
                                                <div className="flex items-center gap-4">
                                                    <span
                                                        aria-hidden="true"
                                                        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[color-mix(in_srgb,var(--color-aviso)_20%,transparent)] text-lg font-bold"
                                                    >
                                                        {a.cantidad}
                                                    </span>
                                                    <span className="font-medium">
                                                        {a.cantidad === 1 ? a.singular : a.plural}
                                                    </span>
                                                </div>

                                                <span className="flex items-center gap-1 text-sm font-semibold">
                                                    Revisar
                                                    <Icono nombre="siguiente" className="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
                                                </span>
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    </section>
                </>
            )}

            {(esDocente || esEstudiante) && (
                <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <div className="space-y-8 lg:col-span-2">
                        <section aria-labelledby="titulo-proximos" className="superficie overflow-hidden rounded-xl shadow-sm">
                            <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                                <h2 id="titulo-proximos" className="text-lg font-bold">
                                    {esDocente ? 'Tus próximos grupos' : 'Tus próximos cursos'}
                                </h2>
                            </div>

                            {(datos.proximos ?? []).length === 0 ? (
                                <div className="p-8 text-center">
                                    <div className="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-(--color-superficie-2) text-(--color-texto-suave)">
                                        <Icono nombre={esDocente ? 'usuarios' : 'catalogo'} className="h-6 w-6" />
                                    </div>
                                    <h3 className="mb-1 text-base font-medium">
                                        {esDocente ? 'No tienes grupos asignados por ahora' : 'No tienes cursos activos por ahora'}
                                    </h3>
                                    <p className="mb-4 text-sm text-(--color-texto-suave)">
                                        {esDocente
                                            ? 'Cuando coordinación académica te asigne un grupo, aparecerá en este listado.'
                                            : 'Cuando la administración te inscriba, los verás aquí. Mientras tanto puedes revisar la oferta disponible.'}
                                    </p>
                                    {esEstudiante && (
                                        <Link
                                            to="/catalogo"
                                            className="btn btn-primario"
                                        >
                                            Ir al catálogo
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <ul className="list-none divide-y divide-(--color-borde) p-0">
                                    {datos.proximos.map((g) => {
                                        const destino = esDocente ? `/mis-grupos/${g.id}` : `/mis-cursos/${g.id}`;

                                        return (
                                            <li key={g.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                                                <Link to={destino} className="group flex items-center justify-between gap-4 p-4 no-underline sm:p-6">
                                                    <div className="min-w-0 flex-1">
                                                        <h3 className="truncate text-base font-bold transition-colors group-hover:text-(--color-acento)">
                                                            {g.modulo?.nombre ?? g.nombre}
                                                        </h3>
                                                        <div className="mt-1 flex flex-col gap-1 text-sm text-(--color-texto-suave) sm:flex-row sm:items-center sm:gap-3">
                                                            <span className="flex items-center gap-1.5">
                                                                <Icono nombre="calendario" className="h-4 w-4 shrink-0 opacity-75" />
                                                                <span>{g.periodo_texto || g.nombre}</span>
                                                            </span>
                                                            <span className="hidden opacity-40 sm:inline">•</span>
                                                            <span className="flex items-center gap-1.5">
                                                                <Icono nombre="usuarios" className="h-4 w-4 shrink-0 opacity-75" />
                                                                {esDocente ? (
                                                                    <span>{g.inscripciones_count ?? 0} inscritos</span>
                                                                ) : (
                                                                    <span>{g.docente ?? 'Docente por asignar'}</span>
                                                                )}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {g.fase_etiqueta && (
                                                        <Insignia tono={tonoEstado(g.fase)}>{g.fase_etiqueta}</Insignia>
                                                    )}
                                                </Link>
                                            </li>
                                        );
                                    })}
                                </ul>
                            )}
                        </section>
                    </div>

                    <div className="space-y-8 lg:col-span-1">
                        <section aria-labelledby="titulo-perfil" className="superficie overflow-hidden rounded-xl shadow-sm">
                            <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                                <h2 id="titulo-perfil" className="text-lg font-bold">
                                    Mi perfil
                                </h2>
                            </div>

                            <div className="p-6">
                                <div className="mb-6 flex items-center gap-4">
                                    {usuario?.foto_perfil_url ? (
                                        <img
                                            src={conBase(usuario.foto_perfil_url)}
                                            alt=""
                                            className="h-16 w-16 rounded-full border-2 border-white object-cover shadow-sm"
                                        />
                                    ) : (
                                        <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-full border-2 border-white bg-(--color-acento-suave) text-2xl font-bold text-(--color-acento) uppercase shadow-sm">
                                            {iniciales(usuario)}
                                        </div>
                                    )}
                                    <div className="min-w-0">
                                        <h3 className="truncate font-bold">{usuario?.nombre_completo}</h3>
                                        <p className="text-sm text-(--color-texto-suave)">{usuario?.rol_label}</p>
                                    </div>
                                </div>

                                <dl className="space-y-4">
                                    <div>
                                        <dt className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                                            Correo institucional
                                        </dt>
                                        <dd className="rounded-lg border border-(--color-borde) bg-(--color-superficie-cabecera) p-2.5 text-sm font-medium break-all">
                                            {usuario?.correo_institucional ?? 'Sin asignar'}
                                        </dd>
                                    </div>

                                    <div>
                                        <dt className="mb-1 text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                                            Teléfono
                                        </dt>
                                        <dd className="rounded-lg border border-(--color-borde) bg-(--color-superficie-cabecera) p-2.5 text-sm font-medium">
                                            {usuario?.telefono || '—'}
                                        </dd>
                                    </div>
                                </dl>

                                <Link to="/perfil" className="btn btn-secundario mt-5 w-full text-xs">
                                    <Icono nombre="editar" className="h-3.5 w-3.5" />
                                    Editar perfil
                                </Link>
                            </div>
                        </section>
                    </div>
                </div>
            )}
        </section>
    );
}
