---
name: breakdown-tasks
description: Divide el plan de una feature de NEXO SIS en tareas pequeñas, ordenadas y verificables (tasks.md), cada una con el RF/CA que cubre, sus dependencias y la marca [P] si es paralelizable.
---

# Dividir en tareas

Entrada: `spec.md` y `plan.md` consistentes. Salida: `specs/NNN-<feature>/tasks.md`.

## Plantilla

```markdown
# Tareas NNN — <Feature>

Leyenda: [ ] pendiente · [x] hecha · [P] paralelizable · Dep: tareas previas

## Fase 1 — Datos
- [ ] T-01 Migración de `<tabla>` · Cubre: RF-01 · Dep: —
- [ ] T-02 [P] Enum `<Estado>` · Cubre: RF-02 · Dep: —

## Fase 2 — Lógica
- [ ] T-03 Test del Service (rojo) · Cubre: CA-01 · Dep: T-01
- [ ] T-04 `<X>Service` (verde) · Cubre: RF-01 · Dep: T-03

## Fase 3 — Interfaz / API
- [ ] T-05 Ruta, Form Request y controlador · Cubre: RF-03 · Dep: T-04

## Verificación final
- [ ] T-99 Recorrer todos los CA contra los tests y `php artisan test`
```

## Reglas

- Una tarea = un cambio verificable por sí solo, que cabe en un commit atómico (idealmente < 1 hora).
- Cada tarea cita el RF o CA que cubre. Un RF sin tarea o un CA sin test es un hueco: corrígelo.
- Orden: datos → lógica (test antes que código) → interfaz/API → verificación.
- Marca `[P]` solo si dos tareas no tocan los mismos archivos, para que dos integrantes puedan tomarlas a la vez.
- Verbos concretos, rutas de archivo cuando se conocen. Sin tareas vagas ("mejorar", "revisar").
- No añadir tareas que no vengan del plan.
