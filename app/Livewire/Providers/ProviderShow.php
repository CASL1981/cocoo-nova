<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Actions\Providers\ManageProviderVersionLines;
use App\Actions\Providers\ProviderWorkflow;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Livewire\Concerns\Notifies;
use App\Livewire\Forms\ProviderDocumentForm;
use App\Livewire\Forms\ProviderLinkageTermsForm;
use App\Livewire\Forms\ProviderPepRelativeForm;
use App\Livewire\Forms\ProviderShareholderForm;
use App\Models\CharacteristicOption;
use App\Models\Process;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\ProviderVersionDocument;
use App\Models\User;
use App\Settings\CompanySettings;
use App\Support\ProviderApprovalRules;
use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Detalle de un proveedor desde la vinculación de un proceso.
 *
 * Muestra la versión en trámite (o la vigente, o la última rechazada) del formato FO-300-01 con
 * sus líneas (accionistas, familiares PEP y anexos) y el panel de revisión con las firmas. Los
 * años del formato aparecen como cajas: al elegir una se consulta la versión de ese año (solo
 * lectura, con su PDF). Lo extienden las páginas SFC de cada módulo, que fijan scope().
 *
 * @property-read ProviderLinkage $freshLinkage
 * @property-read ProviderVersion|null $defaultVersion
 * @property-read ProviderVersion|null $version
 * @property-read Collection<int, ProviderVersion> $versions
 * @property-read list<array{year: int, version: ProviderVersion|null, date: ?string}> $years
 * @property-read bool $viewingDefault
 * @property-read bool $canViewSensitive
 */
abstract class ProviderShow extends Component
{
    use Notifies, WithFileUploads;

    #[Locked]
    public ProviderLinkage $linkage;

    public ProviderShareholderForm $shareholderForm;

    public ProviderPepRelativeForm $relativeForm;

    public ProviderDocumentForm $documentForm;

    public ProviderLinkageTermsForm $termsForm;

    // Observaciones de la firma (verificar, cumplimiento, aprobar) o motivo del rechazo.
    public string $reviewNotes = '';

    // Registro de la firma física de Gerencia (PEP): modal con la fecha en que firmó.
    public bool $showManagementApproval = false;

    public string $managementSignedOn = '';

    // Modal de condiciones del proceso.
    public bool $showTerms = false;

    // Versión elegida en las cajas por año (null = la versión de trabajo). Se valida al consultarla.
    public ?int $selectedVersionId = null;

    /** Tipo de proveedor que gestiona la pantalla. */
    abstract protected function scope(): ProviderScope;

    public function mount(ProviderLinkage $linkage): void
    {
        // Cada módulo solo abre las vinculaciones de su propio tipo.
        abort_unless($linkage->scope === $this->scope(), 404);
        Gate::authorize('view', $linkage);

        $this->linkage = $linkage;

        // Sin formato de trabajo (proveedor básico con histórico): abre el año más reciente en solo lectura.
        if ($this->defaultVersion === null) {
            $this->selectedVersionId = $this->versions->first()?->id;
        }
    }

    public function providerScope(): ProviderScope
    {
        return $this->scope();
    }

    /** Vinculación con el proveedor, su versión en trámite y la vigente (se recarga tras cada acción). */
    #[Computed]
    public function freshLinkage(): ProviderLinkage
    {
        return ProviderLinkage::withTrashed()->with([
            'provider.openVersion', 'provider.currentVersion', 'provider.latestVersion', 'provider.linkages',
            'provider.municipality.parent', 'provider.legacyUpdates',
        ])->findOrFail($this->linkage->id);
    }

    /**
     * Versión de trabajo: la que se firma (en trámite, o la vigente si una vinculación espera), si no
     * la vigente y, sin vigente, la última si fue rechazada (muestra su motivo y se corrige). Un
     * proveedor básico con formatos de años anteriores (histórico cargado) no tiene versión de
     * trabajo: se ven sus datos básicos y los años se consultan en "Ver años".
     */
    #[Computed]
    public function defaultVersion(): ?ProviderVersion
    {
        $provider = $this->freshLinkage->provider;
        $rejected = $provider->latestVersion?->status === ProviderVersionStatus::Rejected ? $provider->latestVersion : null;

        return $provider->reviewVersion() ?? $provider->currentVersion ?? $rejected;
    }

    /** Se consulta la versión de trabajo (no la de otro año): solo en ella se edita y se firma. */
    #[Computed]
    public function viewingDefault(): bool
    {
        return $this->selectedVersionId === null || $this->selectedVersionId === $this->defaultVersion?->id;
    }

    /** Versión que se muestra: la elegida en las cajas por año o la de trabajo. */
    #[Computed]
    public function version(): ?ProviderVersion
    {
        $version = $this->viewingDefault
            ? $this->defaultVersion
            : $this->freshLinkage->provider->versions()->find($this->selectedVersionId);

        return $version?->load([
            'contacts', 'shareholders', 'pepRelatives', 'documents.uploader:id,name,lastname', 'approvals.user:id,name,lastname',
            'department', 'municipality', 'country', 'nationality', 'birthDepartment', 'birthMunicipality',
            'economicActivity', 'employerEconomicActivity', 'submitter:id,name,lastname', 'verifier:id,name,lastname',
            'complianceOfficer:id,name,lastname', 'rejecter:id,name,lastname', 'managementSigner:id,name,lastname',
        ]);
    }

    /** @return Collection<int, ProviderVersion> */
    #[Computed]
    public function versions(): Collection
    {
        return $this->freshLinkage->provider->versions()->orderByDesc('number')
            ->get(['id', 'provider_id', 'number', 'request_type', 'status', 'risk_level', 'is_pep', 'created_at', 'approved_at', 'rejected_at']);
    }

    /**
     * Cajas por año, de la más reciente a la más antigua: una por versión del formato y una
     * informativa por cada actualización del sistema anterior (sin formato digital).
     *
     * @return list<array{year: int, version: ProviderVersion|null, date: ?string}>
     */
    #[Computed]
    public function years(): array
    {
        $boxes = [];

        foreach ($this->versions as $version) {
            $boxes[] = [
                'year' => $version->created_at->year,
                'version' => $version,
                'date' => ($version->approved_at ?? $version->rejected_at ?? $version->created_at)->format('Y/m/d'),
            ];
        }

        $versionYears = array_column($boxes, 'year');

        foreach ($this->freshLinkage->provider->legacyUpdates as $update) {
            if (! in_array($update->year, $versionYears, true)) {
                $boxes[] = ['year' => $update->year, 'version' => null, 'date' => $update->updated_on?->format('Y/m/d')];
            }
        }

        // Año descendente; en un mismo año (datos anteriores a la regla anual), la versión más reciente primero.
        usort($boxes, fn (array $a, array $b): int => [$b['year'], $b['version']->number ?? 0] <=> [$a['year'], $a['version']->number ?? 0]);

        return $boxes;
    }

    /** Consulta el formato de un año (cajas por año). */
    public function selectVersion(int $versionId): void
    {
        $exists = $this->freshLinkage->provider->versions()->whereKey($versionId)->exists();
        $this->selectedVersionId = $exists ? $versionId : null;
        $this->refreshAll();
    }

    /** Vuelve a la versión de trabajo. */
    public function showDefaultVersion(): void
    {
        $this->selectedVersionId = null;
        $this->refreshAll();
    }

    /** El usuario puede ver los datos sensibles (información financiera, PEP, grupo de protección). */
    #[Computed]
    public function canViewSensitive(): bool
    {
        return Gate::allows('viewSensitive', $this->freshLinkage);
    }

    /** El usuario puede modificar las líneas: consulta la versión de trabajo, está en Borrador y tiene permiso de editar. */
    #[Computed]
    public function canEditDraft(): bool
    {
        return $this->viewingDefault && Gate::allows('update', $this->freshLinkage);
    }

    /**
     * Firmas pendientes por proceso, con el proceso que debe aprobar cada una y quiénes pueden
     * firmar hoy (director y reemplazos). Sin firmantes, la pantalla avisa que falta configurarlo.
     *
     * @return array<string, array{process: string, approvers: list<string>}>
     */
    #[Computed]
    public function pendingApprovals(): array
    {
        $version = $this->version;
        $signable = $version !== null && $this->viewingDefault
            && ($version->status->isOpen() || $version->status === ProviderVersionStatus::Approved);

        if (! $signable) {
            return [];
        }

        $rules = app(ProviderApprovalRules::class);

        $processes = collect($rules->pendingScopes($version))
            ->mapWithKeys(fn (ProviderScope $scope) => [$scope->label() => $rules->approverProcessName($version, $scope)]);

        // PEP con la Gerencia aprobando en el sistema: también espera esa firma.
        if ($rules->requiresManagementInSystem($version) && ! $version->hasManagementApproval() && $version->status->isOpen()) {
            $processes->put('de Gerencia (PEP)', Process::GENERAL_MANAGEMENT);
        }

        return $processes
            ->map(fn (string $process): array => ['process' => $process, 'approvers' => $rules->approverNames($process)])
            ->all();
    }

    /** La Gerencia aprueba en físico: su firma se muestra como registro informativo. */
    #[Computed]
    public function managementInPhysical(): bool
    {
        $version = $this->version;

        return $version !== null && $version->is_pep && ! app(ProviderApprovalRules::class)->requiresManagementInSystem($version);
    }

    /** Oficial de cumplimiento configurado (para informar a quién le corresponde). */
    #[Computed]
    public function complianceOfficerName(): ?string
    {
        $id = app(CompanySettings::class)->compliance_officer_id;

        return $id ? User::find($id, ['id', 'name', 'lastname'])?->fullName() : null;
    }

    /**
     * Listas de Características usadas en las líneas y las condiciones.
     *
     * @return array<string, array<string, string>>
     */
    #[Computed]
    public function characteristicOptions(): array
    {
        return collect([
            'relationship' => ProviderVersion::RELATIONSHIP_CHARACTERISTIC_CODE,
            'commercial_segment' => ProviderLinkage::COMMERCIAL_SEGMENT_CHARACTERISTIC_CODE,
            'payment_condition' => ProviderLinkage::PAYMENT_CONDITION_CHARACTERISTIC_CODE,
            'payment_term' => ProviderLinkage::PAYMENT_TERM_CHARACTERISTIC_CODE,
            'document_type' => ProviderVersionDocument::TYPE_CHARACTERISTIC_CODE,
        ])->map(fn (string $code) => CharacteristicOption::forCharacteristicCode($code)->all())->all();
    }

    #[On('provider-saved')]
    public function refreshProvider(): void
    {
        $this->refreshAll();
    }

    // ── Flujo de revisión ────────────────────────────────────────────────────────

    public function editDraft(): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $this->dispatch('open-provider-edit', linkageId: $this->linkage->id);
    }

    public function submit(ProviderWorkflow $workflow): void
    {
        Gate::authorize('submit', $this->freshLinkage);
        $this->runWorkflow(fn () => $workflow->submit($this->openVersion(), $this->actor()), 'Versión enviada a verificación');
    }

    public function verify(ProviderWorkflow $workflow): void
    {
        Gate::authorize('verify', $this->freshLinkage);
        $this->runWorkflow(fn () => $workflow->verify($this->openVersion(), $this->actor(), $this->notes()), 'Información verificada');
    }

    public function certifyCompliance(ProviderWorkflow $workflow): void
    {
        Gate::authorize('certifyCompliance', $this->freshLinkage);
        $this->runWorkflow(fn () => $workflow->certifyCompliance($this->openVersion(), $this->actor(), $this->notes()), 'Revisión de cumplimiento registrada');
    }

    public function approve(ProviderWorkflow $workflow): void
    {
        Gate::authorize('approve', $this->freshLinkage);
        $this->runWorkflow(function () use ($workflow): void {
            $scopes = $workflow->approve($this->reviewVersion(), $this->actor(), $this->notes());
            $this->toastSuccess('Aprobado: '.collect($scopes)->map(fn (ProviderScope $scope) => $scope->label())->join(', '));
        });
    }

    /** Observación del oficial de cumplimiento a un proveedor que no es PEP. */
    public function addComplianceObservation(ProviderWorkflow $workflow): void
    {
        Gate::authorize('addComplianceObservation', $this->freshLinkage);

        $this->validate(['reviewNotes' => ['required', 'string', 'min:5', 'max:2000']], [], ['reviewNotes' => 'observación']);

        $this->runWorkflow(fn () => $workflow->addComplianceObservation($this->openVersion(), $this->actor(), trim($this->reviewNotes)), 'Observación de cumplimiento registrada');
    }

    /** Aprobación de Gerencia en el sistema (PEP con el modo "en el sistema"). */
    public function approveAsManagement(ProviderWorkflow $workflow): void
    {
        Gate::authorize('approveAsManagement', $this->freshLinkage);
        $this->runWorkflow(fn () => $workflow->approveAsManagement($this->openVersion(), $this->actor(), $this->notes()), 'Aprobado por Gerencia');
    }

    public function openManagementApproval(): void
    {
        Gate::authorize('registerManagementApproval', $this->freshLinkage);
        $this->resetValidation('managementSignedOn');
        $this->managementSignedOn = today()->toDateString();
        $this->showManagementApproval = true;
    }

    /** Registra la firma física de Gerencia (informativa; no cambia el estado de la versión). */
    public function registerManagementApproval(ProviderWorkflow $workflow): void
    {
        Gate::authorize('registerManagementApproval', $this->freshLinkage);

        $this->validate(['managementSignedOn' => ['required', 'date', 'before_or_equal:today']], [], ['managementSignedOn' => 'fecha de la firma']);

        $provider = $this->freshLinkage->provider;
        $version = $provider->openVersion ?? $provider->currentVersion
            ?? throw ValidationException::withMessages(['version' => 'El proveedor no tiene un formato para registrar.']);

        $this->runWorkflow(function () use ($workflow, $version): void {
            $workflow->registerPhysicalManagementApproval($version, $this->actor(), $this->managementSignedOn, $this->notes());
            $this->showManagementApproval = false;
        }, 'Firma física de Gerencia registrada');
    }

    public function reject(ProviderWorkflow $workflow): void
    {
        Gate::authorize('reject', $this->freshLinkage);

        $this->validate(['reviewNotes' => ['required', 'string', 'min:5', 'max:2000']], [], ['reviewNotes' => 'motivo del rechazo']);

        $this->runWorkflow(fn () => $workflow->reject($this->reviewVersion(), $this->actor(), trim($this->reviewNotes)), 'Rechazado');
    }

    public function returnToDraft(ProviderWorkflow $workflow): void
    {
        Gate::authorize('returnToDraft', $this->freshLinkage);
        $this->runWorkflow(fn () => $workflow->returnToDraft($this->openVersion()), 'Versión devuelta a Borrador');
    }

    /** Abre la actualización anual (o el primer FO-300-01 de un proveedor básico). */
    public function startUpdate(ProviderWorkflow $workflow): void
    {
        Gate::authorize('startUpdate', $this->freshLinkage);
        $this->selectedVersionId = null;
        $this->runWorkflow(fn () => $workflow->startUpdate($this->freshLinkage->provider), 'FO-300-01 abierto en Borrador');
    }

    /** Reabre en Borrador el FO-300-01 rechazado para corregirlo y volver a enviarlo. */
    public function correctRejected(ProviderWorkflow $workflow): void
    {
        Gate::authorize('correctRejected', $this->freshLinkage);
        $this->selectedVersionId = null;

        $version = $this->freshLinkage->provider->latestVersion
            ?? throw ValidationException::withMessages(['version' => 'El proveedor no tiene versiones.']);

        $this->runWorkflow(fn () => $workflow->correctRejected($version), 'FO-300-01 reabierto en Borrador para corregirlo');
    }

    // ── Condiciones del proceso ──────────────────────────────────────────────────

    public function openTerms(): void
    {
        Gate::authorize('updateTerms', $this->freshLinkage);
        $this->termsForm->reset();
        $this->resetErrorBag();
        $this->showTerms = true;
        $this->termsForm->setLinkage($this->freshLinkage);
    }

    public function saveTerms(): void
    {
        $linkage = $this->freshLinkage;
        Gate::authorize('updateTerms', $linkage);

        $linkage->update($this->termsForm->payload());
        $this->showTerms = false;
        $this->refreshAll();
        $this->toastSuccess('Condiciones actualizadas');
    }

    // ── Líneas del Borrador ──────────────────────────────────────────────────────

    /**
     * Al cambiar el tipo de documento del accionista a uno distinto de NIT se descarta el
     * representante legal: solo lo lleva una persona jurídica.
     */
    public function updatedShareholderFormDocumentType(): void
    {
        if (! $this->shareholderForm->requiresLegalRepresentative()) {
            $this->shareholderForm->legal_representative_name = null;
            $this->shareholderForm->legal_representative_identification = null;
        }
    }

    public function addShareholder(ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $data = $this->shareholderForm->payload();

        $this->runLine(function () use ($lines, $data): void {
            $lines->addShareholder($this->openVersion(), $data);
            $this->shareholderForm->reset();
        }, 'shareholderForm');
    }

    public function removeShareholder(int $shareholderId, ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $this->runLine(fn () => $lines->removeShareholder($this->openVersion()->shareholders()->findOrFail($shareholderId)));
    }

    public function addRelative(ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $data = $this->relativeForm->payload();

        $this->runLine(function () use ($lines, $data): void {
            $lines->addPepRelative($this->openVersion(), $data);
            $this->relativeForm->reset();
        }, 'relativeForm');
    }

    public function removeRelative(int $relativeId, ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $this->runLine(fn () => $lines->removePepRelative($this->openVersion()->pepRelatives()->findOrFail($relativeId)));
    }

    public function addDocument(ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $data = $this->documentForm->payload();
        $file = $this->documentForm->file;

        $this->runLine(function () use ($lines, $data, $file): void {
            $lines->addDocument($this->openVersion(), $data, $file, $this->actor());
            $this->documentForm->reset();
        }, 'documentForm');
    }

    public function removeDocument(int $documentId, ManageProviderVersionLines $lines): void
    {
        Gate::authorize('update', $this->freshLinkage);
        $this->runLine(fn () => $lines->removeDocument($this->openVersion()->documents()->findOrFail($documentId)));
    }

    // ── Descargas ────────────────────────────────────────────────────────────────

    /** Descarga un anexo de cualquier versión del proveedor (disco privado). */
    public function downloadDocument(int $documentId): StreamedResponse
    {
        Gate::authorize('view', $this->freshLinkage);

        $document = ProviderVersionDocument::query()
            ->whereHas('version', fn ($query) => $query->where('provider_id', $this->freshLinkage->provider_id))
            ->findOrFail($documentId);

        return Storage::disk(ProviderVersionDocument::DISK)->download($document->path, $document->original_name);
    }

    /** PDF del formato FO-300-01 de una versión; los datos sensibles solo para quien puede verlos. */
    public function downloadPdf(int $versionId): StreamedResponse
    {
        Gate::authorize('view', $this->freshLinkage);

        $version = $this->freshLinkage->provider->versions()->with([
            'provider.linkages', 'contacts', 'shareholders', 'pepRelatives', 'documents', 'approvals.user:id,name,lastname',
            'department', 'municipality', 'country', 'nationality', 'birthDepartment', 'birthMunicipality',
            'economicActivity', 'employerEconomicActivity', 'verifier:id,name,lastname', 'complianceOfficer:id,name,lastname',
            'managementSigner:id,name,lastname',
        ])->findOrFail($versionId);

        $pdf = Pdf::loadView('pdf.provider-version', [
            'version' => $version,
            'company' => app(CompanySettings::class),
            'sensitive' => $this->canViewSensitive,
        ])->setPaper('letter');

        return response()->streamDownload(fn () => print ($pdf->output()), $version->pdfFileName());
    }

    // ── Apoyo ────────────────────────────────────────────────────────────────────

    private function openVersion(): ProviderVersion
    {
        return $this->freshLinkage->provider->openVersion
            ?? throw ValidationException::withMessages(['version' => 'El proveedor no tiene una versión en trámite.']);
    }

    /** Versión sobre la que se firma (en trámite, o la vigente si una vinculación espera aprobación). */
    private function reviewVersion(): ProviderVersion
    {
        return $this->freshLinkage->provider->reviewVersion()
            ?? throw ValidationException::withMessages(['version' => 'El proveedor no tiene firmas pendientes.']);
    }

    private function notes(): ?string
    {
        $notes = trim($this->reviewNotes);

        return $notes === '' ? null : mb_substr($notes, 0, 2000);
    }

    /** Ejecuta un paso del flujo y muestra los errores de negocio como aviso. */
    private function runWorkflow(Closure $step, ?string $success = null): void
    {
        try {
            $step();
        } catch (ValidationException $exception) {
            $this->toastError(collect($exception->errors())->flatten()->first());

            return;
        } catch (\Throwable $e) {
            Log::error('providers.workflow_failed', ['linkage' => $this->linkage->id, 'error' => $e->getMessage()]);
            $this->toastError('No se pudo completar la acción. Inténtalo de nuevo.');

            return;
        }

        $this->reset('reviewNotes');
        $this->refreshAll();

        if ($success !== null) {
            $this->toastSuccess($success);
        }
    }

    /** Ejecuta un cambio de línea; los errores de negocio se muestran en el formulario indicado. */
    private function runLine(Closure $change, ?string $formName = null): void
    {
        try {
            $change();
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $key = $formName && ! str_contains($field, '.') ? "{$formName}.{$field}" : $field;
                $this->addError($key, $messages[0]);
            }

            $this->toastError(collect($exception->errors())->flatten()->first());

            return;
        }

        $this->refreshAll();
    }

    private function refreshAll(): void
    {
        unset($this->freshLinkage, $this->defaultVersion, $this->viewingDefault, $this->version, $this->versions, $this->years,
            $this->canViewSensitive, $this->canEditDraft, $this->pendingApprovals);
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
