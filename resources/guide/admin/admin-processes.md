Los **procesos** son las áreas de la organización: Gerencia, Administrativo, Financiero, Comercial y Logística y Operaciones. Cada proceso tiene un **director**. Cuando el director no está, uno o varios **reemplazos** aprueban en su nombre durante un rango de fechas.

## Por qué importa

El sistema usa el director del proceso (o su reemplazo vigente) para decidir **quién aprueba**:

- **Requisiciones de suministros** (aseo, papelería y cafetería): las aprueba el director del proceso de la persona que solicita.
- **Proveedores (FO-300-01):**
  - La vinculación administrativa la aprueba el director de **Administrativo**.
  - La vinculación comercial la aprueba el director de **Comercial**.
  - Si el proveedor es **PEP** (persona expuesta políticamente), aprueba el director de **Gerencia**, después de la revisión del oficial de cumplimiento.

  Ver [Verificar, aprobar o rechazar](guide:providers-review).

> **Importante:** el sistema busca los procesos por su nombre exacto: **Gerencia**, **Administrativo** y **Comercial**. No les cambies el nombre ni los inactives, o las aprobaciones de proveedores quedarán sin responsable.

## Asignar el director de un proceso

Entra por **Utilidades → Procesos** en el menú lateral.

![Listado de procesos](images/admin-processes/01-list.png)

1. **Agregar:** crea un proceso nuevo.
2. **Editar seleccionado:** abre el proceso marcado.
3. La columna **Director** muestra el director actual. La columna **Usuarios** muestra cuántas personas pertenecen al proceso.

Para asignar o cambiar el director:

1. Marca el proceso y pulsa **Editar seleccionado**.
2. Revisa el **Nombre** (1). No se puede repetir.
3. En **Director** (2), escribe parte del nombre, la identificación o el correo y elige a la persona en la lista. Solo aparecen usuarios activos.
4. Si el proceso queda sin director, pulsa **Quitar director** (3).
5. Pulsa **Guardar**.

![Formulario del proceso con su director](images/admin-processes/02-process-form.png)

El estado del proceso se cambia desde el listado, con **Activar / inactivar seleccionados**. Un proceso con usuarios asignados no se puede eliminar: primero reasigna a esas personas (ver [Usuarios](guide:admin-users)).

## Registrar un reemplazo de director

Entra por **Configuración → Datos Generales → Reemplazo de director** en el menú lateral.

![Listado de reemplazos de directores](images/admin-processes/03-substitutions.png)

1. **Agregar:** registra un reemplazo.
2. **Desde:** fecha en que empieza el reemplazo. El listado se ordena por esta columna, del más reciente al más antiguo.
3. **Hasta:** fecha en que termina.
4. **Permanente:** el reemplazo no tiene fecha de fin. Por ejemplo, Financiero aprueba siempre las requisiciones de Gerencia.

Para registrar un reemplazo:

1. Pulsa **Agregar**.
2. Elige el **Proceso** (1). Solo aparecen procesos activos.
3. En **Reemplazo** (2), busca a la persona por nombre, identificación o correo. Debe ser un usuario activo y distinto del director del proceso.
4. Indica **Desde** (3), el primer día en que aprueba.
5. Indica **Hasta** (4), el último día. Déjalo vacío para una asignación permanente.
6. Escribe el motivo en **Observaciones**, por ejemplo vacaciones o licencia.
7. Pulsa **Guardar**.

![Formulario del reemplazo](images/admin-processes/04-substitution-form.png)

> **Regla:** durante las fechas del reemplazo, aprueban **tanto el director como el reemplazo**: el director no pierde su aprobación. Fuera de esas fechas, el reemplazo ya no aparece como aprobador.

> **Regla:** una misma persona no puede tener dos reemplazos del mismo proceso con fechas que se crucen. La fecha **Hasta** debe ser igual o posterior a **Desde**.

Para terminar un reemplazo antes de tiempo, edítalo y cambia la fecha **Hasta**, o elimínalo. Los reemplazos no tienen estado activo o inactivo: su vigencia depende solo de las fechas.
