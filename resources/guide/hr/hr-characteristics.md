**Características de Talento Humano** guarda las listas propias del área, separadas de las características generales de la aplicación. La principal es **Cargos**: los 37 cargos del organigrama vigente, cada uno con su proceso y su jefe inmediato. Entra por **Módulos → Talento Humano → Características** en la barra superior.

## Consultar características y cargos

![Pantalla de características de Talento Humano](images/hr-characteristics/01-overview.png)

1. Elige una característica en el panel izquierdo para ver sus opciones a la derecha. La fila elegida queda resaltada.
2. **Proceso:** en Cargos, filtra los cargos de una dirección (Gerencia, Comercial, Logística y Operaciones, Financiero o Administrativo).
3. **Jefe inmediato:** el cargo al que reporta cada uno, según el organigrama.
4. **Agregar** crea una opción en la característica elegida.

Los botones de editar, duplicar, eliminar, activar o inactivar, restaurar, auditoría y exportar funcionan como en cualquier listado. Ver [Listados: buscar, filtrar y actuar sobre registros](guide:standard-lists).

## Agregar o editar un cargo

1. Elige **Cargos** en el panel izquierdo.
2. Pulsa **Agregar**, o selecciona un cargo y pulsa **Editar**.
3. Completa los campos y pulsa **Guardar**.

![Formulario Editar cargo](images/hr-characteristics/02-position-form.png)

1. **Nombre** del cargo, único dentro de Cargos.
2. **Proceso (dirección)** al que pertenece. Es obligatorio en los cargos.
3. **Jefe inmediato:** opcional. Solo lista los cargos activos.

> **Regla:** un cargo no puede reportarse a sí mismo ni a uno de sus subordinados. Un cargo con cargos que le reportan no se puede eliminar: primero cambia su jefe inmediato en esos cargos.

En las demás características, por ejemplo Tipos de contrato, las opciones solo llevan nombre y estado.

## Agregar o editar una característica

![Formulario Editar característica](images/hr-characteristics/03-characteristic-form.png)

Una característica tiene nombre, estado y un **código interno** (1) en minúsculas y con guion bajo, por ejemplo `contract_type`. La aplicación busca la lista por ese código.

> **Importante:** cuando una característica ya tiene opciones, su código no se puede cambiar y la característica no se puede eliminar. La característica **Cargos** usa el código `positions`.
