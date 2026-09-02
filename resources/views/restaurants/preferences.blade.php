<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2">
            <a href="{{ route('restaurants.index') }}" class="text-sm text-emerald-700 hover:underline">← {{ __('Retour aux restaurants') }}</a>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight dark:text-white">{{ __('Personnaliser ma sélection') }}</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Choisis un profil ou ajuste finement ce qui compte pour toi.') }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <form method="POST" action="{{ route('restaurants.preferences.store') }}" class="space-y-6" id="preferences-form">
                @csrf

                <section class="synoria-panel rounded-2xl p-6 space-y-4">
                    <div>
                        <h3 class="text-lg font-semibold text-synoria-ink dark:text-white">{{ __('Profils rapides') }}</h3>
                        <p class="text-sm text-synoria-ink-soft mt-1">{{ __('Clique sur un profil pour préremplir les curseurs.') }}</p>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ($presets as $key => $preset)
                            <button type="button"
                                    data-preset="{{ $key }}"
                                    data-weights='@json($preset['weights'])'
                                    class="preset-card text-left rounded-2xl border p-4 transition
                                        {{ $matchPreset === $key ? 'border-synoria-green bg-emerald-50/60 ring-2 ring-synoria-green/20 dark:bg-emerald-950/40' : 'border-synoria-yellow/30 bg-white hover:border-synoria-yellow/60 dark:bg-slate-900' }}">
                                <p class="font-semibold text-synoria-ink dark:text-white">{{ __($preset['label']) }}</p>
                                <p class="text-sm text-synoria-ink-soft mt-1">{{ __($preset['description']) }}</p>
                            </button>
                        @endforeach
                    </div>
                </section>

                <section class="synoria-panel rounded-2xl p-6 space-y-5">
                    <div>
                        <h3 class="text-lg font-semibold text-synoria-ink dark:text-white">{{ __('Réglages avancés') }}</h3>
                        <p class="text-sm text-synoria-ink-soft mt-1">{{ __('Ajuste chaque critère de 0 à 4.') }}</p>
                    </div>

                    @php
                        $sliders = [
                            'distance' => ['label' => __('Distance'), 'hint' => __('Plus c’est haut, plus on favorise les restos proches.')],
                            'price' => ['label' => __('Prix des plats'), 'hint' => __('Priorise les menus abordables.')],
                            'fee' => ['label' => __('Frais de livraison'), 'hint' => __('Favorise les livraisons moins chères.')],
                            'courier' => ['label' => __('Livreurs disponibles'), 'hint' => __('Privilégie les zones avec livreurs actifs.')],
                            'rating' => ['label' => __('Note client'), 'hint' => __('Met en avant les restaurants les mieux notés.')],
                        ];
                    @endphp

                    <div class="space-y-5">
                        @foreach ($sliders as $key => $slider)
                            <div>
                                <div class="flex items-center justify-between gap-3">
                                    <label for="pref_{{ $key }}" class="text-sm font-medium text-synoria-ink">
                                        {{ $slider['label'] }}
                                    </label>
                                    <span class="text-xs font-semibold text-synoria-green" data-pref-value="{{ $key }}">
                                        {{ $matchWeights[$key] ?? 0 }}/4
                                    </span>
                                </div>
                                <input id="pref_{{ $key }}"
                                       type="range"
                                       name="pref_{{ $key }}"
                                       min="0"
                                       max="4"
                                       step="1"
                                       value="{{ old('pref_'.$key, $matchWeights[$key] ?? 0) }}"
                                       class="mt-2 w-full accent-synoria-green"
                                       data-pref-slider="{{ $key }}">
                                <p class="mt-1 text-xs text-synoria-ink-faint">{{ $slider['hint'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </section>

                <div class="flex flex-wrap items-center gap-3">
                    <x-primary-button>{{ __('Appliquer') }}</x-primary-button>
                    <a href="{{ route('restaurants.index') }}" class="text-sm font-medium text-synoria-ink-soft hover:text-synoria-ink">
                        {{ __('Annuler') }}
                    </a>
                </div>
            </form>

            @if ($hasCustomPreferences)
                <form method="POST" action="{{ route('restaurants.preferences.reset') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-700">
                        {{ __('Revenir à la sélection automatique') }}
                    </button>
                </form>
            @endif
        </div>
    </div>

    <script>
        const presets = @json($presets);
        const presetButtons = document.querySelectorAll('[data-preset]');
        const sliders = document.querySelectorAll('[data-pref-slider]');

        const applyWeights = (weights) => {
            Object.entries(weights).forEach(([key, value]) => {
                const slider = document.querySelector(`[data-pref-slider="${key}"]`);
                const label = document.querySelector(`[data-pref-value="${key}"]`);
                if (slider) slider.value = value;
                if (label) label.textContent = `${value}/4`;
            });
        };

        presetButtons.forEach((button) => {
            button.addEventListener('click', () => {
                presetButtons.forEach((btn) => {
                    btn.classList.remove('border-synoria-green', 'bg-emerald-50/60', 'ring-2', 'ring-synoria-green/20');
                    btn.classList.add('border-synoria-yellow/30', 'bg-white');
                });
                button.classList.add('border-synoria-green', 'bg-emerald-50/60', 'ring-2', 'ring-synoria-green/20');
                button.classList.remove('border-synoria-yellow/30', 'bg-white');
                applyWeights(JSON.parse(button.dataset.weights));
            });
        });

        sliders.forEach((slider) => {
            slider.addEventListener('input', () => {
                const key = slider.dataset.prefSlider;
                const label = document.querySelector(`[data-pref-value="${key}"]`);
                if (label) label.textContent = `${slider.value}/4`;
                presetButtons.forEach((btn) => {
                    btn.classList.remove('border-synoria-green', 'bg-emerald-50/60', 'ring-2', 'ring-synoria-green/20');
                    btn.classList.add('border-synoria-yellow/30', 'bg-white');
                });
            });
        });
    </script>
</x-app-layout>
