import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import cliente from '../api/cliente';
import { useAuth } from '../auth/AuthContext';
import Tarjeta from '../componentes/graficos/Tarjeta';

/** Inicio: el contenido depende del rol, igual que el panel del servidor. */
export default function Panel() {
    const { usuario } = useAuth();
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/inicio')
            .then(({ data }) => vigente && setDatos(data))
            .catch((e) => vigente && setAviso(e.message));

        return () => {
            vigente = false;
        };
    }, []);

    const esAdmin = datos?.rol === 'administrador';

    /**
     * El servidor manda los totales y la oferta como objetos de conteos; acá se
     * arman las tarjetas con el rótulo y el detalle que corresponde a cada una.
     */
    const tarjetas = () => {
        const t = datos.totales ?? {};
        const o = datos.oferta ?? {};

        return [
            {
                clave: 'crm',
                etiqueta: 'Visible para CRM',
                valor: o.programas ?? 0,
                detalle: `programas abiertos · ${o.modulos ?? 0} módulos`,
                icono: 'candado',
            },
            {
                clave: 'programas',
                etiqueta: 'Programas',
                valor: t.programas ?? 0,
                detalle: 'en el catálogo',
                icono: 'programa',
            },
            {
                clave: 'modulos',
                etiqueta: 'Módulos',
                valor: t.modulos ?? 0,
                detalle: 'en el catálogo',
                icono: 'modulos',
            },
            {
                clave: 'usuarios',
                etiqueta: 'Usuarios',
                valor: t.usuarios ?? 0,
                detalle: `${t.docentes ?? 0} docentes · ${t.estudiantes ?? 0} estudiantes`,
                icono: 'usuarios',
            },
        ];
    };

    /** La ruta del aviso viene como URL del panel Blade; acá se usa la de la SPA. */
    const rutaSpaDelAviso = (url) => {
        try {
            const { pathname, search } = new URL(url, window.location.origin);
            return `/app${pathname}${search}`;
        } catch {
            return '/app';
        }
    };

    return (
        <section>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-3xl font-extrabold">Hola, {usuario?.nombres}</h1>
                    <p className="mt-1 text-[var(--color-texto-suave)]">
                        {esAdmin
                            ? 'Resumen del catálogo académico de NEXO SIS.'
                            : 'Sistema de Administración Académica de NEXO.'}
                    </p>
                </div>

                {esAdmin && (
                    <Link
                        to="/app/programas/nuevo"
                        className="rounded-xl bg-[var(--color-acento)] px-4 py-2.5 text-sm font-semibold text-[var(--color-acento-texto)] no-underline"
                    >
                        + Nuevo programa
                    </Link>
                )}
            </div>

            {aviso && <p role="alert" className="text-sm text-[var(--color-error)]">{aviso}</p>}

            {!datos && !aviso && (
                <p role="status" className="text-[var(--color-texto-suave)]">Cargando…</p>
            )}

            {esAdmin && (
                <>
                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                        {tarjetas().map((t) => (
                            <Tarjeta
                                key={t.clave}
                                etiqueta={t.etiqueta}
                                valor={t.valor}
                                detalle={t.detalle}
                                icono={t.icono}
                            />
                        ))}
                    </div>

                    <div className="superficie p-6">
                        <h2 className="mb-4 text-lg font-bold">Requiere atención</h2>

                        {(datos.avisos ?? []).length === 0 ? (
                            <p className="text-sm text-[var(--color-texto-suave)]">
                                No hay nada pendiente. El catálogo está al día.
                            </p>
                        ) : (
                            <ul className="list-none space-y-3 p-0">
                                {datos.avisos.map((a, i) => (
                                    <li
                                        key={i}
                                        className="flex flex-wrap items-center gap-3 rounded-xl border px-4 py-3"
                                        style={{
                                            borderColor: 'var(--color-aviso)',
                                            background: 'var(--color-aviso-fondo)',
                                        }}
                                    >
                                        <span
                                            aria-hidden="true"
                                            className="grid h-8 w-8 shrink-0 place-items-center rounded-full text-sm font-bold"
                                            style={{
                                                background: 'var(--color-aviso)',
                                                color: 'var(--color-aviso-fondo)',
                                            }}
                                        >
                                            {a.cantidad}
                                        </span>

                                        <span
                                            className="flex-1 text-sm font-medium"
                                            style={{ color: 'var(--color-aviso)' }}
                                        >
                                            {a.cantidad === 1 ? a.singular : a.plural}
                                        </span>

                                        {a.ruta && (
                                            <Link
                                                to={rutaSpaDelAviso(a.ruta)}
                                                className="text-sm font-semibold"
                                                style={{ color: 'var(--color-aviso)' }}
                                            >
                                                Revisar →
                                            </Link>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </>
            )}

            {(datos?.rol === 'docente' || datos?.rol === 'estudiante') && (
                <div>
                    <h2 className="mb-3 text-lg font-bold">Tus próximas clases</h2>

                    {(datos.proximos ?? []).length === 0 ? (
                        <p className="superficie p-6 text-sm text-[var(--color-texto-suave)]">
                            No tenés clases próximas registradas.
                        </p>
                    ) : (
                        <ul className="list-none space-y-3 p-0">
                            {datos.proximos.map((g) => (
                                <li key={g.id} className="superficie p-4">
                                    <p className="font-semibold">{g.modulo?.nombre ?? g.nombre}</p>
                                    <p className="text-sm text-[var(--color-texto-suave)]">
                                        {g.nombre} · {g.modalidad_label}
                                        {g.fecha_inicio ? ` · desde el ${g.fecha_inicio}` : ''}
                                    </p>
                                    {(g.horarios ?? []).length > 0 && (
                                        <p className="mt-1 text-xs text-[var(--color-texto-suave)]">
                                            {g.horarios
                                                .map((h) => `${h.dia_label} ${h.hora_inicio}–${h.hora_fin}`)
                                                .join(' · ')}
                                        </p>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}

                    <div className="mt-4 flex flex-wrap gap-3">
                        <Link to="/app/calendario" className="superficie px-4 py-2 text-sm no-underline">
                            Ver calendario
                        </Link>
                        <Link
                            to={datos.rol === 'docente' ? '/app/mis-grupos' : '/app/mis-cursos'}
                            className="superficie px-4 py-2 text-sm no-underline"
                        >
                            {datos.rol === 'docente' ? 'Mis grupos' : 'Mis cursos'}
                        </Link>
                    </div>
                </div>
            )}
        </section>
    );
}
