---
paths:
  - "routes/api.php"
  - "app/Http/Controllers/Api/**"
---

# API para el CRM

El SIS actúa como servidor API (RNF1). Estado actual: prefijo `/api/v1`, autenticación Sanctum (Bearer) y middleware `cliente.api`.

- Todo endpoint va versionado bajo `/api/v1` y protegido con `auth:sanctum` + `cliente.api`.
- Respuestas siempre JSON, también en errores (ya configurado en `bootstrap/app.php`).
- Códigos HTTP: `200` lectura, `201` creación, `400/422` validación, `401` sin token, `403` cliente no autorizado, `404` no encontrado.
- Los contratos (método, URL, request, response, errores) se definen en `specs/NNN-<feature>/contracts/` **antes** de implementar y son la referencia del CRM.
- Los recursos se devuelven con campos explícitos; nunca exponer el modelo completo ni datos personales innecesarios.
- Un cambio que rompe un contrato ya publicado exige nueva versión o acuerdo con el equipo del CRM (Grupo 5); se registra en `specs/000-decisiones.md`.
- El SIS es dueño del catálogo (programas, módulos, estado comercial). El CRM es dueño de ventas e historial transaccional. No duplicar esa responsabilidad.
- Los puntos de precios y de quién inicia el flujo de alumnos convertidos están en decisión abierta: ver `specs/000-decisiones.md`.
