import { useEffect, useState } from 'react';
import { claseControl } from '../componentes/Campo';
import Cargador from '../componentes/Cargador';
import EncabezadoPagina from '../componentes/EncabezadoPagina';
import Insignia, { tonoEstado } from '../componentes/Insignia';
import Paginacion from '../componentes/Paginacion';
import TablaDatos from '../componentes/TablaDatos';
import useListado from '../hooks/useListado';

const EVENTOS = [
    { valor: '', etiqueta: 'Todos los eventos' },
    { valor: 'login_exitoso', etiqueta: 'Inicio de sesión' },
    { valor: 'login_fallido', etiqueta: 'Intento fallido' },
    { valor: 'logout', etiqueta: 'Cierre de sesión' },
    { valor: 'acceso_recurso', etiqueta: 'Acceso a recurso' },
];

export default function Bitacora() {
    const {
        filas,
        meta,
        cargando,
        aviso,
        valorDe,
        filtrar,
        filtrarConRetraso,
        limpiarFiltros,
        irAPagina,
    } = useListado('/bitacora');

    const [busqueda, setBusqueda] = useState(valorDe('q'));

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    const hayFiltros = Boolean(valorDe('q') || valorDe('evento') || valorDe('desde') || valorDe('hasta'));

    return (
        <section>
            <EncabezadoPagina
                titulo="Bitácora de auditoría"
                subtitulo="Registro persistente de inicios de sesión, intentos fallidos, IPs y accesos a los recursos."
            />

            <div className="superficie mb-6 flex flex-wrap items-end gap-3 rounded-xl p-4 shadow-sm">
                <label className="min-w-[220px] flex-1">
                    <span className="mb-1 block text-xs font-medium text-[var(--color-texto-suave)]">
                        Buscar
                    </span>
                    <input
                        type="search"
                        placeholder="Recurso, IP o correo"
                        className={claseControl}
                        value={busqueda}
                        onChange={(e) => {
                            setBusqueda(e.target.value);
                            filtrarConRetraso('q', e.target.value);
                        }}
                    />
                </label>

                <label className="min-w-[180px]">
                    <span className="mb-1 block text-xs font-medium text-[var(--color-texto-suave)]">
                        Evento
                    </span>
                    <select
                        className={claseControl}
                        value={valorDe('evento')}
                        onChange={(e) => filtrar('evento', e.target.value)}
                    >
                        {EVENTOS.map((e) => (
                            <option key={e.valor} value={e.valor}>
                                {e.etiqueta}
                            </option>
                        ))}
                    </select>
                </label>

                <label>
                    <span className="mb-1 block text-xs font-medium text-[var(--color-texto-suave)]">
                        Desde
                    </span>
                    <input
                        type="date"
                        className={claseControl}
                        value={valorDe('desde')}
                        onChange={(e) => filtrar('desde', e.target.value)}
                    />
                </label>

                <label>
                    <span className="mb-1 block text-xs font-medium text-[var(--color-texto-suave)]">
                        Hasta
                    </span>
                    <input
                        type="date"
                        className={claseControl}
                        value={valorDe('hasta')}
                        onChange={(e) => filtrar('hasta', e.target.value)}
                    />
                </label>

                {hayFiltros && (
                    <button type="button" onClick={limpiarFiltros} className="btn btn-secundario !min-h-9 !px-3 text-xs">
                        Quitar filtros
                    </button>
                )}
            </div>

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Eventos registrados en la bitácora"
                columnas={[
                    { etiqueta: 'Fecha' },
                    { etiqueta: 'Evento' },
                    { etiqueta: 'Usuario' },
                    { etiqueta: 'Recurso' },
                    { etiqueta: 'IP' },
                ]}
                vacio={!cargando && filas.length === 0}
                vacioIcono="candado"
                vacioTitulo="No hay eventos registrados"
                vacioDescripcion="No existen eventos que coincidan con el filtro aplicado."
            >
                {filas.map((fila) => (
                    <tr key={fila.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                        <td className="px-6 py-4 text-sm whitespace-nowrap tabular-nums">{fila.fecha}</td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <Insignia tono={tonoEstado(fila.evento)}>{fila.evento_label}</Insignia>
                        </td>
                        <td className="px-6 py-4 text-sm">{fila.usuario}</td>
                        <td className="px-6 py-4 text-xs break-all text-[var(--color-texto-suave)]">{fila.recurso}</td>
                        <td className="px-6 py-4 text-sm whitespace-nowrap tabular-nums">{fila.ip}</td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="eventos" />
        </section>
    );
}
