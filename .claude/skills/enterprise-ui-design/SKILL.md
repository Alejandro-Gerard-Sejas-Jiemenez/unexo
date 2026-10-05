---
name: enterprise-ui-design
description: Diseña y desarrolla interfaces web enterprise modernas, profesionales y altamente funcionales inspiradas en patrones de productos como Odoo, ERP, CRM, SaaS administrativos y sistemas corporativos. Usar cuando se creen dashboards, paneles administrativos, CRUD, ERP, CRM, sistemas de gestión, módulos empresariales, tablas, formularios, configuraciones, reportes o interfaces internas.
---

# Enterprise UI Design Skill

## Objetivo

Crear interfaces de usuario con apariencia y comportamiento de software **enterprise profesional**, evitando diseños genéricos, excesivamente decorativos o similares a landing pages.

Las interfaces deben transmitir:

- profesionalismo;
- claridad;
- alta densidad de información;
- consistencia;
- rapidez operativa;
- jerarquía visual;
- escalabilidad;
- facilidad de navegación;
- sensación de producto terminado.

Tomar como referencia conceptual productos como:

- Odoo;
- ERP empresariales modernos;
- CRM profesionales;
- sistemas administrativos;
- plataformas SaaS B2B;
- backoffices;
- software de gestión interna.

No copiar literalmente ninguna interfaz existente.

Extraer sus mejores patrones de diseño y adaptarlos al producto actual.

---

# 1. Principio fundamental

Antes de escribir código, identificar:

1. Qué tipo de módulo se está construyendo.
2. Qué información necesita consultar el usuario.
3. Qué acciones realiza con mayor frecuencia.
4. Qué elementos deben permanecer visibles.
5. Qué información es primaria, secundaria y contextual.
6. Qué acciones pueden ser destructivas.
7. Qué información necesita filtros.
8. Qué información necesita búsqueda.
9. Qué información necesita tabla.
10. Qué información necesita formulario.

La interfaz debe construirse alrededor del flujo de trabajo.

No alrededor de elementos decorativos.

---

# 2. Filosofía visual

Usar una estética:

- limpia;
- profesional;
- sobria;
- moderna;
- compacta;
- funcional;
- consistente.

Evitar:

- interfaces excesivamente grandes;
- tarjetas gigantes;
- bordes exageradamente redondeados;
- gradientes innecesarios;
- sombras fuertes;
- Glassmorphism sin justificación;
- Neo-brutalismo;
- colores saturados en exceso;
- iconos gigantes;
- botones enormes;
- espacios vacíos innecesarios;
- apariencia de landing page;
- diseños demasiado minimalistas que sacrifiquen información.

El objetivo no es crear una interfaz "bonita".

El objetivo es crear una interfaz que parezca un **producto empresarial real y maduro**.

---

# 3. Densidad de información

Las aplicaciones enterprise deben aprovechar correctamente el espacio.

Priorizar una densidad:

**media → media-alta**

Los usuarios normalmente trabajan durante varias horas en estas interfaces.

Por lo tanto:

- mostrar información relevante sin obligar a hacer demasiados clics;
- evitar tarjetas innecesariamente grandes;
- mantener alturas compactas;
- aprovechar correctamente el ancho disponible;
- utilizar tablas cuando hay información estructurada;
- colocar acciones cerca del contenido afectado.

Ejemplo incorrecto:

```text
[                    ]

      245 usuarios

Usuarios registrados

[                    ]
```

Ejemplo preferido:

```text
Usuarios
245 registrados
+12 este mes
```

---

# 4. Layout principal

Para aplicaciones empresariales usar preferentemente:

```text
┌────────────────────────────────────────────────────────────┐
│ Topbar                                                    │
├──────────────┬─────────────────────────────────────────────┤
│              │ Breadcrumb / Header                        │
│   Sidebar    ├─────────────────────────────────────────────┤
│              │ Toolbar / filtros                          │
│              ├─────────────────────────────────────────────┤
│              │                                             │
│              │ Contenido principal                         │
│              │                                             │
└──────────────┴─────────────────────────────────────────────┘
```

La estructura normalmente contiene:

- sidebar;
- topbar;
- breadcrumb;
- título de página;
- descripción opcional;
- acciones principales;
- filtros;
- contenido.

---

# 5. Sidebar

La navegación lateral debe ser una de las principales referencias espaciales.

Características:

- ancho aproximado de 220–270 px en escritorio;
- opción colapsada de 56–72 px;
- icono + etiqueta;
- grupos de navegación;
- estado activo claramente visible;
- jerarquía de módulos;
- submenús solo cuando aporten valor.

Ejemplo:

```text
Dashboard

OPERACIONES
Reservas
Conductores
Vehículos
Parqueos

ADMINISTRACIÓN
Usuarios
Roles y permisos
Pagos

SISTEMA
Configuración
Auditoría
```

No llenar el sidebar con divisores innecesarios.

---

# 6. Topbar

La barra superior puede contener:

- búsqueda global;
- selector de organización;
- selector de sucursal;
- notificaciones;
- ayuda;
- avatar;
- menú de usuario.

Debe permanecer discreta.

Altura recomendada:

```text
52px – 64px
```

---

# 7. Encabezados de página

El encabezado debe facilitar orientación y acciones.

Estructura recomendada:

```text
Usuarios                           [+ Nuevo usuario]
Administra usuarios y permisos
```

Opcionalmente:

```text
Administración / Usuarios / Detalle
```

Las acciones principales deben ubicarse preferentemente en el extremo derecho.

---

# 8. Jerarquía de acciones

Clasificar acciones como:

## Primary

Acción principal de la pantalla.

Ejemplos:

- Crear usuario
- Nueva reserva
- Guardar
- Registrar parqueo

Debe existir normalmente **una acción primaria dominante**.

## Secondary

Acciones frecuentes pero no dominantes.

Ejemplos:

- Exportar
- Importar
- Duplicar
- Imprimir

## Tertiary

Acciones contextuales.

Ejemplos:

- Ver historial
- Abrir configuración
- Ver actividad

## Destructive

Acciones peligrosas.

Ejemplos:

- Eliminar
- Cancelar definitivamente
- Revocar acceso

Nunca hacer visualmente dominante una acción destructiva.

---

# 9. Tablas enterprise

Las tablas son componentes fundamentales.

Utilizarlas para:

- usuarios;
- reservas;
- ventas;
- productos;
- pagos;
- vehículos;
- transacciones;
- auditorías;
- inventarios;
- registros administrativos.

Una tabla profesional debe considerar:

```text
┌───────────────────────────────────────────────────────┐
│ Buscar...        Estado ▼ Tipo ▼       + Nuevo       │
├────┬────────────┬───────────┬──────────┬──────────────┤
│ □  │ Usuario    │ Rol       │ Estado   │ Acciones     │
├────┼────────────┼───────────┼──────────┼──────────────┤
│ □  │ Juan Pérez │ Admin     │ Activo   │ ...          │
│ □  │ Ana López  │ Operador  │ Activo   │ ...          │
└────┴────────────┴───────────┴──────────┴──────────────┘
```

Considerar siempre:

- búsqueda;
- filtros;
- ordenamiento;
- selección múltiple cuando corresponda;
- paginación;
- contador de resultados;
- loading state;
- empty state;
- acciones por fila;
- columnas alineadas;
- encabezado reconocible.

Evitar llenar las filas de botones.

Preferir:

```text
...
```

para acciones secundarias.

---

# 10. Altura de las filas

Para aplicaciones empresariales utilizar normalmente:

```text
40px – 52px
```

Opcionalmente implementar densidad:

```text
Compact
Comfortable
```

---

# 11. Filtros

Los filtros deben sentirse como herramientas operativas.

Pueden incluir:

```text
Buscar usuario...

Estado
[ Todos ▼ ]

Rol
[ Todos ▼ ]

Fecha
[ Este mes ▼ ]

[ Limpiar filtros ]
```

Para filtros avanzados utilizar:

- popover;
- dropdown;
- panel lateral.

Mostrar filtros activos mediante chips cuando sea útil.

Ejemplo:

```text
Estado: Activo ×
Rol: Operador ×
Santa Cruz ×
```

---

# 12. Formularios

Evitar formularios visualmente débiles.

Agrupar información por contexto.

Ejemplo:

```text
Información general
────────────────────────

Nombre
[________________________]

Correo
[________________________]

Teléfono
[________________________]


Acceso al sistema
────────────────────────

Rol
[ Operador ▼ ]

Estado
[ Activo ▼ ]
```

Para formularios largos usar:

- secciones;
- tabs;
- acordeones;
- pasos;
- panel lateral.

No colocar más campos en una sola fila de los que resulten fáciles de leer.

---

# 13. Formularios de edición

Para entidades empresariales complejas considerar:

```text
┌───────────────────────────────────────────────┐
│ ← Usuario / Juan Pérez                        │
├───────────────────────────────┬───────────────┤
│                               │ Información   │
│ Formulario                    │ relacionada   │
│                               │               │
│                               │ Actividad     │
└───────────────────────────────┴───────────────┘
```

La información secundaria puede colocarse en sidebar contextual.

---

# 14. Detail pages

Una pantalla detalle debe responder rápidamente:

- qué entidad estoy viendo;
- cuál es su estado;
- cuál es su información principal;
- qué puedo hacer;
- qué actividad tuvo;
- qué objetos relacionados existen.

Ejemplo:

```text
← Usuarios

Juan Pérez                       [Editar] [⋯]
Operador
● Activo

[ General ] [ Permisos ] [ Actividad ]

Información personal
...

Actividad reciente
...
```

---

# 15. Status badges

Utilizar estados compactos.

Ejemplo:

```text
● Activo
● Pendiente
● Cancelado
● Finalizado
```

Los colores deben complementar el texto.

Nunca depender únicamente del color.

---

# 16. Cards

No abusar de cards.

Utilizarlas cuando exista una agrupación conceptual real.

Buenas situaciones:

- KPIs;
- resumen financiero;
- configuración;
- información relacionada;
- preview;
- estado de servicio.

Evitar colocar absolutamente cada elemento dentro de una tarjeta.

---

# 17. KPI Cards

Deben ser compactas.

Ejemplo:

```text
Reservas activas
128
↑ 8.4% vs. mes anterior
```

No utilizar iconos decorativos enormes.

---

# 18. Dashboard

Un dashboard enterprise debe priorizar decisiones.

Orden recomendado:

```text
Header

KPI row

Gráfico principal          Resumen secundario

Tabla / actividad reciente

Alertas o pendientes
```

Cada gráfico debe responder una pregunta.

No agregar gráficos únicamente por decoración.

---

# 19. Modales

Utilizar modales para operaciones acotadas.

Ejemplos:

- confirmar acción;
- crear entidad simple;
- asignar rol;
- modificar estado;
- añadir comentario.

No utilizar modal para formularios enormes.

En ese caso usar página completa o drawer.

---

# 20. Drawers

Los paneles laterales son recomendables para:

- edición rápida;
- detalles contextuales;
- filtros avanzados;
- activity feed;
- configuración secundaria.

Ejemplo:

```text
                       ┌────────────────────┐
                       │ Detalle reserva    │
                       │                    │
                       │ Conductor          │
                       │ Vehículo           │
                       │ Estado             │
                       │                    │
                       │ [Editar]            │
                       └────────────────────┘
```

---

# 21. Tabs

Utilizar cuando una entidad tenga subconjuntos claros.

Ejemplo:

```text
General | Reservas | Pagos | Historial
```

Evitar tabs si existen solamente para ocultar tres campos.

---

# 22. Búsqueda global

En aplicaciones grandes considerar:

```text
⌘ K
```

o:

```text
Ctrl + K
```

La búsqueda global puede permitir:

- navegar a módulos;
- encontrar usuarios;
- encontrar registros;
- ejecutar acciones.

---

# 23. Command palette

Para sistemas enterprise avanzados considerar una command palette:

```text
Search or run command...

> Crear usuario
> Nueva reserva
> Buscar vehículo
> Abrir pagos
```

Implementarla solamente cuando tenga sentido.

---

# 24. Breadcrumbs

Utilizar breadcrumbs especialmente en jerarquías profundas.

Ejemplo:

```text
Parqueos / Central / Espacios / A-12
```

No repetir innecesariamente la misma información.

---

# 25. Tipografía

La tipografía debe ser altamente legible.

Preferencias:

- Inter;
- Geist;
- system-ui;
- SF Pro;
- Segoe UI.

Escala aproximada:

```text
Page title:        24–30px
Section title:     16–20px
Body:              14px
Table:             13–14px
Metadata:          12–13px
```

Evitar cuerpos de texto de 16–18px en toda la aplicación.

Una aplicación enterprise requiere mayor densidad.

---

# 26. Peso tipográfico

Usar:

```text
400 regular
500 medium
600 semibold
```

Evitar utilizar bold para todo.

La jerarquía debe construirse con:

- tamaño;
- peso;
- espacio;
- color.

---

# 27. Sistema de espaciado

Trabajar preferentemente con múltiplos de 4.

Ejemplo:

```text
4
8
12
16
20
24
32
40
48
```

Valores frecuentes:

```text
gap pequeño:       4–8px
gap normal:        12–16px
sección:           24–32px
page padding:      20–32px
```

---

# 28. Bordes

Preferir bordes sutiles.

Ejemplo conceptual:

```text
1px solid border-subtle
```

Utilizar bordes para crear estructura antes que sombras exageradas.

---

# 29. Border radius

Mantener radios moderados.

Valores habituales:

```text
4px
6px
8px
10px
```

Evitar utilizar:

```text
20px
24px
32px
```

de manera sistemática.

Enterprise no significa interfaz completamente redondeada.

---

# 30. Sombras

Utilizar sombras solamente para indicar elevación:

- dropdown;
- popover;
- modal;
- drawer;
- menú contextual.

No agregar sombra fuerte a todas las tarjetas.

---

# 31. Colores

Mantener una base neutra.

Ejemplo conceptual:

```text
Background
Surface
Surface Secondary
Border
Text Primary
Text Secondary
Text Muted
Primary
Success
Warning
Danger
Info
```

Utilizar el color principal estratégicamente.

No convertir toda la aplicación al color corporativo.

---

# 32. Dark mode

Si se implementa, debe mantener:

- contraste;
- jerarquía;
- legibilidad;
- diferenciación de superficies.

No simplemente invertir colores.

---

# 33. Iconografía

Preferir una sola librería.

Ejemplos:

- Lucide;
- Heroicons;
- Phosphor.

Tamaños frecuentes:

```text
14px
16px
18px
20px
```

Evitar mezclar estilos.

---

# 34. Responsive

Enterprise no significa solo desktop.

Considerar:

## Desktop

Sidebar expandido.

## Tablet

Sidebar colapsable.

## Mobile

Navigation drawer.

Las tablas complejas deben poder:

- realizar scroll horizontal;
- ocultar columnas secundarias;
- convertirse en cards cuando tenga sentido.

No sacrificar información crítica.

---

# 35. Empty states

Todo módulo debe considerar estado vacío.

Ejemplo:

```text
No hay usuarios registrados

Los usuarios que agregues aparecerán aquí.

[ + Crear usuario ]
```

Evitar ilustraciones enormes salvo que aporten valor.

---

# 36. Loading states

Implementar:

- skeletons;
- loaders discretos;
- estados disabled;
- feedback.

Evitar cambios abruptos de layout durante la carga.

---

# 37. Error states

Mostrar errores cerca del contexto afectado.

Ejemplo:

```text
Correo electrónico

[email@]

El correo electrónico no es válido.
```

No depender solamente de toast para errores de formulario.

---

# 38. Success feedback

Utilizar toast para acciones como:

```text
Usuario creado correctamente.
Cambios guardados.
Reserva actualizada.
```

El mensaje debe ser corto.

---

# 39. Confirmaciones

Antes de operaciones destructivas mostrar:

- qué ocurrirá;
- cuál entidad será afectada;
- si es reversible.

Ejemplo:

```text
Eliminar usuario

¿Deseas eliminar a Juan Pérez?

Esta acción no puede deshacerse.

[Cancelar] [Eliminar]
```

---

# 40. Estados interactivos

Todo componente interactivo debe contemplar:

```text
default
hover
focus
active
disabled
loading
error
selected
```

Nunca implementar solamente el estado normal.

---

# 41. Accesibilidad

La interfaz debe:

- utilizar HTML semántico;
- ser navegable por teclado;
- tener focus visible;
- utilizar labels;
- incluir aria cuando corresponda;
- mantener buen contraste;
- no depender exclusivamente del color.

---

# 42. Arquitectura de componentes

No construir cada pantalla desde cero.

Crear componentes reutilizables.

Ejemplo:

```text
AppShell
Sidebar
Topbar
PageHeader
Breadcrumb
Toolbar

DataTable
DataTableToolbar
DataTablePagination

Button
Input
Select
Combobox
Checkbox
Radio
Switch
Textarea

Badge
Avatar
Tooltip
Popover

Dialog
Drawer
DropdownMenu

Tabs
Accordion

Card
StatCard

EmptyState
Skeleton
Alert
Toast
```

---

# 43. Sistema de variantes

Los componentes deben soportar variantes.

Ejemplo:

```text
Button

variant:
- primary
- secondary
- outline
- ghost
- destructive

size:
- sm
- md
- lg
```

Mantener las variantes controladas.

No inventar nuevos estilos para cada pantalla.

---

# 44. Componentes específicos enterprise

Considerar componentes como:

```text
DataTable
FilterBar
SearchInput
BulkActions
ColumnVisibility
DateRangePicker
EntitySelector
StatusBadge
ActivityTimeline
AuditLog
KpiCard
MetricTrend
FileUploader
UserPicker
RolePicker
PermissionMatrix
CommandPalette
NotificationCenter
```

---

# 45. Permission Matrix

Para roles y permisos utilizar patrones similares a:

```text
                    Ver   Crear   Editar   Eliminar
Usuarios             ✓      ✓       ✓         -
Reservas             ✓      ✓       ✓         ✓
Pagos                ✓      -       -         -
```

Las relaciones complejas deben poder interpretarse rápidamente.

---

# 46. Activity timeline

Para sistemas administrativos considerar:

```text
Hoy

09:42
Reserva creada
por Juan Pérez

09:51
Pago confirmado
por María López

10:02
Estado cambiado a Activo
```

Especialmente útil en:

- ERP;
- CRM;
- reservas;
- tickets;
- órdenes;
- auditoría.

---

# 47. Auditoría

Los sistemas enterprise deberían contemplar:

- creado por;
- creado en;
- actualizado por;
- actualizado en;
- cambios de estado;
- actividad.

No necesariamente mostrarlo siempre, pero diseñar el sistema pensando en ello.

---

# 48. Contextual actions

Preferir acciones contextuales.

Ejemplo:

Al seleccionar 3 registros:

```text
3 seleccionados

[Activar] [Desactivar] [Exportar] [...]
```

En lugar de mantener esas acciones siempre visibles.

---

# 49. Navigation consistency

Un módulo debe sentirse relacionado con los demás.

No hacer:

```text
Usuarios → diseño A
Reservas → diseño B
Pagos → diseño C
```

Crear patrones consistentes:

```text
Index
Create
Edit
Show
```

---

# 50. Patrón List / Form

Para CRUD utilizar normalmente:

```text
INDEX
tabla/listado

CREATE
formulario

SHOW
detalle

EDIT
formulario existente
```

---

# 51. Priorizar reconocimiento

Los usuarios deben reconocer patrones.

No obligarlos a descubrir interacciones ocultas.

Por ejemplo:

- filtros parecen filtros;
- botones parecen botones;
- inputs parecen inputs;
- links parecen links;
- estados parecen estados.

---

# 52. Diseño orientado a productividad

Preguntarse constantemente:

> ¿Esta decisión reduce o aumenta la cantidad de acciones necesarias para completar la tarea?

Priorizar productividad sobre espectacularidad.

---

# 53. Evitar el "AI generated UI look"

No generar automáticamente el patrón típico:

```text
Gradient background
+
Huge rounded cards
+
Huge icons
+
Large hero title
+
Random statistics
+
Excessive whitespace
```

Eso es apropiado para marketing.

No para sistemas enterprise.

---

# 54. No convertir un dashboard en landing page

Incorrecto:

```text
Welcome back, John! 🚀

Transform your business today!

[Get started]

Huge illustration
```

Preferido:

```text
Dashboard

Resumen de operaciones
12 septiembre 2026

Reservas hoy     48
Ocupación        76%
Ingresos       Bs 3.240
Pendientes        7
```

---

# 55. Apariencia tipo Odoo / ERP moderno

Cuando el usuario solicite explícitamente una interfaz inspirada en Odoo, utilizar principalmente estos conceptos:

- barra superior compacta;
- navegación clara por módulos;
- vistas list;
- vistas kanban cuando aporten valor;
- formularios estructurados;
- estados visibles;
- acciones contextuales;
- chatter/activity panel cuando corresponda;
- filtros potentes;
- agrupaciones;
- búsqueda avanzada;
- alta densidad;
- pocas distracciones visuales.

No copiar colores, logos ni elementos propietarios.

---

# 56. Kanban

Utilizar Kanban cuando una entidad tenga flujo por estados.

Ejemplos:

```text
NUEVO
EN PROCESO
APROBADO
FINALIZADO
```

Ideal para:

- CRM;
- tickets;
- tareas;
- oportunidades;
- solicitudes.

No usar Kanban únicamente porque visualmente se ve atractivo.

---

# 57. List view

En sistemas administrativos la vista de lista normalmente debe ser la predeterminada cuando:

- hay muchas entidades;
- importa comparar datos;
- existen filtros;
- se realizan acciones masivas.

---

# 58. Saved views

Para productos enterprise avanzados considerar:

```text
Mis filtros

- Reservas de hoy
- Pendientes de pago
- Clientes frecuentes
```

Permitir guardar configuraciones de filtros cuando resulte útil.

---

# 59. Column configuration

En tablas complejas considerar:

```text
Columnas

✓ Nombre
✓ Estado
✓ Creado en
✓ Responsable
□ Teléfono
□ Ciudad
```

---

# 60. Group by

Cuando aporte valor permitir:

```text
Agrupar por:

Estado
Responsable
Fecha
Sucursal
```

Especialmente útil en sistemas con grandes volúmenes.

---

# 61. Diseño basado en datos reales

Cuando exista información del proyecto:

- utilizar nombres de campos reales;
- utilizar estados reales;
- utilizar entidades reales;
- respetar relaciones reales;
- respetar permisos reales.

No inventar contenido genérico si el proyecto ya proporciona datos.

---

# 62. Integración con backend

Si se está implementando sobre un proyecto existente:

1. inspeccionar modelos;
2. inspeccionar migrations/schema;
3. inspeccionar endpoints;
4. inspeccionar enums;
5. inspeccionar permisos;
6. inspeccionar componentes existentes.

La interfaz debe representar fielmente el dominio.

---

# 63. No romper arquitectura existente

Antes de crear nuevas dependencias:

- comprobar framework;
- comprobar librería UI existente;
- comprobar sistema de estilos;
- comprobar componentes internos.

Reutilizar lo existente cuando tenga calidad suficiente.

No introducir una segunda librería UI innecesariamente.

---

# 64. Tailwind

Si se usa Tailwind:

- evitar clases arbitrarias repetidas;
- crear componentes reutilizables;
- utilizar tokens;
- mantener consistencia.

Ejemplo conceptual:

```text
bg-background
bg-card
text-foreground
text-muted-foreground
border-border
```

Preferir tokens semánticos.

---

# 65. React

En React:

- separar lógica y presentación razonablemente;
- crear componentes reutilizables;
- evitar archivos gigantes;
- tipar props;
- evitar estados duplicados;
- crear patrones consistentes.

---

# 66. Vue

En Vue:

- utilizar componentes pequeños;
- props explícitas;
- emits explícitos;
- composables para lógica reutilizable;
- evitar duplicación de lógica.

---

# 67. Laravel / Blade / Livewire

Cuando el proyecto sea Laravel:

- respetar componentes Blade existentes;
- usar componentes Livewire cuando la interacción lo amerite;
- evitar JavaScript innecesario;
- reutilizar layouts;
- respetar Policies y permisos;
- mantener los estados de UI sincronizados con backend.

Patrones sugeridos:

```text
<x-app-layout>
<x-page-header>
<x-data-table>
<x-modal>
<x-form.input>
<x-form.select>
<x-badge>
```

La nomenclatura debe adaptarse al proyecto.

---

# 68. Manejo de permisos en UI

No basta con ocultar elementos visualmente.

Cuando exista autorización:

- utilizar permisos reales del backend;
- ocultar acciones no disponibles;
- deshabilitar cuando corresponda;
- garantizar validación del servidor.

Ejemplo Laravel:

```php
@can('users.create')
    ...
@endcan
```

---

# 69. Consistencia entre módulos

Crear primero un pequeño sistema visual y después extenderlo.

Antes de implementar múltiples pantallas definir:

```text
Color tokens
Typography
Spacing
Buttons
Inputs
Tables
Cards
Dialogs
Sidebar
Page layout
```

Después reutilizarlos.

---

# 70. Proceso obligatorio al generar una pantalla

Para cada nueva interfaz seguir:

## Paso 1 — Comprender

Determinar:

- usuario;
- tarea;
- entidad;
- datos;
- acciones;
- estados.

## Paso 2 — Elegir patrón

Seleccionar:

- table;
- form;
- detail;
- dashboard;
- kanban;
- wizard;
- settings.

## Paso 3 — Jerarquía

Definir:

- primary information;
- secondary information;
- metadata;
- actions.

## Paso 4 — Layout

Crear estructura antes que detalles.

## Paso 5 — Componentes

Reutilizar componentes del sistema.

## Paso 6 — Estados

Implementar:

- loading;
- empty;
- error;
- success;
- disabled.

## Paso 7 — Responsive

Validar escritorio, tablet y móvil.

## Paso 8 — Refinamiento

Revisar:

- spacing;
- alignment;
- typography;
- density;
- consistency.

---

# 71. Cuando se proporcione una captura o referencia

No copiar mecánicamente.

Primero analizar:

- layout;
- proporciones;
- jerarquía;
- navegación;
- densidad;
- componentes;
- estados;
- relación entre áreas.

Después reconstruir siguiendo el sistema visual del proyecto.

---

# 72. Si el usuario dice "hazlo como Odoo"

Interpretar como:

> Crear una interfaz empresarial compacta, funcional y modular con navegación clara, tablas robustas, formularios estructurados, filtros, estados, acciones contextuales y alta densidad de información.

No significa:

> Copiar pixel por pixel Odoo.

---

# 73. Si el usuario dice "enterprise"

Automáticamente priorizar:

1. Productividad.
2. Claridad.
3. Consistencia.
4. Densidad.
5. Navegación.
6. Escalabilidad.
7. Accesibilidad.
8. Estados completos.
9. Feedback del sistema.
10. Calidad de implementación.

---

# 74. Checklist previo a finalizar

Antes de considerar terminada cualquier interfaz revisar:

### Layout

- ¿Existe jerarquía visual?
- ¿El contenido usa correctamente el espacio?
- ¿La navegación es consistente?

### UX

- ¿Las acciones principales son evidentes?
- ¿Los filtros son fáciles de usar?
- ¿Existe feedback?
- ¿Se entiende el estado de la entidad?

### Datos

- ¿Las tablas tienen alineación correcta?
- ¿Existen estados vacíos?
- ¿Existe loading?
- ¿Existe paginación cuando corresponde?

### Formularios

- ¿Los campos están agrupados?
- ¿Los labels son claros?
- ¿Los errores aparecen junto al campo?

### Visual

- ¿Se abusó de tarjetas?
- ¿Se abusó de border radius?
- ¿Se abusó de sombras?
- ¿Existe demasiado espacio vacío?
- ¿Parece una aplicación enterprise o una landing page?

### Responsive

- ¿Funciona correctamente en desktop?
- ¿Funciona correctamente en tablet?
- ¿Es utilizable en móvil?

### Accesibilidad

- ¿Existe foco visible?
- ¿Tiene contraste?
- ¿Puede utilizarse con teclado?
- ¿Los inputs tienen label?

---

# 75. Criterio de aprobación

Una interfaz está lista solamente si visualmente podría pertenecer a:

- un ERP comercial;
- un CRM profesional;
- una plataforma administrativa empresarial;
- un SaaS B2B maduro.

Debe evitar cualquier apariencia de:

- demo;
- mockup incompleto;
- template genérico;
- UI generada automáticamente;
- landing page.

---

# 76. Resultado esperado

Cuando esta skill esté activa, el agente debe producir interfaces que se perciban como:

> Software empresarial diseñado para personas que lo utilizan diariamente para realizar trabajo real.

No simplemente:

> Una interfaz moderna.

La prioridad siempre será:

**claridad + productividad + consistencia + densidad + escalabilidad.**
