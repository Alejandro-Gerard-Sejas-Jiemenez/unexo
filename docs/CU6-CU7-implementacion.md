# CU6 y CU7: matriculación flexible, estado de matrícula y API

Fecha: 2026-10-15. Laravel 12, PostgreSQL (Neon), IDs UUID, Sanctum para la API.

## Qué cubre cada caso de uso

| Caso (contexto.md) | Casos del documento "Casos de uso" | Dónde está |
|---|---|---|
| **CU6** Recepción y matriculación flexible | CU21 alumno convertido, CU22 módulo suelto, CU23 programa completo | `MatriculaService`, `MatriculaController` (ya existían) + `Api/EstudianteConvertidoController` (nuevo) |
| **CU7** Estado de matrícula y exposición API | CU24 estado, CU25 retirar, CU26/27 consultas, CU28 acceso API, CU31 subir comprobante, CU32 aprobar pago | `MatriculaService::cambiarEstado`, `ComprobanteController`, `Api/MatriculaApiController`, `ClienteApiController` |

Fuera de alcance: CU33 (continuar programa tras módulo suelto), API del catálogo (CU3), notas y certificados.

## Qué se agregó

**Migraciones**
- `2026_10_15_000001_create_personal_access_tokens_table`: tokens de Sanctum con `uuidMorphs`.
- `2026_10_15_000002_create_api_clientes_table`: clientes de la API (el CRM).
- `2026_10_15_000003_create_comprobantes_pago_table`: comprobantes (`pendiente`, `aprobado`, `rechazado`), con `CHECK` en PostgreSQL.

**Código**
- Modelos `ClienteApi` (con `HasApiTokens`) y `Comprobante`; enum `EstadoComprobante`; `Matricula::comprobantes()`.
- `MatriculaService`: `cambiarEstado()`, `recibirConvertido()`, `buscarConvertido()`. `inscribirEnCohorte/GrupoSuelto` aceptan el origen (`manual` o `crm`).
- Web: `ClienteApiController` (`/clientes-api`), `ComprobanteController`.
- API: `routes/api.php`, `Api/EstudianteConvertidoController`, `Api/MatriculaApiController`, `MatriculaResource`, middleware `SoloClienteApi` (alias `cliente.api`).
- `bootstrap/app.php`: registra la API y hace que `api/*` responda siempre JSON.
- Vistas: `clientes-api/index` (nueva), `matriculas/show` (comprobantes y acciones por estado), `mis-cursos/index` (subida de comprobante). Menú del administrador: "Acceso API".
- Tests: `tests/Feature/MatriculasApiTest.php` (10 casos). Toda la suite: 50 pasan, en SQLite en memoria.

## Reglas

- **Estados** (`MatriculaService::cambiarEstado`): una matrícula `retirada` no se modifica (conserva el historial, RNF2) y no se repite el mismo estado. Retirar guarda `fecha_retiro`.
- **Aprobar pago (CU32)**: aprobar un comprobante lo marca `aprobado` y pasa la matrícula de `pendiente` a `activo`. Rechazarlo no cambia la matrícula.
- **Comprobante (CU31)**: solo el estudiante dueño de la matrícula, solo si está `pendiente`, archivos jpg/png/pdf de hasta 4 MB. Se guarda en el disco privado `local` (`storage/app/private/comprobantes`) y lo ven el administrador y el dueño en `GET /comprobantes/{id}`.
- **Alumno convertido (R4)**: es idempotente. Se busca por `id_externo_crm` o, si no, por correo (enlaza un alumno cargado a mano). Si ya existe, se actualizan sus datos. La matrícula solo se crea si no existía para esa cohorte o grupo.

## API (propuesta A: el CRM envía los alumnos)

Todas las rutas requieren `Authorization: Bearer <token>` de un cliente de la API (Sanctum). Sin token responde `401`. Un token que no sea de un cliente responde `403`. Siempre JSON.

**El administrador genera el token** en `/clientes-api` (menú "Acceso API"). Se muestra una sola vez. "Revocar" borra el cliente y sus tokens.

### `POST /api/v1/estudiantes-convertidos`

Para el equipo del CRM: ver la sección [Contrato para el equipo del CRM](#contrato-para-el-equipo-del-crm) al final de este documento.

### `GET /api/v1/matriculas`
Filtros opcionales: `id_externo_crm`, `estado` (`pendiente`, `activo`, `retirado`). Paginado.

### `GET /api/v1/matriculas/{id}`
Una matrícula con su estudiante, programa, cohorte y grupos.

```json
{ "data": { "id": "…", "estado": "pendiente", "origen": "crm", "fecha_matricula": "2026-10-15",
  "fecha_retiro": null, "estudiante": { "id": "…", "id_externo_crm": "CRM-100", "nombre": "Ana Rojas", "email": "…" },
  "programa": "Diplomado", "cohorte": "C1",
  "grupos": [ { "id": "…", "modulo": "Excel", "nombre": "A", "fecha_inicio": "2026-09-01", "fecha_fin": "2026-10-01" } ] } }
```

Ejemplo:

```bash
curl -H "Authorization: Bearer <token>" -H "Accept: application/json" \
  "http://localhost:8000/api/v1/matriculas?id_externo_crm=CRM-100"
```

## Decisiones y cosas a saber

- **Tabla `api_clientes`, no `clientes_api`**: la base de Neon ya tenía una tabla `clientes_api` con otro esquema (`identificador`, `token_hash`, `permisos`…), que no está en las migraciones del repositorio. No se tocó. Si ese diseño de tokens propios es el que se quiere, hay que decidir cuál queda y quitar el otro.
- **Neon y las migraciones**: la base de Neon tiene el esquema desfasado respecto a las migraciones del repo (en `modulos` ya no existe `es_independiente` pero sí `requisitos_recomendados`, y ya existen `temario` y `categoria`). Por eso `php artisan migrate` completo falla en `2026_10_05_000001_quitar_requisitos…`. Para esta entrega se corrieron solo las tres migraciones nuevas con `--path`, y las tablas `aulas`, `cohortes`, `grupos`, `matriculas` e `inscripciones_grupo` (que faltaban en Neon) se crearon con `migrate`. **Actualización (15/10, con acuerdo del equipo):** se corrieron también las migraciones del 05 y 08 de octubre. Se volvieron tolerantes (`Schema::hasColumn`) porque Neon ya tenía `descripcion` y no tenía `es_independiente`. `requisitos_recomendados` se eliminó tras comprobar que no tenía datos (0 de 6 módulos). `php artisan migrate:status` ya no muestra pendientes.
- **No se corrieron seeders** contra Neon, para no mezclar datos de prueba con la base compartida.
- **Entorno local**: se instaló Composer en `~/composer` (agregado al PATH de usuario) y se creó `~/php-config/php.ini` (variable `PHPRC`) con las extensiones `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `pdo_sqlite`, etc. Abre una terminal nueva para que lo tome. Los assets se compilaron con `pnpm install && pnpm run build` (los tests renderizan vistas y necesitan `public/build`).
- **Pendientes del cliente**: método de autenticación de la API (aquí: Bearer con Sanctum), si el CRM envía o se consulta (aquí: el CRM envía), formato final del alumno convertido, y si la operadora de caja necesita acceso al SIS.

## Cómo probar

```bash
php artisan test                      # SQLite en memoria; no toca Neon
php artisan serve                     # http://localhost:8000
# 1. Entrar como administrador → "Acceso API" → generar token
# 2. curl con el token (ver arriba)
# 3. Entrar como estudiante con matrícula pendiente → Mis cursos → subir comprobante
# 4. Como administrador → Matrículas → ver la matrícula → Aprobar pago
```

---

## Contrato para el equipo del CRM

Esta sección se puede pasar sola al equipo del CRM. Describe `POST /api/v1/estudiantes-convertidos`: lo que el CRM debe enviar y lo que el SIS hace con eso.

### Qué hace el endpoint

El CRM avisa al SIS de un prospecto que ya pagó y fue verificado ("convertido"). El SIS:

1. **Crea el alumno** (rol estudiante, activo) o, si ya existe, **actualiza sus datos**. El alumno existente se busca por `id_externo_crm` y, si no aparece, por `email` (así se enlaza con alguien que la administración ya había cargado a mano; se le asigna entonces el `id_externo_crm`).
2. **Opcionalmente lo matricula** en una oferta (`cohorte_id` o `grupo_id`). La matrícula queda en estado `pendiente` con origen `crm`. La administración la pasa a `activo` cuando aprueba el comprobante de pago en el SIS.
3. **Es idempotente**: enviar lo mismo dos veces no duplica el alumno ni la matrícula. El CRM puede reintentar sin riesgo si no recibió respuesta.

El SIS **no** hace con este endpoint: procesar pagos, enviar correos, ni dar una contraseña al alumno (se crea con una clave aleatoria; el acceso lo entrega la administración). Tampoco guarda historial de ventas, precios ni descuentos (eso es del CRM).

### Petición

```
POST {base}/api/v1/estudiantes-convertidos
Authorization: Bearer <token>
Content-Type: application/json
Accept: application/json
```

El token lo genera el administrador del SIS en "Acceso API" y se entrega una sola vez. `{base}` es el servidor del SIS (en pruebas, `http://localhost:8000`).

```json
{
  "id_externo_crm": "CRM-100",
  "nombres": "Ana María",
  "apellidos": "Rojas Quispe",
  "email": "ana.rojas@example.com",
  "documento_identidad": "1234567",
  "telefono": "70000000",
  "cohorte_id": "0198f3c2-7b1a-7c4d-9e21-5a6b7c8d9e01"
}
```

| Campo | Tipo | Obligatorio | Reglas |
|---|---|---|---|
| `id_externo_crm` | string | **Sí** | Máx. 60. Id del prospecto en el CRM; es la clave para no duplicar. |
| `nombres` | string | **Sí** | Máx. 120. |
| `apellidos` | string | **Sí** | Máx. 120. |
| `email` | string | **Sí** | Correo válido, máx. 255. Único en el SIS (salvo que sea del mismo alumno). |
| `documento_identidad` | string | No | Máx. 30. Único en el SIS (salvo que sea del mismo alumno). |
| `telefono` | string | No | Máx. 30. |
| `cohorte_id` | uuid | No | Matricula al alumno en **todos los grupos** de esa cohorte (programa completo). Debe existir. |
| `grupo_id` | uuid | No | Matricula al alumno en un **módulo suelto**: el grupo debe existir y no pertenecer a ninguna cohorte. |

- `cohorte_id` y `grupo_id` **no se pueden enviar juntos**. Sin ninguno solo se registra al alumno.
- Los campos que no estén en la tabla se ignoran.
- Si se reenvía un alumno existente, los campos opcionales que vengan `null` o ausentes **no borran** los datos que ya tiene el SIS.
- Para enviar una oferta nueva de un alumno ya registrado, se vuelve a llamar con el mismo `id_externo_crm` y el nuevo `cohorte_id`/`grupo_id`.

### Respuestas

**`201 Created`**: el alumno no existía y se creó. **`200 OK`**: ya existía y se actualizó. Mismo cuerpo en ambos:

```json
{
  "estudiante": {
    "id": "0198f3c2-1111-7c4d-9e21-5a6b7c8d9e02",
    "id_externo_crm": "CRM-100",
    "nombres": "Ana María",
    "apellidos": "Rojas Quispe",
    "email": "ana.rojas@example.com"
  },
  "matricula": {
    "id": "0198f3c2-2222-7c4d-9e21-5a6b7c8d9e03",
    "estado": "pendiente",
    "origen": "crm",
    "fecha_matricula": "2026-10-15",
    "fecha_retiro": null,
    "estudiante": { "id": "…", "id_externo_crm": "CRM-100", "nombre": "Ana María Rojas Quispe", "email": "ana.rojas@example.com" },
    "programa": "Análisis de Datos",
    "cohorte": "Cohorte 2026-2",
    "grupos": [
      { "id": "…", "modulo": "Excel", "nombre": "Grupo A", "fecha_inicio": "2026-09-21", "fecha_fin": "2026-10-09" }
    ]
  }
}
```

`matricula` es `null` si no se envió oferta o si el alumno **ya estaba matriculado** en esa cohorte/grupo (no se crea otra). En módulo suelto, `programa` y `cohorte` son `null`.

| Código | Cuándo | Qué hacer |
|---|---|---|
| `401` | Falta el token, es inválido o fue revocado. | Pedir un token nuevo a la administración. |
| `403` | El token no pertenece a un cliente de la API. | Usar el token que entrega "Acceso API". |
| `409` | El `email` ya pertenece a otro alumno con **otro** `id_externo_crm`. | Revisar el dato; no reintentar igual. Avisar a la administración. |
| `422` | Datos inválidos. Cuerpo: `{"message": "...", "errors": {"campo": ["..."]}}`. | Corregir y reenviar. |
| `5xx` | Falla del servidor. | Reintentar más tarde (es seguro por idempotencia). |

Ejemplo de `422`:

```json
{ "message": "El campo email no es un correo válido.", "errors": { "email": ["El campo email no es un correo válido."] } }
```

Los mensajes de validación vienen en el idioma configurado en el SIS; el CRM debe basarse en el nombre del campo, no en el texto.

### Cómo lo llama el CRM

```bash
curl -X POST "http://localhost:8000/api/v1/estudiantes-convertidos" \
  -H "Authorization: Bearer <token>" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"id_externo_crm":"CRM-100","nombres":"Ana","apellidos":"Rojas","email":"ana@example.com","cohorte_id":"<uuid>"}'
```

### Qué debe hacer el CRM

- Enviar el alumno **cuando el pago ya fue verificado** (estado "convertido").
- Guardar el `id` UUID del `estudiante` y de la `matricula` de la respuesta, y usar `GET /api/v1/matriculas?id_externo_crm=CRM-100` para consultar luego si la matrícula pasó a `activo` o `retirado`.
- Reintentar ante `5xx` o falta de respuesta; no reintentar sin cambios ante `409` o `422`.

### Pendiente antes de que el CRM pueda usarlo

- **Los ids de cohorte y grupo no se pueden consultar todavía por API.** El endpoint de catálogo (programas, módulos, cohortes, grupos abiertos) no está implementado. Hasta entonces, la administración debe pasar al CRM los UUID de la oferta, o enviar al alumno sin oferta y matricularlo a mano en el SIS.
- Confirmar con el CRM los nombres de campo (`id_externo_crm`, etc.) y si necesitan algún dato más del alumno.