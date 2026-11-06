La mayoría de pantallas con registros (Procesos, Usuarios, Reemplazos de director, Agotados, Pendientes, Productos, Proveedores…) comparten el mismo listado. Lo que aprendas aquí sirve para todas ellas. Los ejemplos usan **Procesos** y **Agotados**.

## Partes de un listado

![Partes de un listado estándar](images/standard-lists/01-overview.png)

1. **Barra de acciones:** botones para agregar, editar, eliminar y demás acciones. Se explican abajo.
2. **Encabezados de columna:** pulsa un encabezado para ordenar por esa columna. Si lo pulsas otra vez, el orden se invierte. La flecha indica la columna y el sentido del orden actual.
3. **Fila de filtros**, debajo de los encabezados:
   - En una casilla de texto, escribe parte del valor buscado. El listado se actualiza solo mientras escribes.
   - En una lista desplegable (por ejemplo **Estado**), elige un valor. **Todos** quita el filtro.
4. **Fila roja con la marca «Elim»:** el registro está **eliminado**. Sigue visible para consultarlo o restaurarlo.
5. **Fila amarilla:** el registro está **inactivo**. Existe, pero no se ofrece en los formularios ni en los procesos nuevos.
6. **Mostrar N registros** y el botón de **columnas** (ícono de tres columnas). Mostrar cambia cuántas filas ves por página (10, 25, 50 o 100).
7. **Resumen y paginación:** «Mostrando 1–10 de 14 registros» y los botones para cambiar de página.

Puedes combinar varios filtros: el listado muestra solo los registros que cumplen todos.

> **Consejo:** los filtros, el orden, el número de filas por página y las columnas ocultas se guardan para tu usuario. Si sales de la pantalla y vuelves, el listado sigue como lo dejaste. Para volver a ver todo, pulsa **Limpiar filtros** (ícono de borrador).

Si una celda tiene un texto largo, se recorta con «…». Pasa el mouse sobre ella para leer el texto completo.

## Botones de la barra de acciones

Los botones solo muestran un ícono. Pasa el mouse sobre cada uno para ver su nombre. Solo aparecen los botones que tu rol tiene permitidos, y algunos se activan únicamente cuando seleccionas registros.

![Barra de acciones completa en una pantalla ancha (Agotados)](images/standard-lists/02-toolbar.png)

| N.º | Ícono | Nombre (al pasar el mouse) | Qué hace |
|---|---|---|---|
| 1 | Más (+) | Agregar | Abre el formulario para crear un registro. |
| 2 | Lápiz | Editar seleccionado | Abre el formulario del registro seleccionado. Requiere seleccionar **uno** que no esté eliminado. |
| 3 | Dos hojas | Duplicar seleccionado | Crea una copia del registro seleccionado y la deja seleccionada para que la edites. Los valores que no se pueden repetir reciben «-COPIA» y las copias con estado nacen **Inactivas**. Solo está en algunas pantallas. |
| 4 | Papelera | Eliminar seleccionados | Elimina los registros seleccionados, después de pedir confirmación. |
| 5 | Interruptor | Activar / inactivar seleccionados | Cambia el estado de los seleccionados: los activos pasan a inactivos y los inactivos a activos. |
| 6 | Flecha circular | Restaurar eliminados seleccionados | Recupera los registros eliminados que estén seleccionados (filas rojas). |
| 7 | Reloj | Auditoría (seleccione un registro) | Muestra quién creó, editó por última vez y eliminó el registro seleccionado. |
| 8 | Flecha hacia arriba | Importar desde Excel / CSV | Carga registros desde un archivo. Solo está en algunas pantallas. |
| 9 | Hoja de Excel | Exportar a Excel | Descarga el listado en un archivo .xlsx. |
| 10 | Archivo CSV | Exportar a CSV | Descarga el listado en un archivo .csv. |
| 11 | Borrador | Limpiar filtros | Quita todos los filtros del listado. |

Algunas pantallas agregan botones propios entre Eliminar y los de estado. Por ejemplo, Usuarios tiene **Habilitar** y **Deshabilitar solicitud de suministros** (ver [Usuarios](guide:admin-users)).

> **Importante:** la exportación incluye **todos** los registros que cumplen los filtros y en el orden actual, no solo la página que ves. También incluye las columnas que ocultaste y una columna **Eliminado** (Sí/No).

### Menú «Más» en pantallas medianas

En pantallas de menos de 1600 píxeles de ancho (la mayoría de portátiles), los botones 5 a 10 no caben en la barra. Pasan al menú **Más acciones**:

1. Pulsa **Más acciones** (tres puntos).
2. Elige la acción. Funciona igual que el botón correspondiente.

![Menú Más acciones con un registro seleccionado](images/standard-lists/03-more-menu.png)

## Mostrar u ocultar columnas

1. En el pie del listado, pulsa **Configurar columnas** (ícono de tres columnas).
2. Desmarca las columnas que no necesitas. **Mostrar todas** las vuelve a mostrar.

![Configuración de columnas visibles](images/standard-lists/04-columns.png)

Cuando hay columnas ocultas, el botón muestra cuántas son. Algunas columnas, como el nombre principal, no se pueden ocultar (su casilla aparece bloqueada). Siempre queda al menos una columna visible.

> **Importante:** si ocultas una columna que tenía un filtro escrito, ese filtro se borra, para que el listado no quede filtrado por algo que no ves.

## Seleccionar registros

- Para seleccionar un registro, marca la casilla al inicio de su fila.
- Para seleccionar todos los registros de la página, marca la casilla del encabezado (1). Si la marcas otra vez, se desmarcan.

Cuando la página completa queda marcada y hay más registros con los filtros actuales, aparece un aviso (2):

![Aviso para seleccionar todos los registros que coinciden con los filtros](images/standard-lists/05-selection-banner.png)

1. Pulsa **Seleccionar los N registros que coinciden con los filtros** para que las acciones masivas (eliminar, activar o inactivar, restaurar…) se apliquen a todos, no solo a los de la página.
2. El aviso cambia a «Están seleccionados los N registros que coinciden con los filtros». Pulsa **Limpiar selección** para deshacerlo.

> **Regla:** una acción masiva abarca como máximo **1.000** registros. Si los filtros traen más, el aviso te pide afinar los filtros.

La selección ampliada se cancela sola si cambias un filtro o marcas o desmarcas una fila. Así nunca actúas sobre registros distintos a los contados.

Si alguno de los registros seleccionados no admite la acción (por ejemplo, un proceso con usuarios asignados no se puede eliminar), el sistema aplica la acción a los demás y te avisa cuántos omitió y por qué.

## Confirmar antes de eliminar

Al pulsar **Eliminar seleccionados**, el sistema pregunta cuántos registros vas a eliminar. Pulsa **Sí, continuar** para confirmar o **Cancelar** para no hacer nada.

![Confirmación de eliminación](images/standard-lists/06-confirm-delete.png)

Eliminar no borra el registro de forma definitiva: la fila queda en rojo y puedes recuperarla con **Restaurar eliminados seleccionados**, si tu rol lo permite.

## Consultar la auditoría

1. Selecciona **un solo** registro (también puede ser uno eliminado).
2. Pulsa **Auditoría** (reloj) o, en pantallas medianas, **Más acciones → Auditoría**.

![Auditoría del registro](images/standard-lists/07-audit.png)

La ventana muestra quién creó el registro y cuándo, quién lo editó por última vez y, si está eliminado, quién lo eliminó y cuándo. Pulsa **Cerrar** para volver.

## Formularios

**Agregar** y **Editar** abren el formulario en una ventana sobre el listado:

- Los campos con asterisco rojo (*) son obligatorios.
- Si un dato no es válido, el campo se marca en rojo con el motivo debajo. Corrígelo y vuelve a guardar.
- **Guardar** registra los cambios y muestra un aviso verde en la esquina.
- **Cancelar**, la **X** o la tecla Esc cierran la ventana sin guardar.
