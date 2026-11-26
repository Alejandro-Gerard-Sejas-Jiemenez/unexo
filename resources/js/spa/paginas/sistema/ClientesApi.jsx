import { useState } from 'react';
import { Link } from 'react-router-dom';
import { conBase } from '../../base';
import cliente, { ErrorDeValidacion } from '../../api/cliente';
import Campo, { claseControl } from '../../componentes/Campo';
import Cargador from '../../componentes/Cargador';
import DialogoConfirmacion from '../../componentes/DialogoConfirmacion';
import Icono from '../../componentes/Icono';
import Insignia from '../../componentes/Insignia';
import Paginacion from '../../componentes/Paginacion';
import { useToast } from '../../componentes/ToastContext';
import useListado from '../../hooks/useListado';

export default function ClientesApi() {
    const { mostrar } = useToast();
    const { filas: clientes, meta, cargando, aviso, setAviso, irAPagina, cambiarTamano, refrescar } =
        useListado('/clientes-api');

    const [nombre, setNombre] = useState('');
    const [tokenNuevo, setTokenNuevo] = useState(null);
    const [copiado, setCopiado] = useState(false);
    const [confirmando, setConfirmando] = useState(null);
    const [errores, setErrores] = useState({});
    const [enviando, setEnviando] = useState(false);

    async function crear(e) {
        e.preventDefault();
        setErrores({});
        setAviso(null);
        setEnviando(true);

        try {
            const { data } = await cliente.post('/clientes-api', { nombre });
            setTokenNuevo(data.token);
            setCopiado(false);
            mostrar(data.mensaje);
            setNombre('');
            await refrescar();
        } catch (error) {
            if (error instanceof ErrorDeValidacion) {
                setErrores(Object.fromEntries(Object.entries(error.errores).map(([c, l]) => [c, l[0]])));
            } else {
                setAviso(error.message);
            }
        } finally {
            setEnviando(false);
        }
    }

    async function confirmarRevocacion() {
        if (!confirmando) return;
        const objetivo = confirmando;
        setConfirmando(null);
        setAviso(null);
        try {
            const { data } = await cliente.delete(`/clientes-api/${objetivo.id}`);
            mostrar(data.mensaje);
            await refrescar();
        } catch (error) {
            setAviso(error.message);
        }
    }

    async function copiarToken() {
        if (!tokenNuevo) return;
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(tokenNuevo);
        } else {
            const area = document.createElement('textarea');
            area.value = tokenNuevo;
            area.setAttribute('readonly', '');
            area.style.position = 'absolute';
            area.style.left = '-9999px';
            document.body.appendChild(area);
            area.select();
            document.execCommand('copy');
            document.body.removeChild(area);
        }
        setCopiado(true);
        mostrar('Token copiado al portapapeles.');
    }

    const baseApiV1 = `${window.location.origin}${conBase('/api/v1')}`;

    return (
        <section>
            <div className="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                <div>
                    <div className="flex items-center gap-2">
                        <span className="rounded-full bg-(--color-exito-fondo) px-2.5 py-0.5 text-xs font-semibold text-(--color-exito)">
                            Módulo de Seguridad
                        </span>
                        <span className="text-xs text-(--color-texto-suave)">REST API v1</span>
                    </div>
                    <h1 className="mt-1 text-3xl font-bold tracking-tight">Acceso API &amp; Integración CRM</h1>
                    <p className="mt-1 text-sm text-(--color-texto-suave)">
                        Genera credenciales seguras (tokens Bearer) y consulta la documentación de endpoints para que el CRM
                        consuma el catálogo de cursos y sincronice matrículas.
                    </p>
                </div>

                <div className="flex items-center gap-2">
                    <Link to="/catalogo" className="btn btn-secundario">
                        <Icono nombre="catalogo" className="h-4 w-4 text-(--color-acento)" />
                        Ver catálogo visual
                    </Link>
                </div>
            </div>

            {aviso && (
                <p role="alert" className="superficie mb-6 border-(--color-error) p-3 text-sm text-(--color-error)">
                    {aviso}
                </p>
            )}

            {tokenNuevo && (
                <div
                    role="alert"
                    className="mb-8 rounded-xl border border-[color-mix(in_srgb,var(--color-aviso)_45%,transparent)] bg-(--color-aviso-fondo) p-5 shadow-sm"
                >
                    <div className="flex items-start gap-3">
                        <div className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-[color-mix(in_srgb,var(--color-aviso)_20%,transparent)] text-(--color-aviso)">
                            <Icono nombre="candado" className="h-5 w-5" />
                        </div>
                        <div className="min-w-0 flex-1">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <h2 className="text-base font-bold text-(--color-aviso)">
                                    Token de acceso generado con éxito
                                </h2>
                                <button
                                    type="button"
                                    onClick={() => setTokenNuevo(null)}
                                    className="rounded border-0 bg-transparent px-2 py-1 text-xs font-semibold text-(--color-aviso) hover:underline"
                                >
                                    Cerrar
                                </button>
                            </div>
                            <p className="mt-0.5 mb-3 text-xs text-(--color-aviso)">
                                Copia este token ahora mismo. Por motivos de seguridad criptográfica,{' '}
                                <strong>no se volverá a mostrar</strong> una vez que salgas de esta pantalla.
                            </p>
                            <div className="flex max-w-2xl items-center gap-2">
                                <input
                                    type="text"
                                    readOnly
                                    value={tokenNuevo}
                                    onClick={(e) => e.currentTarget.select()}
                                    className="w-full rounded-lg border border-[color-mix(in_srgb,var(--color-aviso)_45%,transparent)] bg-(--color-superficie) p-2.5 font-mono text-xs select-all shadow-inner"
                                />
                                <button
                                    type="button"
                                    onClick={copiarToken}
                                    className="btn btn-primario min-h-9! shrink-0 px-4! text-xs"
                                >
                                    <Icono nombre={copiado ? 'check' : 'copiar'} className="h-4 w-4" />
                                    {copiado ? 'Copiado' : 'Copiar'}
                                </button>
                            </div>
                            <p className="mt-2 text-xs text-(--color-aviso)">
                                Uso en peticiones HTTP:{' '}
                                <code className="rounded bg-(--color-superficie)/80 px-1.5 py-0.5 font-mono text-[11px]">
                                    Authorization: Bearer {tokenNuevo}
                                </code>
                            </p>
                        </div>
                    </div>
                </div>
            )}

            <div className="mb-12 grid grid-cols-1 gap-8 lg:grid-cols-3">
                <div className="superficie flex flex-col justify-between rounded-xl p-6 shadow-sm lg:col-span-1">
                    <div>
                        <div className="mb-3 flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-md border border-(--color-borde) bg-(--color-acento-suave) text-(--color-acento)">
                                <Icono nombre="candado" className="h-4 w-4" />
                            </div>
                            <h2 className="text-base font-bold">Crear cliente API</h2>
                        </div>
                        <p className="mb-5 text-xs leading-relaxed text-(--color-texto-suave)">
                            Registra un nuevo sistema cliente (ej. <em>CRM Comercial</em>, <em>Landing Page</em>,{' '}
                            <em>Portal Web</em>) para otorgarle acceso a los endpoints de la API.
                        </p>

                        <form onSubmit={crear} noValidate className="space-y-4">
                            <Campo
                                id="nombre"
                                etiqueta="Nombre del cliente o sistema"
                                error={errores.nombre}
                                requerido
                            >
                                {(p) => (
                                    <input
                                        {...p}
                                        type="text"
                                        maxLength={80}
                                        placeholder="Ej. CRM Comercial HubSpot"
                                        className={claseControl}
                                        value={nombre}
                                        onChange={(e) => setNombre(e.target.value)}
                                    />
                                )}
                            </Campo>

                            <button
                                type="submit"
                                disabled={enviando || !nombre.trim()}
                                className="btn btn-primario w-full"
                            >
                                <Icono nombre="mas" className="h-4 w-4" />
                                {enviando ? 'Generando…' : 'Generar nuevo token'}
                            </button>
                        </form>
                    </div>

                    <div className="mt-6 border-t border-(--color-borde) pt-4 text-xs text-(--color-texto-suave)">
                        Los tokens utilizan la especificación <strong>Laravel Sanctum Bearer</strong> con cifrado SHA-256.
                    </div>
                </div>

                <div className="lg:col-span-2">
                    <div className="superficie overflow-hidden rounded-xl shadow-sm">
                        <div className="flex items-center justify-between border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-5">
                            <div>
                                <h2 className="text-base font-bold">Sistemas y Tokens Activos</h2>
                                <p className="mt-0.5 text-xs text-(--color-texto-suave)">
                                    Sistemas autorizados para realizar consultas e inserciones vía API.
                                </p>
                            </div>
                            <span className="rounded-full border border-(--color-borde) bg-(--color-superficie) px-2.5 py-1 text-xs font-semibold">
                                {meta?.total ?? clientes.length}{' '}
                                {(meta?.total ?? clientes.length) === 1 ? 'cliente' : 'clientes'}
                            </span>
                        </div>

                        {cargando && <Cargador texto="Cargando" />}

                        <div className="overflow-x-auto">
                            <table className="w-full divide-y divide-(--color-borde) text-left text-sm">
                                <caption className="sr-only">Clientes registrados de la API</caption>
                                <thead className="bg-(--color-superficie-cabecera) text-xs font-semibold tracking-wider text-(--color-texto-suave) uppercase">
                                    <tr>
                                        <th scope="col" className="px-6 py-3.5">Sistema / Cliente</th>
                                        <th scope="col" className="px-6 py-3.5">Estado</th>
                                        <th scope="col" className="px-6 py-3.5">Tokens</th>
                                        <th scope="col" className="px-6 py-3.5">Fecha alta</th>
                                        <th scope="col" className="px-6 py-3.5 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-(--color-borde) bg-(--color-superficie)">
                                    {!cargando && clientes.length === 0 && (
                                        <tr>
                                            <td colSpan={5} className="px-6 py-12 text-center text-(--color-texto-suave)">
                                                <Icono nombre="candado" className="mx-auto mb-2 h-8 w-8 opacity-40" />
                                                <p className="font-medium text-(--color-texto)">
                                                    Aún no hay clientes API registrados
                                                </p>
                                                <p className="mt-1 text-xs">
                                                    Crea uno en el formulario lateral para habilitar al CRM.
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                    {!cargando &&
                                        clientes.map((c) => (
                                            <tr key={c.id} className="transition-colors hover:bg-(--color-superficie-2)/60">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2 font-bold">
                                                        <span
                                                            aria-hidden="true"
                                                            className="h-2 w-2 rounded-full bg-(--color-exito)"
                                                        />
                                                        {c.nombre}
                                                    </div>
                                                    <span className="ml-4 font-mono text-[11px] text-(--color-texto-suave)">
                                                        ID: {String(c.id).slice(0, 8)}…
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <Insignia tono="exito">Activo</Insignia>
                                                </td>
                                                <td className="px-6 py-4 text-xs font-medium text-(--color-texto-suave) tabular-nums">
                                                    {c.tokens_count} {c.tokens_count === 1 ? 'token' : 'tokens'}
                                                </td>
                                                <td className="px-6 py-4 text-xs text-(--color-texto-suave) tabular-nums">
                                                    {c.creado}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <button
                                                        type="button"
                                                        onClick={() => setConfirmando(c)}
                                                        className="border-0 bg-transparent p-0 text-xs font-semibold text-(--color-error) hover:underline"
                                                    >
                                                        Revocar acceso
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <Paginacion meta={meta} irAPagina={irAPagina} cambiarTamano={cambiarTamano} sustantivo="clientes" />
                </div>
            </div>

            <div className="superficie rounded-xl p-6 shadow-sm sm:p-8">
                <div className="mb-6 flex flex-col justify-between gap-4 border-b border-(--color-borde) pb-6 sm:flex-row sm:items-center">
                    <div>
                        <div className="mb-2 inline-flex items-center gap-1.5 rounded-md border border-(--color-borde) bg-(--color-acento-suave) px-2.5 py-1 text-xs font-bold tracking-wider text-(--color-acento) uppercase">
                            Guía de Integración CRM
                        </div>
                        <h2 className="text-2xl font-bold tracking-tight">Documentación de la API para el CRM</h2>
                        <p className="mt-1 text-sm text-(--color-texto-suave)">
                            Endpoints listos para consumir los programas, módulos ofertables, ediciones y convertir prospectos.
                        </p>
                    </div>
                    <div className="rounded-lg border border-(--color-borde) bg-(--color-superficie-cabecera) p-3 font-mono text-xs text-(--color-texto-suave)">
                        <span>Base URL:</span> <strong className="text-(--color-texto)">{baseApiV1}</strong>
                    </div>
                </div>

                <div className="space-y-4">
                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-emerald-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    GET
                                </span>
                                <code className="font-mono text-sm font-semibold">/api/v1/crm/catalogo</code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">
                                Recomendado para el CRM (un solo fetch)
                            </span>
                        </div>
                        <div className="p-5 text-sm text-(--color-texto-suave)">
                            <p className="mb-3 text-xs leading-relaxed">
                                Devuelve la <strong>oferta comercial completa</strong>: programas abiertos con su lista
                                ordenada de módulos, ediciones, grupos con modalidad y horarios, y módulos libres.
                            </p>
                            <ul className="mb-3 list-disc space-y-1 pl-5 text-xs leading-relaxed">
                                <li>
                                    <strong>Qué se publica:</strong> solo programas abiertos con al menos un módulo abierto,
                                    módulos abiertos y ediciones/grupos programados o en curso.
                                </li>
                                <li>
                                    <strong>Precio:</strong> en bolivianos (BOB), sin descuentos. Los descuentos los aplica el CRM.
                                </li>
                                <li>
                                    <strong>Fechas del grupo:</strong> <code>fecha_inicio</code> y <code>fecha_fin</code> son el
                                    período real de cursado.
                                </li>
                                <li>
                                    <strong>Nombre del grupo:</strong> generado automáticamente por el sistema.
                                </li>
                                <li>
                                    <strong>Docente:</strong> solo se envía su nombre completo, nunca datos personales.
                                </li>
                                <li>
                                    <strong>Certificado:</strong> <code>certificado_otorga</code> y{' '}
                                    <code>certificado_otorga_label</code>.
                                </li>
                                <li>
                                    <strong>Horarios:</strong> informativos y expresados en hora de Bolivia (BOT).
                                </li>
                            </ul>

                            <details className="group">
                                <summary className="flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-(--color-acento) hover:underline [&::-webkit-details-marker]:hidden">
                                    <Icono
                                        nombre="chevron"
                                        className="h-3.5 w-3.5 transition-transform group-open:rotate-180"
                                    />
                                    Ver ejemplo de respuesta JSON
                                </summary>
                                <div className="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-4 font-mono text-xs leading-relaxed text-emerald-400 shadow-inner">
                                    <pre>{`{
  "exito": true,
  "resumen": {
    "total_programas_abiertos": 1,
    "total_modulos_libres": 2
  },
  "programas": [
    {
      "id": "uuid-programa-1",
      "codigo": "DEMO-P01",
      "nombre": "Diplomado en Análisis de Datos",
      "version": "1.0",
      "precio": 1500.50,
      "moneda": "BOB",
      "certificado_otorga_label": "Certificado",
      "estado_comercial": "abierto"
    }
  ]
}`}</pre>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-emerald-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    GET
                                </span>
                                <code className="font-mono text-sm font-semibold">/api/v1/crm/programas?q=&#123;busqueda&#125;</code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">Búsqueda de programas</span>
                        </div>
                        <div className="p-4 text-xs text-(--color-texto-suave)">
                            Permite al CRM buscar programas abiertos por nombre, código o descripción.
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-emerald-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    GET
                                </span>
                                <code className="font-mono text-sm font-semibold">/api/v1/crm/programas/&#123;id&#125;</code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">Ficha por ID</span>
                        </div>
                        <div className="p-4 text-xs text-(--color-texto-suave)">
                            Obtiene un único programa con su plan de módulos, requisitos, certificado, precio en BOB y ediciones.
                            Responde 404 si está en borrador o cerrado.
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-emerald-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    GET
                                </span>
                                <code className="font-mono text-sm font-semibold">
                                    /api/v1/crm/ediciones?programa_id=&#123;id&#125;&amp;estado=&#123;estado&#125;
                                </code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">
                                Ediciones de los programas
                            </span>
                        </div>
                        <div className="p-5 text-sm text-(--color-texto-suave)">
                            <p className="mb-3 text-xs leading-relaxed">
                                Lista las ediciones programadas o en curso. Detalle individual en{' '}
                                <code>/api/v1/crm/ediciones/&#123;id&#125;</code>.
                            </p>
                            <details className="group">
                                <summary className="flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-(--color-acento) hover:underline [&::-webkit-details-marker]:hidden">
                                    <Icono
                                        nombre="chevron"
                                        className="h-3.5 w-3.5 transition-transform group-open:rotate-180"
                                    />
                                    Ver ejemplo de respuesta JSON
                                </summary>
                                <div className="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-4 font-mono text-xs leading-relaxed text-emerald-400 shadow-inner">
                                    <pre>{`{
  "data": [
    {
      "id": "uuid-edicion-1",
      "nombre": "Edición 1",
      "fecha_inicio": "2026-09-21",
      "fecha_fin": "2026-12-18",
      "estado": "en_curso",
      "grupos": [
        {
          "id": "uuid-grupo-1",
          "modulo_nombre": "Fundamentos de Excel",
          "nombre": "Grupo 1",
          "modalidad": "presencial"
        }
      ]
    }
  ]
}`}</pre>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-emerald-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    GET
                                </span>
                                <code className="font-mono text-sm font-semibold">/api/v1/crm/modulos?q=&#123;busqueda&#125;</code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">Cursos y módulos sueltos</span>
                        </div>
                        <div className="p-4 text-xs text-(--color-texto-suave)">
                            Lista módulos abiertos disponibles individualmente con su prerrequisito, grupos y horarios. Ficha en{' '}
                            <code>/api/v1/crm/modulos/&#123;id&#125;</code>.
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-(--color-borde)">
                        <div className="flex flex-col justify-between gap-3 border-b border-(--color-borde) bg-(--color-superficie-cabecera) p-4 md:flex-row md:items-center">
                            <div className="flex items-center gap-3">
                                <span className="rounded bg-blue-600 px-2.5 py-1 font-mono text-xs font-bold text-white">
                                    POST
                                </span>
                                <code className="font-mono text-sm font-semibold">/api/v1/estudiantes-convertidos</code>
                            </div>
                            <span className="text-xs font-medium text-(--color-texto-suave)">
                                Conversión de Lead a Alumno
                            </span>
                        </div>
                        <div className="p-5 text-sm text-(--color-texto-suave)">
                            <p className="mb-3 text-xs leading-relaxed">
                                Envía los datos del prospecto convertido en el CRM para crear su matrícula automáticamente
                                en una edición de programa o en un módulo suelto.
                            </p>
                            <details className="group">
                                <summary className="flex cursor-pointer list-none items-center gap-1 text-xs font-semibold text-(--color-acento) hover:underline [&::-webkit-details-marker]:hidden">
                                    <Icono
                                        nombre="chevron"
                                        className="h-3.5 w-3.5 transition-transform group-open:rotate-180"
                                    />
                                    Ver ejemplo de solicitud cURL
                                </summary>
                                <div className="mt-3 overflow-x-auto rounded-lg bg-slate-900 p-4 font-mono text-xs leading-relaxed text-slate-200 shadow-inner">
                                    <pre>{`curl -X POST "${baseApiV1}/estudiantes-convertidos" \\
  -H "Authorization: Bearer <TU_TOKEN>" \\
  -H "Content-Type: application/json" \\
  -H "Accept: application/json" \\
  -d '{
    "id_externo_crm": "LEAD-9824",
    "nombres": "Juan Carlos",
    "apellidos": "Pérez Gómez",
    "email": "juan.perez@example.com",
    "documento_identidad": "8472910 LP",
    "telefono": "+591 71234567",
    "cohorte_id": "uuid-de-la-edicion"
  }'`}</pre>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>
            </div>

            <DialogoConfirmacion
                abierto={Boolean(confirmando)}
                titulo="¿Revocar acceso a este cliente API?"
                mensaje={
                    confirmando
                        ? `Se invalidarán los tokens activos de «${confirmando.nombre}» inmediatamente.`
                        : ''
                }
                confirmarTexto="Revocar acceso"
                peligro
                alConfirmar={confirmarRevocacion}
                alCancelar={() => setConfirmando(null)}
            />
        </section>
    );
}
