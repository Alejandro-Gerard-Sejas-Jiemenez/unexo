---
name: db-migration
description: Procedimiento para crear o modificar tablas en NEXO SIS con migraciones de Laravel (UUID, nombres en español, integridad referencial estricta, compatibilidad PostgreSQL y SQLite de pruebas). Úsala en cualquier cambio de esquema.
---

# Migraciones

## Procedimiento

1. El cambio debe estar en `data-model.md` del plan. No inventes columnas fuera de él.
2. `php artisan make:migration create_<tabla>_table` o `<accion>_<campo>_to_<tabla>_table`.
3. **Nunca edites una migración ya aplicada o compartida**: crea una nueva que la modifique.
4. Escribe `up()` y un `down()` que lo revierta.
5. Actualiza modelo (`$table`, `$fillable`, casts a Enum), factory/seeder (datos `DEMO-`) y tests.
6. Verifica con `php artisan migrate:fresh --seed` y `php artisan test`.

## Convenciones

- Tablas en español, plural, `snake_case`. Primary key `uuid('id')->primary()`.
- Claves foráneas con `foreignUuid('x_id')->constrained('tabla')`. Si el registro tiene historial académico, `restrictOnDelete()` (RNF2). Nunca `cascadeOnDelete()` sobre estudiantes, matrículas o grupos.
- Estados como columna de texto casteada a un Enum de `app/Enums/`.
- Borrado lógico por estado, no por eliminación física.
- Índices en columnas de búsqueda y en claves foráneas consultadas.
- Las migraciones deben correr tanto en PostgreSQL como en SQLite (tests). Evita SQL específico de un motor sin alternativa.
- Al renombrar o quitar una columna, planifica los datos existentes y regístralo en el plan.

## Riesgos a avisar al equipo

Cambios destructivos (borrar columnas o tablas) y cambios que afecten lo que consume el CRM: se coordinan antes de aplicarlos.
