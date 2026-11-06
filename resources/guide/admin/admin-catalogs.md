Este tema cubre dos catálogos que alimentan el resto del sistema: las **sedes** (centros de costos) y las **características**, que son las listas de opciones de los formularios.

## Sedes y centros de costos

Las sedes son la Oficina Principal, las farmacias y los demás servicios de la organización. Se usan en los usuarios, en los pendientes y productos nuevos de farmacia, y en los consolidados. Entra por **Utilidades → Centro de Costos** en el menú lateral.

![Listado de sedes y servicios](images/admin-catalogs/01-service-points.png)

1. **Agregar:** crea una sede.
2. **Buscar:** busca por código, nombre o abreviatura.
3. **Excel**, **CSV** y **Limpiar filtros:** exportan el listado con la búsqueda actual, o quitan la búsqueda.
4. **Acciones de cada fila:**
   - **Editar** abre la sede.
   - El ícono de dos hojas la duplica.
   - **Eliminar** la elimina, después de pedir confirmación.
   - El reloj muestra su auditoría.

Esta pantalla todavía usa botones en cada fila en lugar de la barra estándar de [Cómo usar los listados](guide:standard-lists). Ordenar por columna y elegir cuántos registros mostrar funcionan igual.

### Crear o editar una sede

1. Pulsa **Agregar**, o **Editar** en la fila de la sede.
2. Diligencia la sección **Identificación**:
   1. **Código** (1): código del centro de costos. No se puede repetir.
   2. **Nombre:** nombre completo de la sede.
   3. **Abreviatura** (2): nombre corto, de máximo 20 caracteres. Es obligatoria y no se puede repetir, porque encabeza las columnas del consolidado de pendientes.
   4. **Tipo** (3): **Oficina Principal**, **Farmacia** u **Otro**.
   5. **Estado:** **Activo** o **Inactivo**.
3. En **Contacto y capacidad**, registra si aplica la **Dirección**, el **Teléfono** y los valores **Mínimo** y **Máximo**. El máximo no puede ser menor que el mínimo.
4. Pulsa **Guardar**.

![Formulario de la sede](images/admin-catalogs/02-service-point-form.png)

> **Regla:** la sede con **código 1** es la **Oficina Principal**. Sus usuarios ven y gestionan los pendientes y productos nuevos de **todas** las sedes, y solo ellos pueden elegir un destino distinto a su propia sede. No le cambies ese código.

> **Importante:** una sede con usuarios asignados no se puede eliminar; por eso la Oficina Principal del ejemplo no muestra el botón **Eliminar**. Primero reasigna a esas personas. Las sedes eliminadas no se muestran en este listado.

## Características y sus opciones

Las características son listas de opciones que aparecen en los formularios, por ejemplo:

- **Condición de pago** y **Plazo de pago** de los proveedores.
- **Categoría de pendientes**, **EPS**, **Modalidad de contratación** y **Forma de facturación** de los pendientes.
- **Clasificación de agotados**.
- **Régimen tributario**, **Ocupación**, **Nivel de educación** y otras listas del FO-300-01.

Entra por **Utilidades → Características** en el menú lateral. La pantalla tiene dos paneles: a la izquierda las características y a la derecha las opciones de la característica que elijas.

![Característica seleccionada y sus opciones](images/admin-catalogs/03-characteristics.png)

1. Pulsa el nombre de una característica para seleccionarla. Su fila queda resaltada.
2. El panel **Opciones** muestra sus valores. Ahí puedes:
   - **Agregar** una opción.
   - Buscar una opción.
   - Marcar opciones para **Editar**, duplicar, **Eliminar**, restaurar, ver su auditoría o exportarlas.

Los botones de cada panel actúan sobre las filas marcadas con la casilla de ese mismo panel. Marcar la casilla no cambia la característica seleccionada.

### Agregar o cambiar una opción

1. Selecciona la característica a la izquierda.
2. En **Opciones**, pulsa **Agregar**, o marca una opción y pulsa **Editar**.
3. Escribe el **Nombre** del valor tal como debe verse en los formularios y elige el **Estado**.
4. Pulsa **Guardar**.

Una opción **Inactiva** deja de ofrecerse en los formularios, pero los registros que ya la tienen la conservan. Prefiere inactivar antes que eliminar.

> **Importante:** si la característica está eliminada, sus opciones quedan bloqueadas y el panel muestra «La característica está eliminada: restáurala para gestionar sus opciones.».

### Crear o editar una característica

![Formulario de la característica](images/admin-catalogs/04-characteristic-form.png)

1. **Nombre:** cómo se ve la lista en esta pantalla.
2. **Código interno:** identificador técnico, en minúsculas y sin espacios (por ejemplo, `payment_condition`). No se puede repetir. Si la característica ya tiene opciones, el campo aparece gris, no se puede modificar y muestra la nota "No editable: la característica ya tiene opciones y la aplicación la busca por este código."
3. **Estado:** **Activo** o **Inactivo**.

> **Regla:** los formularios buscan cada lista por su **código interno**. Por eso el código solo se puede cambiar mientras la característica no tenga opciones; después queda fijo. Crea características nuevas solo cuando un desarrollo nuevo las necesite.

Una característica que tiene opciones no se puede eliminar. Primero elimina sus opciones.
