<?php

use App\Models\User;
use App\Support\GuideCatalog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Guía de usuario: temas en Markdown (resources/guide) filtrados por los permisos del usuario.
 * El botón de ayuda de la barra superior la abre con ?context={ruta} en el tema de esa pantalla.
 */
new #[Layout('layouts.app')] #[Title('Guía de usuario')] class extends Component
{
    #[Url]
    public string $topic = '';

    public string $search = '';

    public function mount(): void
    {
        if (! GuideCatalog::canView($this->user(), $this->topic)) {
            $context = request()->query('context');
            $this->topic = GuideCatalog::forRoute($this->user(), is_string($context) ? $context : null) ?? 'getting-started';
        }
    }

    /** Abre un tema desde el índice. */
    public function open(string $slug): void
    {
        if (GuideCatalog::canView($this->user(), $slug)) {
            $this->topic = $slug;
            $this->js('window.scrollTo({ top: 0, behavior: "smooth" })');
        }
    }

    /**
     * Índice por sección; con búsqueda solo los temas que contienen todas las palabras.
     *
     * @return array<string, array<string, array{section: string, title: string, summary: string, abilities: list<string>, routes: list<string>}>>
     */
    #[Computed]
    public function sections(): array
    {
        $words = array_filter(explode(' ', GuideCatalog::normalize(trim($this->search))));
        $sections = GuideCatalog::visibleTo($this->user());

        if ($words === []) {
            return $sections;
        }

        foreach ($sections as $key => $topics) {
            $sections[$key] = array_filter($topics, function (string $slug) use ($words): bool {
                $text = GuideCatalog::searchableText($slug);

                return collect($words)->every(fn (string $word): bool => str_contains($text, $word));
            }, ARRAY_FILTER_USE_KEY);
        }

        return array_filter($sections);
    }

    /**
     * Tema abierto con su HTML, y los temas anterior y siguiente para leer en orden.
     *
     * @return array{slug: string, title: string, summary: string, section: array{label: string, icon: string, description: string}, html: string, previous: ?string, next: ?string}|null
     */
    #[Computed]
    public function current(): ?array
    {
        if (! GuideCatalog::canView($this->user(), $this->topic)) {
            return null;
        }

        $topic = GuideCatalog::TOPICS[$this->topic];
        $order = array_merge(...array_values(array_map('array_keys', GuideCatalog::visibleTo($this->user()))));
        $position = (int) array_search($this->topic, $order, true);

        return [
            'slug' => $this->topic,
            'title' => $topic['title'],
            'summary' => $topic['summary'],
            'section' => GuideCatalog::SECTIONS[$topic['section']],
            'html' => GuideCatalog::markdown($this->topic) === '' ? '' : GuideCatalog::render($this->topic),
            'previous' => $order[$position - 1] ?? null,
            'next' => $order[$position + 1] ?? null,
        ];
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
};
?>

<div class="guide row g-3">
    <aside class="col-lg-3">
        <div class="card guide-nav">
            <div class="card-body">
                <h5 class="guide-nav-title"><i class="bi bi-book" aria-hidden="true"></i> Guía de usuario</h5>
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm mb-3"
                       placeholder="Buscar en la guía" aria-label="Buscar en la guía">

                @forelse ($this->sections as $key => $topics)
                    <div class="guide-nav-section" wire:key="guide-section-{{ $key }}">
                        <i class="bi {{ GuideCatalog::SECTIONS[$key]['icon'] }}" aria-hidden="true"></i>
                        {{ GuideCatalog::SECTIONS[$key]['label'] }}
                    </div>
                    <ul class="guide-nav-list">
                        @foreach ($topics as $slug => $item)
                            <li wire:key="guide-topic-{{ $slug }}">
                                <button type="button" wire:click="open('{{ $slug }}')"
                                        @class(['guide-nav-link', 'active' => $slug === $topic])
                                        @if ($slug === $topic) aria-current="page" @endif>
                                    {{ $item['title'] }}
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @empty
                    <p class="text-body-secondary small mb-0">No hay temas que coincidan con la búsqueda.</p>
                @endforelse
            </div>
        </div>
    </aside>

    <section class="col-lg-9">
        @if ($current = $this->current)
            <x-document.header :title="$current['title']" :icon="$current['section']['icon']" :subtitle="$current['summary']"
                               :status="$current['section']['label']" status-class="bg-primary-subtle text-primary-emphasis" />

            <div class="card">
                <div class="card-body">
                    @if ($current['html'] === '')
                        <p class="text-body-secondary mb-0">Este tema está en preparación.</p>
                    @else
                        <article class="guide-article" wire:key="guide-article-{{ $current['slug'] }}">
                            {!! $current['html'] !!}
                        </article>
                    @endif

                    <nav class="guide-pager" aria-label="Temas de la guía">
                        @if ($current['previous'])
                            <button type="button" wire:click="open('{{ $current['previous'] }}')" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left" aria-hidden="true"></i> {{ GuideCatalog::TOPICS[$current['previous']]['title'] }}
                            </button>
                        @endif
                        @if ($current['next'])
                            <button type="button" wire:click="open('{{ $current['next'] }}')" class="btn btn-sm btn-outline-primary ms-auto">
                                {{ GuideCatalog::TOPICS[$current['next']]['title'] }} <i class="bi bi-arrow-right" aria-hidden="true"></i>
                            </button>
                        @endif
                    </nav>
                </div>
            </div>
        @else
            <div class="card">
                <div class="card-body text-body-secondary">El tema solicitado no existe o no está disponible para tu usuario.</div>
            </div>
        @endif
    </section>
</div>
