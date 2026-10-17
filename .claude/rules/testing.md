---
paths:
  - "tests/**"
---

# Testing

- Cada criterio de aceptación (CA) de la spec tiene al menos un test. El nombre o un comentario del test cita el CA o RF que cubre (ej. `// CA-004-02`).
- Pruebas de comportamiento en `tests/Feature/` (HTTP, base de datos). Unitarias en `tests/Unit/` solo para lógica pura.
- Nombre del test en español describiendo el comportamiento: `test_el_administrador_puede_cerrar_un_modulo`.
- Usar `RefreshDatabase`. La suite corre con SQLite en memoria (`phpunit.xml`); no depender de PostgreSQL.
- Probar siempre: camino feliz, validación fallida, rol no autorizado y casos borde de la spec.
- Un test nuevo debe fallar antes de implementar (rojo) y pasar después (verde).
- Un bug corregido lleva un test que lo reproduce.
- Todo debe pasar con `php artisan test` antes de cerrar una tarea.
