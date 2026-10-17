---
name: write-plan
description: Redacta el plan técnico (plan.md, data-model.md y contracts/) de una feature de NEXO SIS a partir de su spec aprobada, con decisiones y alternativas, modelo de datos, contratos, trazabilidad y riesgos.
---

# Redactar un plan

El plan define el **cómo**. Parte siempre de una `spec.md` aprobada; si la spec tiene clarificaciones abiertas, detente y pregúntalas.

## Antes de escribir

1. Lee `specs/constitution.md`, la `spec.md` y las reglas de `.claude/rules/`.
2. Revisa el código existente (modelos, services, migraciones) para reutilizar en vez de duplicar.

## Plantilla de `specs/NNN-<feature>/plan.md`

```markdown
# Plan NNN — <Feature>

Spec: specs/NNN-<feature>/spec.md

## 1. Enfoque
Resumen de la solución (3-6 líneas).

## 2. Decisiones técnicas
| Decisión | Elegida | Alternativas descartadas | Motivo |
|---|---|---|---|

## 3. Componentes
Controladores, Services, Form Requests, Enums, vistas, rutas y middleware a crear o modificar (rutas de archivo reales).

## 4. Modelo de datos
Resumen; el detalle va en `data-model.md`.

## 5. Contratos
Resumen; el detalle va en `contracts/`.

## 6. Trazabilidad
| RF | CA | Componente | Test previsto |
|---|---|---|---|

## 7. Riesgos
| Riesgo | Mitigación |
|---|---|
```

## `data-model.md`
Por tabla: nombre (español, plural), columnas con tipo, nulabilidad, UUID como PK, claves foráneas con `restrictOnDelete` si hay historial académico, índices y migración necesaria. Para una migración usa la skill `db-migration`.

## `contracts/`
Un archivo por endpoint o pantalla con contrato externo: método, URL, autenticación, request, response, códigos de error y ejemplo JSON. Obligatorio para todo lo que consuma el CRM.

## Reglas

- Cada RF y CA de la spec debe aparecer en la tabla de trazabilidad.
- No introduzcas requisitos nuevos: si falta algo, vuelve a la spec.
- Respeta la constitución; si una decisión la contradice, se detiene y se consulta al equipo.
- Invoca al agente `architect` para el borrador y valida consistencia spec/plan antes de `/tasks`.
