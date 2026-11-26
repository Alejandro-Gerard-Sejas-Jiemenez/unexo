import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { BASE } from './base';
import { AuthProvider, useAuth } from './auth/AuthContext';
import { TemaProvider } from './tema/TemaContext';
import { ToastProvider } from './componentes/ToastContext';
import Cargador from './componentes/Cargador';
import Layout from './componentes/Layout';
import RutaProtegida from './componentes/RutaProtegida';
import Login from './paginas/Login';
import Panel from './paginas/Panel';
import Perfil from './paginas/Perfil';
import UsuariosLista from './paginas/usuarios/Lista';
import UsuarioFormulario from './paginas/usuarios/Formulario';
import Dashboard from './paginas/reportes/Dashboard';
import Bitacora from './paginas/sistema/Bitacora';
import Pagos from './paginas/Pagos';
import ProgramasLista from './paginas/catalogo/ProgramasLista';
import ProgramaDetalle from './paginas/catalogo/ProgramaDetalle';
import ProgramaFormulario from './paginas/catalogo/ProgramaFormulario';
import ModulosLista from './paginas/catalogo/ModulosLista';
import ModuloFormulario from './paginas/catalogo/ModuloFormulario';
import EdicionesLista from './paginas/planificacion/EdicionesLista';
import EdicionDetalle from './paginas/planificacion/EdicionDetalle';
import GruposLista from './paginas/planificacion/GruposLista';
import GrupoFormulario from './paginas/planificacion/GrupoFormulario';
import GrupoDetalle from './paginas/planificacion/GrupoDetalle';
import MatriculasLista from './paginas/matriculas/MatriculasLista';
import MatriculaNueva from './paginas/matriculas/MatriculaNueva';
import MatriculaDetalle from './paginas/matriculas/MatriculaDetalle';
import Reportes from './paginas/reportes/Reportes';
import CatalogoPublico from './paginas/CatalogoPublico';
import Calendario from './paginas/docencia/Calendario';
import MisClases from './paginas/docencia/MisClases';
import MiGrupoDetalle from './paginas/docencia/MiGrupoDetalle';
import MiCursoDetalle from './paginas/docencia/MiCursoDetalle';
import ClientesApi from './paginas/sistema/ClientesApi';
import Aulas from './paginas/sistema/Aulas';

const ADMIN = ['administrador'];
const DOCENTE = ['docente'];
const ESTUDIANTE = ['estudiante'];

function Rutas() {
    const { usuario, cargando } = useAuth();

    if (cargando) {
        return <Cargador pantallaCompleta texto="Cargando" />;
    }

    if (!usuario) {
        return <Login />;
    }

    return (
        <Routes>
            <Route path="/" element={<Layout />}>
                <Route index element={<Panel />} />
                <Route path="perfil" element={<Perfil />} />

                <Route path="catalogo" element={<CatalogoPublico />} />
                <Route path="pagos" element={<Pagos />} />
                <Route
                    path="calendario"
                    element={
                        <RutaProtegida roles={['docente', 'estudiante']}>
                            <Calendario />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="mis-grupos"
                    element={
                        <RutaProtegida roles={DOCENTE}>
                            <MisClases modo="docente" />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="mis-grupos/:id"
                    element={
                        <RutaProtegida roles={DOCENTE}>
                            <MiGrupoDetalle />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="mis-cursos"
                    element={
                        <RutaProtegida roles={ESTUDIANTE}>
                            <MisClases modo="estudiante" />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="mis-cursos/:id"
                    element={
                        <RutaProtegida roles={ESTUDIANTE}>
                            <MiCursoDetalle />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="usuarios"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <UsuariosLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="usuarios/nuevo"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <UsuarioFormulario />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="usuarios/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <UsuarioFormulario />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="programas"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ProgramasLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="programas/nuevo"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ProgramaFormulario />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="programas/:id/ver"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ProgramaDetalle />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="programas/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ProgramaFormulario />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="modulos"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ModulosLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="modulos/nuevo"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ModuloFormulario />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="modulos/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ModuloFormulario />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="cohortes"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <EdicionesLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="cohortes/nueva"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <EdicionDetalle />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="cohortes/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <EdicionDetalle />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="grupos"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <GruposLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="grupos/nuevo"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <GrupoFormulario />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="grupos/:id/ver"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <GrupoDetalle />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="grupos/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <GrupoFormulario />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="aulas"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <Aulas />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="matriculas"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <MatriculasLista />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="matriculas/nueva"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <MatriculaNueva />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="matriculas/:id"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <MatriculaDetalle />
                        </RutaProtegida>
                    }
                />

                <Route
                    path="reportes"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <Reportes />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="estadisticas"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <Dashboard />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="bitacora"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <Bitacora />
                        </RutaProtegida>
                    }
                />
                <Route
                    path="clientes-api"
                    element={
                        <RutaProtegida roles={ADMIN}>
                            <ClientesApi />
                        </RutaProtegida>
                    }
                />

                <Route path="*" element={<Navigate to="/" replace />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
        </Routes>
    );
}

export default function App() {
    return (
        <TemaProvider>
            <ToastProvider>
                <BrowserRouter basename={BASE || '/'}>
                    <AuthProvider>
                        <Rutas />
                    </AuthProvider>
                </BrowserRouter>
            </ToastProvider>
        </TemaProvider>
    );
}
