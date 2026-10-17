---
description: Crea o modifica la especificación (spec.md) de una feature
argument-hint: <idea de feature | NNN existente>
---

Trabajas en la fase **Especificación** de SDD. Argumento: `$ARGUMENTS`.

1. Lee `specs/constitution.md`, `specs/000-decisiones.md`, `PRODUCT.md` y lo relevante de `context/`.
2. Determina el caso:
   - **Feature nueva**: crea `specs/NNN-<nombre>/spec.md` con el siguiente número libre.
   - **Spec existente** (cambio, corrección o baja de funcionalidad): lee la spec actual y modifícala. No renumeres IDs; añade nuevos o marca `[ELIMINADO]` con motivo. Anota el cambio en una sección "Historial" al final.
3. Redacta siguiendo la skill `write-spec`: solo el qué y el por qué, requisitos en EARS, CA en Dado/Cuando/Entonces.
4. Si hay huecos o decisiones abiertas, regístralos en la tabla de clarificaciones y **pregúntalos al usuario** antes de dar la spec por buena.
5. Invoca al agente `spec-reviewer` y resuelve sus hallazgos.
6. Marca `Estado: revisada` y pide al usuario la aprobación (Gate 1). No pases a plan ni a código.

Si es un cambio sobre una feature ya implementada, indica qué partes de `plan.md`, `tasks.md` y tests quedan afectadas.
