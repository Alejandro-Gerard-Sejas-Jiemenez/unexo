import { useState } from 'react';
import { POR_PAGINA, paginar } from '../lib/paginar';

/** Paginación de una lista ya cargada. Si cambia `clave`, vuelve a la primera página. */
export default function usePaginaLocal(filas, clave = '') {
    const [estado, setEstado] = useState({ clave, pagina: 1 });
    const [tamano, setTamano] = useState(POR_PAGINA);
    const { visibles, meta } = paginar(filas, estado.clave === clave ? estado.pagina : 1, tamano);

    return {
        visibles,
        meta,
        irAPagina: (pagina) => setEstado({ clave, pagina }),
        cambiarTamano: (nuevo) => {
            setTamano(nuevo);
            setEstado({ clave, pagina: 1 });
        },
    };
}
