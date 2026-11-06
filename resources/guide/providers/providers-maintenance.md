Este tema cubre el mantenimiento de un proveedor después de su registro:

- La actualización anual del FO-300-01.
- La vinculación de un proveedor existente a otro proceso.
- La activación o inactivación de la vinculación.
- Las condiciones de pago.
- Quién puede ver los datos sensibles.

## Actualización anual del FO-300-01

Un FO-300-01 aprobado vence un año después de su aprobación. Antes de esa fecha, abre una actualización. Necesitas el permiso de crear proveedores en tu proceso, y el proveedor no puede tener otra versión en trámite.

> **Regla:** cada proveedor tiene como máximo **una solicitud FO-300-01 por año calendario**. Si el formato de este año ya existe, el botón **Actualizar FO-300-01** no aparece: espera al próximo año. Si el formato de este año fue rechazado, no se abre otro: se corrige con **Corregir FO-300-01** (ver [Verificar, aprobar o rechazar](guide:providers-review)).

El botón se llama **Actualizar FO-300-01** cuando el proveedor tiene un formato vigente, y **Diligenciar FO-300-01** cuando no lo tiene (proveedor básico o con formatos solo de años anteriores).

### 1. Abrir la actualización

Abre la ficha del proveedor.

![Ficha de un proveedor vigente con el botón Actualizar FO-300-01](images/providers-maintenance/01-update-button.png)

1. La sección **FO-300-01** muestra el estado (**Vigente**), la fecha de la **próxima actualización**, los otros procesos a los que está vinculado y, en **Años**, el botón **Ver años** para consultar los formatos de cada año (ver [Verificar, aprobar o rechazar](guide:providers-review)).
2. Haz clic en **Actualizar FO-300-01**.

También puedes seleccionar al proveedor en el listado y usar el ícono del lápiz (tooltip "Editar seleccionado"). Como un proveedor aprobado no se edita directamente, se abre la ventana **Actualizar proveedor aprobado**, con el botón **Abrir actualización**.

### 2. Confirmar

![Confirmación para abrir la actualización](images/providers-maintenance/02-update-confirm.png)

Haz clic en **Sí, continuar**. El sistema crea una versión nueva en **Borrador**, del tipo "Actualización de datos". La versión copia la vigente con sus contactos, accionistas, familiares PEP y anexos.

### 3. Corregir, enviar y aprobar

Corrige lo que haya cambiado, actualiza los anexos y envía la versión, igual que en [Diligenciar el FO-300-01](guide:providers-form). La actualización vuelve a pasar por verificación y aprobación ([Verificar, aprobar o rechazar](guide:providers-review)). Si el proveedor está vinculado a los dos procesos, firman los dos directores (o Gerencia, si es PEP).

> **Importante:** mientras la actualización está en trámite, la versión vigente sigue activa y se le puede seguir comprando al proveedor hasta su fecha de vencimiento.

### Formato vencido

Si la fecha pasa sin una actualización aprobada, el formato queda **Vencido**:

![Ficha de un proveedor con el FO-300-01 vencido](images/providers-maintenance/03-expired.png)

1. El estado es **Vencido** y la próxima actualización aparece en rojo con el aviso "Vencida: no se pueden hacer órdenes hasta actualizarlo". En el listado, la fecha también aparece en rojo con un ícono de alerta.

> **Regla:** con el formato vencido no se permiten órdenes de compra al proveedor hasta que se apruebe su actualización. Esto aplica aunque sus compras estén por debajo del tope de 2 SMMLV.

## Vincular un proveedor a tu proceso

Si el proveedor ya existe en el otro proceso, no lo registres de nuevo: vincúlalo. Necesitas el permiso de crear proveedores en tu proceso.

1. En el listado de tu proceso, haz clic en el botón **+** (tooltip "Agregar").
2. Digita la identificación del proveedor y sal del campo.
3. Aparece el aviso "Ya existe … con esta identificación" con los procesos donde está vinculado. Haz clic en **Vincular a mi proceso**.

![Vincular desde Compras un proveedor administrativo](images/providers-maintenance/04-link-other-process.png)

1. **Número de identificación** del proveedor existente.
2. **Vincular a mi proceso** crea la vinculación con tu proceso y abre la ficha del proveedor.

Lo que pasa después depende del proceso:

| Proceso que vincula | Resultado |
|---|---|
| **Administrativo** | La vinculación queda **Activa** de inmediato, sin aprobación. |
| **Compras** | La vinculación queda **Pendiente**. Qué sigue depende del formato del proveedor (ver la tabla siguiente). |

Cuando la vinculación queda **Pendiente**:

| Situación del FO-300-01 del proveedor | Qué pasa |
|---|---|
| Tiene una versión en trámite | Tu vinculación se suma a su aprobación: tu director también firma esa versión. |
| Tiene aprobado el formato de **este año** | No se abre una versión nueva. El director de tu proceso (o Gerencia, si es PEP) aprueba o rechaza tu vinculación sobre la versión vigente, en **Verificación y aprobación**. La fecha de la próxima actualización no cambia. |
| El formato de este año fue rechazado | No se abre otro: tu vinculación espera a que se corrija con **Corregir FO-300-01**. |
| No tiene formato de este año | Se abre una actualización en Borrador (o el primer FO-300-01 si es un proveedor básico): complétala y envíala. |

> **Regla:** un proveedor de Compras siempre necesita el FO-300-01 aprobado. Por eso, al vincular un proveedor básico de Administrativo, se abre su primer FO-300-01 en Borrador con sus datos básicos.

## Activar o inactivar una vinculación

Cada proceso activa o inactiva su propia vinculación. Inactivar un proveedor en Compras no lo inactiva en Administrativo, y al revés. Necesitas el permiso de activar o inactivar proveedores de tu proceso.

![Selección de un proveedor y botón Activar / inactivar](images/providers-maintenance/05-toggle.png)

1. Marca la casilla de uno o varios proveedores.
2. Haz clic en el ícono del interruptor (tooltip "Activar / inactivar seleccionados"). Cada vinculación Activa pasa a Inactiva, y cada Inactiva pasa a Activa.

En pantallas de menos de 1600 px de ancho, este botón está dentro del menú **Más acciones** (ícono de tres puntos), con el nombre "Activar / inactivar seleccionados".

> **Regla:** solo se activan o inactivan vinculaciones ya aprobadas (Activas o Inactivas). Las Pendientes y Rechazadas se omiten, y el sistema avisa cuántas no cambió.

Una vinculación **Inactiva** no admite órdenes de compra. En el listado se ve en amarillo.

### Eliminar una vinculación

El ícono de la papelera (tooltip "Eliminar seleccionados") solo elimina vinculaciones **Pendientes** o **Rechazadas**, es decir, las que nunca se aprobaron. Las aprobadas no se eliminan: se inactivan. Quien tiene el permiso de reversar puede restaurar una vinculación eliminada desde el listado (ver [Cómo usar los listados](guide:standard-lists)).

## Condiciones de pago del proceso

Cada proceso negocia su propia condición y su propio plazo de pago con el proveedor. Se pueden cambiar en cualquier momento, sin abrir una nueva versión del formato. Necesitas el permiso de editar proveedores de tu proceso.

1. En la ficha del proveedor, haz clic en **Condiciones**.
2. Elige los valores y haz clic en **Guardar**.

![Ventana Condiciones del proceso](images/providers-maintenance/06-terms.png)

1. **Condición de pago**, por ejemplo Contado o Crédito.
2. **Plazo de pago**, por ejemplo 30 días.

En **Compras** la ventana incluye además el **Segmento comercial** (medicamentos, dispositivos médicos o ambos). Las opciones de estas listas se administran en **Características**.

## Quién ve los datos sensibles

Algunos datos del FO-300-01 son datos personales sensibles (Ley 1581 de 2012):

- La información financiera (ingresos, activos, pasivos, patrimonio, insolvencia y moneda extranjera).
- Las respuestas PEP y el grupo familiar o la relación con PEP.
- El grupo de protección especial de una persona natural.

Solo los ven:

- Quien tiene el permiso de **procesar** proveedores del proceso, es decir, quien verifica.
- El **oficial de cumplimiento**.
- Los **aprobadores**, mientras la versión está en aprobación y tienen una firma pendiente.

Los demás ven en su lugar la sección **Datos reservados**:

![Sección Datos reservados para un usuario que solo consulta](images/providers-maintenance/07-sensitive-hidden.png)

1. **Datos reservados** reemplaza las secciones de información financiera y PEP.

El PDF del formato aplica la misma regla: solo incluye estos datos si quien lo descarga puede verlos.

> **Importante:** quien edita un Borrador sí ve y diligencia estos campos en la ventana del formato. Después de guardar, la ficha los muestra solo a las personas de la lista anterior.
