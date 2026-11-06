<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\GuideCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Entrega las capturas de la guía (resources/guide/images/{tema}/{archivo}.png). Muestran la
 * interfaz con datos de prueba, pero solo las ve quien puede leer el tema al que pertenecen.
 */
final class GuideImageController extends Controller
{
    public function __invoke(Request $request, string $path): BinaryFileResponse
    {
        [$slug, $file] = explode('/', $path, 2);

        abort_unless($request->user() !== null && GuideCatalog::canView($request->user(), $slug), 404);

        $fullPath = GuideCatalog::imagesPath($slug).'/'.$file;
        abort_unless(is_file($fullPath), 404);

        return response()->file($fullPath, ['Cache-Control' => 'private, max-age=86400']);
    }
}
