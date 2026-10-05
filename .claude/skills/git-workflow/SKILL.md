---
name: git-workflow
description: Manual integral de Control de Versiones, Commits Atómicos y Estrategias Git/GitHub basado en "Git y GitHub: Fundamentos".
---

# Git Workflow & Conventional Commits — Manual de Buenas Prácticas

> **Fuente:** *Git y GitHub: Fundamentos y Buenas Prácticas para el Control de Versiones Profesional*.

---

## 1. El Concepto de Commit Atómico

Un **commit atómico** es una unidad de cambio mínima, completa y coherente:
* **Mínima:** Resuelve una sola tarea, bug o refactorización. No mezcles "ajuste de colores CSS" con "cambio de algoritmo en VentaService".
* **Completa:** Incluye el código de producción, las pruebas unitarias asociadas y la actualización de documentación (`FILE_INDEX.md`, `AUDIT_LOG.md`).
* **Coherente:** En todo momento, cualquier commit en la historia del repositorio debe compilar limpiamente (`mvnw test-compile`) y pasar todas las pruebas (`mvnw test`).

---

## 2. Anatomía de un Mensaje de Commit Profesional

El mensaje de commit debe seguir la especificación **Conventional Commits 1.0**:

```
<tipo>(<alcance_opcional>): <asunto_en_imperativo>

[cuerpo_opcional_explicando_el_por_que]

[pie_opcional_con_referencias]
```

### Ejemplo Canónico:
```git
feat(ventas): add real-time stock validation before checkout

- Validate that available stock is >= requested quantity for each line item.
- Throw BadRequestException when stock is insufficient with explicit SKU.
- Add unit tests verifying stock exhaustion edge cases.

Closes #FEAT-1
```

---

## 3. Tipos Estándar de Commits (Conventional Types)

| Tipo | Propósito | Ejemplo |
|---|---|---|
| `feat` | Nueva funcionalidad visible para el usuario o API | `feat(catalogo): add bulk discount calculation` |
| `fix` | Corrección de un fallo o error de lógica | `fix(ventas): prevent negative stock on concurrent checkout` |
| `refactor` | Reestructuración de código sin alterar comportamiento | `refactor(backend): modularize package-by-feature layout` |
| `test` | Adición o corrección de pruebas automatizadas | `test(inventario): add unit tests for Kardex stock movements` |
| `style` | Cambios de formato, CSS o diseño (sin cambio de lógica) | `style(frontend): improve visual contrast in modal action buttons` |
| `docs` | Modificación de documentación, specs o índices | `docs(agents): enrich skills based on reference books` |
| `chore` | Tareas de mantenimiento, dependencias o `.gitignore` | `chore(gitignore): exclude logic design and PDF docs` |
| `perf` | Optimización de rendimiento | `perf(catalogo): add indexed query for active products` |

---

## 4. Reglas Estrictas de Estilo en Commits

1. **Modo Imperativo y Presente:** Escribe *"add feature"*, *"fix bug"*, *"refactor service"*. ❌ NO escribas *"added"*, *"fixing"*, *"modificaciones"*.
2. **Límite de Longitud:**
   * Línea de Asunto: Máximo **50 a 72 caracteres**.
   * Líneas del Cuerpo: Máximo **72 caracteres por línea**.
3. **Puntuación:** ❌ NUNCA coloques punto final al terminar la línea de asunto.
4. **Enfócate en el POR QUÉ, no solo en el QUÉ:** El diff de Git ya muestra qué líneas cambiaron; el mensaje debe explicar *por qué fue necesario el cambio y qué impacto tiene*.

---

## 5. Estrategia de Ramas (Branching Strategy)

* `main` / `master`: Rama productiva y estable. Solo código verificado al 100%.
* `feature/<nombre-feature>`: Para desarrollo de nuevas características (ej. `feature/validacion-stock`).
* `fix/<nombre-bug>`: Para correcciones específicas (ej. `fix/error-sku-duplicado`).
* `refactor/<nombre-refactor>`: Para mejoras estructurales (ej. `refactor/modular-packages`).

### Buenas Prácticas al Combinar Cambios:
* Antes de fusionar a `main`, actualizar con `git pull --rebase origin main` para mantener una historia lineal y limpia sin commits de merge vacíos.
* Eliminar las ramas de feature una vez fusionadas.

---

## 6. Anti-Patrones Comunes de Git

* ❌ **El Mega-Commit ("Subir todo"):** Acumular 3 días de trabajo con 50 archivos distintos y comitear *"cambios del proyecto"*.
* ❌ **Commits Rotos:** Comitear código que no compila o que tiene tests en rojo con la promesa de *"lo arreglo en el siguiente commit"*.
* ❌ **Contaminación del Repositorio:** Subir archivos binarios pesados (PDFs, videos), credenciales, archivos de base de datos (`.mv.db`) o carpetas `node_modules`/`target`.
* ❌ **Mezclar Formateo y Lógica:** Hacer un commit que formatea 20 archivos y a la vez cambia una regla de cálculo de precios.
