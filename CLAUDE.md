# NEXO SIS - Sistema de Administración Académica

Proyecto de Tecnología Web (UAGRM). Equipo: **Grupo 6 SA**. Idioma de trabajo: español informal. Prefiere salidas concretas (código que corre, pasos claros).

## Stack
- Laravel 12 (PHP 8.2; Laravel 13 exige PHP 8.3) con arquitectura MVC. Blade con **Tailwind 4 + Vite** (`resources/css/app.css`, `vite.config.js`); sin Breeze. No existe `public/css/`.
- Diseño: paleta NEXO definida como tokens en `resources/css/app.css` (`indigo` = azul `#082F4E`, `violet` = cian `#07B0B0`, `rose` = rojo `#E01E26`; los nombres de clase son de Tailwind, los colores son de marca). Fuente `Instrument Sans` cargada desde Google Fonts en `layouts/app.blade.php`. Layout único `layouts/app.blade.php` (el login también lo extiende).
- PostgreSQL 16 en Docker (`docker-compose.yml`, contenedor `nexo_sis_db`, puerto host **5433**). Todo en localhost.
- IDs siempre **UUID** (`HasUuids`, `uuid('id')->primary()`, `foreignUuid`). Nunca enteros autoincrementales.
- Datos inventados con prefijo `DEMO-` en los seeders. Contraseña de los usuarios demo: `Demo12345` (admin: `admin@nexo.test`).

## Cómo arrancar
```
docker compose up -d        # esperar ~10 s si el volumen es nuevo
php artisan migrate:fresh --seed
npm install                 # solo la primera vez
npm run build               # compila los estilos (o `npm run dev` mientras se edita)
php artisan serve           # http://localhost:8000
```
Sin `npm run build` o `npm run dev` la página se ve sin estilos. Si existe `public/hot` pero el servidor de Vite no corre, borrar `public/hot` (Laravel lo toma por encima de `public/build`).

## Alcance (cronograma oficial, Grupo 6 SA)
- **5 oct:** login por rol (Administrador, Docente, Estudiante), ABM de perfiles con correo institucional, programas y módulos, estado comercial (borrador/abierto/cerrado).
- **12 oct:** cohortes con fechas, docente y datos de Meet/aula, inscripción a módulo suelto o cohorte, estados de matrícula (pendiente, pagado, retirado), endpoints API consumidos por el CRM.
- **26 oct:** flujo completo con el Grupo 5. **16 nov:** entrega final, integración SIS-CRM, manual de usuario.
- Lo que solo pide el grupo SC (baja lógica, áreas/temario, choque de horario, cupo mínimo, comprobante, descuentos, notas, logos, responsive) NO es nuestro, salvo que se confirme con la ingeniera.

## Decisiones tomadas
- Actores del sistema: solo Administrador, Docente y Estudiante. El CRM no es un actor: es un endpoint externo.
- El CRM consume nuestro catálogo (API de solo lectura, `/api/v1`, token Bearer). Nosotros recibimos los estudiantes convertidos (dirección de la integración pendiente de confirmar con el equipo del CRM).
- El comprobante de pago lo sube el estudiante y una operadora de caja lo revisa (fase posterior).
- El orden de módulos de un programa se guarda en `programa_modulo.orden` y se renumera tras cada cambio.
- `cantidad_modulos` no es columna: se calcula contando los módulos.
- Los módulos son **totalmente libres** (05/10/2026): no hay requisitos ni la marca «independiente». Cualquier módulo se puede tomar suelto esté o no en un programa; el CRM ve todo módulo/programa en estado `abierto`. Se quitaron las columnas `requisitos_recomendados` y `es_independiente` (migración `2026_10_05_000001_*`).
- Reglas de negocio irán en `app/Services`; validación inline por ahora, pasar a FormRequests si crece.

## Estructura relevante
- `app/Enums` (Rol, EstadoComercial), `app/Models` (User, Programa, Modulo, Cohorte, Grupo, Aula, Matricula, InscripcionGrupo), `app/Http/Controllers` (Login, Panel, Usuario, Programa, Modulo, Cohorte, Grupo, Aula, Matricula, MisCursos, MisGrupos, Calendario), `app/Http/Middleware/RolRequerido.php` (alias `rol:`).
- `routes/web.php`, `resources/views/*` (con `components/` y `partials/`), `resources/css/app.css`, `lang/es/validation.php`.
- `database/migrations/2026_10_04_*` (8 migraciones), `2026_10_05_*` (quita requisitos e independiente de módulos) y seeders `UsuariosDemoSeeder`, `CatalogoDemoSeeder`.
- `tests/Feature` (Login, GestionAdmin, MisClases, CalendarioYBusqueda, OrdenModulos, PaginacionYLimites, ModulosLibres): 40 pruebas pasando.
- Límites de longitud, filas por página y tope de opciones de los `<select>`: `config/limites.php` (lo leen las validaciones y los componentes `x-campo*`).
- Ya existen cohortes, grupos, aulas, matrículas y calendario (fase del 12 oct, adelantada).

## Pendientes conocidos
- Probar en pantalla con los tres roles (Docker y las pruebas automáticas ya funcionan).
- Sin ruta de eliminar (`destroy`) en programas, módulos ni usuarios: solo se desactivan usuarios. Decidir si se pide (CU03, CU07, CU10) con la regla R2.
- Confirmar con el equipo del CRM: dirección de integración, campos mínimos del estudiante, autenticación y reintentos.
- Confirmar con la ingeniera: uso de Laravel y el alcance SA frente a SC.
- Sanctum y tabla `clientes_api` para la API (fase del 12 oct).
- Borrar la carpeta `.git` heredada de Composer antes de hacer `git init`.

## Documentación
Las notas están en el vault de Obsidian, carpeta `1. Proyectos/Tecnologia Web - NEXO SIS/` (índice: `Tecnologia Web - NEXO SIS.md`). Cada carpeta tiene su índice: leer el índice antes de abrir notas. Problemas ya resueltos: `3. Recursos/Soluciones y troubleshooting/`.

## Credenciales de prueba (solo desarrollo, datos inventados)
Contraseña de todos los usuarios: `Demo12345`. Se puede entrar con el correo personal o el institucional.

| Rol | Nombre | Correo personal | Correo institucional |
|---|---|---|---|
| Administrador | Marcela Rojas Vaca | admin@nexo.test | admin@nexo.edu.test |
| Docente | Carlos Mendoza Peña | carlos.mendoza@nexo.test | cmendoza@nexo.edu.test |
| Docente | Lucía Salvatierra Ortiz | lucia.salvatierra@nexo.test | lsalvatierra@nexo.edu.test |
| Estudiante | Andrés Flores Suárez | andres.flores@nexo.test | aflores@nexo.edu.test |
| Estudiante | Daniela Choque Mamani | daniela.choque@nexo.test | dchoque@nexo.edu.test |
| Estudiante | Mateo Justiniano Ribera | mateo.justiniano@nexo.test | mjustiniano@nexo.edu.test |

Base de datos (Docker): host `127.0.0.1`, puerto `5433`, base `nexo_sis`, usuario `nexo`, contraseña `nexo_dev`.

## Comandos útiles
```
# Docker / base de datos
docker compose up -d                     # levantar PostgreSQL
docker compose down                      # apagar (conserva los datos)
docker compose down -v                   # apagar y BORRAR los datos
docker ps -a                             # ver si nexo_sis_db está Up
docker compose logs db --tail 15         # ver si dice "ready to accept connections"
docker exec -it nexo_sis_db psql -U nexo -d nexo_sis   # consola SQL (\dt lista tablas)

# Laravel
php artisan migrate:fresh --seed         # recrear tablas y datos demo
php artisan db:seed                      # solo datos demo (idempotente)
php artisan config:clear                 # tras cambiar .env
php artisan route:list                   # ver rutas
php artisan tinker                       # consola PHP con el modelo cargado
php artisan test                         # pruebas
php artisan serve                        # http://localhost:8000
```

## Notas de operación
- Si `migrate` falla con "server closed the connection" justo después de `up -d`, esperar ~10 s y repetir.
- Extensiones de PHP necesarias en `php.ini`: openssl, fileinfo, zip, mbstring, curl, pdo_pgsql, pgsql.
- Estas credenciales son solo para desarrollo local. No usarlas en un servidor real.

## Flujo para arrancar el proyecto (cada vez que se retoma el trabajo)
1. Abrir Docker Desktop y esperar a que diga que está corriendo.
2. En la carpeta del proyecto: `docker compose up -d`.
3. Comprobar: `docker ps -a` (nexo_sis_db en `Up`) y `docker compose logs db --tail 5` (debe decir `ready to accept connections`). Si el volumen es nuevo, esperar ~10 s.
4. Si es la primera vez o la base está vacía: `php artisan migrate:fresh --seed`. Si ya tiene datos, no hace falta.
5. `php artisan serve` y abrir http://localhost:8000.
6. Entrar con `admin@nexo.test` / `Demo12345` y verificar que carga el panel.

Primera vez en una PC nueva: instalar PHP 8.2+ con las extensiones listadas, Composer y Docker; `composer install`; copiar `.env.example` a `.env`; `php artisan key:generate`; luego los pasos 1 a 6.

## Flujo para hacer cambios

**Cambiar el `.env`:** editar, correr `php artisan config:clear` y reiniciar `php artisan serve`. Mantener `.env.example` igual en lo que no sea secreto.

**Agregar o cambiar tablas:**
1. `php artisan make:migration crear_xxx_table` (en español, tablas en plural).
2. Usar `$table->uuid('id')->primary()` y `foreignUuid(...)->constrained()->restrictOnDelete()`. Nunca `id()` ni `foreignId`.
3. Valores cerrados (estados, roles): `string` + CHECK en la migración + Enum en `app/Enums` + cast en el modelo.
4. Mientras no haya datos reales: `php artisan migrate:fresh --seed`. No editar migraciones ya compartidas con el equipo: crear una nueva.

**Agregar un módulo CRUD:**
1. Modelo con `HasUuids` y `$fillable`: `php artisan make:model Xxx`, luego añadir `use HasUuids`.
2. Controlador en `app/Http/Controllers`, validación con `Rule::enum` y `Rule::unique(...)->ignore($modelo)`.
3. Rutas en `routes/web.php` dentro del grupo `rol:administrador` si es solo de admin; usar `->parameters([...])` para nombres en español.
4. Vistas en `resources/views/xxx/` extendiendo `layouts.app`; mensajes de éxito con `->with('ok', ...)`.
5. Agregar el enlace al menú en `layouts/app.blade.php`.
6. Textos de validación nuevos en `lang/es/validation.php` (sección `attributes`).

**Cambiar los datos demo:** editar `database/seeders/*`. Los seeders usan `firstOrCreate`, así que `php artisan db:seed` no duplica. Para partir de cero: `migrate:fresh --seed`.

**Endpoints de la API (fase del 12 oct):** `routes/api.php` bajo `/api/v1`, solo lectura para el CRM, autenticación con token Bearer (Sanctum, modelo `ClienteApi`). Usar API Resources para dar forma al JSON, nunca devolver modelos crudos.

**Antes de dar un cambio por terminado:**
- `php artisan route:list` si se tocaron rutas.
- `php artisan test`.
- Probar en pantalla con los tres roles: admin, docente y estudiante. Un docente o estudiante que entre a una ruta de admin debe recibir 403.
- Actualizar la nota de avance en Obsidian y, si hubo un error nuevo, una nota en `3. Recursos/Soluciones y troubleshooting/` con su índice.

**Reiniciar todo desde cero (borra datos):**
```
docker compose down -v
docker compose up -d
# esperar ~10 s
php artisan migrate:fresh --seed
```
