import { useEffect, useId, useRef, useState } from 'react';
import { conBase } from '../base';
import Icono from './Icono';

export default function ZonaArchivo({
    etiqueta,
    ayuda,
    accept = 'image/jpeg,image/png,image/webp',
    maxMb = 2,
    esImagen = true,
    urlActual = null,
    archivo,
    alCambiar,
    error = null,
}) {
    const id = useId();
    const inputRef = useRef(null);
    const [arrastrando, setArrastrando] = useState(false);
    const [errorLocal, setErrorLocal] = useState(null);
    const [vistaPrevia, setVistaPrevia] = useState(null);

    useEffect(() => {
        if (!archivo || !esImagen || !archivo.type.startsWith('image/')) {
            setVistaPrevia(null);
            return undefined;
        }
        const url = URL.createObjectURL(archivo);
        setVistaPrevia(url);
        return () => URL.revokeObjectURL(url);
    }, [archivo, esImagen]);

    const tiposPermitidos = accept.split(',').map((t) => t.trim().toLowerCase());
    const MIME_EXTENSIONES = {
        'image/jpeg': ['.jpg', '.jpeg'],
        'image/png': ['.png'],
        'image/webp': ['.webp'],
        'application/pdf': ['.pdf'],
    };

    function validarYSeleccionar(lista) {
        const seleccionado = lista?.[0];
        if (!seleccionado) return;

        setErrorLocal(null);
        const nombre = seleccionado.name.toLowerCase();
        const tipoMime = (seleccionado.type || '').toLowerCase();
        const tipoOk =
            (tipoMime && tiposPermitidos.includes(tipoMime)) ||
            tiposPermitidos.some((t) => {
                if (t.startsWith('.')) return nombre.endsWith(t);
                return (MIME_EXTENSIONES[t] ?? []).some((ext) => nombre.endsWith(ext));
            });

        if (!tipoOk) {
            setErrorLocal(`Formato no permitido. Usa: ${accept}`);
            return;
        }
        if (seleccionado.size > maxMb * 1024 * 1024) {
            setErrorLocal(`El archivo supera el máximo permitido de ${maxMb} MB.`);
            return;
        }

        alCambiar(seleccionado);
    }

    const imagenMostrar = vistaPrevia || (esImagen && urlActual ? conBase(urlActual) : null);
    const mensajeError = error || errorLocal;

    return (
        <div className="campo">
            <label htmlFor={id} className="campo-etiqueta">
                {etiqueta}
            </label>

            <div
                className={`zona-archivo ${arrastrando ? 'arrastrando' : ''}`}
                onDragOver={(e) => {
                    e.preventDefault();
                    setArrastrando(true);
                }}
                onDragLeave={() => setArrastrando(false)}
                onDrop={(e) => {
                    e.preventDefault();
                    setArrastrando(false);
                    validarYSeleccionar(e.dataTransfer.files);
                }}
            >
                {imagenMostrar && (
                    <img
                        src={imagenMostrar}
                        alt="Vista previa"
                        className="max-h-44 max-w-full rounded-lg border border-(--color-borde) object-cover"
                    />
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <input
                        ref={inputRef}
                        id={id}
                        type="file"
                        accept={accept}
                        className="sr-only"
                        onChange={(e) => validarYSeleccionar(e.target.files)}
                    />
                    <button
                        type="button"
                        onClick={() => inputRef.current?.click()}
                        className="btn btn-secundario text-xs"
                    >
                        <Icono nombre="subir-archivo" className="h-4 w-4" />
                        {archivo ? 'Cambiar archivo' : 'Elegir o arrastrar archivo'}
                    </button>
                    {archivo && (
                        <button
                            type="button"
                            onClick={() => {
                                alCambiar(null);
                                if (inputRef.current) inputRef.current.value = '';
                            }}
                            className="btn btn-secundario text-xs"
                        >
                            Quitar selección
                        </button>
                    )}
                </div>

                {archivo ? (
                    <p className="text-xs font-medium text-(--color-texto)">
                        Archivo seleccionado: {archivo.name} ({Math.round(archivo.size / 1024)} KB)
                    </p>
                ) : (
                    ayuda && <p className="text-xs text-(--color-texto-suave)">{ayuda}</p>
                )}
            </div>

            {mensajeError && (
                <p role="alert" className="campo-error">
                    <Icono nombre="alerta" className="h-4 w-4 shrink-0" />
                    <span>{mensajeError}</span>
                </p>
            )}
        </div>
    );
}
