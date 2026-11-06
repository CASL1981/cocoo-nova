Un **rol** agrupa los permisos de un cargo, por ejemplo «Regente de farmacia» o «Auxiliar de compras». Cada usuario tiene un rol, y el rol decide qué pantallas, botones y temas de la guía ve. Entra por **Usuarios → Roles y Permisos** en el menú lateral.

## Las tarjetas de roles

En la parte superior aparecen los roles con más usuarios. Cada tarjeta muestra cuántos usuarios tiene el rol y las iniciales de algunos de ellos (o su foto, si la tienen). Si hay más, un círculo gris indica cuántos faltan, por ejemplo **+1**.

![Tarjetas de roles](images/admin-roles/01-cards.png)

1. **Tarjeta del rol**, con tres enlaces:
   - **Editar rol:** cambia el nombre.
   - **Permisos:** abre la matriz de permisos para modificarla.
   - **Ver permisos:** abre la misma matriz en modo de solo lectura.
2. **Agregar rol:** crea un rol nuevo.

Debajo de las tarjetas está la tabla **Roles** con todos los roles. Tiene búsqueda por nombre, selector de registros por página, **Limpiar filtros** y, en cada fila, los botones **Editar**, **Ver permisos**, **Permisos** y **Eliminar**.

> **Regla:** un rol con usuarios asignados no se puede eliminar. Primero cambia el rol de esas personas (ver [Usuarios](guide:admin-users)).

## Crear un rol

1. Pulsa **Agregar rol**.
2. Escribe el **Nombre** del cargo.
3. Pulsa **Guardar**.

![Formulario Agregar rol](images/admin-roles/02-role-form.png)

El rol nace sin permisos. Asígnaselos con el enlace **Permisos** de su tarjeta o con el botón **Permisos** de su fila en la tabla.

## Asignar permisos: la matriz

1. Pulsa **Permisos** en la tarjeta o en la fila del rol.
2. Marca las casillas que necesita el cargo. Cada fila es un módulo (2) y cada columna es una acción (1). Las ocho acciones se ven a la vez, cada una con su ícono encima del nombre.
3. Pulsa **Guardar**. El cambio aplica a todos los usuarios con ese rol desde su siguiente pantalla.

![Matriz de permisos del rol](images/admin-roles/03-permissions.png)

Los encabezados de la matriz están abreviados: **Elim.** es Eliminar, **Act./Ina.** es Activar/Inactivar, **Proces.** es Procesar, **Rever.** es Reversar e **Impor.** es Importar.

### Qué permite cada acción

| Acción | Qué permite en general |
|---|---|
| **Leer (Ver)** | Ver la pantalla del módulo en los menús, consultar sus registros, exportar a Excel/CSV y ver la auditoría. Sin este permiso el módulo no aparece. |
| **Crear** | Botón **Agregar** y **Duplicar**. |
| **Editar** | Botón **Editar** y los cambios de datos del registro. |
| **Eliminar** | Botón **Eliminar seleccionados**. |
| **Activar/Inactivar** | Botón **Activar / inactivar seleccionados**. |
| **Procesar** | Ejecutar el paso de trabajo propio del módulo (ver la tabla siguiente). |
| **Reversar** | Deshacer o corregir ese paso, y **restaurar** registros eliminados. |
| **Importar** | Cargar registros desde un archivo Excel o CSV, en los módulos que lo tienen. |

### Procesar y Reversar en cada módulo

| Módulo | Procesar | Reversar |
|---|---|---|
| Pendientes | Terminar pendientes de otras personas (quien creó el pendiente siempre puede terminar el suyo). | Reabrir pendientes terminados. |
| Inventario disponible | Asignar stock a los ítems de los pendientes y asignar el número de TB. | Corregir la cantidad enviada y cambiar o quitar un TB ya asignado. |
| Productos nuevos | Asociar el ítem, asignar unidades y TB, y rechazar solicitudes. Solo usuarios de Oficina Principal. | Corregir lo enviado, desasociar el ítem, cambiar o quitar el TB y reabrir solicitudes rechazadas. Solo Oficina Principal. |
| Proveedores administrativos y comerciales | Verificar o rechazar el FO-300-01 en revisión. | Devolver el formato a borrador, corregir un formato rechazado (**Corregir FO-300-01**) y restaurar proveedores eliminados. |
| Demás módulos | No se usa. | Restaurar registros eliminados. |

En proveedores, además, **Crear** registra el proveedor, **Editar** permite diligenciar el borrador y enviarlo a revisión, y **Activar/Inactivar** cambia el estado de la vinculación. La aprobación no depende de un permiso: la da el director del proceso (ver [Verificar, aprobar o rechazar](guide:providers-review)).

**Datos de la empresa** solo usa **Leer** (consultar) y **Editar** (modificar).

### Cómo repartir los permisos

- **Empieza por Leer.** Sin Leer, las demás casillas del módulo no sirven, porque la pantalla no aparece.
- **Da solo lo que el cargo necesita.** Por ejemplo, una farmacia suele tener Leer, Crear, Editar y Eliminar en Pendientes, pero no Procesar ni Reversar.
- **Separa quién ejecuta de quién corrige.** Procesar y Reversar son líneas distintas para que puedas dar a un cargo la operación diaria y reservar las correcciones a un líder.
- **Los procesos de otra área llevan su propia línea.** Por ejemplo, la gestión de Oficina Principal sobre los pendientes está en **Inventario disponible**, no en Pendientes. Así, dar acceso a las farmacias no da acceso a la asignación de stock.
- **Usuarios, Roles y Datos de la empresa** deben quedar solo en el rol de administración, porque permiten cambiar accesos y configuración global.

> **Importante:** los permisos definen lo que una persona **puede intentar**. Algunas reglas de negocio se aplican además de los permisos: por ejemplo, la sede del usuario en Farmacia o la regla de que solo el director del proceso aprueba un proveedor.
