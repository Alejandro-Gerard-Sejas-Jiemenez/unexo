---
name: write-spec
description: Redacta o modifica la especificación (spec.md) de una feature de NEXO SIS en formato EARS, con historias, criterios de aceptación Dado/Cuando/Entonces, casos borde y tabla de clarificaciones. Úsala al crear o cambiar requisitos, nunca para decidir tecnología.
---

# Redactar una spec

La spec define el **qué** y el **por qué**. Sin tecnología (nada de tablas, clases, rutas ni librerías: eso es del plan).

## Antes de escribir

1. Lee `specs/constitution.md` y `specs/000-decisiones.md`. Si el tema tiene una decisión abierta, no la resuelvas: regístrala en la tabla de clarificaciones y pregunta.
2. Revisa las fuentes en `context/` y `PRODUCT.md`.
3. Si es una spec retroactiva (el código ya existe), deriva los requisitos del comportamiento real y de los tests, y marca lo que no tenga test.

## Plantilla de `specs/NNN-<feature>/spec.md`

```markdown
# NNN — <Nombre de la feature>

Estado: borrador | revisada | aprobada | implementada
Casos de uso: CUx
Módulo: <identidad | catálogo | planificación | matrículas | API | reportes>

## 1. Contexto y objetivo
Qué problema resuelve y para quién (2-5 líneas).

## 2. Usuarios e historias
- HU-01: Como <rol>, quiero <acción>, para <beneficio>.

## 3. Requisitos funcionales (EARS)
- RF-01 (HU-01): Cuando <evento>, el sistema deberá <respuesta>.

## 4. Requisitos no funcionales
- RNF-01: ...

## 5. Criterios de aceptación
- CA-01 (RF-01): Dado <contexto>, cuando <acción>, entonces <resultado>.

## 6. Casos borde y errores
- Si <condición no deseada>, entonces el sistema deberá <respuesta>.

## 7. Fuera de alcance
- ...

## 8. Clarificaciones
| # | Pregunta | Respuesta | Estado |
|---|---|---|---|
```

## Plantillas EARS

| Patrón | Plantilla |
|---|---|
| Ubicuo | El `<sistema>` deberá `<respuesta>` |
| Evento | Cuando `<evento>`, el `<sistema>` deberá `<respuesta>` |
| Estado | Mientras `<estado>`, el `<sistema>` deberá `<respuesta>` |
| No deseado | Si `<condición>`, entonces el `<sistema>` deberá `<respuesta>` |
| Opcional | Donde `<característica incluida>`, el `<sistema>` deberá `<respuesta>` |

## Reglas

- Cada RF cita su HU; cada CA cita su RF. IDs estables: no renumerar al editar, solo añadir o marcar `[ELIMINADO]`.
- Un requisito = un comportamiento verificable. Evita "rápido", "fácil", "adecuado" sin medida.
- Lenguaje del proceso administrativo, en español.
- Al terminar, invoca al agente `spec-reviewer` y resuelve sus hallazgos antes de pasar a `/plan`.
