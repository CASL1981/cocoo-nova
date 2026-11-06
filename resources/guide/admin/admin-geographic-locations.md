**Ubicación geográfica** reúne en una sola tabla los países, departamentos y ciudades que ofrecen los formularios de la aplicación, por ejemplo el municipio del proveedor o la nacionalidad del FO-300-01. Entra por **Configuración → Datos Generales → Ubicación geográfica** en el menú lateral.

Cada registro tiene un **nivel** y pertenece a otro: un departamento pertenece a un país y una ciudad a un departamento. Un país no pertenece a nadie.

## Consultar las ubicaciones

![Listado de Ubicación geográfica](images/admin-geographic-locations/01-list.png)

1. **Agregar** abre el formulario de una ubicación nueva.
2. **Nivel:** muestra solo países, departamentos o ciudades.
3. **Pertenece a:** busca por el departamento o el país. Por ejemplo, «Colombia» trae sus departamentos y todas sus ciudades.
4. Una fila amarilla es una ubicación **inactiva**: ya no se ofrece en los formularios nuevos, pero los registros que la usan la siguen mostrando.
5. Una fila roja es una ubicación **eliminada**. Se puede restaurar con el permiso **Reversar**.

Los botones de editar, eliminar, activar o inactivar, auditoría, importar y exportar funcionan como en cualquier listado. Ver [Listados: buscar, filtrar y actuar sobre registros](guide:standard-lists).

## Agregar o editar una ubicación

1. Pulsa **Agregar**, o selecciona una fila y pulsa **Editar**.
2. Completa los campos y pulsa **Guardar**.

![Formulario Agregar ubicación](images/admin-geographic-locations/02-form.png)

1. **Nivel:** País, Departamento o Ciudad.
2. **Código:** único en toda la tabla. Se guarda en mayúsculas y admite letras, números y guion.
   - País: código ISO de dos letras (CO).
   - Departamento y ciudad de Colombia: código DANE (23 para Córdoba, 23001 para Montería).
   - Exterior: código ISO 3166-2 o uno con el prefijo del país (AU-NSW, AU-SYD).
3. **Pertenece a:** aparece al elegir Departamento (lista de países) o Ciudad (lista de departamentos con su país).
4. **Nombre** de la ubicación.
5. **Población:** opcional; se guarda para uso futuro.

> **Consejo:** para una ciudad del exterior, crea primero su país y su departamento, estado o provincia.

## Ubicaciones protegidas

![Ubicación en uso con campos bloqueados](images/admin-geographic-locations/03-locked.png)

Si una ubicación ya la usa algún proveedor o tiene ubicaciones que le pertenecen, el formulario lo avisa (1) y bloquea el nivel, el código y la ubicación a la que pertenece. El nombre y la población sí se pueden corregir.

> **Regla:** una ubicación en uso o con ubicaciones activas que le pertenecen no se puede eliminar. Una ubicación eliminada no se restaura mientras la ubicación a la que pertenece siga eliminada.

## Importar desde Excel

Con el permiso **Importar**, el botón de importación de la barra de acciones (en pantallas medianas, dentro del menú **Más**) carga muchas ubicaciones a la vez.

1. Descarga la plantilla desde la ventana de importación.
2. Llena una fila por ubicación. Las columnas son `level` (País, Departamento o Ciudad), `code`, `name`, `parent_code` (código de la ubicación a la que pertenece), `population` y `status` (Activo o Inactivo).
3. Sube el archivo.

- Si el código ya existe, la fila **actualiza** esa ubicación; si no, la **crea**.
- Una columna que no esté en el archivo, o un nivel, estado o `parent_code` vacío, conserva el valor actual. Por ejemplo, un archivo solo con `code` y `population` actualiza la población.
- El archivo se valida completo: si alguna fila tiene errores no se guarda ninguna y la ventana indica la fila y el motivo.

> **Importante:** el `parent_code` debe existir antes de importar. Si vas a crear un país con sus departamentos y ciudades, importa primero el país, luego los departamentos y por último las ciudades.
