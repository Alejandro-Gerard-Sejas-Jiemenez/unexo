/**
 * Íconos del menú. El servidor manda un nombre y acá se dibuja: así la
 * definición del menú sigue siendo una sola y no viaja SVG por la API.
 */
const TRAZOS = {
    inicio: 'M3 10.5 12 3l9 7.5M5.5 9.5V20h13V9.5',
    catalogo: 'M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5zM20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z',
    modulos: 'M12 3 3 7.5l9 4.5 9-4.5zM3 12l9 4.5 9-4.5M3 16.5 12 21l9-4.5',
    programa: 'M12 3 2 8l10 5 10-5zM6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5',
    calendario: 'M4 7.5A1.5 1.5 0 0 1 5.5 6h13A1.5 1.5 0 0 1 20 7.5v11A1.5 1.5 0 0 1 18.5 20h-13A1.5 1.5 0 0 1 4 18.5zM8 3v5M16 3v5M4 11h16',
    usuarios: 'M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 10.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM21 20v-1.5a4 4 0 0 0-3-3.9M16 3.6a4 4 0 0 1 0 7.7',
    matricula: 'M8 4h8a1 1 0 0 1 1 1v15l-5-2.5L7 20V5a1 1 0 0 1 1-1zM9.5 9h5',
    reportes: 'M5 20V11M12 20V5M19 20v-6',
    candado: 'M6.5 10.5h11A1.5 1.5 0 0 1 19 12v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 5 19v-7a1.5 1.5 0 0 1 1.5-1.5zM8.5 10.5V7a3.5 3.5 0 0 1 7 0v3.5',
    video: 'M3.5 7.5A1.5 1.5 0 0 1 5 6h8a1.5 1.5 0 0 1 1.5 1.5v9A1.5 1.5 0 0 1 13 18H5a1.5 1.5 0 0 1-1.5-1.5zM14.5 10.5 20.5 7v10l-6-3.5',
};

export default function Icono({ nombre, className = 'h-[18px] w-[18px]' }) {
    const d = TRAZOS[nombre] ?? TRAZOS.inicio;

    return (
        <svg
            className={className}
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.7"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden="true"
        >
            <path d={d} />
        </svg>
    );
}
