---
description: Divide el plan aprobado en tareas pequeñas y verificables (tasks.md)
argument-hint: <NNN>
---

Fase **Tareas**. Feature: `$ARGUMENTS`.

1. Verifica que `plan.md` existe y está aprobado.
2. Genera o actualiza `specs/$ARGUMENTS-*/tasks.md` con la skill `breakdown-tasks`.
3. Al actualizar, conserva las tareas ya hechas `[x]`; añade las nuevas y marca como obsoletas las que la spec ya no pide.
4. Validación previa a implementar: todo RF tiene tarea, todo CA tiene tarea de test, no hay dependencias circulares. Reporta los huecos.
5. Muestra el plan de tareas, indicando cuáles son `[P]` para repartir entre integrantes, y pide aprobación (Gate 3).
