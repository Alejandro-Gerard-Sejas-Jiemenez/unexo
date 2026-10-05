---
name: ui-ux-usability
description: Manual integral de Usabilidad, Diseño Intuitivo y Experiencia de Usuario basado en "No me hagas pensar" de Steve Krug.
---

# UI/UX & Usabilidad Web — No Me Hagas Pensar

> **Fuente:** *Don't Make Me Think, Revisited: A Common Sense Approach to Web Usability* — Steve Krug.

---

## 1. La Primera Ley de la Usabilidad: "No me hagas pensar"

> *"El principio fundamental de la usabilidad es crear interfaces autoevidentes. En la medida de lo posible, al mirar una pantalla, el usuario debe comprender al instante qué es y cómo usarla sin tener que reflexionar."* — Steve Krug.

* 💡 **Autoevidente vs Autoexplicativo:**
  - *Autoevidente (Excelente):* Todo se entiende sin instrucciones. Un botón de "Nueva Venta" es obviamente un botón interactivo primario.
  - *Autoexplicativo (Aceptable):* Requiere una fracción de segundo para deducir el funcionamiento.
  - *Confuso (Inaceptable):* El usuario se pregunta: *¿Se puede hacer clic aquí?*, *¿Dónde guardo los cambios?*, *¿Qué significa este icono?*.

---

## 2. Cómo Usan Realmente la Aplicación los Usuarios

Los diseñadores imaginan al usuario leyendo cada texto cuidadosamente. En la realidad:
1. **Los usuarios no leen, escanean (Scan):** Buscan palabras clave, botones de acción destacados o alertas visuales que coincidan con su objetivo inmediato.
2. **Los usuarios eligen la primera opción razonable (Satisficing):** No analizan todas las opciones para encontrar la óptima; hacen clic en lo primero que parece resolver su problema.
3. **Los usuarios improvisan (Muddle through):** Si algo funciona por casualidad, seguirán haciéndolo aunque no sea el camino previsto.

---

## 3. Las 5 Reglas para Diseñar Interfaces que se Escanean Fácilmente

### Regla 1: Crear una Jerarquía Visual Clara e Inequívoca
* **Importancia = Prominencia:** Cuanto más importante sea un elemento, más visible debe ser (mayor tamaño, mayor contraste, tipografía destacada).
* **Agrupación Lógica:** Los elementos relacionados deben estar visualmente juntos (dentro de la misma tarjeta o contenedor con borde sutil).
* **Anidación Visual:** Los elementos subordinados deben estar indentados o contenidos visualmente dentro de sus elementos padre.

### Regla 2: Aprovechar las Convenciones Establecidas
* No reinventes la rueda en patrones de interacción comunes.
* Icono de lupa = Búsqueda.
* Icono de basura = Eliminar.
* Icono de lápiz = Editar.
* Botón rojo = Acción destructiva / Alerta.
* Barra lateral izquierda = Navegación principal.

### Regla 3: Dividir las Pantallas en Zonas Claramente Delimitadas
* La vista debe organizarse en bloques evidentes:
  1. *Encabezado de Vista:* Título `h1`, descripción breve y botón de acción principal.
  2. *Barra de Herramientas:* Filtros, buscador y selector de vistas.
  3. *Área de Contenido:* Tabla de datos o tarjetas de métricas.
  4. *Pie / Paginación:* Controles de navegación y totales.

### Regla 4: Hacer Obvio Qué es Clickeable y Qué No
* Los botones deben tener relieve, color de acento o bordes distintivos.
* Efecto `hover` y cambio de cursor (`cursor: pointer`) en todos los elementos interactivos.
* ❌ NUNCA uses estilos de enlaces o botones para textos estáticos.

### Regla 5: Eliminar el Ruido Visual (Visual Noise)
* **Ruido por desorden:** Demasiados elementos apiñados sin espacio en blanco (`padding` / `gap` insuficiente).
* **Ruido por estridencia:** Demasiados colores llamativos compitiendo por atención al mismo tiempo. (Usa color de acento solo para la acción prioritaria).

---

## 4. "La Prueba del Baúl" (The Trunk Test) en la Navegación

Cualquier usuario que aterrice en cualquier pantalla del sistema debe poder responder en **menos de 5 segundos**:
1. **¿Dónde estoy?** ➔ Título de la página visible y elemento activo resaltado en el Sidebar.
2. **¿Qué puedo hacer aquí?** ➔ Botones de acción principales visibles en el encabezado.
3. **¿Cómo busco algo específico?** ➔ Campo de búsqueda prominente con placeholder claro (*"Buscar por SKU o nombre..."*).
4. **¿Cómo regreso al inicio?** ➔ Enlace al Dashboard claro en el menú superior o lateral.

---

## 5. Usabilidad en Formularios y Modales (Form & Modal Best Practices)

| Aspecto | ❌ Práctica Incorrecta | ✅ Práctica Correcta (No me hagas pensar) |
|---|---|---|
| **Labels de campos** | Solo usar `placeholder` (desaparece al escribir) | `<label>` visible encima del `<input>` + placeholder de ejemplo |
| **Validaciones** | Esperar al submit y recargar la página con error genérico | Validación visual inline inmediata con mensaje explicativo |
| **Acciones del Modal** | Dos botones idénticos: "Guardar" y "Cancelar" | Botón principal de acento ("Guardar") y secundario neutral ("Cancelar") |
| **Salida del Modal** | Obligar a buscar un botón "Cerrar" | Botón `X` superior derecho, clic fuera del modal y tecla `Escape` |
| **Acciones destructivas** | Eliminar un registro al hacer clic | Modal de confirmación explícito: *"¿Eliminar este producto? Esta acción no se puede deshacer."* |
| **Estados de espera** | El botón no reacciona al hacer clic (provoca doble clic) | Deshabilitar botón + mostrar spinner de carga (*"Guardando..."*) |

---

## 6. Omitir Palabras Innecesarias (Omit Needless Words)

> *"Elimina la mitad de las palabras de cada página. Luego, elimina la mitad de lo que quedó."* — Steve Krug.

* ❌ *"Bienvenido a la sección de administración de inventarios donde usted podrá consultar y actualizar los productos del catálogo."*
* ✅ **"Productos"** (Subtítulo: *"Gestión de inventario y catálogo de productos"*).
