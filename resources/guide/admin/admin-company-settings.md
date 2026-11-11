**Datos de la empresa** guarda la información general que el sistema usa en formatos, PDF y correos, por ejemplo el FO-300-01 o la orden de compra. También guarda los valores que controlan cuándo se exige el FO-300-01, quién es el oficial de cumplimiento, quién verifica a los proveedores y cómo aprueba la Gerencia a los proveedores PEP. Entra por **Configuración → Datos Generales → Datos de la empresa** en el menú lateral.

## Consultar los datos

![Ficha de Datos de la empresa](images/admin-company-settings/01-view.png)

La ficha muestra estas secciones:

- **Identificación:** razón social, NIT y logo.
- **Contacto:** dirección, ciudad, teléfono, correo y sitio web.
- **Cumplimiento** (2): el oficial de cumplimiento.
- **Proveedores (FO-300-01)** (3): el SMMLV del año en curso y el tope calculado.
- **Revisión del FO-300-01:** cómo aprueba la Gerencia a los PEP y qué cargos verifican cada tipo de proveedor.

El botón **Editar** (1) solo aparece si tu rol tiene el permiso **Editar** en Datos de la empresa.

## Editar los datos

1. Pulsa **Editar**.
2. Ajusta los campos y pulsa **Guardar**.

![Formulario Editar datos de la empresa](images/admin-company-settings/02-edit.png)

1. **Razón social** y **NIT** son obligatorios. Escribe el NIT tal como debe salir en los documentos, con el dígito de verificación si aplica (ej. 812001561-1).
2. **Logo:** imagen PNG, JPG o WEBP de máximo 1 MB. Aparece en los PDF. Si ya hay un logo, puedes marcar **Quitar el logo actual**.
3. **Tope (múltiplo del SMMLV):** cuántos salarios mínimos puede comprarse a un proveedor sin exigir el FO-300-01.
4. **SMMLV por año:** el salario mínimo mensual legal vigente de cada año, en pesos y sin puntos (ej. 1750905).
5. **Agregar año:** agrega una fila para el SMMLV de un año nuevo. El ícono de papelera de cada fila la quita.
6. **Oficial de cumplimiento:** busca al usuario por nombre o identificación y elígelo de la lista. **Quitar** deja el cargo vacío.

La sección **Contacto** (dirección, ciudad, teléfono, correo y sitio web) es opcional.

Al final del formulario está la sección **Revisión del FO-300-01**:

![Sección Revisión del FO-300-01](images/admin-company-settings/03-review.png)

1. **Aprobación de Gerencia (PEP):** **En físico** (por defecto) o **En el sistema**. En físico, quien procesa proveedores registra la fecha en que la Gerencia firmó el formato impreso, y ese registro no bloquea la aprobación del director. En el sistema, el director de Gerencia debe pulsar **Aprobar como Gerencia** para que el PEP quede aprobado.
2. **Cargos que verifican proveedores administrativos:** marca los cargos que pueden pulsar **Verificar**. Además del cargo, la persona necesita el permiso de procesar proveedores administrativos.
3. **Cargos que verifican proveedores comerciales:** lo mismo para los proveedores comerciales.

> **Importante:** si no marcas ningún cargo para un tipo de proveedor, verifica cualquiera con el permiso de procesar. El cargo de cada persona se asigna en [Usuarios](guide:admin-users) y los cargos se mantienen en [Características y cargos](guide:hr-characteristics).

## El tope del FO-300-01

El FO-300-01 se exige cuando una orden, o lo comprado al proveedor en el mes, supera el tope:

**Tope = SMMLV del año × múltiplo**

Con el SMMLV de 2026 ($1.750.905) y el múltiplo 2, el tope es **$3.501.810**. Por debajo del tope, un proveedor administrativo puede trabajar como proveedor básico, sin el formato. Ver [Conceptos: proveedor básico y FO-300-01](guide:providers-overview).

> **Importante:** registra el SMMLV de cada año nuevo apenas el Gobierno lo publique. Si el año en curso no tiene SMMLV, la ficha muestra «Sin configurar» y el sistema no puede calcular el tope.

## El oficial de cumplimiento

El oficial de cumplimiento revisa a los proveedores **PEP** (personas expuestas políticamente) antes de que el director del proceso los apruebe, y puede dejar observaciones a los proveedores que no son PEP. Si el cargo queda vacío, esa revisión no tiene responsable y los proveedores PEP pueden quedar detenidos. Ver [Verificar, aprobar o rechazar](guide:providers-review).

> **Consejo:** cuando cambie la persona que ocupa el cargo, actualiza este campo el mismo día.
