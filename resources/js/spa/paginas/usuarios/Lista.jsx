import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import cliente from '../../api/cliente';
import { useAuth } from '../../auth/AuthContext';
import { conBase } from '../../base';
import BarraHerramientas from '../../componentes/BarraHerramientas';
import BotonAccion from '../../componentes/BotonAccion';
import Cargador from '../../componentes/Cargador';
import DialogoConfirmacion from '../../componentes/DialogoConfirmacion';
import EncabezadoPagina from '../../componentes/EncabezadoPagina';
import Icono from '../../componentes/Icono';
import Paginacion from '../../componentes/Paginacion';
import TablaDatos from '../../componentes/TablaDatos';
import { useToast } from '../../componentes/ToastContext';
import useListado from '../../hooks/useListado';

function AvatarUsuario({ usuario }) {
    const [error, setError] = useState(false);

    useEffect(() => {
        setError(false);
    }, [usuario.foto_perfil_url]);

    if (usuario.foto_perfil_url && !error) {
        return (
            <img
                src={conBase(usuario.foto_perfil_url)}
                alt=""
                onError={() => setError(true)}
                className="h-7 w-7 rounded-full object-cover"
            />
        );
    }

    return (
        <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[var(--color-superficie-2)] text-xs font-bold text-[var(--color-texto-suave)]">
            {usuario.nombres?.[0]?.toUpperCase()}
            {usuario.apellidos?.[0]?.toUpperCase()}
        </span>
    );
}

export default function UsuariosLista() {
    const { usuario: actual } = useAuth();
    const { mostrar } = useToast();
    const {
        filas,
        meta,
        cargando,
        aviso,
        setAviso,
        valorDe,
        filtrarVarios,
        filtrarConRetraso,
        limpiarFiltros,
        irAPagina,
        refrescar,
    } = useListado('/usuarios');

    const [busqueda, setBusqueda] = useState(valorDe('q'));
    const [confirmando, setConfirmando] = useState(null);

    useEffect(() => {
        setBusqueda(valorDe('q'));
    }, [valorDe]);

    async function confirmarCambioEstado() {
        if (!confirmando) return;
        const objetivo = confirmando;
        setConfirmando(null);
        setAviso(null);
        try {
            const { data } = await cliente.patch(`/usuarios/${objetivo.id}/estado`);
            mostrar(data.mensaje);
            await refrescar();
        } catch (error) {
            setAviso(error.errores?.usuario?.[0] ?? error.message);
        }
    }

    const rolActual = valorDe('rol');
    const estadoActual = valorDe('estado');

    const pildoras = [
        {
            etiqueta: 'Todos',
            activo: !rolActual && !estadoActual,
            onClick: () => filtrarVarios({ estado: '', rol: '' }),
        },
        {
            etiqueta: 'Administradores',
            activo: rolActual === 'administrador' && !estadoActual,
            onClick: () => filtrarVarios({ estado: '', rol: 'administrador' }),
        },
        {
            etiqueta: 'Docentes',
            activo: rolActual === 'docente' && !estadoActual,
            onClick: () => filtrarVarios({ estado: '', rol: 'docente' }),
        },
        {
            etiqueta: 'Estudiantes',
            activo: rolActual === 'estudiante' && !estadoActual,
            onClick: () => filtrarVarios({ estado: '', rol: 'estudiante' }),
        },
        {
            etiqueta: 'Inactivos',
            activo: estadoActual === 'inactivo',
            onClick: () => filtrarVarios({ rol: '', estado: 'inactivo' }),
        },
    ];

    const columnas = [
        { etiqueta: 'Nombre' },
        { etiqueta: 'Rol' },
        { etiqueta: 'Correo institucional' },
        { etiqueta: 'Correo personal' },
        { etiqueta: 'Estado' },
        { etiqueta: 'Acciones', srOnly: true, alinear: 'derecha' },
    ];

    return (
        <section>
            <EncabezadoPagina
                titulo="Usuarios"
                acciones={
                    <Link to="/usuarios/nuevo" className="btn btn-primario">
                        <Icono nombre="mas" className="h-5 w-5" />
                        Nuevo usuario
                    </Link>
                }
            />

            <BarraHerramientas
                pildoras={pildoras}
                busqueda={busqueda}
                placeholder="Buscar por nombre o correo…"
                alCambiarBusqueda={(val) => {
                    setBusqueda(val);
                    filtrarConRetraso('q', val);
                }}
                alBuscar={(val) => filtrar('q', val)}
                alLimpiarBusqueda={() => {
                    setBusqueda('');
                    limpiarFiltros();
                }}
            />

            {aviso && (
                <p role="alert" className="superficie mb-4 border-[var(--color-error)] p-3 text-sm text-[var(--color-error)]">
                    {aviso}
                </p>
            )}

            {cargando && <Cargador texto="Cargando" />}

            <TablaDatos
                caption="Listado de usuarios del sistema"
                columnas={columnas}
                vacio={!cargando && filas.length === 0}
                vacioIcono="usuarios"
                vacioTitulo="No se encontraron usuarios"
                vacioDescripcion="Intenta cambiar los filtros o la búsqueda."
            >
                {filas.map((u) => (
                    <tr key={u.id} className="transition-colors hover:bg-[var(--color-superficie-2)]/60">
                        <td className="px-6 py-4 whitespace-nowrap">
                            <div className="flex items-center gap-2.5">
                                <AvatarUsuario usuario={u} />
                                <span className="text-sm font-semibold">
                                    {u.apellidos}, {u.nombres}
                                </span>
                            </div>
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span className="inline-flex rounded-md bg-[var(--color-superficie-2)] px-2.5 py-1 text-xs font-medium text-[var(--color-texto-suave)]">
                                {u.rol_label}
                            </span>
                        </td>
                        <td className="px-6 py-4 text-sm text-[var(--color-texto-suave)] whitespace-nowrap">
                            {u.correo_institucional || '—'}
                        </td>
                        <td className="px-6 py-4 text-sm text-[var(--color-texto-suave)] whitespace-nowrap">
                            {u.email}
                        </td>
                        <td className="px-6 py-4 whitespace-nowrap">
                            <span
                                className={
                                    'inline-flex rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset ' +
                                    (u.activo
                                        ? 'bg-[var(--color-exito-fondo)] text-[var(--color-exito)] ring-[color-mix(in_srgb,var(--color-exito)_30%,transparent)]'
                                        : 'bg-[var(--color-error-fondo)] text-[var(--color-error)] ring-[color-mix(in_srgb,var(--color-error)_30%,transparent)]')
                                }
                            >
                                {u.activo ? 'Activo' : 'Inactivo'}
                            </span>
                        </td>
                        <td className="px-6 py-4 text-right text-sm font-medium whitespace-nowrap">
                            <div className="inline-flex items-center gap-2">
                                <BotonAccion to={`/usuarios/${u.id}`} icono="editar">
                                    Editar
                                </BotonAccion>
                                {actual?.id !== u.id && (
                                    <BotonAccion
                                        onClick={() => setConfirmando(u)}
                                        icono={u.activo ? 'usuario-desactivar' : 'usuario-activar'}
                                        variante={u.activo ? 'peligro' : 'exito'}
                                    >
                                        {u.activo ? 'Desactivar' : 'Activar'}
                                    </BotonAccion>
                                )}
                            </div>
                        </td>
                    </tr>
                ))}
            </TablaDatos>

            <Paginacion meta={meta} irAPagina={irAPagina} sustantivo="usuarios" />

            <DialogoConfirmacion
                abierto={Boolean(confirmando)}
                titulo={confirmando?.activo ? 'Desactivar usuario' : 'Activar usuario'}
                mensaje={
                    confirmando?.activo
                        ? `¿Deseas desactivar a ${confirmando?.nombre_completo}? No podrá ingresar al sistema.`
                        : `¿Deseas reactivar el acceso de ${confirmando?.nombre_completo}?`
                }
                confirmarTexto={confirmando?.activo ? 'Sí, desactivar' : 'Sí, activar'}
                peligro={Boolean(confirmando?.activo)}
                alConfirmar={confirmarCambioEstado}
                alCancelar={() => setConfirmando(null)}
            />
        </section>
    );
}
