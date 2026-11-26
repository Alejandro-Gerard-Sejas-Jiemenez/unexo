import { conBase } from '../base';

export default function Cargador({
    texto = 'Cargando',
    pantallaCompleta = false,
    enContenido = true,
}) {
    if (!pantallaCompleta && !enContenido) {
        return (
            <div className="flex items-center justify-center gap-3 py-8 text-sm text-[var(--color-texto-suave)]" role="status">
                <div className="cargando-marca !h-8 !w-8">
                    <div className="cargando-anillo" />
                    <img src={conBase('/images/logo_v2.png')} alt="" className="h-4 w-4 object-contain" />
                </div>
                <span className="text-xs font-semibold tracking-[0.18em] uppercase">{texto}</span>
            </div>
        );
    }

    const clasePantalla = pantallaCompleta ? 'cargando-pantalla' : '';

    return (
        <>
            <div className={`cargando-barra ${clasePantalla}`} aria-hidden="true" />
            <div className={`cargando ${clasePantalla}`} role="status" aria-live="polite">
                <div className="flex flex-col items-center">
                    <div className="cargando-marca">
                        <div className="cargando-anillo" />
                        <img
                            src={conBase('/images/logo_v2.png')}
                            alt=""
                            className="cargando-logo"
                        />
                    </div>
                    <p className="cargando-texto">{texto}</p>
                </div>
            </div>
        </>
    );
}
