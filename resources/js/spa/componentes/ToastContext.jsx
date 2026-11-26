import { createContext, useCallback, useContext, useState } from 'react';
import Icono from './Icono';

const ToastContext = createContext(null);

export function ToastProvider({ children }) {
    const [lista, setLista] = useState([]);

    const quitar = useCallback((id) => {
        setLista((prev) => prev.filter((t) => t.id !== id));
    }, []);

    const mostrar = useCallback(
        (mensaje, tipo = 'exito') => {
            if (!mensaje) return;
            const id = Date.now() + Math.random();
            setLista((prev) => [...prev, { id, mensaje, tipo }]);
            setTimeout(() => quitar(id), 4500);
        },
        [quitar],
    );

    return (
        <ToastContext.Provider value={{ mostrar }}>
            {children}
            <div
                aria-live="polite"
                className="pointer-events-none fixed right-4 bottom-4 z-50 flex max-w-sm flex-col gap-2"
            >
                {lista.map((t) => (
                    <div
                        key={t.id}
                        role="status"
                        className={
                            'superficie animate-fade-in pointer-events-auto flex items-start gap-2.5 px-4 py-3 text-sm font-medium ' +
                            (t.tipo === 'error'
                                ? 'border-(--color-error) bg-(--color-error-fondo) text-(--color-error)'
                                : 'border-(--color-exito) bg-(--color-exito-fondo) text-(--color-exito)')
                        }
                    >
                        <Icono nombre={t.tipo === 'error' ? 'alerta' : 'check'} className="mt-0.5 h-4 w-4 shrink-0" />
                        <span className="flex-1">{t.mensaje}</span>
                        <button
                            type="button"
                            onClick={() => quitar(t.id)}
                            aria-label="Cerrar aviso"
                            className="border-0 bg-transparent p-0.5 text-current opacity-70 hover:opacity-100"
                        >
                            <Icono nombre="cerrar" className="h-3.5 w-3.5" />
                        </button>
                    </div>
                ))}
            </div>
        </ToastContext.Provider>
    );
}

export function useToast() {
    const ctx = useContext(ToastContext);
    if (!ctx) throw new Error('useToast se usó fuera de ToastProvider.');
    return ctx;
}
