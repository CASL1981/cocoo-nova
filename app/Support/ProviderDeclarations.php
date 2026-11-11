<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\PersonType;

/**
 * Textos de la sección "Declaraciones y autorizaciones" del FO-300-01, tomados de los formatos
 * físicos (doc/FO-300-01 ... Persona Jurídica / Persona Natural). Se muestran completos al pasar
 * el mouse por el ícono de cada declaración (formulario y detalle) y en el PDF.
 *
 * Diferencias por tipo de persona: el correo de tratamiento de datos del formato natural es
 * contratacion@ y el del jurídico thcalidad@; los mecanismos electrónicos solo aplican a natural.
 */
final class ProviderDeclarations
{
    private const TRUTHFULNESS = 'Certifico que toda la información suministrada en este formato es verídica y autorizo a la COOPERATIVA DE ENTIDADES DE SALUD DE CÓRDOBA - COODESCOR para que la verifique ante cualquier persona natural o jurídica, pública o privada, sin limitación alguna; y declaro que estoy informado de mi obligación de actualizar o confirmar la información suministrada una vez al año o cuando lo amerite.';

    private const DATA_PROCESSING = 'De acuerdo con la Ley Estatutaria 1581 de 2012 sobre protección de datos personales y normas que la reglamenten, autorizo, como titular de los datos, que éstos sean incorporados en una base de datos responsabilidad de la COOPERATIVA DE ENTIDADES DE SALUD DE CÓRDOBA - COODESCOR, siendo tratados con la finalidad de mantener, desarrollar y controlar la presente relación contractual y con base a la política de tratamiento de datos personales, a la cual podré tener acceso en la página web www.coodescor.org.co o a través del correo electrónico :email. Asimismo, declaro haber sido informado de que puedo ejercitar los derechos de acceso, corrección, supresión, revocación o reclamo por infracción sobre mis datos, mediante escrito dirigido a la COOPERATIVA DE ENTIDADES DE SALUD DE CÓRDOBA - COODESCOR a la dirección de correo electrónico :email indicando en el asunto el derecho que desea ejercitar, o mediante correo ordinario remitido a la dirección CL 28A No. 23-03 Barrio San José, Montería, Córdoba.';

    private const INFORMATION_RECEIVED = 'Declaro que he recibido la información sobre las políticas comerciales, sobre los requisitos de selección, contratación, seguimiento, evaluación y calidad de Coodescor. Manifiesto conocer los procedimientos de operación y facturación. Adicionalmente, estoy enterado de mi obligación de actualizar anualmente la información que solicite la entidad y dar cumplimiento a los estándares exigidos para mantener el vínculo comercial.';

    private const FUNDS_ORIGIN = 'Declaro que mis ingresos y bienes provienen del desarrollo de mi actividad económica principal. Declaro que el origen de los recursos y demás activos proceden del giro ordinario de actividades lícitas.';

    private const RISK_BUREAU = 'Autorizo a la cooperativa para que consulte y reporte información a las centrales de riesgo y el uso de bases de consulta alternativas.';

    // No figura como texto en el formato físico: redacción propuesta a partir de la Ley 527 de 1999.
    private const ELECTRONIC_MEANS = 'Acepto que este formato y sus anexos se diligencien, firmen, envíen y verifiquen por medios electrónicos, con la misma validez jurídica de los documentos físicos, de acuerdo con la Ley 527 de 1999.';

    /**
     * Declaraciones que aplican al tipo de persona, por campo de ProviderVersion.
     *
     * @return array<string, array{label: string, text: string}>
     */
    public static function for(PersonType $personType): array
    {
        $natural = $personType === PersonType::Natural;
        $email = $natural ? 'contratacion@coodescor.org.co' : 'thcalidad@coodescor.org.co';

        $declarations = [
            'accepts_truthfulness' => ['label' => 'Declaración de veracidad', 'text' => self::TRUTHFULNESS],
            'accepts_data_processing' => ['label' => 'Consentimiento para el tratamiento de datos personales', 'text' => str_replace(':email', $email, self::DATA_PROCESSING)],
            'accepts_information_received' => ['label' => 'Declaración de información recibida por COODESCOR', 'text' => self::INFORMATION_RECEIVED],
            'accepts_funds_origin' => ['label' => 'Declaración de origen de fondos', 'text' => self::FUNDS_ORIGIN],
            'accepts_risk_bureau' => ['label' => 'Autoriza la consulta y reporte en centrales de riesgo', 'text' => self::RISK_BUREAU],
        ];

        if ($natural) {
            $declarations['accepts_electronic_means'] = ['label' => 'Acepta mecanismos electrónicos de verificación (Ley 527 de 1999)', 'text' => self::ELECTRONIC_MEANS];
        }

        return $declarations;
    }
}
