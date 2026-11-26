import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';

const VACIO = { nombre: '', capacidad: '' };

export default function AulaFormulario() {
    const { id } = useParams();
    const editando = Boolean(id);
    const navegar = useNavigate();
    const { mostrar } = useToast();

    const [datos, setDatos] = useState(VACIO);
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(editando);
    const [noDisponible, setNoDisponible] = useState(false);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        if (!editando) return undefined;
        let vigente = true;

        cliente
            .get(`/aulas/${id}`)
            .then(({ data }) => {
                if (vigente) setDatos({ nombre: data.data.nombre, capacidad: data.data.capacidad ?? '' });
            })
            .catch((error) => {
                if (!vigente) return;
                setAviso(error.message);
                // Sin los datos no se muestra el formulario: guardarlo vacío los pisaría.
                setNoDisponible(true);
            })
            .finally(() => vigente && setCargando(false));

        return () => {
            vigente = false;
        };
    }, [editando, id]);

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            const { data } = editando
                ? await cliente.put(`/aulas/${id}`, datos)
                : await cliente.post('/aulas', datos);
            mostrar(data.mensaje);
            navegar('/aulas');
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

    if (cargando) return <Cargador texto="Cargando" />;

    return (
        <section>
            <EncabezadoPagina
                volverA="/aulas"
                volverTexto="Volver a aulas"
                titulo={editando ? 'Editar aula' : 'Nueva aula'}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {!noDisponible && (
                <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                    <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                        <div className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
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

                        <div className="form-acciones">
                            <button type="button" onClick={() => navegar('/aulas')} className="btn btn-secundario">
                                Cancelar
                            </button>
                            <button type="submit" disabled={enviando} className="btn btn-primario">
                                <Icono nombre="guardar" />
                                {enviando ? 'Guardando…' : 'Guardar'}
                            </button>
                        </div>
                    </form>
                </div>
            )}
        </section>
    );
}
