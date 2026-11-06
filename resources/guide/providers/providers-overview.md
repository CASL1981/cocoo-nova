Este tema explica cómo se organizan los proveedores en Cocoo Nova y qué reglas deciden si un proveedor necesita el formato **FO-300-01** (Vinculación y actualización de proveedores). Léelo antes de registrar tu primer proveedor.

## Proveedores administrativos y comerciales

Cada proceso gestiona sus propios proveedores desde su menú:

| Tipo de proveedor | Qué suministra | Dónde se gestiona | Quién aprueba el FO-300-01 |
|---|---|---|---|
| Administrativo | Papelería, cafetería, aseo y otros | **Administrativo → Proveedores** | Director del proceso Administrativo |
| Comercial | Medicamentos y dispositivos médicos | **Compras → Proveedores** | Director del proceso Comercial |

Un mismo proveedor puede ser de los dos tipos. El proveedor se registra **una sola vez** con su número de identificación. Luego cada proceso tiene su propia **vinculación** con él, con su estado, su condición de pago y su plazo de pago.

Si tu proceso necesita un proveedor que ya existe en el otro, no lo registres de nuevo: digita su identificación en **Agregar** y usa **Vincular a mi proceso**. Los pasos están en [Actualización anual, vinculación y estado](guide:providers-maintenance).

> **Importante:** cada pantalla muestra solo los proveedores de su proceso. Para ver los proveedores de Compras necesitas los permisos de esa línea, y lo mismo para Administrativo.

## Proveedor básico o con FO-300-01

El FO-300-01 no se exige a todos los proveedores. Depende de cuánto se le compra:

- **Proveedor básico:** se registra solo con datos mínimos (identificación, nombre, ubicación, teléfono, correo, contacto y condiciones de pago). Queda activo de inmediato y no pasa por aprobación. Ver [Registrar un proveedor básico](guide:providers-basic).
- **Proveedor con FO-300-01:** diligencia el formato completo, que se verifica y se aprueba. Ver [Diligenciar el FO-300-01](guide:providers-form) y [Verificar, aprobar o rechazar](guide:providers-review).

> **Regla:** solo el proceso **Administrativo** admite proveedores básicos. Todos los proveedores **comerciales** (Compras) diligencian siempre el FO-300-01.

### El tope de 2 SMMLV

Un proveedor administrativo debe diligenciar el FO-300-01 cuando se supera el tope de compras, que es **2 SMMLV** del año de la orden. Hay dos formas de superarlo:

1. Una sola orden de compra, con IVA, mayor al tope.
2. El acumulado del **mes calendario** con ese proveedor en el **mismo proceso** mayor al tope. El acumulado suma, con IVA, las órdenes aprobadas, las que están en revisión y la orden nueva.

Qué pasa con cada orden según la situación del proveedor:

| Situación del proveedor | Orden de compra |
|---|---|
| FO-300-01 vigente | Se permite, sin tope. |
| Sin FO-300-01 y por debajo del tope | Se permite. |
| Sin FO-300-01 y por encima del tope | Se permite con una alerta. La vinculación queda marcada **FO-300-01 requerido** hasta que el proveedor diligencie el formato. |
| FO-300-01 vencido | **No se permiten órdenes** hasta actualizarlo. |
| Vinculación que no está Activa (Pendiente, Inactiva o Rechazada) | **No se permiten órdenes.** |

> **Importante:** las órdenes de compra aún no están disponibles en la aplicación. Esta tabla es la política que aplicarán cuando lo estén. Mientras tanto, la marca **Requerido** y el aviso "FO-300-01 requerido" en el detalle te indican qué proveedores deben diligenciar el formato.

### Dónde se configuran el SMMLV y el tope

El valor del SMMLV de cada año y el múltiplo del tope (hoy 2) se configuran en **Datos de la empresa**. Lo hace un administrador; ver [Datos de la empresa](guide:admin-company-settings).

## Situación del FO-300-01

Cada proveedor muestra la situación de su formato en la columna **FO-300-01** del listado y en su detalle:

| Situación | Qué significa |
|---|---|
| **Sin FO-300-01** | Proveedor básico: no tiene un formato vigente ni uno en trámite. Puede tener formatos de años anteriores (ver más abajo). |
| **En trámite** | Tiene su primer formato en Borrador o en revisión, todavía sin aprobar. |
| **Vigente** | Tiene un formato aprobado y su fecha de actualización no ha llegado. |
| **Vencido** | Pasó la fecha de la actualización anual sin aprobar una nueva versión. |

Cuando un proveedor ya aprobado tiene una actualización en curso, la columna muestra además la versión y su estado, por ejemplo **V2 · En verificación**.

### Formatos de años anteriores

Los FO-300-01 de 2024 y 2025 se cargaron desde el sistema anterior como versiones aprobadas de esos años. Sirven de consulta, pero **no hacen vigente el formato**: si el proveedor no tiene un formato aprobado en Cocoo Nova, sigue como proveedor básico (**Sin FO-300-01**).

- Su ficha muestra el aviso de proveedor básico con la nota "Tiene formatos de años anteriores: consúltelos en «Ver años»".
- Conserva el botón **Editar datos básicos**.
- Al usar **Diligenciar FO-300-01**, el Borrador se llena con los datos del año más reciente, para que solo corrijas lo que cambió.

Los pasos están en [Registrar un proveedor básico](guide:providers-basic) y la consulta por año en [Verificar, aprobar o rechazar](guide:providers-review).

## Nivel de riesgo y PEP

El sistema calcula el **nivel de riesgo** al guardar el formato. No se escribe a mano:

| Proveedor | Riesgo |
|---|---|
| Es PEP | **Alto** |
| Persona natural, no PEP | **Medio** |
| Persona jurídica, no PEP | **Bajo** |

Un proveedor es **PEP** (persona expuesta políticamente) si responde "sí" a cualquiera de estas preguntas del formato:

- Administra recursos públicos.
- Es una persona expuesta políticamente.
- Tiene relación con un PEP.

Un cargo público que terminó hace más de 2 años ya no cuenta (Decreto 830 de 2021). Un proveedor PEP pasa además por el oficial de cumplimiento y lo aprueba Gerencia.

## Actualización anual

Cuando se aprueba un FO-300-01, la **próxima actualización** queda para un año después. Ese día el formato pasa a **Vencido** y el listado muestra la fecha en rojo con un ícono de alerta.

> **Regla:** un proveedor que ya diligenció el FO-300-01 sigue obligado a actualizarlo cada año, aunque después sus compras bajen del tope.

> **Regla:** cada proveedor tiene como máximo **una solicitud FO-300-01 por año calendario**. Si la de este año fue rechazada, no se abre otra: se corrige con **Corregir FO-300-01**. Si otro proceso vincula al proveedor en el año de un formato aprobado, su director aprueba la vinculación sobre ese mismo formato.

Los pasos para actualizarlo están en [Actualización anual, vinculación y estado](guide:providers-maintenance).

## El listado de proveedores

Abre **Administrativo → Proveedores** o **Compras → Proveedores**. Las columnas clave son:

![Listado de proveedores administrativos con las columnas clave](images/providers-overview/01-administrative-list.png)

1. **Riesgo:** nivel calculado (Alto, Medio o Bajo). Un proveedor PEP muestra además la etiqueta negra **PEP**. Los proveedores básicos no tienen riesgo.
2. **Vinculación:** estado del proveedor en tu proceso:
   - **Pendiente:** esperando la aprobación de su FO-300-01.
   - **Activa:** se le puede comprar.
   - **Inactiva:** suspendido por el proceso.
   - **Rechazada:** su formato fue rechazado.
3. **FO-300-01:** situación del formato (Sin FO-300-01, En trámite, Vigente o Vencido). La etiqueta amarilla **Requerido** indica que se superó el tope sin formato. Al ordenar por esta columna, los proveedores quedan en ese orden: Sin FO-300-01, En trámite, Vigente y Vencido.
4. **Próx. actualización:** fecha de la actualización anual. En rojo, con un ícono de alerta, si ya venció.

Las filas amarillas son vinculaciones Inactivas o Rechazadas y las rojas, eliminadas. Para buscar, filtrar, ordenar, exportar o consultar la auditoría, sigue [Cómo usar los listados](guide:standard-lists). Para abrir el detalle de un proveedor, haz clic en su nombre.
