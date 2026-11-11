Después de **Enviar a verificación**, el FO-300-01 pasa por varias firmas antes de quedar aprobado. Cada persona ve en la ficha del proveedor solo los botones de la etapa que le corresponde. Si no le corresponde nada, ve el mensaje "No tienes acciones pendientes en esta etapa".

## Estados de una versión

```
Borrador ──Enviar──▶ En verificación ──Verificar──▶ ¿Es PEP?
                                                      │ sí ──▶ Cumplimiento ──Visto bueno──▶ En aprobación
                                                      │ no ─────────────────────────────────▶ En aprobación
En aprobación ──Aprobar (cada proceso)──▶ Aprobada (versión vigente)

Desde En verificación, Cumplimiento o En aprobación:
   Rechazar ──▶ Rechazada ──Corregir FO-300-01──▶ Borrador (la misma versión)
   Devolver a Borrador ──▶ Borrador
```

| Estado | Qué está pasando | Quién actúa |
|---|---|---|
| **Borrador** | Se diligencia el formato. | Quien tiene permiso de editar. |
| **En verificación** | Se revisa que los datos coincidan con los soportes. | Quien tiene permiso de procesar y uno de los cargos verificadores. |
| **Cumplimiento** | Solo proveedores PEP: revisión del oficial de cumplimiento. | El oficial de cumplimiento. |
| **En aprobación** | Firma de cada proceso vinculado. | El director del proceso o su reemplazo, también si es PEP. |
| **Aprobada** | Es la versión vigente del proveedor. | Nadie: ya no se modifica. |
| **Rechazada** | El formato no se aceptó. | Quien tiene permiso de reversar la corrige con **Corregir FO-300-01**. |

## Quién puede hacer cada acción

| Acción | Botón | Quién |
|---|---|---|
| Enviar | **Enviar a verificación** | Quien tiene el permiso de **editar** proveedores del proceso. |
| Verificar | **Verificar** | Quien tiene el permiso de **procesar** proveedores del proceso y, si están configurados, uno de los **cargos verificadores** de ese tipo de proveedor. |
| Visto bueno de cumplimiento | **Visto bueno de cumplimiento** | El usuario configurado como oficial de cumplimiento en Datos de la empresa (proveedores PEP). |
| Observación de cumplimiento | **Observación de cumplimiento** | El oficial de cumplimiento, en un proveedor que no es PEP, cuando lo considere necesario. |
| Aprobar | **Aprobar** | El director del proceso Administrativo o Comercial, según la vinculación, o su reemplazo vigente. Aplica también a los proveedores PEP. |
| Registrar firma de Gerencia | **Registrar firma de Gerencia** | Proveedor PEP con la Gerencia aprobando en físico: quien tiene el permiso de **procesar** proveedores del proceso. |
| Aprobar como Gerencia | **Aprobar como Gerencia** | Proveedor PEP con la Gerencia aprobando en el sistema: el director de Gerencia o su reemplazo. |
| Rechazar | **Rechazar** | En verificación, quien puede verificar. En cumplimiento, el oficial. En aprobación, un aprobador. |
| Devolver a Borrador | **Devolver a Borrador** | Quien tiene el permiso de **reversar** proveedores del proceso. |
| Corregir un rechazo | **Corregir FO-300-01** | Quien tiene el permiso de **reversar** proveedores del proceso. |

Las firmas de aprobación y de cumplimiento no dependen de un permiso sino de quién es la persona. Esas personas solo necesitan el permiso de consultar proveedores para entrar a la ficha. Los directores y sus reemplazos se configuran en [Procesos y reemplazos de director](guide:admin-processes). El oficial de cumplimiento, los cargos verificadores y la forma en que aprueba la Gerencia, en [Datos de la empresa](guide:admin-company-settings). El cargo de cada usuario se asigna en [Usuarios](guide:admin-users).

> **Importante:** el botón **Aprobar** aparece por ser el director del proceso en Procesos, no por el cargo. Un usuario con el cargo "Director administrativo" no aprueba si no está asignado como director del proceso Administrativo o como su reemplazo vigente. Si el proceso no tiene a nadie asignado, la lista de firmas lo muestra en amarillo: **no tiene director ni reemplazo vigente**.

Cada firma muestra la fecha, el nombre y el **cargo** de quien firmó. El cargo se guarda al firmar: si la persona cambia de cargo después, el formato conserva el que tenía.

## Verificar la información

Abre la ficha del proveedor y baja hasta la tarjeta **Verificación y aprobación**. Revisa el formato y los anexos contra los documentos del proveedor.

![Tarjeta de verificación con sus acciones](images/providers-review/01-verify.png)

1. **Observaciones:** opcionales al firmar y **obligatorias al rechazar**.
2. **Verificar:** firma la verificación. Si el proveedor es PEP, la versión pasa a **Cumplimiento**; si no, pasa a **En aprobación**. Si no ves el botón aunque tengas el permiso de procesar, tu cargo no está entre los verificadores de ese tipo de proveedor.
3. **Rechazar:** rechaza la versión (ver "Rechazar" más abajo).
4. **Devolver a Borrador:** regresa la versión para corregirla.

> **Consejo:** si el error es corregible (un dato mal transcrito, un anexo faltante), usa **Devolver a Borrador** en vez de **Rechazar**. Devolver descarta las firmas de esa versión y permite editarla de nuevo; rechazar la cierra y rechaza las vinculaciones pendientes, hasta que alguien con permiso de reversar use **Corregir FO-300-01**.

## Revisión del oficial de cumplimiento

Solo para proveedores **PEP**. Después de la verificación, la lista muestra **Oficial de cumplimiento: Pendiente** con el nombre del oficial configurado.

![Etapa de cumplimiento de un proveedor PEP](images/providers-review/02-compliance.png)

1. La línea **Oficial de cumplimiento** indica a quién le corresponde la revisión. Si dice "sin configurar en Datos de la empresa", pídele a un administrador que lo configure.
2. El oficial revisa y hace clic en **Visto bueno de cumplimiento**. La versión pasa a **En aprobación**, donde firma el director del proceso.

En un proveedor que **no es PEP**, el oficial puede dejar una **Observación de cumplimiento** mientras la versión está en verificación o en aprobación. Escribe la observación y pulsa el botón: la observación queda en la línea **Oficial de cumplimiento (observación)** y en el PDF, y la versión sigue en la misma etapa.

## Aprobar

En la etapa **En aprobación**, la lista muestra una línea por cada vinculación del proveedor que espera firma, con el proceso que debe aprobarla y el nombre de su director y de sus reemplazos vigentes.

![Aprobación pendiente por cada proceso](images/providers-review/03-approve.png)

1. **Aprobación administrativo:** pendiente de la firma del director de Administrativo.
2. **Aprobación comercial:** si el proveedor también está vinculado a Compras, espera además la firma del director de Comercial.
3. **Aprobar:** cada director firma la vinculación de su proceso. Las observaciones son opcionales.

Cuando están todas las firmas:

- La versión queda **Aprobada** y pasa a ser la **vigente**.
- Las vinculaciones **Pendientes** quedan **Activas**.
- La próxima actualización queda para un año después.
- Los datos básicos del proveedor (correo, teléfono, dirección, municipio) se actualizan con los del formato.

> **Regla:** el director del proceso aprueba siempre, también si el proveedor es PEP. La Gerencia aprueba a los PEP según lo configurado en Datos de la empresa (ver "Aprobación de Gerencia de un proveedor PEP").

## Aprobación de Gerencia de un proveedor PEP

El formato indica que la Gerencia aprueba a los proveedores PEP, previa verificación del oficial de cumplimiento. Datos de la empresa define cómo se hace:

- **En físico** (por defecto): la Gerencia firma el formato impreso y alguien con el permiso de procesar proveedores lo registra.

![Proveedor PEP aprobado por el director, con la firma física de Gerencia pendiente de registrar](images/providers-review/11-management.png)

1. Cada firma muestra la fecha, el nombre y el **cargo** de quien firmó.
2. **Aprobación del director del proceso:** con ella el proveedor ya queda aprobado.
3. **Gerencia (firma física): sin registrar** recuerda que falta registrar la firma; no impide la aprobación.
4. **Registrar firma de Gerencia** abre el registro:

![Registro de la firma física de Gerencia](images/providers-review/12-management-form.png)

1. **Fecha de la firma:** el día en que la Gerencia firmó el formato impreso.
2. **Observaciones:** opcionales. Adjunta el formato firmado en **Anexos**.
- **En el sistema:** el director de Gerencia (o su reemplazo) pulsa **Aprobar como Gerencia**. La versión no queda **Aprobada** hasta que firmen el director del proceso y la Gerencia; la lista muestra **Aprobación de gerencia (pep): pendiente** mientras tanto.

## Rechazar

1. Escribe el motivo en **Observaciones**: es obligatorio y debe tener al menos 5 caracteres.
2. Haz clic en **Rechazar** y confirma con **Sí, continuar**.

![Confirmación del rechazo](images/providers-review/04-reject-confirm.png)

La versión queda **Rechazada** con su motivo. Las vinculaciones que nunca se aprobaron (**Pendientes**) quedan **Rechazadas**. Las que ya estaban activas no cambian y conservan la versión vigente anterior.

En la ficha de un proveedor sin ningún formato aprobado se ve así:

![Ficha de un proveedor con su FO-300-01 rechazado](images/providers-review/05-rejected.png)

1. La vinculación aparece como **Vinculación Rechazada**. En el listado la fila se ve amarilla.
2. El aviso rojo **Rechazado** muestra la fecha, quién rechazó y el motivo.
3. **Corregir FO-300-01:** solo lo ve quien tiene el permiso de **reversar** proveedores del proceso.

> **Regla:** cada proveedor tiene como máximo **una solicitud FO-300-01 por año calendario**. Un formato rechazado no se reemplaza por una solicitud nueva: se corrige con **Corregir FO-300-01**.

## Corregir un formato rechazado

1. En la ficha del proveedor, haz clic en **Corregir FO-300-01**.
2. Confirma con **Sí, continuar**. Verás el mensaje "FO-300-01 reabierto en Borrador para corregirlo".

![Formato rechazado reabierto en Borrador para corregirlo](images/providers-review/06-correction.png)

1. La **misma versión** vuelve a **Borrador**, con el mismo número. Las firmas que tenía se descartan.
2. El aviso amarillo **Corrección de un rechazo** conserva el motivo del rechazo mientras corriges el borrador.
3. Las vinculaciones **Rechazadas** quedan **Pendientes**.

Después, haz clic en **Editar borrador**, corrige lo que indicó el motivo y envía de nuevo con **Enviar a verificación** (ver [Diligenciar el FO-300-01](guide:providers-form)). El formato vuelve a pasar por todas las firmas.

## Aprobar la vinculación de otro proceso en el mismo año

Si un proveedor ya tiene aprobado el FO-300-01 de este año y otro proceso lo vincula (por ejemplo, Compras vincula un proveedor administrativo), **no se abre una versión nueva**. La vinculación del nuevo proceso queda **Pendiente** y su director la aprueba o la rechaza sobre la versión vigente.

![Aprobación de la vinculación comercial sobre la versión vigente](images/providers-review/07-same-year-linkage.png)

1. En **Verificación y aprobación** aparece la línea **Aprobación comercial: pendiente (director de Comercial)**.
2. El director del proceso (o su reemplazo) hace clic en **Aprobar** o en **Rechazar**.

Al aprobar, la vinculación queda **Activa**. La fecha de la próxima actualización no cambia: sigue siendo la del formato vigente.

## PDF del formato

En la ficha, el botón **PDF** (tooltip "Descargar PDF del formato") descarga el FO-300-01 de la versión que se está mostrando: la que está en trámite o, si no hay, la vigente. Si estás consultando un año anterior (ver más abajo), descarga el de ese año.

![Botón PDF y botón Ver años en la ficha del proveedor](images/providers-review/08-pdf-button.png)

1. Haz clic en **PDF**. El archivo incluye las firmas registradas hasta el momento.
2. **Ver años** muestra los formatos de cada año (ver la sección siguiente).

> **Importante:** el PDF incluye la información financiera y PEP solo si tú puedes verla en pantalla (ver [Actualización anual, vinculación y estado](guide:providers-maintenance)).

## Consultar los años del FO-300-01

En la sección **FO-300-01** de la ficha, el campo **Años** tiene el botón **Ver años (N)**, donde N es la cantidad de años registrados. Al hacer clic se abre la tarjeta **Años del FO-300-01**, con una caja por año.

![Tarjeta Años del FO-300-01](images/providers-review/09-years.png)

1. La caja resaltada es el formato que se muestra en la ficha. **Vigente** marca la versión aprobada que está en uso.
2. Cada caja muestra el año, el estado (Aprobada, Rechazada, Borrador…), la versión con su tipo (por ejemplo, "V1 · Vinculación") y la fecha.
3. Las cajas **Sistema anterior** son actualizaciones registradas antes de Cocoo Nova: dicen "Sin formato digital" y solo informan la fecha.

Haz clic en la caja de un año para consultar ese formato:

![Consulta del FO-300-01 de un año anterior](images/providers-review/10-older-year.png)

1. La caja del año elegido queda resaltada.
2. El aviso "Estás consultando el FO-300-01 de AAAA (versión N), solo lectura" indica que ese formato no se puede editar ni firmar.
3. **Volver al formato actual** regresa a la versión de trabajo.
4. **PDF** descarga el formato del año que estás consultando.

> **Regla:** solo se edita y se firma el formato actual (el que está en trámite o el vigente). Los años anteriores son de consulta.
