import Icono from './Icono';

export default function BarraHerramientas({
    pildoras = [],
    pildoraActiva = '',
    alSeleccionarPildora,
    extraIzquierda = null,
    busqueda = '',
    alCambiarBusqueda,
    alBuscar,
    alLimpiarBusqueda,
    placeholder = 'Buscar…',
    mostrarBotonBuscar = true,
}) {
    return (
        <div className="superficie mb-6 flex flex-col justify-between gap-4 rounded-xl p-4 shadow-sm lg:flex-row lg:items-center">
            <div className="flex flex-wrap items-center gap-2">
                {pildoras.map((p) => {
                    const activo = typeof p.activo === 'boolean' ? p.activo : pildoraActiva === p.valor;
                    return (
                        <button
                            key={p.clave ?? `${p.valor}-${p.etiqueta}`}
                            type="button"
                            onClick={() => (p.onClick ? p.onClick() : alSeleccionarPildora?.(p.valor))}
                            className={
                                'cursor-pointer rounded-full px-4 py-1.5 text-sm font-medium transition-colors ' +
                                (activo
                                    ? 'bg-[var(--color-acento-suave)] text-[var(--color-acento)] ring-1 ring-[var(--color-acento)]/30 ring-inset'
                                    : 'bg-[var(--color-superficie-2)] text-[var(--color-texto-suave)] hover:text-[var(--color-texto)]')
                            }
                        >
                            {p.etiqueta}
                        </button>
                    );
                })}
                {extraIzquierda}
            </div>

            {alCambiarBusqueda && (
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        alBuscar?.(busqueda);
                    }}
                    className="flex w-full items-center gap-2 lg:w-auto lg:min-w-[300px]"
                >
                    <div className="relative flex-1">
                        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-[var(--color-texto-suave)]">
                            <Icono nombre="buscar" className="h-4 w-4" />
                        </span>
                        <input
                            type="search"
                            value={busqueda}
                            onChange={(e) => alCambiarBusqueda(e.target.value)}
                            placeholder={placeholder}
                            aria-label={placeholder}
                            className="w-full rounded-lg border border-[var(--color-borde)] bg-[var(--color-superficie-cabecera)] py-2 pr-3 pl-9 text-sm text-[var(--color-texto)] transition-all focus:border-[var(--color-acento)] focus:bg-[var(--color-superficie)] focus:ring-2 focus:ring-[var(--color-realce)]/30 focus:outline-none"
                        />
                    </div>
                    {busqueda && alLimpiarBusqueda && (
                        <button
                            type="button"
                            onClick={alLimpiarBusqueda}
                            className="rounded-lg bg-[var(--color-superficie-2)] px-2.5 py-2 text-xs font-medium text-[var(--color-texto-suave)] transition-colors hover:text-[var(--color-texto)]"
                        >
                            Limpiar
                        </button>
                    )}
                    {mostrarBotonBuscar && (
                        <button type="submit" className="btn btn-primario !min-h-9 !px-4 text-sm">
                            Buscar
                        </button>
                    )}
                </form>
            )}
        </div>
    );
}
