import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom';
import { AuthProvider, useAuth } from './auth/AuthContext';
import Layout from './componentes/Layout';
import Login from './paginas/Login';
import Panel from './paginas/Panel';
import EnConstruccion from './paginas/EnConstruccion';
import UsuariosLista from './paginas/usuarios/Lista';
import UsuarioFormulario from './paginas/usuarios/Formulario';

/** Módulos todavía servidos por el panel Blade; se van quitando al migrarlos. */
const EN_MIGRACION = [
    'catalogo',
    'modulos',
    'programas',
    'cohortes',
    'grupos',
    'matriculas',
    'reportes',
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
        <BrowserRouter>
            <AuthProvider>
                <Rutas />
            </AuthProvider>
        </BrowserRouter>
    );
}
