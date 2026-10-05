# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Primario: el Administrador de NEXO**, personal de la unidad de cursos y consultorías de la UAGRM que trabaja en una PC de escritorio durante la jornada. Su trabajo es cargar y mantener programas, módulos, usuarios (con correo institucional), cohortes y matrículas, y revisar estados. Es quien más tiempo pasa en el sistema y quien inscribe a los estudiantes (el alumno no se inscribe solo).

**Secundarios, uso ocasional:** el Docente (consulta sus cohortes y datos de Meet o aula) y el Estudiante (consulta su información y, en una fase posterior, sube el comprobante de su pago). El CRM no es un usuario: es otro sistema que consume el catálogo por API.

## Product Purpose

Sistema de Administración Académica (SIS) de la unidad NEXO. Mantiene el catálogo académico de programas y módulos, los perfiles de usuario por rol, la planificación de cohortes y las matrículas, y publica la oferta abierta para que el CRM de la unidad la consulte. Es el "proyecto dos" de la materia Tecnología Web (Grupo 6 SA): el CRM capta prospectos y, cuando se convierten en estudiantes, sus datos llegan al SIS. El éxito es que el administrador gestione todo sin planillas paralelas y que el CRM siempre vea la oferta vigente.

## Positioning

El catálogo es la fuente única de la oferta académica y alimenta al CRM. Un módulo puede pertenecer a varios programas (que tienen versiones), tiene un orden dentro de cada programa, requisitos recomendados y un estado comercial (borrador, abierto, cerrado); quien toma un módulo suelto puede después completar un programa. Una hoja de cálculo o un sistema genérico no modelan esa relación ni la exponen al CRM.

## Operating Context

- Uso en escritorio, en una oficina, con sesiones largas de carga y revisión de datos.
- El sistema no procesa pagos: la verificación del depósito la hace una operadora de caja fuera del SIS.
- Los grupos son virtuales y cada uno tiene su propio enlace de videoconferencia.
- Hoy corre en localhost (Laravel con PostgreSQL); se desplegará en el servidor de NEXO al final.
- Entregas por cronograma oficial: 5 oct (login por rol, perfiles, catálogo), 12 oct (cohortes, matrículas, API), 26 oct (flujo completo con el Grupo 5), 16 nov (entrega final con manual de usuario).

## Capabilities and Constraints

- Stack ya definido en el código: Laravel 12, Blade y CSS plano (`public/css/app.css`), sin Vite ni framework de frontend.
- Roles: Administrador, Docente y Estudiante, con vistas distintas por rol.
- Todo identificador es UUID; los datos de ejemplo llevan prefijo `DEMO-`.
- Interfaz en español, tono institucional y claro para personal administrativo; sin jerga técnica en pantalla.
- Responsive para móvil: opcional. Está fuera del alcance del Grupo 6 SA salvo que la ingeniera lo confirme.
- Sin decidir: si el catálogo incluye precios y descuentos, y quién envía los alumnos convertidos (el CRM a nuestro endpoint o al revés).

## Brand Commitments

NEXO entregará su logo y su paleta de colores; todavía no los tiene este proyecto. No inventar marca ni identidad visual hasta que lleguen.

## Evidence on Hand

Solo datos inventados (usuarios, programas y módulos `DEMO-`). No hay logo, paleta, capturas de un sistema previo ni testimonios de NEXO. No fabricar cifras, clientes ni casos de uso reales.

## Product Principles

1. **El trabajo del administrador primero.** Lo importante es completar tareas repetitivas de carga con rapidez y sin errores, no impresionar.
2. **Un catálogo, una verdad.** Lo que se ve y edita en el SIS es exactamente lo que el CRM consume; el estado comercial debe ser siempre explícito.
3. **Claridad antes que decoración.** Textos en lenguaje del proceso administrativo (programas, módulos, matrículas), nunca términos internos de base de datos.
4. **Cada rol ve solo lo suyo.** Un docente o estudiante que llegue a una pantalla de administración recibe un acceso denegado claro.
5. **Marca pendiente, no inventada.** La identidad de NEXO se aplica cuando la entreguen; mientras tanto, neutral y sobrio.

## Accessibility & Inclusion

No hay un estándar formal exigido. Se aplican buenas prácticas básicas: contraste suficiente, navegación por teclado, etiquetas en formularios y mensajes de error comprensibles.
