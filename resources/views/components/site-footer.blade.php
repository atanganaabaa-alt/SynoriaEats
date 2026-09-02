@php
    $phone = config('synoria.contact.phone_display');
    $phoneTel = preg_replace('/\s+/', '', config('synoria.contact.phone'));
    $email = config('synoria.contact.email');
@endphp

<footer class="mt-auto border-t border-synoria-yellow/30 bg-synoria-ink text-gray-300 dark:bg-slate-950 dark:border-slate-800">
    <div class="mx-auto max-w-6xl px-6 py-10">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            <div class="space-y-2 lg:col-span-1">
                <p class="text-base font-semibold text-white">
                    Synoria<span class="text-synoria-yellow">Eats</span>
                </p>
                <p class="text-sm text-gray-400 leading-relaxed max-w-xs">
                    {{ __('Livraison de repas près de chez toi. Restos locaux, menus clairs, suivi en temps réel.') }}
                </p>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-synoria-yellow mb-3">{{ __('Contact') }}</h3>
                <p class="text-sm text-gray-400 mb-2">{{ __('Une question ? On est là pour toi.') }}</p>
                <a href="tel:+237{{ ltrim($phoneTel, '+237') }}"
                   class="inline-flex items-center gap-2 text-sm text-white hover:text-synoria-yellow transition">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                    {{ $phone }}
                </a>
                <a href="mailto:{{ $email }}"
                   class="mt-2 flex items-center gap-2 text-sm text-gray-400 hover:text-synoria-yellow transition">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    {{ $email }}
                </a>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-synoria-yellow mb-3">{{ __('Commander') }}</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a href="{{ route('restaurants.index') }}" class="text-gray-400 hover:text-white transition">{{ __('Voir les restaurants') }}</a></li>
                    <li><a href="{{ route('companion.show') }}" class="text-gray-400 hover:text-white transition">{{ __('Parler à Sara') }}</a></li>
                    @guest
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition">{{ __('Créer un compte') }}</a></li>
                    @endguest
                </ul>
            </div>

            <div>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-synoria-yellow mb-3">{{ __('Restaurateurs') }}</h3>
                <ul class="space-y-1.5 text-sm">
                    <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-white transition">{{ __('Ouvrir mon restaurant') }}</a></li>
                    <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-white transition">{{ __('Espace restaurateur') }}</a></li>
                </ul>
                <h3 class="text-xs font-semibold uppercase tracking-widest text-synoria-yellow mb-2 mt-5">{{ __('Paiement') }}</h3>
                <p class="text-sm text-gray-400">MTN MoMo · Orange Money</p>
            </div>
        </div>

        <div class="mt-8 flex flex-col gap-2 border-t border-white/10 pt-6 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-xs text-gray-500">© {{ date('Y') }} SynoriaEats · {{ __('Suite Synoria') }}</p>
            <p class="text-xs text-gray-500">{{ __('Paiements sécurisés') }}</p>
        </div>
    </div>
</footer>
