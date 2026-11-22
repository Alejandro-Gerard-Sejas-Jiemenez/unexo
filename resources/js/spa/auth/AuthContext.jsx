import { createContext, useCallback, useContext, useEffect, useState } from 'react';
import cliente, { prepararCsrf, SESION_CADUCADA } from '../api/cliente';

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [usuario, setUsuario] = useState(null);
    const [menu, setMenu] = useState([]);
    const [cargando, setCargando] = useState(true);

    /** El menú lo arma el servidor con los permisos del rol: nunca se calcula aquí. */
    const cargarMenu = useCallback(async () => {
        const { data } = await cliente.get('/menu');
        setMenu(data.menu);
    }, []);

    // Al arrancar restauramos la sesión desde la cookie, si todavía es válida.
    useEffect(() => {
        let vigente = true;

        (async () => {
            try {
                const { data } = await cliente.get('/me');
                if (!vigente) return;
                setUsuario(data.usuario);
                await cargarMenu();
            } catch {
                if (vigente) setUsuario(null);
            } finally {
                if (vigente) setCargando(false);
            }
        })();

        return () => {
            vigente = false;
        };
    }, [cargarMenu]);

    // Si el servidor responde 401 en cualquier petición, salimos de la sesión.
    useEffect(() => {
        const alCaducar = () => {
            setUsuario(null);
            setMenu([]);
        };
        window.addEventListener(SESION_CADUCADA, alCaducar);
        return () => window.removeEventListener(SESION_CADUCADA, alCaducar);
    }, []);

    const entrar = useCallback(
        async (credenciales) => {
            await prepararCsrf();
            const { data } = await cliente.post('/login', credenciales);
            setUsuario(data.usuario);
            await cargarMenu();
        },
        [cargarMenu],
    );

    const salir = useCallback(async () => {
        try {
            await cliente.post('/logout');
        } finally {
            setUsuario(null);
            setMenu([]);
        }
    }, []);

    return (
        <AuthContext.Provider value={{ usuario, menu, cargando, entrar, salir }}>
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
