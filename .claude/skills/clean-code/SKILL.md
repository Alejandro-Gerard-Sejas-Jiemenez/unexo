---
name: clean-code
description: Manual integral de Código Limpio y principios de artesanía de software basado en "Clean Code" de Robert C. Martin (Uncle Bob).
---

# Clean Code — Manual de Artesanía de Software

> **Fuente:** *Clean Code: A Handbook of Agile Software Craftsmanship* — Robert C. Martin (Uncle Bob, Object Mentor).

---

## 1. Filosofía Fundamental del Código Limpio

> *"Cualquier tonto puede escribir código que un ordenador entienda. Los buenos programadores escriben código que los humanos pueden entender."* — Martin Fowler.

* 🧹 **La Regla del Boy Scout:** *"Deja el campamento (código) más limpio de como lo encontraste."* Si tocas un archivo para añadir una función, limpia al menos un mal nombre, una variable huérfana o un condicional confuso.
* 🪟 **Teoría de las Ventanas Rotas:** Un solo método descuidado, una clase desordenada o un `// TODO` abandonado invita a más negligencia. Corrige el desorden de inmediato.
* 🎌 **Las 5S de Lean aplicadas al Software:**
  1. *Seiri (Organización):* Nombres exactos y clases en los paquetes adecuados.
  2. *Seiton (Sistematización):* Cada pieza de código en su lugar esperado (`controller`, `dto`, `model`, `repository`, `service`).
  3. *Seiso (Limpieza):* Cero código muerto comentado, cero imports innecesarios.
  4. *Seiketsu (Estandarización):* Convenciones unificadas en todo el equipo.
  5. *Shutsuke (Disciplina):* Respetar las reglas de diseño en cada commit.

---

## 2. Nombres con Sentido (Meaningful Names)

| Regla de Clean Code | ❌ Código Deficiente | ✅ Código Limpio |
|---|---|---|
| **Revelar intención** | `int d; // días transcurridos` | `int diasTranscurridosDesdeUltimaVenta;` |
| **Evitar desinformación** | `List<Producto> productoList;` | `List<Producto> productosDisponibles;` |
| **Distinciones significativas** | `ProductoData`, `ProductoInfo`, `ProductoObject` | `Producto` (Entidad) vs `ProductoRequestDto` (Entrada) |
| **Nombres pronunciables y buscables** | `genymdhms`, `modcdate` | `fechaGeneracionReporte`, `fechaModificacion` |
| **Sin prefijos ni codificaciones** | `m_descripcion`, `IProductoService` | `descripcion`, `ProductoService` |
| **Clases = Sustantivos** | `ProcesarVenta`, `DataParser` | `VentaService`, `GestorInventario` |
| **Métodos = Verbos / Frases verbales** | `producto()`, `total()` | `obtenerProductoPorId()`, `calcularTotalVenta()` |
| **Un concepto por palabra** | Mezclar `get()`, `fetch()`, `retrieve()`, `search()` | Usar consistentemente `obtenerPorId()` y `buscarPorCriterio()` |

---

## 3. Funciones y Métodos (Functions)

### Directivas Esenciales:
1. **Pequeñas (Small!):** Los métodos deben tener idealmente **menos de 15-20 líneas**.
2. **Hacer una sola cosa (Do One Thing):** Una función debe tener una única responsabilidad y ejecutarla completamente. Si una función hace validación, cálculo y persistencia a la vez, debe descomponerse.
3. **Un solo nivel de abstracción por función (Regla del Paso Descendente):** Leer el código de arriba hacia abajo debe sentirse como leer una narración de párrafos lógicos.
4. **Número de Argumentos (Arguments):**
   * *0 argumentos (Niládica):* Ideal.
   * *1 argumento (Monádica):* `obtenerPorSku(sku)`.
   * *2 argumentos (Diádica):* `crearDetalle(producto, cantidad)`.
   * *3 o más argumentos (Poliádica):* ⚠️ **Antipadrón**. Empaquetar en un objeto de valor o DTO:
     ```java
     // ❌ MAL
     public Venta registrarVenta(Long clienteId, Long usuarioId, String metodoPago, List<Detalle> detalles, String obs) { ... }
     
     // ✅ BIEN
     public Venta registrarVenta(VentaRequestDto dto) { ... }
     ```
5. **Cero Efectos Secundarios Ocultos (No Side Effects):** Un método `validarCredenciales()` jamás debe mutar el estado del usuario o renovar el token en segundo plano.
6. **Separación de Comandos y Consultas (Command-Query Separation):** Una función debe *hacer algo* (mutar estado) o *responder algo* (consultar datos), pero no ambas cosas a la vez.

---

## 4. Manejo de Errores y Excepciones

* 🎯 **Usa Excepciones en lugar de Códigos de Error:** Retornar `-1` o códigos numéricos obliga al llamador a encadenar `if-else` infinitos.
* 📦 **Define Clases de Excepción de Dominio:**
  - `ResourceNotFoundException` (Entidad no encontrada ➔ HTTP 404).
  - `BadRequestException` (Regla de negocio rota ➔ HTTP 400).
* 🛡️ **NUNCA Retornes `null`:**
  ```java
  // ❌ MAL
  public List<Producto> buscarPorModelo(Long modeloId) {
      if (sinResultados) return null; // Obliga al cliente a validar null
  }
  
  // ✅ BIEN
  public List<Producto> buscarPorModelo(Long modeloId) {
      if (sinResultados) return Collections.emptyList();
  }
  ```
* 🛡️ **NUNCA Pases `null` como Argumento:** Produce `NullPointerException` impredecibles.
* 🚫 **Prohibido bloques Catch Silenciosos:**
  ```java
  // ❌ NUNCA HACER ESTO
  try {
      procesarPago();
  } catch (Exception e) {
      // no hacer nada
  }
  ```

---

## 5. Pruebas Unitarias Limpias (Clean Tests & F.I.R.S.T.)

Las pruebas son tan importantes como el código de producción. Un código con pruebas sucias es imposible de refactorizar.

### Los Principios F.I.R.S.T. de Clean Code:
* **F (Fast - Rápidas):** Las pruebas deben ejecutarse en milisegundos para poder correrse continuamente.
* **I (Independent - Independientes):** Ninguna prueba debe depender del resultado o estado dejado por otra.
* **R (Repeatable - Repetibles):** Deben dar exactamente el mismo resultado en cualquier entorno (Windows, Linux, CI/CD).
* **S (Self-Validating - Auto-validables):** Devuelven un booleano (Pasa o Falla), no requieren que un humano inspeccione logs.
* **T (Timely - Oportunas):** Se escriben en paralelo o inmediatamente junto al código de negocio.

### Estructura de Test Limpio (Arrange-Act-Assert / Given-When-Then):
```java
@Test
@DisplayName("Debe decrementar stock correctamente al procesar una venta válida")
void debeDecrementarStockAlProcesarVenta() {
    // 1. GIVEN (Arrange): Preparar datos y precondiciones
    Producto producto = crearProductoEjemplo(100);
    int cantidadAVender = 15;

    // 2. WHEN (Act): Ejecutar la acción bajo prueba
    productoService.descontarStock(producto.getId(), cantidadAVender);

    // 3. THEN (Assert): Validar el resultado exacto
    assertEquals(85, producto.getStockActual());
}
```

---

## 6. Olores de Código (Code Smells) y Refactorización

* 👃 **Rigidez:** El software es difícil de cambiar porque cada cambio obliga a cambiar muchas otras partes. (Solución: Desacoplar con interfaces y DTOs).
* 👃 **Fragilidad:** Un cambio rompe partes no relacionadas del sistema. (Solución: Encapsulamiento y pruebas unitarias).
* 👃 **Inmovilidad:** Imposible reutilizar código en otra parte sin llevarse dependencias innecesarias. (Solución: Módulos por dominio).
* 👃 **Viscosidad:** Cuando hacer las cosas bien es más difícil que hacer un "parche rápido". (Solución: Mantener una arquitectura clara).
* 👃 **Código Muerto:** Clases, métodos o imports que nadie invoca. (Solución: Eliminación inmediata sin piedad).
