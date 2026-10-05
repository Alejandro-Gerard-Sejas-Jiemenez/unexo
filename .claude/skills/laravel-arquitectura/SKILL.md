---
name: laravel-arquitectura
description: Arquitectura, estructura de archivos, convenciones de nombres, prácticas de codificación y reglas para un proyecto Laravel 12, según la documentación oficial (laravel.com/docs/12.x) y adaptadas a NEXO SIS (Blade, PostgreSQL, UUID, español). Úsala al crear o revisar controladores, modelos, migraciones, rutas, Form Requests, servicios y vistas, o al decidir dónde colocar un archivo.
---

# Arquitectura de un proyecto Laravel 12

> Fuente: documentación oficial de Laravel 12.x (Directory Structure, Controllers, Eloquent, Validation). Adaptado a **NEXO SIS**: PHP 8.2, Laravel 12, Blade con CSS, PostgreSQL, IDs **UUID**, nombres de dominio en **español**.

## 1. Principio rector

Laravel **no impone** dónde va cada clase: lo único obligatorio es que Composer pueda autocargarla (PSR-4 bajo el namespace `App\`). La estructura por defecto es un buen punto de partida para proyectos chicos y grandes; **no sobre-estructures** (no crees capas ni carpetas "por si acaso"). Agrega carpetas solo cuando un `make:` las genera o cuando una necesidad real lo justifica.

## 2. Estructura de directorios (raíz)

| Carpeta | Qué contiene |
|---|---|
| `app/` | El código de la aplicación (namespace `App`, PSR-4). |
| `bootstrap/` | `app.php` (arranca el framework) y `cache/` (rutas y servicios cacheados). |
| `config/` | Archivos de configuración. Léelos para conocer las opciones. |
| `database/` | `migrations/`, `factories/`, `seeders/`. |
| `public/` | `index.php` (punto de entrada) y assets públicos (CSS, JS, imágenes). |
| `resources/` | `views/` (Blade) y assets sin compilar. |
| `routes/` | `web.php` (con sesión, CSRF, cookies) y `console.php`. `api.php` solo si se instala (`install:api`). |
| `storage/` | Logs, vistas Blade compiladas, cachés, archivos generados. `storage/app/public` para archivos de usuario (enlazar con `php artisan storage:link`). |
| `tests/` | Pruebas. Cada clase termina en `Test`. Correr con `php artisan test`. |
| `vendor/` | Dependencias de Composer (no se edita ni se versiona). |

## 3. Dentro de `app/`

Por defecto solo existen `Http/`, `Models/` y `Providers/`. El resto se crea cuando lo generas con `make:`.

| Carpeta | Qué va aquí | Se crea con |
|---|---|---|
| `Http/Controllers/` | Controladores. | `make:controller` |
| `Http/Middleware/` | Middleware. | `make:middleware` |
| `Http/Requests/` | Form Requests (validación + autorización). | `make:request` |
| `Models/` | Modelos Eloquent (uno por tabla). | `make:model` |
| `Providers/` | Service providers (bootstrap, bindings). Ya existe `AppServiceProvider`. | `make:provider` |
| `Console/Commands/` | Comandos Artisan propios. | `make:command` |
| `Policies/` | Autorización por recurso (quién puede hacer qué). | `make:policy` |
| `Rules/` | Reglas de validación reutilizables. | `make:rule` |
| `Jobs/`, `Events/`, `Listeners/`, `Notifications/`, `Mail/`, `Exceptions/` | Según se necesiten. | sus `make:` |
| `Services/` | **No es de Laravel**, pero es una convención sana: lógica de negocio que no cabe en el controlador ni en el modelo (cálculos, flujos con transacciones). En NEXO SIS ya se usa (`ResumenCatalogoService`, `MatriculaService`). | a mano |

`Http/` y `Console/` son **la puerta de entrada** (HTTP y CLI) al núcleo; no deben contener la lógica de negocio, solo orquestarla.

## 4. Convenciones de nombres

| Elemento | Convención | Ejemplo |
|---|---|---|
| Controlador | `PascalCase` + `Controller`, singular | `CohorteController` |
| Modelo | `PascalCase`, **singular** | `Cohorte`, `Matricula` |
| Tabla | `snake_case`, **plural** | `cohortes`, `matriculas` |
| Migración | `snake_case` con acción | `2026_10_04_000005_create_cohortes_table` |
| Form Request | `PascalCase` + acción + `Request` | `StoreCohorteRequest`, `UpdateGrupoRequest` |
| Enum | `PascalCase` | `EstadoMatricula` |
| Service | `PascalCase` + `Service` | `MatriculaService` |
| Vista Blade | carpeta por recurso en `kebab`/plural + acción | `cohortes/index.blade.php`, `cohortes/form.blade.php` |
| Ruta nombrada | `recurso.accion` | `cohortes.index`, `matriculas.estado` |
| Variable / método | `camelCase` autoexplicativo | `fechaInicio`, `inscribirEnCohorte()` |

> Nota NEXO SIS: las tablas del dominio van en **español** (`programas`, `modulos`). Como Laravel pluraliza en inglés, cada modelo declara `protected $table = '...'` explícito. `users` se mantiene así para el login.

## 5. Controladores

- Generar con `php artisan make:controller XxxController`. Para CRUD completo: `--resource` (opcional `--model=Xxx` para route-model binding, `--requests` para generar los Form Requests).
- **Resource controller:** un método por acción. Registrar con `Route::resource('recurso', XxxController::class)`:

| Verbo | URI | Acción | Ruta |
|---|---|---|---|
| GET | `/recurso` | index | recurso.index |
| GET | `/recurso/create` | create | recurso.create |
| POST | `/recurso` | store | recurso.store |
| GET | `/recurso/{x}` | show | recurso.show |
| GET | `/recurso/{x}/edit` | edit | recurso.edit |
| PUT/PATCH | `/recurso/{x}` | update | recurso.update |
| DELETE | `/recurso/{x}` | destroy | recurso.destroy |

- Usar `->only([...])` / `->except([...])` para registrar solo las acciones necesarias (NEXO SIS no usa `destroy`: borrado lógico por estado).
- **Controlador de acción única:** cuando una acción es compleja, un controlador con `__invoke()` (`make:controller Xxx --invokable`), ruta `Route::post('/x', Xxx::class)`.
- **Inyección de dependencias:** el service container resuelve el controlador. Type-hint en el constructor (servicios compartidos) o en el método (p. ej. `Request $request`, o un service puntual). Los parámetros de ruta van **después** de las dependencias: `update(Request $request, Cohorte $cohorte)`.
- **Route-model binding:** type-hint del modelo en el método para recibir la instancia ya cargada (404 automático si no existe).
- **Mantén los controladores enfocados.** Si necesitas métodos fuera del set CRUD de forma habitual, parte el controlador o mueve la lógica a un `Service`.
- **Middleware:** preferir asignarlo en las rutas (`->middleware('rol:administrador')`). Para lógica por método dentro del controlador, implementar `HasMiddleware::middleware()`.

## 6. Modelos (Eloquent)

- Uno por tabla. `php artisan make:model Xxx` (añadir `-m` para la migración, `-mf` con factory).
- **Convenciones:** tabla = plural snake_case del modelo; PK = `id`; `created_at`/`updated_at` automáticos.
- **UUID (regla de NEXO SIS):** usar el trait `HasUuids`. En la migración `uuid('id')->primary()` y `foreignUuid(...)->constrained()->restrictOnDelete()`. Nunca enteros autoincrementales.
- **Mass assignment:** declarar `protected $fillable = [...]` (lista blanca). No mezclar con `$guarded`.
- **Casts:** usar el método `casts()` para enums, fechas, booleanos, hashed: `'estado' => EstadoMatricula::class`, `'fecha_inicio' => 'date'`, `'password' => 'hashed'`.
- **Relaciones:** métodos con el tipo de retorno declarado (`BelongsTo`, `HasMany`, `BelongsToMany`). Nombres en singular para 1-a-1/pertenece y plural para colecciones.
- **Scopes locales:** encapsular filtros repetidos como `scopeXxx(Builder $q)` y usarlos como `Modelo::xxx()`. En NEXO SIS: `Programa::abierto()`, `Modulo::huerfano()`.
- **Strictness (recomendado en desarrollo):** considerar `Model::shouldBeStrict()` en `AppServiceProvider::boot()` para detectar lazy-loading no intencionado, asignación masiva silenciosa y acceso a atributos inexistentes.
- **Nada de lógica HTTP en el modelo:** el modelo consulta y expresa reglas de datos; los flujos de negocio con varios pasos van en un `Service`.

## 7. Validación

- **Inline** (`$request->validate([...])`) para casos simples y de un solo uso. Si falla: redirect-back con errores (web) o JSON 422 (XHR).
- **Form Request** (enfoque recomendado por la documentación) cuando la validación es compleja, se reutiliza, necesita autorización o mensajes propios:

```php
php artisan make:request StoreCohorteRequest
```

```php
class StoreCohorteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->rol === \App\Enums\Rol::Administrador;
    }

    public function rules(): array
    {
        return [
            'programa_id' => ['required', 'uuid', 'exists:programas,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
        ];
    }

    public function messages(): array
    {
        return ['fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior al inicio.'];
    }
}
```

En el controlador se type-hint y la validación ocurre antes de entrar: `public function store(StoreCohorteRequest $request)` y luego `$request->validated()`. Métodos útiles: `authorize()`, `rules()`, `messages()`, `attributes()`, `prepareForValidation()`, `after()`.

> NEXO SIS hoy usa validación inline con `$request->validate()` y textos en `lang/es/validation.php`. Pasar a Form Requests cuando un controlador crezca o repita reglas.

## 8. Rutas

- Definir en `routes/web.php` (sesión + CSRF). Nombrar siempre las rutas (`->name('...')`) y referirlas con `route('...')`.
- Agrupar por middleware: `Route::middleware('auth')->group(...)`, y dentro `Route::middleware('rol:administrador')->group(...)`.
- Rutas suplementarias (fuera del CRUD) se declaran **antes** de `Route::resource(...)` para que no las pise.
- `php artisan route:list` para revisar el mapa de rutas.

## 9. Migraciones

- `php artisan make:migration crear_xxx_table` (en español, tabla en plural).
- `uuid('id')->primary()` y claves foráneas con `foreignUuid(...)->constrained(...)->restrictOnDelete()` (cumple "cero borrado físico": se opera por estados).
- Valores cerrados: `string` + `CHECK` en la migración + Enum en `app/Enums` + cast en el modelo.
- Mientras no haya datos reales compartidos: `php artisan migrate:fresh --seed`. Si la migración ya se compartió con el equipo, **no la edites**: crea una nueva.
- Los `CHECK` con `DB::statement` solo aplican en PostgreSQL; en pruebas (SQLite) hay que saltarlos (ver `App\Support\RestriccionCheck`).

## 10. Vistas (Blade)

- Una carpeta por recurso en `resources/views/` con `index`, `create`/`form`, `edit`, `show` según haga falta.
- Reutilizar con componentes (`resources/views/components/`) e includes (`@include`). En NEXO SIS: `x-campo`, `x-campo-select`, `partials/buscador`.
- No poner lógica de negocio en la vista; solo presentación.

## 11. Reglas duras del proyecto (resumen)

1. **UUID siempre**, nunca enteros autoincrementales.
2. **Borrado lógico por estado**, nunca `DELETE` físico de datos del dominio.
3. **Tablas y dominio en español**; `$table` explícito en cada modelo.
4. **Lógica de negocio en `app/Services`**, no en controladores ni vistas.
5. **Validación** inline por ahora; Form Request cuando crezca.
6. **RBAC** con middleware `rol:...`; un rol sin permiso recibe 403.
7. **Enums de PHP** para estados, con `CHECK` en la base.
8. Tipado estricto, sin `any`-equivalentes; métodos cortos y con una sola responsabilidad.

## 12. Comandos Artisan útiles

```bash
php artisan make:controller XxxController --resource --model=Xxx
php artisan make:model Xxx -m          # modelo + migración
php artisan make:request StoreXxxRequest
php artisan make:middleware XxxMiddleware
php artisan make:seeder XxxSeeder
php artisan migrate:fresh --seed
php artisan route:list
php artisan test
php artisan list make                  # ver todo lo que se puede generar
```

## 13. Checklist antes de dar por terminado un módulo

- [ ] Modelo con `HasUuids`, `$table`, `$fillable`, `casts()` y relaciones tipadas.
- [ ] Migración con UUID, FKs `restrictOnDelete`, CHECK de estados.
- [ ] Controlador enfocado (CRUD o acción única), sin lógica de negocio pesada.
- [ ] Lógica con transacciones o reglas multi-paso en un `Service`.
- [ ] Validación presente (inline o Form Request) y mensajes en español.
- [ ] Rutas nombradas y protegidas por el middleware de rol correcto.
- [ ] Vistas con estados de carga/vacío/error y componentes reutilizables.
- [ ] `php artisan test` en verde y `route:list` sin sorpresas.
