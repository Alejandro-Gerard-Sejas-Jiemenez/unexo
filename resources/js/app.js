import './bootstrap';
import { iniciarSidebar } from './sidebar';
import { iniciarNavegacion } from './navegacion';
import { iniciarHorarios } from './horarios';
import { iniciarGrupoFormulario } from './grupo-formulario';
import { iniciarPortada } from './portada';

document.addEventListener('DOMContentLoaded', () => {
    iniciarSidebar();
    iniciarNavegacion();
});

// Delegados en document: sobreviven al reemplazo de #contenido por fetch.
iniciarHorarios();
iniciarGrupoFormulario();
iniciarPortada();
