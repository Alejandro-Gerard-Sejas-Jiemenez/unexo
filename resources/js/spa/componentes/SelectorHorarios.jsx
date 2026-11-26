import { DIAS_CORTOS, NOMBRES_DIA } from '../lib/horarios';
import { claseControl } from './Campo';
import Icono from './Icono';

const describir = (b) => `${b.dias.map((d) => DIAS_CORTOS[d]).join(' · ')}  ${b.hora_inicio}–${b.hora_fin}`;

/** Arma un bloque: varios días con una misma hora. No guarda nada por sí solo. */
export default function SelectorHorarios({ bloque, alCambiar, alAgregar, frecuentes = [], error }) {
    function alternarDia(dia) {
        const dias = bloque.dias.includes(dia) ? bloque.dias.filter((d) => d !== dia) : [...bloque.dias, dia];
        alCambiar({ ...bloque, dias });
    }

    return (
        <div className="space-y-4 rounded-xl border border-(--color-borde) p-4">
            {frecuentes.length > 0 && (
                <div>
                    <p className="mb-2 text-xs text-(--color-texto-suave)">
                        Horarios usados: al elegir uno se completan los días y las horas.
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {frecuentes.map((f) => (
                            <button
                                key={`${f.dias.join()}-${f.hora_inicio}-${f.hora_fin}`}
                                type="button"
                                onClick={() =>
                                    alCambiar({ dias: f.dias, hora_inicio: f.hora_inicio, hora_fin: f.hora_fin })
                                }
                                className="btn btn-secundario min-h-9! px-3! text-xs whitespace-pre"
                            >
                                {describir(f)}
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <div>
                <p id="bloque-dias" className="mb-2 text-xs text-(--color-texto-suave)">
                    Días
                </p>
                <div role="group" aria-labelledby="bloque-dias" className="flex flex-wrap gap-2">
                    {[1, 2, 3, 4, 5, 6, 7].map((dia) => {
                        const marcado = bloque.dias.includes(dia);

                        return (
                            <button
                                key={dia}
                                type="button"
                                aria-pressed={marcado}
                                aria-label={NOMBRES_DIA[dia]}
                                onClick={() => alternarDia(dia)}
                                className={`btn min-h-9! w-12 px-0! text-xs ${marcado ? 'btn-primario' : 'btn-secundario'}`}
                            >
                                {DIAS_CORTOS[dia]}
                            </button>
                        );
                    })}
                </div>
            </div>

            <div className="flex flex-wrap items-end gap-3">
                <label>
                    <span className="mb-1 block text-xs text-(--color-texto-suave)">Inicio</span>
                    <input
                        type="time"
                        className={claseControl}
                        value={bloque.hora_inicio}
                        onChange={(e) => alCambiar({ ...bloque, hora_inicio: e.target.value })}
                    />
                </label>
                <label>
                    <span className="mb-1 block text-xs text-(--color-texto-suave)">Fin</span>
                    <input
                        type="time"
                        className={claseControl}
                        value={bloque.hora_fin}
                        onChange={(e) => alCambiar({ ...bloque, hora_fin: e.target.value })}
                    />
                </label>
                <button type="button" onClick={alAgregar} className="btn btn-primario min-h-9! px-4! text-xs">
                    <Icono nombre="mas" className="h-4 w-4" />
                    Agregar
                </button>
            </div>

            {error && (
                <p role="alert" className="text-xs text-(--color-error)">
                    {error}
                </p>
            )}
        </div>
    );
}
