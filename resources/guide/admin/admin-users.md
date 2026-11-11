Los usuarios son las personas que ingresan a Cocoo Nova. Cada uno tiene un **rol**, que define lo que puede hacer, una **sede** y, opcionalmente, un **proceso**. Entra por **Usuarios → Listado** en el menú lateral.

El listado funciona como todos los demás. Si aún no lo conoces, lee [Cómo usar los listados](guide:standard-lists).

## El listado de usuarios

![Listado de usuarios](images/admin-users/01-list.png)

1. **Agregar:** crea un usuario.
2. **Editar seleccionado:** abre el usuario marcado.
3. **Habilitar solicitud de suministros:** permite a los usuarios seleccionados hacer requisiciones de aseo, papelería y cafetería.
4. **Deshabilitar solicitud de suministros:** les quita ese permiso.
5. **Filtros por columna:** por ejemplo, filtra por **Rol**, **Sede**, **Proceso** o **Estado** para ubicar a un grupo de personas.
6. **Fila amarilla:** usuario **Inactivo**. No puede iniciar sesión.

> **Regla:** solo se habilita la solicitud de suministros a usuarios que tengan **proceso** y **sede**. El proceso define quién aprueba sus requisiciones y la sede indica a dónde se entregan. Los usuarios sin esos datos se omiten, y el sistema te avisa cuántos fueron.

## Crear un usuario

1. Pulsa **Agregar**.
2. Diligencia el formulario **Agregar usuario**:

![Formulario Agregar usuario](images/admin-users/02-create.png)

1. **Identificación:** número de documento, solo dígitos (de 7 a 12). No se puede repetir.
2. **Correo:** el correo de trabajo de la persona. Será su usuario de ingreso y no se puede repetir. Completa también **Nombre** y **Apellido** (mínimo 3 letras cada uno).
3. **Sede/Servicio:** la sede o centro de costos donde trabaja. En Farmacia define qué pendientes puede gestionar. Oficina Principal abarca todas las sedes.
4. **Rol:** obligatorio. Define las pantallas y acciones que verá (ver [Roles y permisos](guide:admin-roles)).
5. **Proceso:** el área a la que pertenece. Su director aprueba las requisiciones de la persona (ver [Procesos y reemplazos de director](guide:admin-processes)).
6. **Cargo:** el cargo del organigrama (ver [Características y cargos](guide:hr-characteristics)). Aparece junto a su nombre en las firmas del FO-300-01 y define si puede verificar proveedores (ver [Datos de la empresa](guide:admin-company-settings)).
7. **Estado:** **Activo**, **Pendiente** o **Inactivo**. Solo los usuarios activos pueden ingresar.
8. **Solicita suministros:** **Sí** habilita las requisiciones de aseo, papelería y cafetería. Si eliges Sí, el proceso y la sede son obligatorios.

3. Pulsa **Guardar**.

### La contraseña temporal

No escribes ninguna contraseña: el sistema la genera al guardar.

- El nuevo usuario recibe en su correo el mensaje **«Acceso creado en…»** con su contraseña temporal y el botón **Iniciar sesión**.
- Tú recibes el mensaje **«Usuario creado en…»** como confirmación. Ese mensaje no incluye la contraseña.
- La primera vez que la persona ingrese, el sistema la obliga a definir una contraseña propia (ver [Ingreso y navegación](guide:getting-started)).

> **Importante:** la contraseña temporal solo se envía al crear el usuario. Si la persona no recibió el correo o la olvidó, debe usar **¿Olvidaste tu contraseña?** en la pantalla de ingreso. Al editar un usuario nunca se cambia su contraseña.

## Editar, activar o inactivar un usuario

1. Marca la casilla del usuario en el listado.
2. Pulsa **Editar seleccionado**.
3. Cambia los datos necesarios. Para bloquear o permitir el ingreso, cambia el **Estado** (1):
   - **Inactivo:** la persona no puede iniciar sesión. Si está conectada, el sistema la saca al abrir otra pantalla.
   - **Pendiente:** cuenta creada que aún no debe usarse. Tampoco permite ingresar.
   - **Activo:** vuelve a permitir el ingreso.
4. Pulsa **Guardar**.

![Edición de un usuario inactivo: campo Estado](images/admin-users/03-edit-status.png)

> **Consejo:** cuando alguien se retira de la empresa, inactívalo en lugar de eliminarlo. Así se conserva el historial de lo que hizo y puedes reactivarlo si regresa.

## Eliminar y restaurar

- **Eliminar seleccionados** marca los usuarios como eliminados (fila roja). No puedes eliminar tu propio usuario.
- **Restaurar eliminados seleccionados** los recupera. Requiere el permiso **Reversar** de Usuarios.

Un usuario eliminado tampoco puede iniciar sesión. Los procesos y sedes con usuarios asignados no se pueden eliminar: primero reasigna a esas personas.
