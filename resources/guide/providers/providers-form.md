El FO-300-01 se diligencia dentro de la aplicación. Un funcionario lo transcribe a partir del formato que entrega el proveedor y de sus anexos. Mientras la versión está en **Borrador** se puede corregir cuantas veces sea necesario. Al terminar, se envía a verificación.

Necesitas el permiso de editar en la línea de proveedores de tu proceso. Para crear un proveedor nuevo necesitas además el permiso de crear.

## Cómo se abre un Borrador

Un Borrador del FO-300-01 nace de una de estas formas:

| Situación | Qué haces |
|---|---|
| Proveedor nuevo en **Compras** | En **Compras → Proveedores**, botón **+** (Agregar). Se abre directamente el formato. |
| Proveedor nuevo en **Administrativo** que supera el tope | Botón **+** (Agregar) y luego **Registrar con FO-300-01**. Ver [Registrar un proveedor básico](guide:providers-basic). |
| Proveedor básico que superó el tope | En su ficha, **Diligenciar FO-300-01**. |
| Actualización anual de un proveedor aprobado | En su ficha, **Actualizar FO-300-01**. Ver [Actualización anual, vinculación y estado](guide:providers-maintenance). |
| Formato rechazado que se vuelve a presentar | En su ficha, **Corregir FO-300-01** (quien tiene el permiso de reversar). No se abre una versión nueva: la misma vuelve a Borrador. Ver [Verificar, aprobar o rechazar](guide:providers-review). |

Al guardar un proveedor nuevo verás "Proveedor registrado en Borrador". Se abre su ficha para cargar accionistas, familiares PEP y anexos, y su vinculación queda **Pendiente** hasta la aprobación.

> **Regla:** un proveedor solo puede tener una versión del formato en trámite a la vez, y **una sola solicitud por año** (por el año en que se abrió). Una versión aprobada nunca se modifica: los cambios se hacen con la actualización del año siguiente, que crea una versión nueva.

## Editar el Borrador

Abre la ficha del proveedor desde el listado (clic en su nombre).

![Ficha de un proveedor con su FO-300-01 en Borrador](images/providers-form/01-draft-detail.png)

1. Haz clic en **Editar borrador**. También puedes seleccionar la fila en el listado y usar el ícono del lápiz (tooltip "Editar seleccionado").
2. La etiqueta gris **Borrador** junto a "FO-300-01 · Versión 1" confirma que la versión todavía se puede editar.

### Datos del proveedor

Se abre la ventana **Editar proveedor (FO-300-01)**. Sus secciones cambian según el tipo de persona.

![Primeras secciones del formato](images/providers-form/02-form-top.png)

1. **Tipo de persona:** Persona natural o Persona jurídica. Al cambiarlo, el formulario muestra los campos de ese tipo.
2. **Número de identificación:** sin puntos ni dígito de verificación. Queda bloqueado cuando el proveedor ya fue aprobado o está en uso.
3. **Ubicación y contacto:** departamento, municipio, dirección, teléfono y correo. Elige primero el departamento para que se carguen sus municipios.

Los campos de cada tipo de persona son:

- **Información del proveedor.**
  - Persona jurídica: País de constitución, Razón social, Régimen tributario y Tipo de empresa.
  - Persona natural: Fecha de expedición, Nombres y apellidos y País de residencia. Con cédula de extranjería o pasaporte se pide también la Nacionalidad.
- **Representante legal** (persona jurídica): nombre completo, tipo y número de documento.
- **Perfil de la persona natural:**
  - Fecha y lugar de nacimiento.
  - Nivel de educación.
  - Ocupación. Si eliges "Otro", se pide "¿Cuál ocupación?".
  - Grupo de protección especial.
  - Empresa donde labora, su actividad y su CIIU.
  - Si el documento es de un menor de edad (registro civil o tarjeta de identidad), se piden el representante legal o apoderado del menor y su identificación.

### Actividad, información financiera y PEP

Baja dentro de la ventana para ver las siguientes secciones.

![Actividad económica, información financiera y preguntas PEP](images/providers-form/03-form-financial-pep.png)

1. **CIIU:** escribe el código o parte de la actividad (por ejemplo, 4761) y elige una opción de la lista. Es obligatorio.
2. **Información financiera ($):**
   - Ingresos mensuales (obligatorio), otros ingresos y su actividad.
   - Persona jurídica: total de activos, pasivos y patrimonio, y si está en un proceso de ley de insolvencia.
   - Si realiza operaciones en moneda extranjera, se pide "¿Cuáles?".
3. **Personas expuestas políticamente (PEP):** tres preguntas. Cualquier respuesta afirmativa califica al proveedor como PEP, con riesgo alto.
   - Si marcas "Es una persona expuesta políticamente", se piden el cargo público, su inicio y su fin. Deja el fin vacío si el cargo sigue vigente.
   - Si marcas "Tiene relación con un PEP", registra a esa persona en la ficha (ver más abajo).

> **Importante:** la información financiera, las respuestas PEP y el grupo de protección son datos sensibles (Ley 1581 de 2012). Después de guardar, solo los ven quienes verifican y aprueban.

### Contactos y declaraciones

![Contactos, declaraciones obligatorias y botón Guardar](images/providers-form/04-form-contacts-declarations.png)

1. **Contactos:** nombre, teléfono y correo de cinco cargos fijos: Jefe comercial o jefe de ventas, Representante de ventas, Facturación, Cartera y Dirección técnica. Deja vacío el cargo que no aplica. Debajo está **Despacho de pedidos**, para los días y horarios de despacho.
2. **Declaraciones y autorizaciones:** las cuatro primeras son **obligatorias para enviar** a verificación:
   - Declaración de veracidad.
   - Consentimiento para el tratamiento de datos personales.
   - Declaración de información recibida por COODESCOR.
   - Declaración de origen de fondos.

   Las otras dos son opcionales: la autorización de consulta en centrales de riesgo y, para persona natural, los mecanismos electrónicos de verificación (Ley 527 de 1999).
3. Haz clic en **Guardar**. Verás "Borrador actualizado". Puedes guardar sin terminar y volver después.

El nivel de riesgo y la condición de PEP se recalculan cada vez que guardas.

## Accionistas

Solo para **persona jurídica**. Se registran los accionistas, propietarios o consorciados con una participación de **5 % o más**. Están en la ficha del proveedor, debajo del formato.

![Captura de accionistas en la ficha del proveedor](images/providers-form/05-shareholders.png)

1. Completa Tipo, Identificación, Nombre o razón social, Representante legal (opcional) y el **%** de participación, entre 5 y 100.
2. Haz clic en **Agregar**. El accionista aparece en la tabla.

La suma de las participaciones no puede superar el 100 %. Para quitar un accionista, usa el ícono rojo de la papelera (tooltip "Quitar accionista") y confirma.

> **Regla:** si cambias el proveedor a persona natural y guardas, sus accionistas se eliminan, porque solo aplican a persona jurídica.

## Grupo familiar o relación con PEP

Si el proveedor tiene relación con un PEP, registra aquí a esa persona.

![Captura de una persona relacionada con un PEP](images/providers-form/06-pep-relatives.png)

1. Completa Tipo y Número de documento (opcionales), **Nombres y apellidos** y **Parentesco**.
2. Si esa persona es PEP, marca **Es PEP**.
3. Con **Es PEP** marcado se pide el **Cargo PEP**, con su inicio y su fin. Deja el fin vacío si sigue vigente. Luego haz clic en **Agregar**.

Esta sección también es un dato sensible: fuera del Borrador solo la ven quienes verifican y aprueban.

## Documentos anexos

Carga los soportes del formato en archivos PDF, JPG o PNG de máximo 5 MB.

![Carga de documentos anexos](images/providers-form/07-documents.png)

1. **Tipo de anexo:**
   - Documento de identificación.
   - Certificado de existencia y representación legal.
   - Registro único tributario (RUT).
   - Políticas de devolución.
   - Formulario FO-300-01 firmado.
   - Otro.
2. **Expedición:** solo se pide para el certificado de existencia y representación legal (cámara de comercio). Si tiene más de 3 meses, la tabla lo marca como **Vencido**.
3. **Archivo:** selecciona el archivo en tu equipo.
4. Haz clic en **Cargar**.

En la tabla, el ícono de descarga (tooltip "Descargar") baja el archivo y el de la papelera (tooltip "Quitar anexo") lo retira de la versión.

> **Consejo:** al actualizar un proveedor, los anexos de la versión anterior se copian al nuevo Borrador. Quita los que ya no estén vigentes y carga los nuevos.

## Enviar a verificación

Cuando el formato esté completo, baja hasta la tarjeta **Verificación y aprobación**.

![Tarjeta Verificación y aprobación con el botón Enviar a verificación](images/providers-form/08-submit.png)

1. La lista muestra las firmas de la versión: envío, verificación y la aprobación pendiente de cada proceso, con el director que debe aprobar.
2. Haz clic en **Enviar a verificación** y confirma. Mientras esté en revisión, el Borrador ya no se puede editar.

Si falta alguna de las cuatro declaraciones obligatorias, el sistema no envía la versión y muestra un aviso. Edita el Borrador, márcalas y vuelve a enviar.

Lo que sigue lo hacen otras personas: ver [Verificar, aprobar o rechazar](guide:providers-review).
