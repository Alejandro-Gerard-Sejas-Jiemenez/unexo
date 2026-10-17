---
description: Implementa una tarea de tasks.md con test primero y revisión contra la spec
argument-hint: <NNN> [T-xx]
---

Fase **Implementación**. Argumentos: `$ARGUMENTS`.

1. Lee `tasks.md` de la feature. Si no se indica `T-xx`, toma la primera tarea pendiente cuyas dependencias estén hechas. **Una sola tarea por ejecución.**
2. Verifica que spec, plan y tasks están aprobados y consistentes. Si no, detente.
3. Agente `test-writer`: escribe el test desde los CA y confirma que falla (rojo).
4. Agente `implementer`: implementa lo mínimo para ponerlo en verde.
5. Ejecuta `php artisan test` completo.
6. Agente `code-reviewer`: valida el cambio contra la spec y el plan. Corrige los hallazgos; si el problema está en la spec, detente y vuelve a `/specify`.
7. Marca la tarea `[x]` en `tasks.md` y propone el mensaje de commit atómico (`tipo: descripción`). No hagas commit sin que el usuario lo pida.
