import { useEffect, useState } from 'react';
import cliente from '../api/cliente';
import Tarjeta from '../componentes/graficos/Tarjeta';
import BarrasRanking from '../componentes/graficos/BarrasRanking';
import SerieTemporal from '../componentes/graficos/SerieTemporal';

/** Panel analítico: métricas del negocio y del flujo de accesos al sitio. */
export default function Dashboard() {
    const [datos, setDatos] = useState(null);
    const [aviso, setAviso] = useState(null);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/estadisticas')
            .then(({ data }) => vigente && setDatos(data))
            .catch((e) => vigente && setAviso(e.message));

        return () => {
            vigente = false;
        };
    }, []);

    if (aviso) {
        return (
            <p role="alert" className="text-sm text-[var(--color-error)]">
                {aviso}
            </p>
        );
    }

    if (!datos) {
        return (
            <p role="status" className="text-[var(--color-texto-suave)]">
                Cargando estadísticas…
            </p>
        );
    }

    const { negocio, acceso } = datos;

    return (
        <div className="space-y-8">
            <section>
                <h1 className="mb-4 text-2xl font-semibold">Estadísticas del negocio</h1>
                <div className="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
                    {negocio.tarjetas.map((t) => (
                        <Tarjeta key={t.clave} etiqueta={t.etiqueta} valor={t.valor} />
                    ))}
                </div>

                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                    <BarrasRanking
                        titulo="Matrículas por estado"
                        datos={negocio.matriculas_por_estado}
                    />
                    <BarrasRanking
                        titulo="Matrículas por origen"
                        descripcion="Cuántas entraron por el CRM y cuántas se cargaron a mano."
                        datos={negocio.matriculas_por_origen}
                    />
                    <BarrasRanking
                        titulo="Grupos por modalidad"
                        datos={negocio.grupos_por_modalidad}
                    />
                    <BarrasRanking
                        titulo="Catálogo por estado comercial"
                        datos={negocio.catalogo_por_estado}
                    />
                </div>

                <div className="mt-4">
                    <BarrasRanking
                        titulo="Ocupación de grupos"
                        descripcion="Inscritos frente al cupo mínimo. La línea marca el cupo."
                        datos={negocio.ocupacion_grupos}
                        meta
                    />
                </div>
            </section>

            <section>
                <h2 className="mb-4 text-2xl font-semibold">Flujo de accesos al sitio</h2>
                <div className="grid grid-cols-2 gap-3 lg:grid-cols-4">
                    {acceso.tarjetas.map((t) => (
                        <Tarjeta key={t.clave} etiqueta={t.etiqueta} valor={t.valor} />
                    ))}
                </div>

                <div className="mt-4">
                    <SerieTemporal
                        titulo="Inicios de sesión por día"
                        descripcion="Últimos 30 días, según la bitácora de auditoría."
                        datos={acceso.logins_por_dia}
                    />
                </div>

                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                    <BarrasRanking
                        titulo="Recursos con mayor índice de acceso"
                        datos={acceso.recursos_mas_accedidos}
                    />
                    <BarrasRanking
                        titulo="Páginas más visitadas"
                        datos={acceso.paginas_mas_visitadas}
                    />
                </div>
            </section>
        </div>
    );
}
