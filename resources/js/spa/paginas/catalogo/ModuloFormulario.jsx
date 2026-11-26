import { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';

const VACIO = {
    codigo: '',
    nombre: '',
    descripcion: '',
    prerrequisito: '',
    duracion_horas: '',
    estado_comercial: 'borrador',
    pdf_temario_url: '',
};

export default function ModuloFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const { mostrar } = useToast();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [opciones, setOpciones] = useState({ estados: [], limites: {} });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const peticiones = [cliente.get('/catalogo/opciones')];
                if (editando) peticiones.push(cliente.get(`/catalogo/modulos/${id}`));

                const [resOpciones, resModulo] = await Promise.all(peticiones);
                if (!vigente) return;

                setOpciones(resOpciones.data);

                if (resModulo) {
                    const m = resModulo.data.data;
                    setDatos({
                        ...VACIO,
                        ...Object.fromEntries(Object.keys(VACIO).map((c) => [c, m[c] ?? ''])),
                    });
                }
            } catch (error) {
                if (vigente) setAviso(error.message);
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [editando, id]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            if (editando) {
                const { data } = await cliente.put(`/catalogo/modulos/${id}`, datos);
                mostrar(data.mensaje);
            } else {
                const { data } = await cliente.post('/catalogo/modulos', datos);
                mostrar(data.mensaje);
            }
            navegar('/modulos');
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(
                    Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])),
                );
                setAviso('Revisa los campos marcados.');
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    if (cargando) return <Cargador texto="Cargando" />;

    const limites = opciones.limites?.modulos ?? {};

    return (
        <section>
            <EncabezadoPagina
                volverA="/modulos"
                volverTexto="Volver a módulos"
                titulo={editando ? 'Editar módulo' : 'Nuevo módulo'}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                    <div className="mb-2 grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                        <Campo id="codigo" etiqueta="Código" error={errores.codigo} icono="hash" requerido>
                            {(p) => (
                                <input
                                    {...p}
                                    type="text"
                                    maxLength={limites.codigo}
                                    className={claseControl}
                                    value={datos.codigo}
                                    onChange={cambiar('codigo')}
                                />
                            )}
                        </Campo>

                        <Campo id="duracion_horas" etiqueta="Duración (horas)" error={errores.duracion_horas}>
                            {(p) => (
                                <input
                                    {...p}
                                    type="number"
                                    min="1"
                                    max={limites.duracion_horas}
                                    className={claseControl}
                                    value={datos.duracion_horas}
                                    onChange={cambiar('duracion_horas')}
                                />
                            )}
                        </Campo>
                    </div>

                    <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} icono="modulos" requerido>
                        {(p) => (
                            <input
                                {...p}
                                type="text"
                                maxLength={limites.nombre}
                                className={claseControl}
                                value={datos.nombre}
                                onChange={cambiar('nombre')}
                            />
                        )}
                    </Campo>

                    <Campo id="descripcion" etiqueta="Descripción" error={errores.descripcion}>
                        {(p) => (
                            <textarea
                                {...p}
                                rows={3}
                                className={claseControl}
                                value={datos.descripcion}
                                onChange={cambiar('descripcion')}
                            />
                        )}
                    </Campo>

                    <Campo
                        id="prerrequisito"
                        etiqueta="Prerrequisito (conocimientos previos)"
                        error={errores.prerrequisito}
                        ayuda="Opcional. Solo informativo: no impide la matrícula."
                        conteo={datos.prerrequisito.length}
                        maximo={limites.prerrequisito ?? 500}
                    >
                        {(p) => (
                            <textarea
                                {...p}
                                rows={2}
                                maxLength={limites.prerrequisito}
                                className={claseControl}
                                value={datos.prerrequisito}
                                onChange={cambiar('prerrequisito')}
                            />
                        )}
                    </Campo>

                    <div className="mt-4 mb-2 grid grid-cols-1 gap-x-6 gap-y-2 border-t border-[var(--color-borde)] pt-4 sm:grid-cols-2">
                        <Campo
                            id="pdf_temario_url"
                            etiqueta="URL del PDF del Temario"
                            error={errores.pdf_temario_url}
                            icono="enlace"
                        >
                            {(p) => (
                                <input
                                    {...p}
                                    type="url"
                                    maxLength={255}
                                    className={claseControl}
                                    value={datos.pdf_temario_url}
                                    onChange={cambiar('pdf_temario_url')}
                                />
                            )}
                        </Campo>

                        <Campo
                            id="estado_comercial"
                            etiqueta="Estado comercial"
                            error={errores.estado_comercial}
                            esSelect
                            requerido
                        >
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    value={datos.estado_comercial}
                                    onChange={cambiar('estado_comercial')}
                                >
                                    {opciones.estados.map((e) => (
                                        <option key={e.valor} value={e.valor}>
                                            {e.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <div className="form-acciones">
                        <button
                            type="button"
                            onClick={() => navegar('/modulos')}
                            className="btn btn-secundario"
                        >
                            Cancelar
                        </button>
                        <button type="submit" disabled={enviando} className="btn btn-primario">
                            <Icono nombre="guardar" />
                            {enviando ? 'Guardando…' : 'Guardar'}
                        </button>
                    </div>
                </form>
            </div>
        </section>
    );
}
