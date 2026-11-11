<?php

use App\Enums\PersonType;
use App\Support\ProviderDeclarations;

it('entrega el texto completo de cada declaración según el tipo de persona', function (): void {
    $legal = ProviderDeclarations::for(PersonType::Legal);
    $natural = ProviderDeclarations::for(PersonType::Natural);

    // Los mecanismos electrónicos solo aplican a persona natural.
    expect(array_keys($legal))->not->toContain('accepts_electronic_means')
        ->and(array_keys($natural))->toContain('accepts_electronic_means')
        // Cada formato físico indica su propio correo para el tratamiento de datos.
        ->and($legal['accepts_data_processing']['text'])->toContain('thcalidad@coodescor.org.co')->not->toContain(':email')
        ->and($natural['accepts_data_processing']['text'])->toContain('contratacion@coodescor.org.co')
        ->and($legal['accepts_truthfulness']['text'])->toStartWith('Certifico que toda la información');
});
