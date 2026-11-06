<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Guía de usuario: fuente única de verdad de sus secciones y temas.
 *
 * Cada tema es un archivo Markdown en resources/guide/{sección}/{tema}.md con sus capturas en
 * resources/guide/images/{tema}/. Un tema solo se muestra a quien tenga alguno de los permisos de
 * `abilities` (vacío = todos los usuarios) y se abre desde el botón de ayuda de las pantallas
 * listadas en `routes`. Toda funcionalidad nueva o modificada debe actualizar su tema.
 */
final class GuideCatalog
{
    /** @var array<string, array{label: string, icon: string, description: string}> */
    public const SECTIONS = [
        'start' => [
            'label' => 'Primeros pasos',
            'icon' => 'bi-signpost-2',
            'description' => 'Ingreso, navegación y uso de los listados comunes a toda la aplicación.',
        ],
        'providers' => [
            'label' => 'Proveedores',
            'icon' => 'bi-truck',
            'description' => 'Registro, formato FO-300-01, aprobación y actualización anual.',
        ],
        'hr' => [
            'label' => 'Talento Humano',
            'icon' => 'bi-person-badge',
            'description' => 'Listas propias del área, como los cargos del organigrama.',
        ],
        'admin' => [
            'label' => 'Administración',
            'icon' => 'bi-gear',
            'description' => 'Usuarios, roles y permisos, procesos y configuración de la empresa.',
        ],
    ];

    /**
     * Temas en el orden en que se leen. La primera coincidencia de `routes` es la que abre el botón de ayuda.
     *
     * @var array<string, array{section: string, title: string, summary: string, abilities: list<string>, routes: list<string>}>
     */
    public const TOPICS = [
        'getting-started' => [
            'section' => 'start',
            'title' => 'Ingreso y navegación',
            'summary' => 'Cómo ingresar, cambiar la clave temporal, moverse por los menús y pedir ayuda.',
            'abilities' => [],
            'routes' => ['dashboard', 'password.force-change', 'profile.edit', 'guide.index'],
        ],
        'standard-lists' => [
            'section' => 'start',
            'title' => 'Cómo usar los listados',
            'summary' => 'Buscar, filtrar, ordenar, seleccionar registros, exportar y consultar la auditoría.',
            'abilities' => [],
            'routes' => [],
        ],
        'providers-overview' => [
            'section' => 'providers',
            'title' => 'Conceptos: proveedor básico y FO-300-01',
            'summary' => 'Tipos de proveedor, cuándo se exige el formato, estados, riesgo y actualización anual.',
            'abilities' => ['administrative-providers-read', 'commercial-providers-read'],
            'routes' => ['administrative-providers.index', 'commercial-providers.index'],
        ],
        'providers-basic' => [
            'section' => 'providers',
            'title' => 'Registrar un proveedor básico',
            'summary' => 'Registro y edición de proveedores administrativos con compras hasta el tope.',
            'abilities' => ['administrative-providers-read'],
            'routes' => [],
        ],
        'providers-form' => [
            'section' => 'providers',
            'title' => 'Diligenciar el FO-300-01',
            'summary' => 'Borrador del formato: datos, contactos, accionistas, PEP, anexos y envío a revisión.',
            'abilities' => ['administrative-providers-read', 'commercial-providers-read'],
            'routes' => ['administrative-providers.show', 'commercial-providers.show'],
        ],
        'providers-review' => [
            'section' => 'providers',
            'title' => 'Verificar, aprobar o rechazar',
            'summary' => 'Revisión del formato, oficial de cumplimiento, aprobación por proceso y PDF.',
            'abilities' => ['administrative-providers-read', 'commercial-providers-read'],
            'routes' => [],
        ],
        'providers-maintenance' => [
            'section' => 'providers',
            'title' => 'Actualización anual, vinculación y estado',
            'summary' => 'Actualizar el formato, vincular un proveedor a otro proceso y activarlo o inactivarlo.',
            'abilities' => ['administrative-providers-read', 'commercial-providers-read'],
            'routes' => [],
        ],
        'hr-characteristics' => [
            'section' => 'hr',
            'title' => 'Características y cargos',
            'summary' => 'Listas de Talento Humano y los cargos del organigrama con su proceso y jefe inmediato.',
            'abilities' => ['hr-characteristics-read'],
            'routes' => ['hr-characteristics.index'],
        ],
        'admin-users' => [
            'section' => 'admin',
            'title' => 'Usuarios',
            'summary' => 'Crear usuarios, asignar rol y sede, clave temporal y activar o inactivar.',
            'abilities' => ['users-read'],
            'routes' => ['users.index'],
        ],
        'admin-roles' => [
            'section' => 'admin',
            'title' => 'Roles y permisos',
            'summary' => 'Crear roles y asignar las líneas de permisos de cada módulo.',
            'abilities' => ['roles-read'],
            'routes' => ['roles.index'],
        ],
        'admin-processes' => [
            'section' => 'admin',
            'title' => 'Procesos y reemplazos de director',
            'summary' => 'Directores de cada proceso y reemplazos temporales que aprueban en su nombre.',
            'abilities' => ['processes-read', 'process-substitutions-read'],
            'routes' => ['processes.index', 'process-substitutions.index'],
        ],
        'admin-catalogs' => [
            'section' => 'admin',
            'title' => 'Sedes y características',
            'summary' => 'Centros de costos y listas de opciones usadas en los formularios.',
            'abilities' => ['service-points-read', 'characteristics-read'],
            'routes' => ['service-points.index', 'characteristics.index'],
        ],
        'admin-geographic-locations' => [
            'section' => 'admin',
            'title' => 'Ubicación geográfica',
            'summary' => 'Países, departamentos y ciudades que ofrecen los formularios, en una sola tabla.',
            'abilities' => ['geographic-locations-read'],
            'routes' => ['geographic-locations.index'],
        ],
        'admin-company-settings' => [
            'section' => 'admin',
            'title' => 'Datos de la empresa',
            'summary' => 'Datos para formatos y PDF, SMMLV por año, tope del FO-300-01 y oficial de cumplimiento.',
            'abilities' => ['company-settings-read'],
            'routes' => ['company-settings.index'],
        ],
    ];

    /** Indica si el usuario puede ver el tema (inexistente = no). */
    public static function canView(User $user, string $slug): bool
    {
        $topic = self::TOPICS[$slug] ?? null;

        return $topic !== null && ($topic['abilities'] === [] || $user->canAny($topic['abilities']));
    }

    /**
     * Temas visibles para el usuario, agrupados por sección y en el orden del catálogo.
     *
     * @return array<string, array<string, array{section: string, title: string, summary: string, abilities: list<string>, routes: list<string>}>>
     */
    public static function visibleTo(User $user): array
    {
        $grouped = [];

        foreach (self::TOPICS as $slug => $topic) {
            if (self::canView($user, $slug)) {
                $grouped[$topic['section']][$slug] = $topic;
            }
        }

        return array_intersect_key(array_replace(array_fill_keys(array_keys(self::SECTIONS), []), $grouped), $grouped);
    }

    /** Tema que explica la pantalla indicada (botón de ayuda), o null si no hay uno visible. */
    public static function forRoute(User $user, ?string $routeName): ?string
    {
        foreach (self::TOPICS as $slug => $topic) {
            if ($routeName !== null && in_array($routeName, $topic['routes'], true) && self::canView($user, $slug)) {
                return $slug;
            }
        }

        return null;
    }

    /** Ruta del archivo Markdown del tema. */
    public static function path(string $slug): string
    {
        return resource_path('guide/'.self::TOPICS[$slug]['section'].'/'.$slug.'.md');
    }

    /** Ruta de la carpeta de capturas del tema. */
    public static function imagesPath(string $slug): string
    {
        return resource_path('guide/images/'.$slug);
    }

    /** Markdown del tema; vacío si aún no se ha escrito. */
    public static function markdown(string $slug): string
    {
        $path = self::path($slug);

        return is_file($path) ? (string) file_get_contents($path) : '';
    }

    /**
     * HTML del tema. Convenciones del Markdown:
     * - Capturas: ![Descripción](images/{tema}/{archivo}.png); la descripción se muestra como pie de figura.
     * - Enlaces a otro tema: [texto](guide:{tema}).
     * - Notas: citas que empiezan con **Importante:**, **Consejo:** o **Regla:**.
     * El Markdown es del repositorio, aun así se descarta el HTML crudo y los enlaces inseguros.
     */
    public static function render(string $slug): string
    {
        $html = Str::markdown(self::markdown($slug), ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        $html = (string) preg_replace_callback('/href="guide:([a-z0-9-]+)"/', fn (array $m): string => 'href="'.route('guide.index', ['topic' => $m[1]]).'"', $html);

        // Captura sola en su párrafo → figura ampliable con pie.
        $html = (string) preg_replace_callback(
            '#<p><img src="images/([a-z0-9-]+/[a-z0-9-]+\.png)" alt="([^"]*)" /></p>#',
            fn (array $m): string => sprintf(
                '<figure class="guide-figure"><a href="%1$s" target="_blank" rel="noopener"><img src="%1$s" alt="%2$s" loading="lazy"></a><figcaption>%2$s</figcaption></figure>',
                route('guide.image', ['path' => $m[1]]),
                $m[2],
            ),
            $html,
        );

        // Notas destacadas según su etiqueta inicial.
        return (string) preg_replace_callback(
            '#<blockquote>\s*<p><strong>(Importante|Consejo|Regla):</strong>#u',
            fn (array $m): string => '<blockquote class="guide-note guide-note-'.Str::lower(Str::ascii($m[1])).'"><p><strong>'.$m[1].':</strong>',
            $html,
        );
    }

    /** Texto plano del tema, sin tildes y en minúsculas, para la búsqueda. */
    public static function searchableText(string $slug): string
    {
        $topic = self::TOPICS[$slug];

        return self::normalize($topic['title'].' '.$topic['summary'].' '.self::markdown($slug));
    }

    /** Normaliza un texto para comparar sin tildes ni mayúsculas. */
    public static function normalize(string $text): string
    {
        return Str::lower(Str::ascii($text));
    }
}
