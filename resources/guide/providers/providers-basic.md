Un proveedor básico es un proveedor **administrativo** al que se le compra hasta 2 SMMLV por orden y por mes. Se registra con datos mínimos, queda **Activo** de inmediato y no necesita aprobación. Si no tienes claro si tu proveedor es básico, lee primero [Conceptos: proveedor básico y FO-300-01](guide:providers-overview).

> **Regla:** los proveedores de Compras no pueden ser básicos: siempre diligencian el FO-300-01. Este tema aplica solo a **Administrativo → Proveedores**.

## Registrar un proveedor básico

Necesitas el permiso de crear en la línea de proveedores administrativos.

### 1. Abrir el registro

En **Administrativo → Proveedores**, haz clic en el botón morado con el ícono **+** (tooltip "Agregar").

![Botón Agregar del listado de proveedores administrativos](images/providers-basic/01-add-button.png)

### 2. Digitar primero la identificación

Se abre la ventana **Registrar proveedor**. Empieza siempre por la identificación: así el sistema te avisa si el proveedor ya existe.

![Ventana Registrar proveedor con los campos de identificación](images/providers-basic/02-empty-modal.png)

1. **Tipo de persona:** Persona natural o Persona jurídica.
2. **Tipo de documento:** se ajusta al tipo de persona (por ejemplo, NIT para persona jurídica y cédula para persona natural).
3. **Identificación:** sin puntos ni dígito de verificación. Al salir del campo, el sistema busca si ya existe un proveedor con ese número.
4. **Registrar con FO-300-01:** úsalo si ya sabes que la orden, o lo comprado en el mes, supera 2 SMMLV. Cierra esta ventana y abre el formato completo (ver [Diligenciar el FO-300-01](guide:providers-form)).

### 3. Si el proveedor ya existe

Si la identificación ya está registrada, por ejemplo como proveedor de Compras, aparece un aviso amarillo con su nombre y sus vinculaciones actuales.

![Aviso de proveedor ya registrado con el botón Vincular a mi proceso](images/providers-basic/03-existing-provider.png)

1. El aviso indica el proveedor encontrado y en qué procesos está, por ejemplo "Comercial: Activa".
2. Haz clic en **Vincular a mi proceso**. El proveedor queda vinculado a Administrativo, **Activo** de inmediato, y se abre su detalle. No tienes que volver a digitar sus datos.

Si el proveedor ya está vinculado a tu proceso, el botón no aparece: búscalo en el listado.

> **Importante:** no registres dos veces al mismo proveedor con otra identificación (por ejemplo, con el dígito de verificación). La identificación es única en toda la aplicación.

### 4. Completar los datos

Si la identificación es nueva, completa el resto de la ventana:

![Registro básico diligenciado](images/providers-basic/04-filled-form.png)

1. **Nombre o razón social** (obligatorio) y **Contacto**, la persona con quien se trata.
2. **Ubicación y contacto:** Departamento, Municipio, Dirección, Teléfono / celular y Correo electrónico. Todos son obligatorios. Elige primero el departamento para que se carguen sus municipios.
3. **Condiciones de pago:** Condición de pago y Plazo de pago. Son opcionales y se pueden definir después.
4. Haz clic en **Guardar**. Verás el mensaje "Proveedor registrado y activo" y el proveedor aparecerá en el listado con la vinculación **Activa** y el FO-300-01 en **Sin FO-300-01**.

> **Consejo:** las listas de condición y plazo de pago salen de **Características**. Si falta una opción, pídele a un administrador que la agregue allí.

## Editar un proveedor básico

Necesitas el permiso de editar en la línea de proveedores administrativos.

![Selección de un proveedor y botones Editar y Ver detalle](images/providers-basic/05-edit-selected.png)

1. Marca la casilla de **un solo** proveedor.
2. Haz clic en el ícono del lápiz (tooltip "Editar seleccionado"). Se abre la ventana **Editar proveedor** con sus datos.
3. El ícono del ojo (tooltip "Ver detalle") abre su ficha. También puedes hacer clic en su nombre.

En la edición, el tipo de persona, el tipo de documento y la identificación aparecen bloqueados: la identificación de un proveedor en uso no se modifica. Corrige los demás datos y haz clic en **Guardar**.

> **Regla:** un proveedor básico no se elimina, porque su vinculación ya está activa. Si dejas de comprarle, **inactívalo** (ver [Actualización anual, vinculación y estado](guide:providers-maintenance)).

## La ficha del proveedor básico

Al abrir el detalle de un proveedor básico verás sus datos, su vinculación con tu proceso y la situación del formato.

![Ficha de un proveedor básico](images/providers-basic/06-basic-detail.png)

1. **Condiciones:** cambia la condición y el plazo de pago de tu proceso.
2. **Editar datos básicos:** abre la misma ventana de edición del paso anterior.
3. **Diligenciar FO-300-01:** inicia el formato completo cuando el proveedor supera el tope.
4. El aviso azul recuerda que es un proveedor básico, sin FO-300-01, con compras hasta 2 SMMLV por orden y por mes.

Si en el detalle aparece el aviso amarillo **FO-300-01 requerido**, el proveedor ya superó el tope y debes diligenciar el formato.

### Proveedor básico con formatos de años anteriores

Algunos proveedores tienen el FO-300-01 de 2024 o 2025 cargado desde el sistema anterior. Esos formatos son de consulta: el proveedor sigue siendo básico (**Sin FO-300-01**) hasta que apruebe un formato en Cocoo Nova.

![Ficha de un proveedor básico con formatos de años anteriores](images/providers-basic/08-historical-years.png)

La ficha abre directamente el formato del año más reciente, en solo lectura:

1. **Editar datos básicos** sigue disponible.
2. **Diligenciar FO-300-01** abre el Borrador prellenado con el formato del año más reciente, no solo con los datos básicos.
3. **Años del FO-300-01** muestra una caja por año, con el más reciente elegido. Haz clic en otra para consultar ese año (ver [Verificar, aprobar o rechazar](guide:providers-review)).
4. El aviso azul indica que estás consultando un formato de un año anterior, que no está vigente, y que el proveedor sigue trabajando como básico.

## Pasar de básico a FO-300-01

Cuando un proveedor básico supera el tope:

1. En su ficha, haz clic en **Diligenciar FO-300-01**.
2. Confirma con **Sí, continuar**.

![Confirmación para diligenciar el FO-300-01](images/providers-basic/07-start-form-confirm.png)

El sistema crea el formato en **Borrador**, prellenado con los datos básicos; el contacto queda como "Representante de ventas". Si el proveedor tiene formatos de años anteriores, el Borrador se prellena con el del año más reciente. La vinculación sigue **Activa** mientras el formato se diligencia y se aprueba.

Desde ese momento:

- El botón **Editar datos básicos** desaparece mientras el formato esté en trámite, vigente o rechazado.
- Los datos del proveedor se corrigen en el formato. Si el formato es rechazado, se corrige con **Corregir FO-300-01** (ver [Verificar, aprobar o rechazar](guide:providers-review)).
- Al aprobarse el formato, los datos básicos se actualizan con los del FO-300-01.

Continúa en [Diligenciar el FO-300-01](guide:providers-form).
