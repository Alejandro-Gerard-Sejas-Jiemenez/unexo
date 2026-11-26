import { useEffect, useState } from 'react';
import { claveCache, guardarCache, leerCache } from '../../api/cache';
import cliente from '../../api/cliente';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import BarrasRanking from '../../componentes/graficos/BarrasRanking';
import SerieTemporal from '../../componentes/graficos/SerieTemporal';
import Tarjeta from '../../componentes/graficos/Tarjeta';

export default function Dashboard() {
    const llave = claveCache('/estadisticas');
    const [datos, setDatos] = useState(() => leerCache(llave));
    const [aviso, setAviso] = useState(null);

    useEffect(() => {
        let vigente = true;

        cliente
            .get('/estadisticas')
            .then(({ data }) => {
                guardarCache(llave, data);
                if (vigente) setDatos(data);
            })
            .catch((e) => vigente && !leerCache(llave) && setAviso(e.message));

        return () => {
            vigente = false;
        };
    }, [llave]);

    if (aviso) {
        return (
            <p role="alert" className="superficie border-(--color-error) p-4 text-sm text-(--color-error)">
                {aviso}
            </p>
        );
    }

    if (!datos) {
        return <Cargador texto="Cargando" />;
    }

    const { negocio, acceso } = datos;

    return (
        <div className="space-y-10">
            <section>
                <EncabezadoPagina
                    grande
                    titulo="Estadísticas del negocio"
                    subtitulo="Resumen académico, matrículas, ocupación de grupos e ingresos registrados."
                />

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {negocio.tarjetas.map((t) => (
                        <Tarjeta key={t.clave} etiqueta={t.etiqueta} valor={t.valor} detalle={t.detalle} />
                    ))}
                </div>

                <div className="mt-4 grid gap-4 lg:grid-cols-2">
                    <BarrasRanking titulo="Matrículas por estado" datos={negocio.matriculas_por_estado} />
                    <BarrasRanking
                        titulo="Matrículas por origen"
                        descripcion="Cuántas entraron por el CRM y cuántas se registraron en caja."
                        datos={negocio.matriculas_por_origen}
                    />
                    <BarrasRanking titulo="Grupos por modalidad" datos={negocio.grupos_por_modalidad} />
                    <BarrasRanking titulo="Catálogo por estado comercial" datos={negocio.catalogo_por_estado} />
                </div>

                <div className="mt-4">
                    <BarrasRanking
                        titulo="Ocupación de grupos"
                        descripcion="Inscritos activos frente al cupo mínimo. La línea marca el quórum."
                        datos={negocio.ocupacion_grupos}
                        meta
                    />
                </div>
            </section>

            <section>
                <div className="mb-4">
                    <h2 className="text-2xl font-semibold">Flujo de accesos al sitio</h2>
                    <p className="text-sm text-(--color-texto-suave)">
                        Métricas de tráfico, recursos consultados y auditoría de inicios de sesión.
                    </p>
                </div>

                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {acceso.tarjetas.map((t) => (
                        <Tarjeta key={t.clave} etiqueta={t.etiqueta} valor={t.valor} detalle={t.detalle} />
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
                    <BarrasRanking titulo="Recursos con mayor índice de acceso" datos={acceso.recursos_mas_accedidos} />
                    <BarrasRanking titulo="Páginas más visitadas" datos={acceso.paginas_mas_visitadas} />
                </div>
            </section>
        </div>
    );
}
