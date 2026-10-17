# Arquitectura

Detalle completo en la skill `laravel-arquitectura`. Aquí solo lo que siempre aplica.

- Los controladores orquestan: validan (Form Request), llaman a un Service y devuelven la vista o la respuesta. No contienen lógica de negocio ni consultas complejas.
- La lógica de negocio con reglas o transacciones va en `app/Services/` (ej. `MatriculaService`). No crear capas ni carpetas "por si acaso".
- Los controladores no acceden a otros modelos para decidir reglas de negocio: lo hace el Service.
- Autorización por rol con el middleware `rol:` en `routes/web.php`. Cada rol ve solo lo suyo; el acceso indebido responde con acceso denegado claro.
- El borrado es lógico (por estado), no físico. Un registro con historial académico vinculado no se elimina (RNF2).
- Los estados son Enums en `app/Enums/` con método `label()` en español.
- Las tablas del dominio van en español y cada modelo declara `protected $table`. Toda clave primaria es UUID.
- Las rutas web van en `routes/web.php`; la API para el CRM en `routes/api.php` (ver `api.md`).
