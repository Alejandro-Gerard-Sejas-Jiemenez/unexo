import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import { useToast } from '../../componentes/ToastContext';
import ZonaArchivo from '../../componentes/ZonaArchivo';

const VACIO = {
    codigo: '',
    nombre: '',
    version: '1.0',
    descripcion: '',
    estado_comercial: 'borrador',
    precio: '',
    requisitos_ingreso: '',
    certificado_otorga: '',
};

export default function ProgramaFormulario() {
    const { id } = useParams();
    const navegar = useNavigate();
    const { mostrar } = useToast();
    const editando = Boolean(id);

    const [datos, setDatos] = useState(VACIO);
    const [portadaActual, setPortadaActual] = useState(null);
    const [archivoPortada, setArchivoPortada] = useState(null);
    const [opciones, setOpciones] = useState({ estados: [], certificados: [], limites: {} });
    const [errores, setErrores] = useState({});
    const [aviso, setAviso] = useState(null);
    const [cargando, setCargando] = useState(true);
    const [enviando, setEnviando] = useState(false);

    const cargar = useCallback(async () => {
        try {
            const { data: op } = await cliente.get('/catalogo/opciones');
            setOpciones(op);

            if (editando) {
                const { data } = await cliente.get(`/catalogo/programas/${id}`);
                const p = data.data;
                setDatos({
                    ...VACIO,
                    ...Object.fromEntries(Object.keys(VACIO).map((c) => [c, p[c] ?? ''])),
                });
                setPortadaActual(p.imagen_portada_url ?? null);
            }
        } catch (error) {
            setAviso(error.message);
        } finally {
            setCargando(false);
        }
    }, [editando, id]);

    useEffect(() => {
        cargar();
    }, [cargar]);

    const cambiar = (campo) => (e) => setDatos((p) => ({ ...p, [campo]: e.target.value }));

    async function enviar(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            const fd = new FormData();
            Object.entries(datos).forEach(([k, v]) => fd.append(k, v ?? ''));
            if (archivoPortada) {
                fd.append('imagen_portada_url', archivoPortada);
            }

            if (editando) {
                fd.append('_method', 'PUT');
                const { data } = await cliente.post(`/catalogo/programas/${id}`, fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                mostrar(data.mensaje);
                navegar(`/programas/${id}/ver`);
            } else {
                const { data } = await cliente.post('/catalogo/programas', fd, {
                    headers: { 'Content-Type': 'multipart/form-data' },
                });
                mostrar(data.mensaje);
                navegar(`/programas/${data.programa.id}/ver`);
            }
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

    const limites = opciones.limites?.programas ?? {};

    return (
        <section>
            <EncabezadoPagina
                volverA="/programas"
                volverTexto="Volver a programas"
                titulo={editando ? 'Editar programa' : 'Nuevo programa'}
                acciones={
                    editando && (
                        <Link to={`/programas/${id}/ver`} className="btn btn-secundario">
                            <Icono nombre="modulos" className="h-4 w-4" />
                            Plan de módulos
                        </Link>
                    )
                }
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            <div className="superficie max-w-3xl overflow-hidden rounded-xl shadow-sm">
                <form onSubmit={enviar} noValidate className="p-6 sm:p-8">
                    <div className="mb-8">
                        <h2 className="mb-4 border-b border-[var(--color-borde)] pb-2 text-lg font-semibold">
                            1. Datos Generales y Académicos
                        </h2>
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

                            <Campo id="version" etiqueta="Versión" error={errores.version} requerido>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="text"
                                        maxLength={limites.version}
                                        className={claseControl}
                                        value={datos.version}
                                        onChange={cambiar('version')}
                                    />
                                )}
                            </Campo>
                        </div>

                        <Campo id="nombre" etiqueta="Nombre" error={errores.nombre} requerido>
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

                        <Campo id="certificado_otorga" etiqueta="Certificado que otorga" error={errores.certificado_otorga} esSelect>
                            {(p) => (
                                <select
                                    {...p}
                                    className={claseControl}
                                    value={datos.certificado_otorga}
                                    onChange={cambiar('certificado_otorga')}
                                >
                                    <option value="">— No definido —</option>
                                    {opciones.certificados.map((c) => (
                                        <option key={c.valor} value={c.valor}>
                                            {c.etiqueta}
                                        </option>
                                    ))}
                                </select>
                            )}
                        </Campo>
                    </div>

                    <div className="mb-8">
                        <h2 className="mb-4 border-b border-[var(--color-borde)] pb-2 text-lg font-semibold">
                            2. Contenido y Difusión
                        </h2>

                        <ZonaArchivo
                            etiqueta="Imagen de portada"
                            ayuda="JPG, PNG o WebP de hasta 2 MB."
                            urlActual={portadaActual}
                            archivo={archivoPortada}
                            alCambiar={setArchivoPortada}
                            error={errores.imagen_portada_url}
                        />

                        <Campo
                            id="descripcion"
                            etiqueta="Descripción breve"
                            error={errores.descripcion}
                            conteo={datos.descripcion.length}
                            maximo={limites.descripcion ?? 2000}
                        >
                            {(p) => (
                                <textarea
                                    {...p}
                                    rows={3}
                                    maxLength={limites.descripcion}
                                    className={claseControl}
                                    value={datos.descripcion}
                                    onChange={cambiar('descripcion')}
                                />
                            )}
                        </Campo>

                        <Campo
                            id="requisitos_ingreso"
                            etiqueta="Conocimientos previos recomendados"
                            error={errores.requisitos_ingreso}
                        >
                            {(p) => (
                                <textarea
                                    {...p}
                                    rows={2}
                                    className={claseControl}
                                    value={datos.requisitos_ingreso}
                                    onChange={cambiar('requisitos_ingreso')}
                                />
                            )}
                        </Campo>
                    </div>

                    <div className="mb-8">
                        <h2 className="mb-4 border-b border-[var(--color-borde)] pb-2 text-lg font-semibold">
                            3. Configuración Comercial y API
                        </h2>
                        <div className="grid grid-cols-1 gap-x-6 gap-y-2 sm:grid-cols-2">
                            <Campo id="precio" etiqueta="Precio base (Bs)" error={errores.precio}>
                                {(p) => (
                                    <input
                                        {...p}
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        className={claseControl}
                                        value={datos.precio}
                                        onChange={cambiar('precio')}
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
                    </div>

                    <div className="form-acciones">
                        <button
                            type="button"
                            onClick={() => navegar('/programas')}
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
