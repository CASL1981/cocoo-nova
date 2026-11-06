# CLAUDE.md — Cocoo Nova

Guía para Claude Code en este repositorio. Aplicación empresarial para una organización colombiana que comercializa medicamentos y dispositivos médicos. Prioriza soluciones estables, mantenibles, seguras y escalables. Responde siempre en **español**.

## Stack

- PHP 8.3+/8.4 · Laravel 13 · Livewire 4 · Laravel Fortify (auth) · Alpine.js · SweetAlert2 · Vite
- Plantilla **qompac-ui** (tema admin Bootstrap 5). Layout de páginas: `#[Layout('layouts.app')]`. El frontend también incluye Flux UI y assets legacy jQuery/Bootstrap/DataTables (`package.json`); para pantallas nuevas usar qompac-ui y los componentes Blade del proyecto.
- nwidart/laravel-modules 13 (+ mhmiton/laravel-modules-livewire, `config/modules-livewire.php`)
- spatie/laravel-permission · wildside/userstamps (namespace real del trait: `Mattiverse\Userstamps\Traits\Userstamps`)
- maatwebsite/excel ^4.0 · Pest 5 · Larastan 3 (nivel 7, `phpstan.neon`) · Pint
- Zona horaria `America/Bogota` · Entorno local: WAMP en Windows

**Laravel Boost está configurado** (`boost.json`, MCP vía `php artisan boost:mcp`). Preferir sus herramientas (`search-docs`, `database-query`, `database-schema`, `get-absolute-url`, `browser-logs`) sobre equivalentes manuales y ejecutar `search-docs` antes de cambios relacionados con el framework. Las guías completas de Boost están en `AGENTS.md` (léelo: cubre convenciones de PHP/Livewire/Pint/Pest no repetidas aquí).

Documentación oficial (no inventar métodos, directivas ni APIs; si algo no está confirmado, indicarlo y proponer alternativa): Laravel 13 https://laravel.com/docs/13.x · Livewire 4 https://livewire.laravel.com/docs/4.x · Módulos https://laravelmodules.com/

## Comandos

```bash
composer run dev                                  # serve + queue:listen + vite dev
php artisan test --compact                        # suite completa
php artisan test --compact --filter=NombreTest    # un test
vendor/bin/pint --dirty --format agent            # formatear archivos cambiados (tras cada edición PHP)
vendor/bin/pint --format agent                    # formatear todo
phpstan analyse                                   # análisis estático (nivel 7)
npm run dev / npm run build                       # Vite
composer run test                                 # gate de CI: config:clear + lint + types + tests
php artisan view:clear                            # obligatorio tras cambiar un SFC (caché en storage/framework/views/livewire/classes)
php artisan make:test --pest {Modulo}/{Nombre}Test  # sin prefijo Feature/
composer guide:screenshots                        # regenera las capturas de la guía (requiere npm run build)
```

## Reglas obligatorias

1. **Identificadores en inglés** (clases, métodos, propiedades, variables, rutas, eventos, campos de BD). Comentarios, mensajes de validación y textos de interfaz en español. Nunca mezclar idiomas en identificadores.
2. **Comentarios en español** orientados a futura documentación: intención, reglas de negocio y decisiones. PHPDoc en clases y métodos públicos. No comentar lo obvio.
3. **Migraciones:** toda columna nueva lleva `->comment('...')` en español y la tabla lleva `$table->comment('...')` (regla solo hacia adelante). Usar `$table->auditColumns();` para timestamps, soft deletes y userstamps comentados; nunca escribirlos a mano ni usar los macros `userstamps()` sin comentario.
4. **Nombres de tabla de módulo:** `<modulo>_<modelo_plural>` en snake_case e inglés (`shopping_products`, `pharmacy_stock_outs`). Lo transversal en `app/` no lleva prefijo (`users`, `characteristics`). Excepción: HumanResources usa el prefijo abreviado `hr_` (`hr_characteristics`).
5. **Modelos:** `SoftDeletes` + `Userstamps` + `HasFactory`, `$fillable` explícito (nunca `$guarded = []`), `$table` explícito en modelos de módulo, enums respaldados en `casts()`. Clases `final` por defecto.
6. **Validación siempre en el servidor.** Formularios: `Livewire\Form` con `rules()` y `payload()` → `return $this->sanitize($this->validate());` (trait `SanitizesPayload`). Enums con `Rule::enum()`.
7. **Autorización en dos capas:** Policy (`Gate::authorize(...)`) + middleware de ruta `module.permission`.
8. **Carga diferida prohibida** fuera de producción (`Model::preventLazyLoading`): cargar relaciones con `with()`/`load()`/`loadMissing()`.
9. **Fechas inmutables:** `Date::use(CarbonImmutable::class)` está activo globalmente.
10. Tras editar PHP: `vendor/bin/pint --dirty --format agent`. Mantener PHPStan nivel 7 limpio.
11. **No** código deprecado, **no** dependencias nuevas sin justificar, **no** datos sensibles en propiedades públicas de Livewire ni en logs.
12. Respuestas mínimas pero completas: solo archivos necesarios, explicar solo decisiones de arquitectura, seguridad o compatibilidad. Si algo es ambiguo, una sola pregunta breve antes de generar código extenso.
13. **Interfaz estándar obligatoria en todo CRUD nuevo o modificado:** listado con `<x-data-table>` (`per-page-options`, `columns` + `HasColumnVisibility`), `<x-table.actions>` (nunca botones de acción escritos a mano; acciones propias por su slot, de solo ícono con `btn-table-icon` y `<x-tooltip>`), `<x-table.head>`, `<x-table.filters>`, `'width'` en las columnas de texto largo, `<x-table.row-checkbox>` y `<x-table.selection-banner>`; formularios en `<x-modal title subtitle icon>` con `<x-forms.*>` (`required` según las reglas del Form), `<x-forms.section>` si tiene más de ~6 campos y pie `<x-forms.modal-actions>`. Estilos nuevos solo en `resources/css/app.css` con variables del tema.
14. **Guía de usuario obligatoria:** toda pantalla o regla de negocio nueva o modificada actualiza su tema en `resources/guide/` (o crea uno en `GuideCatalog`) y sus capturas en `tests/Browser/Guide/` (ver sección "Guía de usuario"). Una funcionalidad no está terminada sin su guía.

## Arquitectura

### Monolito modular

- **`app/` (transversal):** auth, User, Role, ServicePoint, Characteristic/CharacteristicOption, `PermissionCatalog`, traits, componentes Blade genéricos, `GenericExport`.
- **`Modules/<Nombre>/` (negocio vertical):** Shopping (Product, proveedores comerciales), Pharmacy, Administrative (proveedores administrativos; requisiciones en diseño) y HumanResources (Talento Humano: características propias y cargos; más adelante modelo `Employee` propio, independiente de User). Orden futuro: Contable. Estado en `modules_statuses.json`, registro en `Modules/<Nombre>/module.json`, rutas cargadas por el `RouteServiceProvider` del módulo, `composer.json` del módulo integrado vía `merge-plugin`.
- Estructura de módulo: `app/{Actions,Models,Policies,Http,Livewire,Providers}`, `routes/{web,api}.php`, `database/{migrations,factories,seeders}`, `resources/views`.
- Un módulo no accede a tablas de otro si existe un Service/Action público; usar eventos o interfaces; evitar dependencias circulares.

### Checklist al crear un módulo (`php artisan module:make {Nombre}`)

Configuración validada tras 8 rondas de depuración en Shopping:

1. `Modules/{Nombre}/composer.json` con doble PSR-4: `"Modules\\{Nombre}\\": "app/"` **y** `"Modules\\{Nombre}\\App\\": "app/"`, más `Database\\Factories` y `Database\\Seeders`. Luego `composer dump-autoload`.
2. En `register()` del ServiceProvider: `config(['livewire.component_namespaces.{modulo}' => module_path('{Nombre}', 'resources/views')]);`
3. En `boot()` (después de `parent::boot()`): `Livewire::addNamespace(namespace:, classNamespace:, classPath:, classViewPath:)` y `Gate::policy(Model::class, ModelPolicy::class)` explícito (el autodescubrimiento no cubre el namespace del módulo).
4. Referencias: páginas `{modulo}::pages.{archivo}`; vistas de clase `{modulo}::livewire.{carpeta}.{archivo}`; componentes de clase `<livewire:{modulo}::{carpeta}.{componente} />`.
5. Eliminar `Modules/{Nombre}/tests/` y cualquier `phpunit.xml` del módulo (los tests van en `tests/Feature/{Nombre}/`); eliminar la ruta de recurso generada en `routes/web.php`.
6. Agregar el slug a `PermissionCatalog::MODULES` y `MODULE_ICONS`.
7. Enlace en el sidebar envuelto en `@can('{slug}-read')`.
8. Sección y temas en `GuideCatalog` con su Markdown y capturas (regla 14).

### Livewire

- **Pantallas con ruta = páginas SFC** (`new class extends Component { ... }`). En `app/`: `resources/views/pages/⚡*.blade.php`, referenciadas como `pages::xxx-index`. En módulos: `Modules/{X}/resources/views/pages/`, referenciadas como `{modulo}::pages.xxx-index`.
- **Componentes de clase solo si es necesario** (modales reutilizables, paneles con estado propio). Vistas en `resources/views/livewire/<area>/`.
- Componentes desacoplados que se comunican por eventos `#[On(...)]` (ej. `characteristic-selected`, `characteristic-saved`), con el **mismo nombre de parámetro** que el dispatch. Las props de un hijo no son reactivas por defecto: usar `#[Reactive]` o eventos.
- Listados: autorizan en `mount()` con `Gate::authorize('viewAny', ...)`; paginador en propiedad `#[Computed]` (refrescar con `unset($this->prop)`), nunca en propiedad pública plana; la misma consulta filtrada/ordenada alimenta la tabla y la exportación.
- Todo crear/editar vive en un **modal** (`<x-modal>`), nunca en página separada. En `open*()`: `$this->openModal()` **antes** de poblar el formulario.
- Filtros/orden/página persistidos por usuario: `#[Session(key: 'modulo.index.prop-{authId}')]` + `HasAuthScopedSession`.
- Búsquedas con `wire:model.live.debounce.300ms`; `wire:model.live` solo cuando se requiere inmediatez.
- `#[Locked]` en ids que solo asigna el servidor (bloquea al cliente, no al servidor).
- Confirmaciones destructivas: `confirmAction({...}).then(() => $wire.metodo()).catch(() => {})` (SweetAlert2), nunca `wire:confirm`. Toasts con `Notifies`.

## Permisos

- `App\Support\PermissionCatalog` es la fuente única de verdad. Acciones: `read, create, update, delete, toggle, process, reverse, import`. Nombre: `{modulo}-{accion}` (ej. `stock-outs-toggle`). **Agregar un módulo exige agregarlo a `MODULES`** (y `MODULE_ICONS`); nada se deriva de las rutas.
- Módulos actuales: `users, roles, service-points, products, characteristics, processes, process-substitutions, company-settings, geographic-locations, stock-outs, pendings, inventory-balances, new-products, administrative-providers, commercial-providers, hr-characteristics`.
- Siembra: `SeedModulePermissions` orquestado desde `AllModulePermissionsSeeder` (en `DatabaseSeeder` y en el `beforeEach` de `tests/Pest.php`).
- Middleware `module.permission` (`EnsureModulePermission`): toma el primer segmento del nombre de ruta como slug y mapea el último con `ROUTE_ACTION_MAP` (index/show→read, create/store→create, edit/update→update, destroy→delete, toggle, process, reverse). **El nombre de ruta debe iniciar con el slug** (`stock-outs.index`); la URL puede llevar prefijo (`pharmacy/stock-outs`). Rutas que no mapean pasan sin verificación.
- Grupo de rutas estándar: `['auth', 'ensure.active', 'force.password.change', 'module.permission']` (alias en `bootstrap/app.php`). `ensure.active` bloquea usuarios inactivos; `force.password.change` redirige a `password.force-change` (ruta excluida para evitar bucle).
- Restaurar registros usa la acción `reverse`.
- Roles híbridos: `users.role_id` (rol primario para UI) + `model_has_roles` de Spatie sincronizado por `UserObserver`.

## Piezas reutilizables

### Traits Livewire (`app/Livewire/Concerns/`)

| Trait | Uso |
|---|---|
| `ManagesModal` | `$show`, `openModal()`, `closeModal()`, abstracto `resetModalState()` |
| `Notifies` | `toastSuccess()`, `toastError()` |
| `HasAuthScopedSession` | `$authId` para claves `#[Session]` por usuario |
| `HasSortableColumns` | `sort()`, `applySort()`. **El componente declara `$sortBy` y `$sortDirection`** |
| `SanitizesPayload` | `sanitize()` en objetos Form |
| `HasRowSelection` | `$selected`, `toggleSelectAll()` (página), `clearSelection()`, `editSelected()` (abre el modal y limpia la selección). Selección ampliada tipo Gmail: `selectAllMatchingRows()` + `#[Locked] $selectAllMatching`; `selectedIds()` resuelve los IDs con `baseQuery()` (o `selectionQuery()`), máximo `BulkSelection::MAX_MATCHING` (1.000). Usar `selectedCount()` en confirmaciones y `<x-table.selection-banner>` sobre la tabla |
| `HasBulkRowActions` | `deleteSelected()`, `toggleSelected()`, `restoreSelected()`, `singleActiveSelected()`, `rowStatusClass()`. Abstractos: `rowModelClass()`, `computedRowsProperty()` |
| `HasColumnFilters` | Filtros desde `columns()`: `applyColumnFilters()`, `resetFilters()`, `perPageOptions()`, `resolvedPerPage()`, `sortableColumns()` y hook `updatedHasColumnFilters()` |
| `HasColumnVisibility` | Botón de columnas en el pie de `<x-data-table>`: `toggleColumn()`, `showAllColumns()`, `activeHiddenColumns()`. El componente declara `#[Session(key: 'modulo.index.hiddenColumns-{authId}')] public array $hiddenColumns = [];`. Ocultar es solo CSS (`nth-child`), no afecta consulta ni exportación; `'hideable' => false` en `columns()` la bloquea; ocultar limpia el filtro de esa columna |
| `ImportsRows` | Modal de importación Excel/CSV (`openImport()`, `import()`, `downloadImportTemplate()`, `$importErrors`). Abstractos: `importer(): RowImporter`, `afterImport()`. Permiso `{slug}-import` (habilidad `import`) |
| `DuplicatesRows` | Botón "Duplicar" (copia directa): `duplicateSelected()` (deja seleccionada la copia) o `duplicateRecord($id)` por fila. Autoriza `Gate::authorize('duplicate', $row)` (= permiso `create` + reglas propias, ej. sede en `PendingPolicy`). El modelo implementa `App\Contracts\Duplicable::prepareDuplicate(User $actor)`; `App\Actions\DuplicateRecord` usa `replicate()` sin auditoría ni detalle. Únicos con `GeneratesCopyValues::uniqueCopyValue()` (`-COPIA`, `-COPIA-2`…, incluye eliminados); las copias con estado nacen Inactivas. En `<x-table.actions>`: prop `duplicable` (+ `single-duplicable` opcional). Productos nuevos excluido a propósito (regla anti-duplicados de `SaveNewProduct`) |
| `ExportsRows` | `export(string $format)` con lista blanca `xlsx`/`csv`. Abstractos: `baseQuery()` (protected), `exportFileName()`, `exportHeadings()`, `exportRow(Model $row)` |

`columns()` devuelve: `campo => ['label' => ..., 'width' => 'xs'|'sm'|'md'|'lg'|'xl'?, 'filter' => 'filterProp'|null, 'type' => 'select'?, 'options' => Enum::options()?, 'hideable' => false?]`. `width` (5/7/10/14/20 rem) fija el ancho en `<x-data-table>` y recorta en una línea con "…"; el texto completo aparece en el tooltip global de celdas recortadas (`resources/js/app.js`, aplica a cualquier `.table`). Toda columna de texto libre o nombre largo lleva `width`; códigos, cantidades, fechas y estados no. Para listados con búsqueda única, preferir un scope `search(...)` en el modelo (ej. `Characteristic::search()`) compartido entre listado y exportación.

### Policies y contratos

- `App\Policies\Concerns\HasStandardModuleAbilities`: `viewAny, view, create, update, delete, toggle, restore, import`. La Policy declara `protected string $module = '{slug}';` y sobrescribe solo lo que tenga reglas extra (ej. no eliminar con dependencias).
- `App\Contracts\HasToggleableStatus` (`toggleStatus()`) para el toggle masivo.

### Blade

- `<x-modal wire-model="show" :title subtitle? icon? size>` con slot `footer` (centrado, cuerpo con scroll, pantalla completa en celular). Pie estándar: `<x-forms.modal-actions submit? submit-label? submit-icon? target? cancel?>` (Cancelar cierra con Alpine; Guardar se bloquea y muestra spinner solo con su `wire:target`).
- Campos con la misma API (`name label required? hint? id?`, todos sobre `<x-forms.field>`): `<x-forms.input type model-modifier?>` · `<x-forms.select :options placeholder>` (sin `name` se comporta como `<select>` simple con `wire:model` propio) · `<x-forms.textarea rows>` · `<x-forms.search-select>`. `required` es solo visual (asterisco + `aria-required`): la validación vive en el servidor. `id` explícito evita ids duplicados. Formularios largos agrupados con `<x-forms.section title icon? description?>` (trae su `row g-3`; los campos van en `col-md-*`). Estilos en la sección "FORMULARIOS Y MODALES" de `resources/css/app.css`.
- `<x-data-table :paginator :per-page-options? :columns? :hidden-columns? :leading-columns=1>` con slots `toolbar-left`, `toolbar-right`, `before-table`, `head`, `filters`. El selector de registros y la configuración de columnas van en el pie (`<x-table.pagination>` + slot `start`), no en `toolbar-left`.
- `<x-table.actions>`: botones de solo ícono con `<x-tooltip>` y `aria-label`, unidos por grupo (`.table-actions-group`, clase `btn-table-icon`): Agregar · Editar · Duplicar · Eliminar | slot | estado/auditoría | importar/exportar | Más | Limpiar; por debajo de 1600 px estado, auditoría e intercambio de datos pasan al menú "Más" (desplegables con Alpine, sin Popper). Estilos en la sección "LISTADOS" de `resources/css/app.css`.
- `<x-table.actions module model-class :selected :single-active create-event edit-event edit-param>`: botones estándar con `@can`, auditoría y exportación.
- Pantallas encabezado + líneas (ej. `pendings.show`; luego solicitudes de papelería y órdenes de compra), solo presentación: `<x-document.header title icon? subtitle? status? status-class? back?>` (slot `actions`) con `<x-document.section title icon>` y `<x-document.field label value?>`; `<x-document.lines title icon entry-title? entry-icon? editing?>` con slots `actions`, `entry` (formulario de captura en línea), `notice` y la tabla en `.document-lines-table` (`<x-document.empty-row colspan text hint?>`). Estilos en la sección "DOCUMENTOS CON LÍNEAS" de `resources/css/app.css`.
- `<x-table.head :columns :sort-by :sort-direction :page-ids :selected>` · `<x-table.filters :columns>` · `<x-table.row-checkbox :id :selected>` · `<x-table.per-page :options>`.

### Datos y exportación

- `Blueprint::auditColumns()` (macro en `AppServiceProvider::boot()`).
- Enums de estado en `app/Enums/` (`Status`, `UserStatus`, `ServicePointStatus`) con `label()`, `badgeClass()` y `options()`. `Status`: `Active`/`Inactive` (valores `active`/`inactive`).
- `App\Exports\GenericExport(query:, headings:, mapRow:)`: nunca crear una clase Export por modelo.
- Importación: una clase `RowImporter` por modelo (`columns`, `key`, `rules`, `prepare`, `save` → Action de escritura), ejecutada por `App\Actions\Imports\ImportRows` (todo o nada, upsert por clave incluidos eliminados, errores "Fila N"). La comparten la pantalla (`<x-table.import>` o `importable` en `<x-table.actions>`) y los seeders (ej. `ProductImporter` + `ProductSeeder` desde `database/DB.xlsx`, hoja `shopping_products`).
- Auditoría: `$dispatch('open-userstamps-audit', { modelClass, modelId })`; botón habilitado con `count($selected) !== 1` (incluye eliminados).
- Listados muestran eliminados con `withTrashed()`; fila roja (eliminado) o amarilla (inactivo) vía `rowStatusClass()`.
- Actions de escritura por modelo (`SaveProduct`, `SaveStockOut`): punto único para reglas de negocio.

## Testing

- Pest. Tests en `tests/Feature/{Modulo}/` (`tests/Feature/Shopping`, `tests/Feature/Pharmacy`). `phpunit.xml` solo lista `tests/Unit` y `tests/Feature`.
- Helpers de `tests/Pest.php`: `actingAsAdmin()` y `userWith(string|array $permissions)`; no armar permisos a mano.
- Páginas: `Livewire::test('{modulo}::pages.{archivo}')`. Componentes de clase: FQCN.
- Autorización denegada en acciones Livewire: `->assertForbidden()`, nunca `->throws(AuthorizationException::class)`.
- Factories con rangos amplios para `unique()`; valores reales solo en seeders.

## Lecciones aprendidas (evitar repetir)

1. Importar `Illuminate\Database\Eloquent\Builder`, no `Query\Builder` (el IDE suele equivocarse).
2. Un trait nunca declara propiedades con valor por defecto que varíen por consumidor: usar métodos abstractos.
3. Modales: `openModal()` antes de poblar datos.
4. Nunca clases Bootstrap `!important` (`d-block`) estáticas junto a `x-show`: condicionarlas con `:class`. CSS `[x-cloak] { display: none !important; }` requerido.
5. SQLite ausente en WAMP: habilitar `pdo_sqlite` y `sqlite3` en el php.ini de la CLI (`php --ini`).
6. Checkboxes de fila con `wire:key` dinámico según su estado de selección.
7. Fijar versiones mayores en Composer (`"^4.0"`).
8. Spatie + SoftDeletes: un rol/permiso eliminado lógicamente sigue bloqueando el mismo nombre (pendiente, diferido).
9. `There is no permission named...`: el módulo no está en `PermissionCatalog` o no se sembró en `tests/Pest.php`.
10. Un 500 en Livewire revierte el estado del componente: revisar la pestaña Network (`livewire/update`) y su respuesta. Causas vistas: `$sortBy`/`$sortDirection` faltantes, columna inexistente en `sortableColumns()`.
11. Verificar nombres reales de columnas contra la migración (ej. `characteristic_options.name`, no `value`).
12. `wire:click.stop` en la celda del checkbox impide que el clic dispare la acción de la fila: es intencional.
13. SFC: los tipos usados en firmas compatibles con traits (`Model`, `Builder`) deben importarse en el SFC.
14. `Cannot redeclare class` en Pest: algún directorio del testsuite incluye archivos de `Modules/` o existen tests/`phpunit.xml` generados por nwidart.
15. El error de consola `"[object Object]" is not valid JSON` proviene de la extensión livewire-devtools, no de la app.
16. Las relaciones con SoftDeletes excluyen padres eliminados: `$child->parent()->exists()` sirve para bloquear hijos de un padre eliminado.
17. Blade compila etiquetas `<x-...>` aunque estén dentro de comentarios PHP de `@props` (`// ej. <x-table.actions>`): provoca `unexpected token "endif"`. En esos comentarios nombrar el componente sin `<x-`.
18. Las pruebas de navegador (pest-plugin-browser) exigen `extension=sockets` en el php.ini de la CLI y `npx playwright install chromium`. Sin `npm run build` las capturas salen sin los estilos nuevos.

## Estado actual

**Terminado y probado:** Usuarios, Roles, Sedes (ServicePoint), Productos (Shopping), Características/Opciones, Ubicación geográfica, Características de Talento Humano (cargos), Agotados (Pharmacy · StockOut), Proveedores (FO-300-01).

- Características: maestro-detalle (`CharacteristicList` + `CharacteristicOptionList`), eliminados visibles, restauración, exportación, auditoría. Opciones bloqueadas (crear/editar/eliminar/restaurar) si su característica está eliminada: Policy (`parentIsActive()`) + `Rule::exists(...)->whereNull('deleted_at')` + computed `parentTrashed`.
- Tabla `products` renombrada a `shopping_products`.
- El código de una característica con opciones no se edita (`Characteristic::isCodeLocked()`, validado en `CharacteristicForm` y `SaveCharacteristic`).
- **Ubicación geográfica** (`geographic-locations.index`, Configuración → Datos Generales): países, departamentos y ciudades en una sola tabla jerárquica `geographic_locations` (`GeographicLocation`, enum `GeographicLevel` con `parentLevel()`). Reemplazó a `countries`, `departments` y `municipalities` (eliminadas). Código único global (ISO alfa-2, DANE, ISO 3166-2 en el exterior), `population` opcional, estado Activo/Inactivo.
  - Escritura en `SaveGeographicLocation` (jerarquía: departamento → país, ciudad → departamento). En uso por proveedores o con hijas (`structureLockReason()`, `lockedChanges()`) no cambia código, nivel ni padre; no se elimina si está en uso o tiene hijas activas; no se restaura con el padre eliminado (`GeographicLocationPolicy`).
  - Selects: `GeographicLocation::options()` / `childOptions()` (solo activas, más la ya guardada); validación con `GeographicLocation::existsRule()`; `colombiaId()`.
  - Importación `GeographicLocationImporter` (clave `code`, padre por `parent_code`; vacío conserva el valor). `ReferenceCatalogSeeder` solo agrega los códigos que faltan (no pisa lo editado).
- **Talento Humano (`Modules/HumanResources`, slug `hr-characteristics`, ruta `hr-characteristics.index`, menú Módulos → Talento Humano):** características propias del área, separadas de las generales: `HrCharacteristic` / `HrCharacteristicOption` (tablas `hr_characteristics`, `hr_characteristic_options`), maestro-detalle con `HrCharacteristicList` + `HrCharacteristicOptionList` (interfaz estándar) y modales. Escritura en `SaveHrCharacteristic` (código bloqueado con opciones) y `SaveHrCharacteristicOption`.
  - **Cargos** (`code = positions`, `HrCharacteristic::POSITIONS_CODE`): sus opciones llevan `process_id` (obligatorio) y `parent_id` (jefe inmediato, sin ciclos: `wouldCreateCycle()`). Un cargo con subordinados no se elimina. `PositionSeeder` carga los 37 cargos de `doc/ORGANIGRAMA.xlsx` (hoja VERSION 6, dic. 2023), sin órganos de gobierno; se llama desde `DatabaseSeeder`. `HrCharacteristicOption::optionsFor($code)` para selects.
- Traducciones de Fortify y configuración en `resources/lang/es.json` (no crear `lang/` en la raíz: Laravel usa `resources/lang` si existe) (textos con `__()`).

**Pendientes menores:**

- Productos ya usa `x-table.*` y `HasColumnVisibility`; falta migrar su lógica a `HasColumnFilters`, `ExportsRows` y `HasStandardModuleAbilities`, y validar el formato en su `export()`.
- Usuarios, Sedes, Características/Opciones y las pantallas de gestión de Farmacia (asignación, TB, consulta de ítems, gestión de productos nuevos) conservan barras escritas a mano: migrarlas a `x-table.actions` cuando se modifiquen (regla 13).
- Verificar referencias a la tabla `products` en reglas `unique`/`exists` (usar `Rule::unique(Product::class)`).
- `CharacteristicOption::forCharacteristicCode()`: confirmar que use la columna `name`.
- Mensaje específico al no poder eliminar una característica con opciones (hoy muestra "0 registro(s) eliminado(s)").

## Guía de usuario

Pantalla `guide.index` (`pages::guide`), abierta a todo usuario autenticado. El botón **?** de la barra superior (`#guide-help`) la abre con `?context={ruta}` en el tema de la pantalla actual. También está "Guía de usuario" en el menú del perfil.

- **Catálogo.** `App\Support\GuideCatalog` es la fuente única: `SECTIONS` y `TOPICS`. Cada tema tiene `section`, `title`, `summary`, `abilities` (permisos que lo muestran; vacío = todos) y `routes` (pantallas cuyo botón de ayuda lo abre; gana la primera coincidencia).
- **Contenido.** `resources/guide/{sección}/{tema}.md` en español y con "tú", sin H1: `##` por tarea, pasos numerados que coinciden con los números de las capturas.
  - Capturas: `![Pie de figura](images/{tema}/{nn}-{nombre}.png)`, solas en su párrafo.
  - Enlaces a otro tema: `[texto](guide:{tema})`.
  - Notas: citas que empiezan con `**Importante:**`, `**Consejo:**` o `**Regla:**`.
  - Escrito para usuarios finales y administradores, organizado por tareas, no por menú.
- **Capturas automáticas.** `tests/Browser/Guide/*GuideTest.php` (pest-plugin-browser + Playwright, fuera de la suite por defecto) usa `guideShot($page, '{tema}/{archivo}', [[selector, 'n']], ?recorte)` de `tests/Pest.php`. Ese helper marca los elementos con recuadros numerados y guarda las imágenes en `resources/guide/images/`. Un selector inexistente hace fallar la prueba y avisa que la pantalla cambió.
  - Se usan datos ficticios: nunca datos reales.
  - Regenerar con `npm run build` y luego `composer guide:screenshots`.
- **Seguridad.** Las imágenes se sirven con `guide.image` (`GuideImageController`) solo a quien puede ver el tema. El Markdown se renderiza descartando HTML crudo.
- **Integridad.** `tests/Feature/Guide/GuideTest.php` verifica que cada tema tenga su Markdown, que existan sus capturas, sus enlaces `guide:`, sus rutas y sus permisos.
- **Al terminar una funcionalidad (regla 14):**
  1. Actualizar o crear el tema.
  2. Agregar las rutas nuevas a `routes` del tema.
  3. Agregar o ajustar las capturas en `tests/Browser/Guide/`.
  4. Regenerar las capturas y revisarlas.
  5. Correr `GuideTest`.
  - Un módulo nuevo agrega su sección en `GuideCatalog::SECTIONS`.

## Proveedores (formato FO-300-01)

Especificación: `doc/especificacion-proveedores.md`.

- **Tercero transversal, vinculación por proceso.** `Provider` (app/, único por `identification` sin DV) guarda solo el resumen de la versión vigente: `risk_level`, `is_pep` y `next_update_on`.
- **Versiones.** Cada vinculación o actualización anual es una `ProviderVersion`, una foto completa del formulario natural o jurídica, con hijas `contacts`, `shareholders`, `pepRelatives`, `documents` y `approvals`. Esas hijas son excepción a la regla 5: no llevan SoftDeletes ni Userstamps.
- **Vinculaciones.** `ProviderLinkage` tiene `scope` = `administrative` o `commercial`. Es la fila de los listados y se activa o inactiva por proceso, con condición y plazo de pago propios.
- **Pantallas.** `administrative-providers.*` (Modules/Administrative) y `commercial-providers.*` (Modules/Shopping) son SFC delgadas que extienden `App\Livewire\Providers\ProviderIndex` y `ProviderShow` e incluyen las vistas comunes `livewire.providers.provider-{index,show}-body`. El modal `ProviderFormModal` es compartido.
- **Escritura.** Punto único en `SaveProviderVersion` (calcula PEP y riesgo), `ProviderWorkflow` (enviar → verificar → cumplimiento si es PEP → aprobar, rechazar, devolver, actualizar, vincular) y `ManageProviderVersionLines`.
- **Regla anual.** Máximo una versión (solicitud) por proveedor y año calendario (`Provider::hasVersionInYear()`, en `startUpdate` y su Policy).
  - Una versión Rechazada no se reemplaza. Con `reverse` se usa "Corregir FO-300-01" (`ProviderWorkflow::correctRejected`, Policy `correctRejected`): vuelve a Borrador con el mismo número, descarta firmas y las vinculaciones Rechazadas pasan a Pendientes. El motivo del rechazo se conserva.
  - Vincular otro proceso en el año de un formato aprobado no abre versión: la vinculación queda Pendiente y su director la aprueba o rechaza sobre la vigente (`Provider::reviewVersion()`; `ProviderApprovalRules::pendingScopes()` con una versión Approved = vinculaciones Pendientes), sin cambiar `next_update_on`.
- **Años del FO-300-01.** El detalle reemplaza el historial por cajas por año ("Ver años"). `ProviderShow::years()` combina las versiones con `provider_legacy_updates` (`ProviderLegacyUpdate`: años del sistema anterior, solo fecha, para el futuro importador).
  - Elegir una caja (`selectVersion`) muestra ese año en solo lectura; editar y firmar solo se permite en la versión de trabajo (`defaultVersion`, `viewingDefault`).
  - "Editar datos básicos" aplica sin formato vigente, en trámite ni rechazado (los años históricos no lo impiden).
- **Autorización.** `ProviderLinkagePolicy` usa el slug del `scope` de cada vinculación.
- **Aprobación.** La define `ProviderApprovalRules`: el director del proceso Administrativo o Comercial, o Gerencia si es PEP, siempre con sus reemplazos vigentes. El oficial de cumplimiento es `CompanySettings::compliance_officer_id`.
- **Datos sensibles.** Información financiera, PEP y grupo de protección solo se muestran con `viewSensitive`.
- **Catálogos de referencia.** País, departamento y municipio apuntan a `geographic_locations` (ver "Ubicación geográfica"); los nombres de columna (`country_id`, `department_id`, `municipality_id`, `nationality_country_id`, `birth_*`) se conservan. `economic_activities` (CIIU) es de solo lectura y se carga con `ReferenceCatalogSeeder` desde `database/data/ciiu.csv`.
- **Proveedores básicos y tope.** El FO-300-01 solo aplica con órdenes (o acumulado mensual por proceso, con IVA, aprobadas + en revisión) mayores a 2 SMMLV. Por debajo, el proveedor es básico: los datos mínimos van en `providers` (`email`, `phone`, `address`, `municipality_id`, `contact_name`), se edita con `ProviderBasicModal` + `SaveProviderBasic` (Policy `updateBasic`) y queda activo sin aprobación. Solo Administrativo admite básicos (`ProviderScope::requiresForm()`: Comercial siempre exige el formato). `Provider::formStatus()` (`ProviderFormStatus`: Sin FO-300-01, En trámite, Vigente, Vencido). Regla de compra para las futuras OC: `App\Support\ProviderPurchaseRules::evaluate()`. Sobre el tope sin formato se permite con alerta y `markFormRequired()`; con el formato vencido o la vinculación inactiva se bloquea. El SMMLV por año y el múltiplo están en `CompanySettings::minimum_wages` y `provider_form_wage_multiplier`.
- **Carga inicial.** `ProviderSeeder` carga los 66 proveedores del sistema anterior más COODESCOR como básicos con vinculación administrativa activa. La plantilla `doc/carga-inicial-proveedores.xlsx` (117 proveedores) espera el importador.
- **Histórico 2024-2025 (carga única).** `ProviderHistorySeeder`, ejecutado a mano, carga desde `doc/proveedores/Administrativos {2024,2025}.xlsx` una versión Aprobada por año (`created_at` = fecha de actualización).
  - Es solo histórico: no se vuelve la vigente y el proveedor sigue básico (`defaultVersion` nulo, editable con `updateBasic`).
  - Crea como básicos los proveedores que faltaban. No carga socios (el archivo no trae el porcentaje) y es idempotente por año.
  - El archivo de 2026 sigue en revisión.
- **Componentes nuevos.** `<x-forms.checkbox>`, `model-modifier` en `<x-forms.select>` y el trait de enums `App\Enums\Concerns\HasLabelOptions` (`options()`, `optionsFor()`).

## Módulo Pharmacy — siguiente desarrollo

Fases: 1) scaffolding y permisos ✅ · 2) CRUD StockOut ✅ · 3) migraciones, enums y modelos de solicitudes (encabezado ✅) · 4) CRUD de solicitudes (encabezado ✅, ítems ✅ en `pendings.show`; pendiente: pantalla de asignación de cantidades/TB e importación Excel) · 5) reglas automáticas.

**Ítems implementados (`PendingItem`, tabla `pharmacy_pending_items`):** destino como `destination_code` (código de sede, sin FK; relación `destination()` por `code`). Permisos de `pendings` vía `PendingItemPolicy` (delega en `PendingPolicy::update`). Agotado se asigna en `SavePendingItem` y es definitivo (no se edita, solo se elimina). **Excepción a la regla 5:** `PendingItem` no usa `SoftDeletes` ni `Userstamps` (sin auditoría; la migración solo lleva `created_at`/`updated_at` comentados) y eliminar un ítem lo borra definitivamente. El buscador agrupa por `generic_name` + `generic_code` (no son 1:1 en el catálogo; si un nombre tiene varios códigos se muestra "· Cód. X"); `product_id` es el producto representativo (MIN(id) activo) y `product_name` guarda su `description`; la pantalla muestra `generic_name`. Un genérico no se repite por pendiente + C.C. El producto no se edita (pantalla y `SavePendingItem`): se elimina el ítem y se agrega el correcto. No se termina un pendiente sin ítems. Importación de ítems con `PendingItemImporter` (formato `doc/detallepedido.xlsx`): en `pendings.show` al pendiente abierto (`idpendiente` debe coincidir) y en el listado, con `PendingItemsImport` (componente de clase), a varios pendientes activos en un archivo, **solo Oficina Principal** (`pendings-import` + `worksAtHeadquarters()`; cada pendiente se valida con `PendingItemPolicy::create`). Buscador reutilizable `<x-forms.search-select>` (sin Select2).

**Encabezado implementado (`Pending`, tabla `pharmacy_pendings`, slug `pendings`):** columna `destination_id` (relación `destination()`); enums `PendingType` (PD/RQ/MI) y `PendingStatus` (`active`/`finished`) en `Modules/Pharmacy/app/Enums/`. Terminar: el solicitante (`user_id`) o permiso `pendings-process`; reabrir: `pendings-reverse`. Editar, eliminar y terminar exigen además que el destino sea la sede del usuario (`users.service_point_id`), salvo Oficina Principal (`ServicePoint::HEADQUARTERS_CODE = '1'`), que abarca todas; solo Oficina Principal elige un destino distinto a su sede (`PendingForm::destinationScopeRule()`, helper `User::worksAtHeadquarters()`). Las acciones masivas notifican los registros omitidos (`runBulkAction(..., $deniedMessage)`). Terminado no se edita ni elimina (`PendingPolicy`). Preferencia de visualización en el perfil: `users.pending_service_point_id` (null = todas las sedes), aplicada con el scope `Pending::visibleTo()`; es preferencia, no seguridad.

**Gestión de Oficina Principal (Farmacia → Gestión, línea de permisos `inventory-balances`):** `InventoryBalance` (tabla `pharmacy_inventory_balances`, sin SoftDeletes/Userstamps: cada carga la reemplaza completa) se llena con `ImportInventoryBalances` desde el archivo del ERP (`ispl`: `ProductId` + `Disponible` sumados por ítem; homologa por `shopping_products.item`, los no homologados se guardan sin `product_id`). Saldo por código genérico = Σ(`available_quantity − assigned_quantity`). `AssignSentQuantity` es el punto único de asignación (`assign` suma al enviado y descuenta saldo; `correct` fija el total y devuelve saldo solo si `date_send` ≥ carga vigente; `markPending`). Pantallas: `inventory-balances.index` (read/import), `inventory-balances.assignment` (read/process; ítems `PendingItem::assignable()` = Sin revisión/Pendiente/Parcial de pendientes Terminados), `inventory-balances.transfer-documents` (Asignación de TB: `AssignTransferDocument` asigna `doc_number` numérico solo a ítems Cumplido y de un mismo C.C.; asignar con `process`, cambiar o quitar un TB existente con `reverse`) y `pendings.items` (consulta de todos los ítems con `pendings-read`; "Corregir enviado" con `inventory-balances-reverse`). `number_day` = días calendario entre `created_at` del ítem y `date_send`, solo al quedar Cumplido. Consolidado de pendientes (`inventory-balances.consolidation`, read): `PendingConsolidation` cruza genérico × sede con lo que falta enviar (`quantity − send_quantity`) de ítems Pendiente/Parcial de pendientes Terminados; columnas solo de sedes con cantidades, encabezadas por `service_points.abbreviation` (obligatoria y única); Excel con formato propio en `PendingConsolidationExport`. `ImportsRows::authorizeImport()` es sobrescribible; `SheetRowsImport::read()` lee una hoja.

**Productos nuevos (`NewProduct`, tabla `pharmacy_new_products`, slug `new-products`):** proceso independiente de los pendientes para productos sin codificación (Farmacia → Productos nuevos). Solicitudes (`new-products.index`): nombre, marca opcional, cantidad y `destination_code`; la farmacia edita/elimina solo Sin asignar sin ítem y de su sede (Oficina Principal todas). `SaveNewProduct` bloquea por `normalized_name` (sin tildes, mayúsculas, espacios simples) si ya hay una solicitud asociada a un ítem ("solicítelo en Pendientes") o una abierta en la misma farmacia; el modal sugiere coincidencias mientras se escribe. Gestión (`new-products.management`, `NewProductPolicy::manage` = read + `worksAtHeadquarters()`): `ManageNewProducts` asocia el ítem (obligatorio antes de asignar), asigna unidades manualmente **sin descontar `InventoryBalance`** (saldo solo informativo, sin superar lo que falta), TB solo a Cumplidas de un mismo C.C. y rechaza Sin asignar (`process`); corrige enviado, desasocia, cambia/quita TB y reabre Rechazadas (`reverse`). Estados `NewProductStatus`: Sin asignar, Parcial, Cumplido, Rechazado. El consolidado muestra al lado la tabla de productos nuevos (`NewProductConsolidation`) y el Excel agrega la hoja PRODUCTOS NUEVOS (`PendingConsolidationWorkbook`).

### Tablas

- `pharmacy_stock_outs` (✅): `generic_name, product_name, brand, classification, circular_number, circular_date, generic_code, status`. Índice `(generic_code, status)`.
- `pharmacy_pending` (encabezado):
  - `type`: enum `PD` = Pedido, `RQ` = Requisiciones, `MI` = Mensaje Interno.
  - `category`: texto desde Characteristic `pending_category`.
  - `destination`: FK obligatoria a `service_points` (código 1 = Oficina Principal cuando abarca varias sedes).
  - `reason`, `duration`, `observations`.
  - `eps`, `contracting_modality`, `invoicing_method`: **texto plano, no FK** (se elige desde `CharacteristicOption::forCharacteristicCode()` y se guarda el texto).
  - `user_id`: FK al solicitante. `manager`: texto libre (persona externa que autoriza).
  - `status`: enum Activo/Terminado. Terminado bloquea la edición del lado de farmacia y la habilita para oficina principal.
- `pharmacy_pending_items` (detalle):
  - `pending_id`: FK al encabezado (nombre decidido).
  - `product_id`: FK a `shopping_products`. Snapshot desnormalizado: `product_name, generic_code, generic_name, brand`.
  - `destination`: FK obligatoria a `service_points` (fuente real de verdad por línea).
  - `quantity`, `send_quantity`, `date_send`, `observations`.
  - `doc_number`: texto libre (número "TB" del ERP u orden de compra).
  - `number_day`: calculado por el sistema = `date_send − created_at`, **solo al pasar a Cumplido**.
  - `circular_number`: texto plano (coincide con `StockOut.circular_number`, no FK).
  - `status`: enum Sin revisión, Cumplido, Parcial, Pendiente, Agotado. Agotado puede volver a Sin revisión solo si su StockOut ya no está activo.

### Reglas de negocio (fase 5)

- Al guardar un ítem: buscar StockOut **activo por `generic_code`**; si existe, asignar `circular_number` y `status = Agotado`.
- Acción masiva "Asignar TB": seleccionar ítems de distintas solicitudes y asignarles un `doc_number` común (patrón `HasBulkRowActions`).
- Vista de trabajo de oficina principal: todos los ítems en Sin revisión/Parcial/Pendiente de todas las sedes (pantalla propia, sin tabla nueva).
- Diferido: `generic_code` en la homologación de Producto para cargas de stock del ERP.
