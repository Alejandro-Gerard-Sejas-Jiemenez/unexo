# NEXO SIS

Sistema de Administración Académica para la unidad NEXO (UAGRM). Proyecto de Tecnología Web, Grupo 6 SA.

Administra el catálogo de programas y módulos, los perfiles de usuario (Administrador, Docente y Estudiante), las cohortes y las matrículas, y expone una API de solo lectura para que el CRM consulte la oferta abierta.

## Stack

- Laravel 12 (PHP 8.2) con patrón MVC y vistas Blade con CSS plano.
- PostgreSQL 16 en Docker, todo en localhost.
- Identificadores UUID en todas las tablas.

## Requisitos

PHP 8.2 o superior con las extensiones `openssl`, `fileinfo`, `zip`, `mbstring`, `curl`, `pdo_pgsql` y `pgsql`; Composer; Docker Desktop.

## Primera instalación

```bash
composer install
cp .env.example .env
php artisan key:generate
docker compose up -d        # esperar ~10 s si el volumen es nuevo
php artisan migrate:fresh --seed
php artisan serve           # http://localhost:8000
```

## Uso diario

```bash
docker compose up -d
php artisan serve
```

Usuarios de prueba (datos inventados, contraseña `Demo12345`):

| Rol | Correo |
|---|---|
| Administrador | admin@nexo.test |
| Docente | carlos.mendoza@nexo.test |
| Estudiante | andres.flores@nexo.test |

También se puede entrar con el correo institucional (por ejemplo `admin@nexo.edu.test`).

## Pruebas

```bash
php artisan test
```

## Documentación

Las notas del proyecto (objetivos, modelo de datos, arquitectura, casos de uso) están en el vault de Obsidian, carpeta `1. Proyectos/Tecnologia Web - NEXO SIS/`. Las instrucciones para trabajar con Claude Code están en `CLAUDE.md`.
