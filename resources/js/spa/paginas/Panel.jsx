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

    return (
        <section>
            <h1 className="mb-1 text-2xl font-semibold">Hola, {usuario?.nombres}</h1>
            <p className="mb-6 text-[var(--color-texto-suave)]">
                Sistema de Administración Académica de NEXO.
            </p>

            {aviso && <p role="alert" className="text-sm text-[var(--color-error)]">{aviso}</p>}

            {!datos && !aviso && (
                <p role="status" className="text-[var(--color-texto-suave)]">Cargando…</p>
            )}

            {datos?.rol === 'administrador' && (
                <>
                    {datos.totales?.length > 0 && (
                        <div className="mb-6 grid grid-cols-2 gap-3 md:grid-cols-4">
                            {datos.totales.map((t) => (
                                <Tarjeta key={t.etiqueta} etiqueta={t.etiqueta} valor={t.valor} />
                            ))}
                        </div>
                    )}

                    {datos.avisos?.length > 0 && (
                        <div className="superficie mb-6 p-4">
                            <h2 className="mb-2 text-lg font-semibold">Requiere tu atención</h2>
                            <ul className="list-disc space-y-1 pl-5 text-sm">
                                {datos.avisos.map((a, i) => (
                                    <li key={i}>{typeof a === 'string' ? a : (a.texto ?? a.mensaje)}</li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div className="flex flex-wrap gap-3">
                        <Link to="/app/estadisticas" className="superficie px-4 py-2 text-sm no-underline">
                            Ver estadísticas
                        </Link>
                        <Link to="/app/matriculas" className="superficie px-4 py-2 text-sm no-underline">
                            Matrículas
                        </Link>
                        <Link to="/app/cohortes" className="superficie px-4 py-2 text-sm no-underline">
                            Ediciones
                        </Link>
                    </div>
                </>
            )}

            {(datos?.rol === 'docente' || datos?.rol === 'estudiante') && (
                <div>
                    <h2 className="mb-3 text-lg font-semibold">Tus próximas clases</h2>

                    {(datos.proximos ?? []).length === 0 ? (
                        <p className="superficie p-4 text-sm text-[var(--color-texto-suave)]">
                            No tenés clases próximas registradas.
                        </p>
                    ) : (
                        <ul className="list-none space-y-3 p-0">
                            {datos.proximos.map((g) => (
                                <li key={g.id} className="superficie p-4">
                                    <p className="font-medium">{g.modulo?.nombre ?? g.nombre}</p>
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
