# Subir NEXO SIS al servidor con FileZilla

Guía para el equipo. Destino:
`https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2`

Por FileZilla no hay consola, así que **todo lo que necesita consola ya está
hecho**: la base está migrada con sus datos y el `.env` viene con la `APP_KEY`
generada. Solo hay que copiar archivos y ajustar permisos.

## 1. Preparar el paquete (una sola persona, en su máquina)

```powershell
pnpm run build
```

```powershell
composer install --no-dev --optimize-autoloader
```

El primero genera `public/build/`, el segundo deja `vendor/` sin las
dependencias de desarrollo. Los dos son obligatorios: el servidor no puede
ejecutarlos.

## 2. Conectarse

En FileZilla, **Archivo → Gestor de sitios → Nuevo sitio**:

| Campo | Valor |
|---|---|
| Servidor | `tecnoweb.org.bo` |
| Protocolo | SFTP, y si no conecta, FTP |
| Usuario | `grupo06sa` |
| Contraseña | la del grupo |

## 3. Qué se sube

Dentro de `/home/grupo06sa/public_html/inf513/grupo06sa/proyecto2/`:

**Sí:** `app/ bootstrap/ config/ database/ lang/ public/ resources/ routes/
storage/ vendor/ artisan composer.json composer.lock`

**No:** `node_modules/` (son miles de archivos y no se usan en el servidor),
`tests/`, `.git/`, `.github/`, ningún `.env*` salvo el del paso 4.

> `vendor/` tarda bastante porque son muchos archivos chicos. En FileZilla,
> **Transferencia → Transferencias simultáneas → 4** acelera mucho.

## 4. El archivo `.env`

Subir el archivo `.env.servidor` que les pasa Carlos y **renombrarlo a `.env`**
ya en el servidor (clic derecho → Renombrar). Tiene que quedar junto a
`artisan`, no dentro de `public/`.

Va por canal privado, nunca al repositorio: lleva la contraseña de la base.

## 5. Permisos de escritura

Laravel necesita escribir en dos carpetas. En FileZilla, clic derecho sobre
cada una → **Permisos de archivo** → `775`, y marcar **“Redirigir a
subdirectorios”** y **“Aplicar solo a directorios”**:

- `storage/`
- `bootstrap/cache/`

Si el sitio muestra una pantalla en blanco o un error 500, nueve de cada diez
veces es esto.

## 6. Dónde apunta el dominio

Laravel espera que `public/` sea la raíz del sitio. Dos casos:

**A. Si el directorio del grupo ya apunta a `public/`:** no hay nada más que
hacer.

**B. Si no se puede configurar:** dejar la aplicación en una carpeta hermana y
en `proyecto2/` solo el contenido de `public/`:

```
inf513/grupo06sa/
├── app-nexo/        ← todo el proyecto menos public/
└── proyecto2/       ← el contenido de public/
    ├── index.php    ← editar las dos líneas de abajo
    ├── .htaccess
    └── build/
```

En `index.php`:

```php
require __DIR__.'/../app-nexo/vendor/autoload.php';
$app = require_once __DIR__.'/../app-nexo/bootstrap/app.php';
```

## 7. Probar

Entrar a `https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2/`

| Qué | Qué tiene que pasar |
|---|---|
| La raíz | Redirige a `/app` y aparece el login con el logo |
| Iniciar sesión | `admin@nexo.test` / `Demo12345` |
| Los estilos | Si se ve sin formato, falta subir `public/build/` |
| El menú | Cambia según el rol con el que entres |
| El pie | Muestra el contador de visitas |
| La API del CRM | `/api/v1/crm/catalogo` con el token Bearer |

Usuarios de prueba, todos con `Demo12345`:

| Rol | Correo |
|---|---|
| Administrador | `admin@nexo.test` |
| Docente | `carlos.mendoza@nexo.test` |
| Estudiante | `andres.flores@nexo.test` |

## Si algo falla

| Síntoma | Causa casi segura |
|---|---|
| Pantalla en blanco o error 500 | Permisos de `storage/` y `bootstrap/cache/` |
| Se ve sin estilos | Falta `public/build/`; correr `pnpm run build` y subirlo |
| Error 419 al iniciar sesión | `SESSION_PATH` o `SESSION_DOMAIN` del `.env` no coinciden con la URL real |
| 404 en todas las rutas menos la raíz | El `.htaccess` no se subió (FileZilla oculta los archivos que empiezan con punto: **Servidor → Forzar mostrar archivos ocultos**) |
| "No application encryption key" | El `.env` no quedó junto a `artisan`, o quedó con otro nombre |

Para ver el error real, poner `APP_DEBUG=true` en el `.env` un momento, y
volverlo a `false` apenas se resuelva.

## Lo que NO hay que hacer

- No correr `migrate:fresh` contra el servidor: borraría las tablas y los datos.
- No cambiar `DB_SEARCH_PATH`. Tiene que decir `unexo_web`. Los otros schemas
  son del Proyecto 1 y de otros grupos de la materia.
- No subir ningún `.env` al repositorio.
