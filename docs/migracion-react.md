# Migración a React (arquitectura de 3 capas)

Estado: **fundación lista + CU1 Usuarios migrado**. El resto de los módulos sigue
atendido por el panel Blade y se migra uno por uno repitiendo el patrón de abajo.

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
| Perfiles y matriz de acceso | CU2 | Menú dinámico listo; falta la pantalla de la matriz |
| Catálogo (programas y módulos) | CU3 | Pendiente |
| Cohortes y grupos | CU4 | Pendiente |
| Logística de clases | CU5 | Pendiente |
| Matriculación | CU6 | Pendiente |
| Estado de matrícula y pagos | CU7 | Pendiente |
| Reportes y estadísticas | CU8 | Pendiente |

Requisitos transversales del enunciado todavía sin implementar: bitácora de
auditoría, los tres temas con modo día/noche, escalado tipográfico y alto
contraste, contador de visitas, búsqueda global y módulo de pagos electrónicos.

Cuando no quede ningún módulo en `EN_MIGRACION`, se mueve la SPA de `/app` a `/`
y se borran las vistas Blade del panel.
