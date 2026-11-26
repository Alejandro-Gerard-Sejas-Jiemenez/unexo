import Icono from './Icono';

export default function TablaDatos({
    columnas = [],
    vacio = false,
    vacioIcono = 'documento',
    vacioTitulo = 'No se encontraron registros',
    vacioDescripcion = 'Intenta cambiar los filtros de búsqueda.',
    vacioAccion = null,
    caption = null,
    compacto = false,
    children,
}) {
    const paddingCelda = compacto ? 'px-4 py-3.5' : 'px-6 py-4';
    if (vacio) {
        return (
            <div className="superficie rounded-xl p-12 text-center shadow-sm">
                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-(--color-superficie-2) text-(--color-texto-suave)">
                    <Icono nombre={vacioIcono} className="h-6 w-6" />
                </div>
                <h3 className="text-sm font-semibold">{vacioTitulo}</h3>
                <p className="mt-1 text-sm text-(--color-texto-suave)">{vacioDescripcion}</p>
                {vacioAccion && <div className="mt-4">{vacioAccion}</div>}
            </div>
        );
    }

    return (
        <div className="superficie min-w-0 overflow-hidden rounded-xl shadow-sm">
            <div className="overflow-x-auto">
                <table className="w-full divide-y divide-(--color-borde)">
                    {caption && <caption className="sr-only">{caption}</caption>}
                    <thead className="bg-(--color-superficie-cabecera)">
                        <tr>
                            {columnas.map((col, idx) => (
                                <th
                                    key={col.etiqueta || idx}
                                    scope="col"
                                    className={
                                        `${paddingCelda} text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase ` +
                                        (col.alinear === 'derecha' ? 'text-right' : 'text-left')
                                    }
                                >
                                    {col.srOnly ? <span className="sr-only">{col.etiqueta}</span> : col.etiqueta}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-(--color-borde) bg-(--color-superficie)">
                        {children}
                    </tbody>
                </table>
            </div>
        </div>
    );
}
