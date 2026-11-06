<?php

declare(strict_types=1);

namespace App\Livewire\Forms;

use App\Enums\ProviderScope;
use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use App\Models\ProviderLinkage;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Condiciones que negocia cada proceso con el proveedor: segmento (solo comercial), condición y plazo de pago.
 */
final class ProviderLinkageTermsForm extends Form
{
    use SanitizesPayload;

    public ?string $commercial_segment = null;

    public ?string $payment_condition = null;

    public ?string $payment_term = null;

    // El segmento comercial solo aplica a la vinculación comercial.
    public bool $commercial = false;

    public function setLinkage(ProviderLinkage $linkage): void
    {
        $this->commercial = $linkage->scope === ProviderScope::Commercial;
        $this->fill($linkage->only(['commercial_segment', 'payment_condition', 'payment_term']));
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        return [
            'commercial_segment' => ['nullable', Rule::in($this->commercial ? $this->options(ProviderLinkage::COMMERCIAL_SEGMENT_CHARACTERISTIC_CODE) : [])],
            'payment_condition' => ['nullable', Rule::in($this->options(ProviderLinkage::PAYMENT_CONDITION_CHARACTERISTIC_CODE))],
            'payment_term' => ['nullable', Rule::in($this->options(ProviderLinkage::PAYMENT_TERM_CHARACTERISTIC_CODE))],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return ['commercial_segment' => 'segmento comercial', 'payment_condition' => 'condición de pago', 'payment_term' => 'plazo de pago'];
    }

    /** @return array<string, ?string> */
    public function payload(): array
    {
        $data = $this->sanitize($this->validate());

        return [
            'commercial_segment' => $data['commercial_segment'] ?? null,
            'payment_condition' => $data['payment_condition'] ?? null,
            'payment_term' => $data['payment_term'] ?? null,
        ];
    }

    /** @return list<string> */
    private function options(string $code): array
    {
        return array_values(CharacteristicOption::forCharacteristicCode($code)->keys()->map(fn ($name) => (string) $name)->values()->all());
    }
}
