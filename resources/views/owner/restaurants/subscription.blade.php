<x-app-layout>
    <x-slot name="header">
        <div>
            <x-back-link :href="route('owner.restaurants.show', $restaurant)" :label="$restaurant->name" />
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Abonnement Synoria') }}</h2>
            <p class="text-sm text-gray-500">{{ __('Essai gratuit :days jours à l’approbation, puis Essentiel ou Pro.', ['days' => $trialDays]) }}</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <div class="bg-emerald-50 text-emerald-800 px-4 py-3 rounded-md text-sm">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 text-red-800 px-4 py-3 rounded-md text-sm">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-2 text-sm">
                <p><span class="text-gray-500">{{ __('Statut') }} :</span>
                    <strong class="{{ $restaurant->hasCatalogAccess() ? 'text-emerald-700' : 'text-red-700' }}">
                        {{ $restaurant->subscriptionLabel() }}
                    </strong>
                </p>
                @if ($restaurant->onTrial())
                    <p>{{ __('Essai jusqu’au :date', ['date' => $restaurant->trial_ends_at->format('d/m/Y')]) }}</p>
                @endif
                @if ($restaurant->hasPaidSubscription())
                    <p>{{ __('Abonnement :plan jusqu’au :date', [
                        'plan' => $restaurant->subscription_plan?->label(),
                        'date' => $restaurant->subscription_ends_at->format('d/m/Y'),
                    ]) }}</p>
                @endif
                @unless ($restaurant->hasCatalogAccess())
                    <p class="text-amber-800">{{ __('Ton resto n’apparaît plus au catalogue. Choisis un plan ci-dessous.') }}</p>
                @endunless
                <p class="text-gray-500">{{ __('Commission Synoria : :rate % sur les plats (les frais de livraison vont au livreur).', ['rate' => number_format(config('synoria.commission_rate') * 100, 0)]) }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                @foreach ($plans as $plan)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6 flex flex-col">
                        <h3 class="font-semibold text-lg text-gray-900">{{ $plan->label() }}</h3>
                        <p class="mt-1 text-2xl font-bold text-emerald-700">
                            {{ number_format($plan->yearlyPrice(), 0, ',', ' ') }}
                            <span class="text-sm font-normal text-gray-500">FCFA / an</span>
                        </p>
                        <p class="mt-3 text-sm text-gray-600 flex-1">{{ $plan->description() }}</p>
                        <form method="POST" action="{{ route('owner.restaurants.subscription.store', $restaurant) }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="plan" value="{{ $plan->value }}">
                            <x-primary-button class="w-full justify-center">
                                {{ $restaurant->subscription_plan === $plan && $restaurant->hasPaidSubscription()
                                    ? __('Renouveler 1 an')
                                    : __('Activer (sandbox)') }}
                            </x-primary-button>
                        </form>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</x-app-layout>
