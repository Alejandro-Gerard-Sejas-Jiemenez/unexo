import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { BASE } from './base';
import { AuthProvider, useAuth } from './auth/AuthContext';
import { TemaProvider } from './tema/TemaContext';
import Layout from './componentes/Layout';
import Login from './paginas/Login';
import Panel from './paginas/Panel';
import EnConstruccion from './paginas/EnConstruccion';
import UsuariosLista from './paginas/usuarios/Lista';
import UsuarioFormulario from './paginas/usuarios/Formulario';
import Dashboard from './paginas/Dashboard';
import Bitacora from './paginas/Bitacora';
import Pagos from './paginas/Pagos';
import ProgramasLista from './paginas/catalogo/ProgramasLista';
import ProgramaFormulario from './paginas/catalogo/ProgramaFormulario';
import ModulosLista from './paginas/catalogo/ModulosLista';
import ModuloFormulario from './paginas/catalogo/ModuloFormulario';
import EdicionesLista from './paginas/planificacion/EdicionesLista';
import EdicionDetalle from './paginas/planificacion/EdicionDetalle';
import GruposLista from './paginas/planificacion/GruposLista';
import GrupoFormulario from './paginas/planificacion/GrupoFormulario';
import MatriculasLista from './paginas/matriculas/MatriculasLista';
import MatriculaNueva from './paginas/matriculas/MatriculaNueva';
import MatriculaDetalle from './paginas/matriculas/MatriculaDetalle';
import Reportes from './paginas/Reportes';

/** Módulos todavía servidos por el panel Blade; se van quitando al migrarlos. */
const EN_MIGRACION = [
    'catalogo',
    'clientes-api',
    'mis-grupos',
    'mis-cursos',
    'calendario',
];

function Rutas() {
    const { usuario, cargando } = useAuth();

    if (cargando) {
        return (
            <p className="p-6 text-[var(--color-texto-suave)]" role="status">
                Cargando…
            </p>
        );
    }

    if (!usuario) {
        return <Login />;
    }

    return (
        <Routes>
            <Route path="/app" element={<Layout />}>
                <Route index element={<Panel />} />
                <Route path="usuarios" element={<UsuariosLista />} />
                <Route path="usuarios/nuevo" element={<UsuarioFormulario />} />
                <Route path="usuarios/:id" element={<UsuarioFormulario />} />
                <Route path="estadisticas" element={<Dashboard />} />
                <Route path="bitacora" element={<Bitacora />} />
                <Route path="pagos" element={<Pagos />} />
                <Route path="programas" element={<ProgramasLista />} />
                <Route path="programas/nuevo" element={<ProgramaFormulario />} />
                <Route path="programas/:id" element={<ProgramaFormulario />} />
                <Route path="modulos" element={<ModulosLista />} />
                <Route path="modulos/nuevo" element={<ModuloFormulario />} />
                <Route path="modulos/:id" element={<ModuloFormulario />} />
                <Route path="cohortes" element={<EdicionesLista />} />
                <Route path="cohortes/nueva" element={<EdicionDetalle />} />
                <Route path="cohortes/:id" element={<EdicionDetalle />} />
                <Route path="grupos" element={<GruposLista />} />
                <Route path="grupos/nuevo" element={<GrupoFormulario />} />
                <Route path="grupos/:id" element={<GrupoFormulario />} />
                <Route path="matriculas" element={<MatriculasLista />} />
                <Route path="matriculas/nueva" element={<MatriculaNueva />} />
                <Route path="matriculas/:id" element={<MatriculaDetalle />} />
                <Route path="reportes" element={<Reportes />} />
                {EN_MIGRACION.map((ruta) => (
                    <Route key={ruta} path={ruta} element={<EnConstruccion />} />
                ))}
                <Route path="*" element={<Navigate to="/app" replace />} />
            </Route>
            <Route path="*" element={<Navigate to="/app" replace />} />
        </Routes>
    );
}

export default function App() {
    return (
        // El tema envuelve todo, incluido el login: la preferencia vale desde
        // la primera pantalla, sin esperar a que haya sesión.
        <TemaProvider>
            <BrowserRouter basename={BASE}>
                <AuthProvider>
                    <Rutas />
                </AuthProvider>
            </BrowserRouter>
        </TemaProvider>
    );
}
