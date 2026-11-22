# Despliegue en www.tecnoweb.org.bo

Destino: `https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2`
Base de datos: `db.tecnoweb.org.bo`, esquema del grupo.

## Lo que hace falta y no está en el repositorio

1. Usuario y contraseña de **SSH o FTP** del grupo en el servidor.
2. Usuario, contraseña y nombre del **esquema PostgreSQL** asignado.
3. Saber si el servidor permite ejecutar `composer` y `php artisan` por SSH.
   Si solo hay FTP, hay que subir `vendor/` y `public/build/` ya construidos
   desde la máquina local.

## El problema del subdirectorio

Laravel espera que `public/` sea la raíz del sitio, pero aquí la aplicación
cuelga de `/inf513/grupo06sa/proyecto2`. Dos formas de resolverlo:

**A. El directorio del grupo apunta a `public/`** (lo ideal, si se puede
configurar un alias o un symlink). No hay que tocar nada más.

**B. Subir la aplicación a una carpeta hermana** y dejar en el directorio
público solo el contenido de `public/`, con `index.php` apuntando una carpeta
más arriba:

```
inf513/grupo06sa/
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
en una etiqueta `<meta name="base-path">` que React Router y el cliente HTTP
leen (`resources/js/spa/base.js`). Sale de `APP_URL`, así que basta con que esa
variable incluya la ruta completa.

## Pasos

```bash
# 1. En local: construir los assets
pnpm install
pnpm run build

# 2. En local: dependencias de producción, sin las de desarrollo
composer install --no-dev --optimize-autoloader

# 3. Subir todo menos: node_modules/, tests/, .git/, .env
#    Sí hay que subir: vendor/ y public/build/

# 4. En el servidor: crear el .env
cp .env.produccion.example .env
php artisan key:generate
# completar DB_PASSWORD y DB_DATABASE

# 5. Permisos de escritura
chmod -R 775 storage bootstrap/cache

# 6. Crear el esquema
php artisan migrate --force
php artisan db:seed --force      # solo si se quieren los datos de demostración

# 7. Cachear configuración y rutas
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Comprobaciones después de subir

| Qué | Cómo |
|---|---|
| La SPA carga | Abrir `/inf513/grupo06sa/proyecto2/app` |
| Los assets resuelven | Que no haya 404 de `/build/...` en la consola del navegador |
| El login funciona | La cookie XSRF debe llegar antes del POST; si da 419, revisar `SESSION_PATH` |
| La API del panel responde | `/api/panel/me` con sesión iniciada |
| La API del CRM sigue viva | `/api/v1/crm/catalogo` con el token Bearer |
| El contador suma | Recargar una vista y mirar el pie |

## Cuidados

- **El `.env` no se versiona.** La contraseña de la base va solo en el servidor.
- **`APP_DEBUG=false`** en producción: con `true` se filtran rutas y credenciales
  en las páginas de error.
- Si se vuelve a desplegar, repetir `php artisan config:cache` después de cada
  cambio del `.env`, o la configuración vieja sigue en caché.
- La base local apunta hoy a Neon (AWS). Al pasar a `db.tecnoweb.org.bo` hay que
  correr las migraciones de nuevo: son esquemas distintos.
