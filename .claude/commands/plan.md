---
description: Genera el plan técnico, modelo de datos y contratos de una spec aprobada
argument-hint: <NNN>
---

Fase **Planificación**. Feature: `$ARGUMENTS`.

1. Verifica que `specs/$ARGUMENTS-*/spec.md` existe, tiene `Estado: aprobada` y no tiene clarificaciones abiertas. Si no, detente e indícalo.
2. Delega en el agente `architect` para generar `plan.md`, `data-model.md` y `contracts/` (skills `write-plan` y `db-migration`).
3. Si ya existía un plan (cambio de spec), actualízalo y lista qué tareas y tests quedan obsoletos.
4. Valida consistencia: todo RF/CA de la spec aparece en la trazabilidad del plan, y el plan no contradice la constitución.
5. Presenta las decisiones y riesgos al usuario y pide aprobación (Gate 2). No escribas código.
