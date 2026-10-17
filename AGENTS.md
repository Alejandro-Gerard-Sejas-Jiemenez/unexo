# UNEXO SIS — Instrucciones para el agente

Sistema de Administración Académica de la unidad NEXO (UAGRM). Proyecto de Tecnología Web, Grupo 6 SA, equipo de 3 personas. Desarrollo **dirigido por especificaciones (SDD)**.

## Regla principal

**La spec manda.** `specs/` es la fuente de verdad del producto; el código es su consecuencia.
- Antes de escribir código, debe existir una spec aprobada: `specs/NNN-<feature>/spec.md`.
- Si cambia un requisito: se actualiza la spec primero, luego plan y tareas, y luego el código. No se parchea el código a mano.
- Una tarea a la vez, verificable por sí sola.
- Flujo completo y casos (crear, corregir, actualizar, borrar): `specs/README.md`.

## Lo primero que debes leer

1. `specs/constitution.md` — principios fijos (stack, arquitectura, calidad).
2. `specs/000-decisiones.md` — contradicciones y decisiones abiertas. **No asumas** una decisión que figure como abierta: pregunta.
3. La spec de la feature en curso.
4. `PRODUCT.md` — usuarios, propósito y principios de producto.
5. `context/` — fuentes crudas del cliente (requerimientos, minuta del audio). Solo lectura.

## Stack y comandos

Laravel 12, PHP 8.2, Blade con CSS plano (`public/css/app.css`), PostgreSQL 16 en Docker, API con Sanctum. UUID en todas las tablas.

```bash
docker compose up -d                 # base de datos (puerto 5433)
php artisan serve                    # http://localhost:8000
php artisan migrate:fresh --seed     # reiniciar datos DEMO-
php artisan test                     # suite completa (SQLite en memoria)
vendor/bin/pint                      # formato
```

## Comandos del flujo SDD

| Comando | Qué hace |
|---|---|
| `/specify <idea o NNN>` | Crea o modifica `spec.md` (qué y por qué, en EARS) y pasa al `spec-reviewer`. |
| `/plan <NNN>` | Genera `plan.md`, `data-model.md` y `contracts/` con el `architect`. |
| `/tasks <NNN>` | Genera `tasks.md`: tareas pequeñas con RF cubierto y dependencias. |
| `/implement <NNN> [T-xx]` | Implementa una tarea con `implementer` + `test-writer`; valida con `code-reviewer`. |

## Límites de trabajo

- No mezclar el *qué* (spec) con el *cómo* (plan).
- Reglas por tema en `.claude/rules/`; procedimientos en `.claude/skills/`.
- Interfaz y mensajes en español, sin jerga técnica en pantalla. Nombres de dominio en español.
- Datos de ejemplo con prefijo `DEMO-`. No inventar marca, cifras ni clientes.
- Nunca leer ni mostrar `.env`. Nunca commitear secretos.
- No cambiar `specs/constitution.md` ni `specs/000-decisiones.md` sin acuerdo de los 3 integrantes.
- Commits atómicos en español, formato `tipo: descripción` (ver skill `git-workflow`). Una rama y un PR por spec.
