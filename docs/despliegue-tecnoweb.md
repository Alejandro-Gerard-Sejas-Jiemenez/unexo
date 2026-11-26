# Correr en local y desplegar en tecnoweb

Destino: `https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2`
Base: `db_grupo06sa` en `db.tecnoweb.org.bo`, schema **`unexo_web`**.

## La base de datos del grupo es compartida

Verificado el 2026-11-22 conectándose con el usuario `grupo06sa`:

| Schema | Qué contiene | Lo tocamos |
|---|---|---|
| `unexo` | **Proyecto 1**, el sistema por correo en Java: `usuario`, `matricula`, `bitacora_comando`, `sincronizacion_sis`… con datos reales | No |
| `public` | Tablas de otros proyectos de la materia (almacén, productos, pedidos) y una tabla `migrations` ajena | No |
| `unexo_web` | **Este proyecto.** 25 tablas de Laravel | Sí |

Por eso `config/database.php` lee el schema de `DB_SEARCH_PATH` en vez de tenerlo
fijo en `public`: si apuntara a `public`, Laravel leería la tabla `migrations`
de otro grupo y chocaría con sus tablas `grupo`, `modulo` y `persona`.

## No desarrolles apuntando a la base del servidor

Medido el 2026-11-22 desde una máquina de casa: **cada consulta a
`db.tecnoweb.org.bo` tarda ~1,1 s** (conectar, 1,5 s). Una pantalla que hace
treinta consultas tarda más de medio minuto, y el login llegó a 57 s.

No es el código: es la latencia de red hasta el servidor de la cátedra. En
producción no pasa, porque ahí la aplicación y la base están en la misma red.

Para desarrollar usá una base local y dejá la del servidor para el despliegue.
Hay dos opciones; la de SQLite no necesita instalar nada:

```
DB_CONNECTION=sqlite
```

y crear el archivo `database/database.sqlite`. O, con Docker Desktop abierto:

```bash
docker compose up -d     # PostgreSQL 16 en el puerto 5433
```

con la configuración que trae `.env.example`.

## Correr en local

Requisitos: PHP 8.2+ con `pdo_pgsql`, Composer, Node 20+ y pnpm.

```bash
composer install
pnpm install
cp .env.example .env
php artisan key:generate
```

Después, en el `.env`, apuntar a la base del servidor:

```
DB_CONNECTION=pgsql
DB_HOST=db.tecnoweb.org.bo
DB_PORT=5432
DB_DATABASE=db_grupo06sa
DB_USERNAME=grupo06sa
DB_PASSWORD=<la del grupo>
DB_SEARCH_PATH=unexo_web
DB_SSLMODE=prefer
```

El schema y las tablas ya existen, así que **no hace falta migrar de nuevo**.
Si alguna vez hubiera que rehacerlo desde cero:

```bash
php artisan migrate --force      # crea las 25 tablas en unexo_web
php artisan db:seed --force      # datos de demostración
```

No usar `migrate:fresh` contra el servidor: borra las tablas del schema.

Para levantarlo hacen falta **dos procesos a la vez**, en dos terminales:

```powershell
php artisan serve
```

```powershell
pnpm run dev
```

Y entrar a `http://127.0.0.1:8000`, que redirige solo a `/app`.

Si se quiere probar el build de producción en lugar del servidor de desarrollo,
alcanza con `pnpm run build` y entonces basta `php artisan serve`.

### Usuarios de prueba

Contraseña `Demo12345` para todos.

| Rol | Correo |
|---|---|
| Administrador | `admin@nexo.test` |
| Docente | `carlos.mendoza@nexo.test` |
| Estudiante | `andres.flores@nexo.test` |

## Subir al servidor

### 1. Preparar el paquete en local

```bash
pnpm run build
composer install --no-dev --optimize-autoloader
```

### 2. Qué se sube y qué no

Sube: `app/ bootstrap/ config/ database/ lang/ public/ resources/ routes/
storage/ vendor/ artisan composer.json`.

**No** subas: `node_modules/`, `tests/`, `.git/`, `.env`, `.env.respaldo-*`.
El `.env` se crea en el servidor; nunca viaja con la contraseña dentro.

Por SFTP, con el mismo usuario y contraseña de la base:

```
Host: tecnoweb.org.bo    Usuario: grupo06sa
```

### 3. El problema del subdirectorio

Laravel espera `public/` como raíz del sitio, pero la app cuelga de
`/inf513/grupo06sa/proyecto2`. Dos variantes:

**A. El directorio del grupo apunta a `public/`** (lo ideal). Nada más que hacer.

**B. Dejar la app en una carpeta hermana** y en el directorio público solo el
contenido de `public/`:

```
/home/grupo06sa/public_html/inf513/grupo06sa/
├── app-nexo/          ← todo el proyecto menos public/
└── proyecto2/         ← el contenido de public/
    ├── index.php      ← se editan las dos rutas de require
    ├── .htaccess
    └── build/
```

En `index.php`, las dos líneas a cambiar:

```php
require __DIR__.'/../app-nexo/vendor/autoload.php';
$app = require_once __DIR__.'/../app-nexo/bootstrap/app.php';
```

El código ya está preparado para la subruta: `spa.blade.php` publica el prefijo
en `<meta name="base-path">`, que React Router y el cliente HTTP leen
(`resources/js/spa/base.js`). Sale de `APP_URL`, así que basta con que esa
variable incluya la ruta completa.

### 4. En el servidor

```bash
cp .env.produccion.example .env
nano .env                      # completar DB_PASSWORD
php artisan key:generate
chmod -R 775 storage bootstrap/cache
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Las migraciones **no** se corren de nuevo: el schema `unexo_web` ya está creado
con sus datos desde local.

## Pagos con Stripe (modo de prueba)

Spec 010. Antes de activar `PAYMENTS_DRIVER=stripe`:

- Correr la migración nueva (`php artisan migrate --force`) contra la base que
  use cada entorno. Nunca `migrate:fresh` contra el servidor.
- El destino del webhook (`/api/webhooks/stripe`) debe enviar
  `checkout.session.completed`, `checkout.session.async_payment_succeeded`,
  `checkout.session.async_payment_failed` y `checkout.session.expired`.
- Poner en el `.env` el `whsec_` de ese destino y en `STRIPE_API_VERSION` la
  misma versión de API que se eligió en él.
- La clave restringida necesita escritura en Checkout Sessions y lectura en
  PaymentIntents.

## Comprobaciones después de subir

| Qué | Cómo |
|---|---|
| La SPA carga | Abrir `/inf513/grupo06sa/proyecto2/` (redirige a `/app`) |
| Los assets resuelven | Sin 404 de `/build/...` en la consola del navegador |
| El prefijo llegó bien | Ver el `<meta name="base-path">` en el HTML: debe decir `/inf513/grupo06sa/proyecto2` |
| El login funciona | Si da 419, revisar `SESSION_PATH` y `SESSION_DOMAIN` |
| La API del panel responde | `/api/panel/me` con sesión iniciada |
| La API del CRM sigue viva | `/api/v1/crm/catalogo` con el token Bearer |
| El contador suma | Recargar una vista y mirar el pie |

## Cuidados

- **`APP_DEBUG=false`** en producción: con `true` se filtran rutas y
  credenciales en las páginas de error.
- Después de cada cambio del `.env`, repetir `php artisan config:cache` o la
  configuración vieja sigue en caché.
- El `.env` nunca se versiona. `.gitignore` ignora todo `.env*` salvo las dos
  plantillas.
- No apuntar `DB_SEARCH_PATH` a `unexo` ni a `public`: son de otros sistemas.
