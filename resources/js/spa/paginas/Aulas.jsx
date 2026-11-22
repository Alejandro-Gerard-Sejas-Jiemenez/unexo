import { useCallback, useEffect, useState } from 'react';
import cliente, { ErrorDeValidacion } from '../api/cliente';
import Campo, { claseControl } from '../componentes/Campo';

const VACIO = { nombre: '', capacidad: '' };

/** Aulas físicas que se asignan a los grupos presenciales o mixtos. */
export default function Aulas() {
    const [aulas, setAulas] = useState([]);
    const [limites, setLimites] = useState({});
    const [datos, setDatos] = useState(VACIO);
    const [editando, setEditando] = useState(null);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [exito, setExito] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargar = useCallback(async () => {
        try {
            const { data } = await cliente.get('/aulas');
            setAulas(data.data);
            setLimites(data.limites ?? {});
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, []);

    useEffect(() => {
        cargar();
    }, [cargar]);

    function editar(aula) {
        setEditando(aula.id);
        setDatos({ nombre: aula.nombre, capacidad: aula.capacidad ?? '' });
        setErrores({});
    }

    function cancelar() {
        setEditando(null);
        setDatos(VACIO);
        setErrores({});
    }

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setExito(null);
        setEnviando(true);

        try {
            const { data } = editando
                ? await cliente.put(`/aulas/${editando}`, datos)
                : await cliente.post('/aulas', datos);
            setExito(data.mensaje);
            cancelar();
            await cargar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    return (
        <section className="max-w-3xl">
            <h1 className="mb-1 text-2xl font-semibold">Aulas</h1>
            <p className="mb-6 text-sm text-[var(--color-texto-suave)]">
                Espacios físicos para los grupos presenciales o mixtos. La capacidad se
                compara con los inscritos al asignar el aula.
            </p>

            {aviso && <p role="alert" className="mb-4 text-sm text-[var(--color-error)]">{aviso}</p>}
            {exito && <p role="status" className="mb-4 text-sm text-[var(--color-exito)]">{exito}</p>}

            <div className="superficie mb-6 overflow-hidden">
                <table className="w-full border-collapse text-sm">
                    <caption className="sr-only">Aulas registradas</caption>
                    <thead>
                        <tr className="border-b border-[var(--color-borde)] text-left">
                            <th scope="col" className="p-3">Aula</th>
                            <th scope="col" className="p-3">Capacidad</th>
                            <th scope="col" className="p-3">Grupos</th>
                            <th scope="col" className="p-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cargando && (
                            <tr>
                                <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Cargando…
                                </td>
                            </tr>
                        )}
                        {!cargando && aulas.length === 0 && (
                            <tr>
                                <td colSpan={4} className="p-6 text-center text-[var(--color-texto-suave)]">
                                    Todavía no hay aulas registradas.
                                </td>
                            </tr>
                        )}
                        {aulas.map((a) => (
                            <tr key={a.id} className="border-b border-[var(--color-borde)]">
                                <td className="p-3">{a.nombre}</td>
                                <td className="p-3 tabular-nums">{a.capacidad ?? '—'}</td>
                                <td className="p-3 tabular-nums">{a.grupos_count}</td>
                                <td className="p-3">
                                    <button type="button" onClick={() => editar(a)}
                                        className="border-0 bg-transparent p-0 underline">
                                        Editar
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <form onSubmit={enviar} noValidate className="superficie p-4">
                <h2 className="mb-4 text-lg font-semibold">
                    {editando ? 'Editar aula' : 'Registrar un aula'}
                </h2>

                <div className="grid grid-cols-2 gap-4">
                    <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} requerido>
                        {(p) => (
                            <input {...p} type="text" maxLength={limites.nombre} className={claseControl}
                                value={datos.nombre}
                                onChange={(e) => setDatos((d) => ({ ...d, nombre: e.target.value }))} />
                        )}
                    </Campo>
                    <Campo id="capacidad" etiqueta="Capacidad" error={errores.capacidad}>
                        {(p) => (
                            <input {...p} type="number" min="1" max={limites.capacidad}
                                className={claseControl} value={datos.capacidad}
                                onChange={(e) => setDatos((d) => ({ ...d, capacidad: e.target.value }))} />
                        )}
                    </Campo>
                </div>

                <div className="flex gap-3">
                    <button type="submit" disabled={enviando}
                        className="rounded-lg bg-[var(--color-acento)] px-4 py-2 text-sm font-medium text-[var(--color-acento-texto)] disabled:opacity-60">
                        {enviando ? 'Guardando…' : 'Guardar'}
                    </button>
                    {editando && (
                        <button type="button" onClick={cancelar} className="superficie px-4 py-2 text-sm">
                            Cancelar
                        </button>
                    )}
                </div>
            </form>
        </section>
    );
}
