import { useState } from 'react';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import BotonAccion from '../../componentes/BotonAccion';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';
import useListado from '../../hooks/useListado';

export default function Aulas() {
    const { mostrar } = useToast();
    const { filas: aulas, meta, cargando, aviso, setAviso, irAPagina, cambiarTamano, refrescar } = useListado('/aulas');

    const [editando, setEditando] = useState(null);
    const [datos, setDatos] = useState({ nombre: '', capacidad: '' });
    const [errores, setErrores] = useState({});
    const [enviando, setEnviando] = useState(false);

    function editar(aula) {
        setEditando(aula.id);
        setDatos({ nombre: aula.nombre, capacidad: aula.capacidad ?? '' });
        setErrores({});
    }

    function cancelar() {
        setEditando(null);
        setDatos({ nombre: '', capacidad: '' });
        setErrores({});
    }

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            if (editando) {
                const { data } = await cliente.put(`/aulas/${editando}`, datos);
                mostrar(data.mensaje);
            } else {
                const { data } = await cliente.post('/aulas', datos);
                mostrar(data.mensaje);
            }
            cancelar();
            await refrescar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])));
                setAviso('Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <section>
            <EncabezadoPagina
                grande
                titulo="Aulas"
                subtitulo="Espacios físicos que se asignan a los grupos presenciales."
            />

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            <div className="superficie mb-10 max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <div className="border-b border-(--color-borde) bg-(--color-superficie-cabecera) px-6 py-5">
                    <h2 className="text-lg font-semibold">
                        {editando ? 'Editar aula' : 'Registrar un aula'}
                    </h2>
                </div>

                <form onSubmit={enviar} noValidate className="p-6">
                    <div className="mb-2 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} requerido>
                            {(p) => (
                                <input
                                    {...p}
                                    type="text"
                                    maxLength={80}
                                    placeholder="Ej. Aula 101"
                                    className={claseControl}
                                    value={datos.nombre}
                                    onChange={(e) => setDatos((d) => ({ ...d, nombre: e.target.value }))}
                                />
                            )}
                        </Campo>

                        <Campo id="capacidad" etiqueta="Capacidad" error={errores.capacidad} ayuda="Opcional">
                            {(p) => (
                                <input
                                    {...p}
                                    type="number"
                                    step="1"
                                    min="1"
                                    max="1000"
                                    className={claseControl}
                                    value={datos.capacidad}
                                    onChange={(e) => setDatos((d) => ({ ...d, capacidad: e.target.value }))}
                                />
                            )}
                        </Campo>
                    </div>

                    <div className="mt-4 flex justify-end gap-3 border-t border-(--color-borde) pt-4">
                        {editando && (
                            <button type="button" onClick={cancelar} className="btn btn-secundario">
                                Cancelar
                            </button>
                        )}
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre={editando ? 'guardar' : 'mas'} className="h-4 w-4" />
                            {enviando ? 'Guardando…' : editando ? 'Guardar cambios' : 'Registrar aula'}
                        </button>
                    </div>
                </form>
            </div>

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Aulas registradas"
                columnas={[
                    { etiqueta: 'Nombre' },
                    { etiqueta: 'Capacidad' },
                    { etiqueta: 'Grupos' },
                    { etiqueta: 'Acciones', alinear: 'derecha' },
                ]}
                vacio={!cargando && aulas.length === 0}
                vacioIcono="catalogo"
                vacioTitulo="No hay aulas registradas"
                vacioDescripcion="Comienza registrando la primera aula usando el formulario de arriba."
            >
                {aulas.map((a) => (
                    <tr key={a.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                        <td className="px-6 py-4 text-sm font-medium">{a.nombre}</td>
                        <td className="px-6 py-4 text-sm text-(--color-texto-suave) tabular-nums">
                            {a.capacidad ?? '—'}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex items-center rounded-full bg-(--color-superficie-2) px-2.5 py-0.5 text-xs font-medium">
                                {a.grupos_count ?? 0} grupos
                            </span>
                        </td>
                        <td className="px-6 py-4 text-right whitespace-nowrap">
                            <BotonAccion onClick={() => editar(a)} icono="editar">
                                Editar
                            </BotonAccion>
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} cambiarTamano={cambiarTamano} sustantivo="aulas" />
        </section>
    );
}
