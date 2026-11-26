# Monitoreo y restablecimiento

URL base: `https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2`

## Cómo saber si algo falló

| Dónde | Qué responde | Quién puede verlo |
|---|---|---|
| `GET /health` | `{"estado":"ok"}` (200) o `{"estado":"falla"}` (503). Sin detalles. | Cualquiera (sirve para monitores) |
| `GET /api/panel/salud` | Todos los chequeos, con mensaje y comando de solución | Solo administrador con sesión |
| `php artisan nexo:salud` | El mismo detalle, en la consola del servidor | Quien tenga SSH |

`/health` no abre sesión ni escribe en la base: responde aunque la base esté caída o vacía.
Cuenta como falla: sin conexión, sin esquema, migraciones pendientes, tablas faltantes,
base sin roles ni administrador, o sin `APP_KEY`. Lo demás (permisos de `storage`, Stripe,
`APP_DEBUG`, extensiones) aparece como alerta o falla en el detalle del administrador.

### Aviso automático (monitor externo)
1. Crear una cuenta gratuita en UptimeRobot (o similar).
2. Nuevo monitor tipo **HTTP(s)**, URL `…/proyecto2/health`, intervalo de 5 minutos.
3. Añadir el correo del grupo como contacto de alerta.

El monitor avisa cuando recibe un 503 o cuando el sitio no responde. Cada falla queda además
en `storage/logs/laravel.log` como `Health: fallan los chequeos críticos (...)`.

## Restablecer

Entrar por SSH, ir a la carpeta y ver el diagnóstico:
```bash
ssh grupo06sa@tecnoweb.org.bo
cd ~/proyecto2
php artisan nexo:salud
```
Cada línea con `FALLA` trae su **Solución**. Si falta esquema, tablas o datos:
```bash
php artisan nexo:salud --reparar
```
Muestra qué hará, pide confirmación y ejecuta solo lo necesario: crear el esquema
`unexo_web`, `migrate --force` y `db:seed --force`. **Nunca borra nada** (los seeders
no duplican datos). Con ese comando `/health` vuelve a `ok`.

### Casos

| Qué pasó | Qué hacer |
|---|---|
| Borraron el esquema `unexo_web` o todas sus tablas | `php artisan nexo:salud --reparar` |
| Borraron el contenido (tablas vacías) | `php artisan nexo:salud --reparar` (vuelve a sembrar) |
| Borraron solo algunas tablas pero quedó la tabla `migrations` | Ver abajo |
| "No se puede conectar a la base" | No es una base borrada. Revisar que `.env` tenga `DB_HOST=localhost` (desde el servidor la base está en la misma máquina), la contraseña, y avisar al ingeniero si PostgreSQL está apagado. `--reparar` se niega a actuar sin conexión. |
| Sin permiso de escritura | `chmod -R 775 storage bootstrap/cache` (si no basta, `777` temporal) |
| Falta `APP_KEY` | `php artisan key:generate` y luego `php artisan config:cache` |
| Stripe sin claves | Añadir `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET` al `.env` y `php artisan config:cache` |
| El `.env` cambió y nada se nota | `php artisan config:clear && php artisan config:cache` |

**Tablas sueltas borradas:** `migrate` cree que ya están creadas porque la tabla `migrations`
lo registra. Primero mira cuál falta con `php artisan nexo:salud`, luego quita su migración
del registro y vuelve a migrar:
```bash
php artisan migrate:status                    # localizar la migración de esa tabla
psql "host=localhost dbname=db_grupo06sa user=grupo06sa" \
  -c "delete from unexo_web.migrations where migration = '<nombre de la migración>';"
php artisan migrate --force
```
Si una migración crea varias tablas y alguna sigue existiendo, `migrate` fallará con
"ya existe": en ese caso pide ayuda antes de borrar nada.

### Nunca
- `migrate:fresh`, `migrate:refresh` ni `db:wipe` contra el servidor: borran el esquema.
- Cambiar `DB_SEARCH_PATH` a `public` o `unexo`: son de otros sistemas.

## Después de restablecer
```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```
Comprobar desde tu PC:
```powershell
curl.exe -s https://www.tecnoweb.org.bo/inf513/grupo06sa/proyecto2/health
```
