import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import cliente, { prepararCsrf, SESION_CADUCADA } from '../api/cliente';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [usuario, setUsuario] = useState(null);
    const [menu, setMenu] = useState([]);
    const [permisos, setPermisos] = useState([]);
    const [cargando, setCargando] = useState(true);

    const aplicarSesion = useCallback((data) => {
        setUsuario(data.usuario ?? null);
        setMenu(data.menu ?? []);
        setPermisos(data.permisos ?? []);
    }, []);

    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const { data } = await cliente.get('/me');
                if (vigente) aplicarSesion(data);
            } catch {
                if (vigente) aplicarSesion({});
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [aplicarSesion]);

    useEffect(() => {
        const alCaducar = () => aplicarSesion({});
        window.addEventListener(SESION_CADUCADA, alCaducar);
        return () => window.removeEventListener(SESION_CADUCADA, alCaducar);
    }, [aplicarSesion]);

    const entrar = useCallback(
        async (credenciales) => {
            await prepararCsrf();
            const { data } = await cliente.post('/login', credenciales);
            aplicarSesion(data);
        },
        [aplicarSesion],
    );

    const salir = useCallback(async () => {
        try {
            await cliente.post('/logout');
        } finally {
            aplicarSesion({});
        }
    }, [aplicarSesion]);

    const recargarUsuario = useCallback(async () => {
        const { data } = await cliente.get('/me');
        aplicarSesion(data);
    }, [aplicarSesion]);

    const tienePermiso = useCallback(
        (clave) => permisos.includes(clave),
        [permisos],
    );

    return (
        <AuthContext.Provider
            value={{ usuario, menu, permisos, cargando, entrar, salir, recargarUsuario, tienePermiso }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    const contexto = useContext(AuthContext);

    if (!contexto) {
        throw new Error('useAuth se usó fuera de AuthProvider.');
    }

    return contexto;
}
