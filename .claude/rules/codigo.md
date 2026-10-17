# Código

Detalle en las skills `clean-code` y `laravel-arquitectura`.

- Seguir el estilo del código vecino: nombres, densidad de comentarios e idioma. Formato con `vendor/bin/pint`.
- Nombres de dominio en español y autoexplicativos (`inscribirEnCohorte()`, `fechaInicio`). Funciones cortas, una responsabilidad.
- Comentar solo el *porqué* no evidente. No dejar código muerto ni comentado.
- Validación con Form Requests (`Store<X>Request`, `Update<X>Request`), mensajes en español.
- Textos de pantalla en lenguaje del proceso administrativo (programas, módulos, matrículas), nunca términos de base de datos.
- Vistas Blade con el CSS plano existente en `public/css/app.css`. No añadir frameworks de frontend. No inventar marca ni paleta: NEXO aún no las entrega.
- Accesibilidad básica: contraste, navegación por teclado, etiquetas en formularios, errores comprensibles.
- Nada de secretos en el código. Configuración sensible vía `.env`.
- Datos de ejemplo con prefijo `DEMO-`.
- Mantener el alcance: implementar solo lo que pide la tarea en curso.
