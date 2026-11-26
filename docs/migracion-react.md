# Migración a React (arquitectura de 3 capas)

Estado: **migración completa**. La SPA cubre los ocho casos de uso y es la
interfaz del sistema: la raíz del sitio entra por `/app`.

## Cómo conviven las dos interfaces

| | Panel Blade (actual) | SPA React (nueva) |
|---|---|---|
| URL | `/panel`, `/usuarios`, … | `/app`, `/app/usuarios`, … |
| Vistas | `resources/views/**` | `resources/js/spa/**` |
| Entrada Vite | `resources/js/app.js` | `resources/js/spa/main.jsx` |
| Datos | Controladores → Blade | `fetch` → `/api/panel/*` → JSON |

Las dos usan **la misma sesión**, así que iniciar sesión en una vale para la otra.
Los módulos aún no migrados muestran en React la pantalla `EnConstruccion`, con un
enlace al panel Blade.

## Las tres capas

```
Navegador                 Servidor                         Base de datos
─────────                 ────────                         ─────────────
React SPA (Vite)   ──►    routes/api.php (panel)    ──►    PostgreSQL
  React Router            middleware: web + auth:sanctum
  api/cliente.js          + rol:<roles>
  AuthContext             FormRequest  (validación)
                          Service      (reglas de negocio)
                          Resource     (JSON de salida)
```

La capa 1 **nunca** construye SQL ni decide permisos: solo pinta lo que la capa 2
le devuelve. La capa 2 es la única que habla con la capa 3.

## Autenticación

Cookie de sesión, no tokens en JavaScript. El grupo `panel` lleva el middleware
`web` explícito (sesión + CSRF) en vez de depender de que Sanctum reconozca el
origen de la petición, que falla cuando no llega `Origin` ni `Referer`.

Flujo: `GET /sanctum/csrf-cookie` → `POST /api/panel/login` → la cookie viaja sola.
`axios` manda el `X-XSRF-TOKEN` gracias a `withXSRFToken: true`.

`/api/v1/*` (CRM) sigue intacto con token Bearer y el middleware `cliente.api`.
Las pruebas verifican que un usuario del panel no entra a `/api/v1` y que un
cliente de la API no entra a `/api/panel`.

## El patrón, módulo por módulo

Para migrar un módulo (por ejemplo Módulos o Matrículas), estos seis pasos:

1. **Extraer las reglas de validación** del controlador Blade a un FormRequest en
   `app/Http/Requests/`, y hacer que el controlador Blade lo use. Un solo juego de
   reglas para los dos extremos. → ver `UsuarioRequest`.
2. **Extraer la consulta con filtros** a un Service en `app/Services/`, que
   devuelva el paginador. → ver `UsuarioService`.
3. **Crear el Resource** en `app/Http/Resources/` con la forma JSON del recurso.
   → ver `UsuarioResource`.
4. **Crear el controlador de API** en `app/Http/Controllers/Api/Panel/`, que use el
   mismo FormRequest y el mismo Service. → ver `UsuarioApiController`.
5. **Registrar las rutas** dentro del grupo `panel` de `routes/api.php`, bajo el
   `rol:` que corresponda.
6. **Escribir la pantalla React** en `resources/js/spa/paginas/<modulo>/`, quitar la
   ruta de la lista `EN_MIGRACION` en `App.jsx` y añadir sus `<Route>`.

Cada paso 1–2 es además una mejora del panel Blade: elimina la duplicación que
tendríamos si la API reimplementara las reglas.

## Convenciones del frontend

- **Todo el tráfico pasa por `api/cliente.js`.** Ahí se centralizan la cookie CSRF,
  el 401 que devuelve al login y la traducción de los 422 a errores por campo.
- **Los mensajes de error los escribe el servidor**, en español. El cliente valida
  solo para ayudar a quien escribe; la autoridad es el FormRequest.
- **El menú lo arma el servidor** (`MenuService::paraSpa`). React nunca decide qué
  opciones mostrar: así la interfaz no ofrece algo que el servidor rechazaría.
- **Los colores viven en variables CSS** (`resources/css/spa.css`). Los tres temas
  por segmento y el modo día/noche se resuelven redefiniendo esas variables, sin
  tocar componentes.
- **Accesibilidad**: cada campo usa el componente `Campo`, que asocia la etiqueta y
  el error por `aria-describedby`. El foco visible no se quita nunca.

## Lo que falta

| Módulo | CU | Estado |
|---|---|---|
| Usuarios | CU1 | Migrado |
| Perfiles: menú dinámico y matriz de acceso | CU2 | Migrado |
| Catálogo (programas, módulos, plan de estudio) | CU3 | Migrado |
| Cohortes y apertura de grupos | CU4 | Migrado |
| Logística de clases, aulas y agenda | CU5 | Migrado |
| Matriculación flexible | CU6 | Migrado |
| Estado de matrícula, pagos y tokens de API | CU7 | Migrado |
| Reportes y estadísticas | CU8 | Migrado |

## Por qué el panel Blade sigue publicado

No es una segunda interfaz que haya que mantener a la par. Comparte con la SPA
las mismas Form Requests y los mismos Services, así que las reglas del negocio
son unas solas: si cambia una validación, cambia en los dos lados a la vez.

Se conserva por dos razones concretas:

1. **Cobertura.** Diecinueve archivos de prueba ejercitan el dominio a través de
   esas vistas. Borrarlas obligaría a reescribir ese trabajo contra la API y,
   mientras tanto, dejaría el dominio sin red.
2. **Respaldo en la demostración.** Si algo de la SPA falla el día de la
   defensa, el panel sigue ahí.

Para retirarlo: borrar las rutas de `routes/web.php` salvo la de la SPA, las
vistas de `resources/views/` salvo `spa.blade.php`, los controladores que solo
devuelven vistas, y portar esas 19 pruebas a la API.

Requisitos transversales del enunciado, todos implementados: bitácora de
auditoría, los tres temas con modo día/noche, escalado tipográfico y alto
contraste, contador de visitas, búsqueda global, panel de estadísticas y módulo
de pagos electrónicos.

Cuando no quede ningún módulo en `EN_MIGRACION`, se mueve la SPA de `/app` a `/`
y se borran las vistas Blade del panel.
