<nav x-data="{ open: false }" class="synoria-nav relative z-[100] border-b border-synoria-yellow/50 bg-white dark:bg-slate-900 dark:border-slate-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <x-brand size="sm" />
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('restaurants.index')" :active="request()->routeIs('restaurants.*')">
                        {{ __('Restaurants') }}
                    </x-nav-link>
                    <x-nav-link :href="route('companion.show')" :active="request()->routeIs('companion.*')">
                        {{ __('Sara') }}
                    </x-nav-link>
                    @auth
                        <x-nav-link :href="route('cart.show')" :active="request()->routeIs('cart.*')">
                            {{ __('Panier') }}
                            @if (($cartCount ?? 0) > 0)
                                <span class="ms-1 inline-flex items-center justify-center px-2 py-0.5 text-xs font-medium bg-synoria-yellow text-synoria-ink rounded-full">{{ $cartCount }}</span>
                            @endif
                        </x-nav-link>
                        @if (Auth::user()->isAdmin())
                            <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                                {{ __('Admin') }}
                            </x-nav-link>
                        @endif
                        @if (Auth::user()->isRestaurantOwner() || Auth::user()->isAdmin())
                            <x-nav-link :href="Auth::user()->isAdmin() || Auth::user()->isApproved() ? route('owner.restaurants.index') : route('owner.pending')" :active="request()->routeIs('owner.*')">
                                {{ __('Mon resto') }}
                            </x-nav-link>
                            @if (Auth::user()->isAdmin() || Auth::user()->isApproved())
                                <x-nav-link :href="route('owner.orders.index')" :active="request()->routeIs('owner.orders.*')">
                                    {{ __('Commandes reçues') }}
                                </x-nav-link>
                            @endif
                        @endif
                        @if (Auth::user()->isCourier())
                            <x-nav-link :href="Auth::user()->isApproved() ? route('courier.missions.index') : route('courier.pending')" :active="request()->routeIs('courier.*')">
                                {{ __('Missions') }}
                            </x-nav-link>
                        @elseif (Auth::user()->isAdmin())
                            <x-nav-link :href="route('courier.missions.index')" :active="request()->routeIs('courier.*')">
                                {{ __('Missions') }}
                            </x-nav-link>
                        @endif
                        @unless (Auth::user()->isAdmin())
                            <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                                {{ __('Commandes') }}
                            </x-nav-link>
                        @endunless
                    @endauth
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                <x-ui-preferences />
                @auth
                    @php
                        $authUser = Auth::user();
                        $avatarUrl = \App\Support\MediaUrl::resolve($authUser->avatar_url);
                        $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($authUser->name), 0, 1));
                    @endphp
                    <x-dropdown align="right" width="72">
                        <x-slot name="trigger">
                            <button
                                type="button"
                                class="inline-flex max-w-xs items-center gap-3 rounded-full border border-synoria-yellow/40 bg-white px-2.5 py-1.5 shadow-sm transition hover:bg-synoria-yellow-soft focus:outline-none focus:ring-2 focus:ring-synoria-yellow/50 dark:bg-slate-800 dark:hover:bg-slate-700"
                            >
                                @if ($avatarUrl)
                                    <img src="{{ $avatarUrl }}" alt="" class="h-9 w-9 shrink-0 rounded-full object-cover ring-1 ring-synoria-yellow/40">
                                @else
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-synoria-yellow text-sm font-bold text-synoria-ink">
                                        {{ $initial }}
                                    </span>
                                @endif
                                <span class="min-w-0 text-left leading-tight pe-1">
                                    <span class="block truncate text-sm font-semibold text-synoria-ink dark:text-white" title="{{ $authUser->name }}">
                                        {{ $authUser->name }}
                                    </span>
                                    <span class="block text-xs text-synoria-ink-faint dark:text-gray-400">
                                        {{ $authUser->role->label() }}
                                    </span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-synoria-ink-faint" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-synoria-yellow/25 px-4 py-3 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    @if ($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="" class="h-12 w-12 rounded-full object-cover ring-2 ring-synoria-yellow/50">
                                    @else
                                        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-synoria-yellow text-base font-bold text-synoria-ink">
                                            {{ $initial }}
                                        </span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-synoria-ink break-words dark:text-white">{{ $authUser->name }}</p>
                                        <p class="mt-0.5 text-xs text-synoria-ink-soft truncate dark:text-gray-400">{{ $authUser->email }}</p>
                                        <p class="mt-0.5 text-xs text-synoria-ink-faint dark:text-gray-500">{{ $authUser->role->label() }}@if ($authUser->phone) · {{ $authUser->phone }}@endif</p>
                                    </div>
                                </div>
                            </div>

                            <div class="border-b border-synoria-yellow/20 px-4 py-3 dark:border-slate-700" x-data="{ uploading: false }">
                                <p class="mb-2 text-xs font-medium uppercase tracking-wide text-synoria-ink-faint dark:text-gray-500">{{ __('Photo de profil') }}</p>
                                <form
                                    method="POST"
                                    action="{{ route('profile.avatar') }}"
                                    enctype="multipart/form-data"
                                    class="space-y-2"
                                    @submit="uploading = true"
                                >
                                    @csrf
                                    <input
                                        type="file"
                                        name="avatar"
                                        accept="image/jpeg,image/png,image/webp,image/gif"
                                        required
                                        class="block w-full text-xs text-synoria-ink-soft file:me-2 file:rounded-lg file:border-0 file:bg-synoria-yellow file:px-2.5 file:py-1.5 file:text-xs file:font-semibold file:text-synoria-ink hover:file:bg-synoria-yellow-deep dark:text-gray-300"
                                    >
                                    <button
                                        type="submit"
                                        class="w-full rounded-lg bg-synoria-ink px-3 py-1.5 text-xs font-semibold text-white hover:bg-synoria-ink/90 disabled:opacity-60 dark:bg-synoria-yellow dark:text-synoria-ink"
                                        :disabled="uploading"
                                    >
                                        <span x-show="!uploading">{{ __('Ajouter / changer la photo') }}</span>
                                        <span x-show="uploading" x-cloak>{{ __('Envoi…') }}</span>
                                    </button>
                                </form>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Mon profil') }}
                            </x-dropdown-link>

                            <div class="border-t border-synoria-yellow/20 px-4 py-2 dark:border-slate-700">
                                <x-logout-button class="w-full text-left text-sm font-medium text-red-600 hover:text-red-700" />
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}" class="text-sm text-synoria-ink-soft hover:text-synoria-ink dark:text-gray-300 dark:hover:text-white">{{ __('Connexion') }}</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-synoria-green text-white text-sm font-medium hover:bg-synoria-green-dark">
                            {{ __('Inscription') }}
                        </a>
                    </div>
                @endauth
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-synoria-ink-faint hover:text-synoria-ink hover:bg-synoria-yellow-soft focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/95 dark:bg-slate-900/95 border-t border-synoria-yellow/30 dark:border-slate-700">
        <div class="px-4 py-3 border-b border-synoria-yellow/20 dark:border-slate-700">
            <x-ui-preferences />
        </div>
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('restaurants.index')" :active="request()->routeIs('restaurants.*')">
                {{ __('Restaurants') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('companion.show')" :active="request()->routeIs('companion.*')">
                {{ __('Sara') }}
            </x-responsive-nav-link>
            @auth
                <x-responsive-nav-link :href="route('cart.show')" :active="request()->routeIs('cart.*')">
                    {{ __('Panier') }} @if(($cartCount ?? 0) > 0)({{ $cartCount }})@endif
                </x-responsive-nav-link>
                @if (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Admin') }}
                    </x-responsive-nav-link>
                @endif
                @if (Auth::user()->isRestaurantOwner() || Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="Auth::user()->isAdmin() || Auth::user()->isApproved() ? route('owner.restaurants.index') : route('owner.pending')" :active="request()->routeIs('owner.*')">
                        {{ __('Mon resto') }}
                    </x-responsive-nav-link>
                @endif
                @if (Auth::user()->isCourier())
                    <x-responsive-nav-link :href="Auth::user()->isApproved() ? route('courier.missions.index') : route('courier.pending')" :active="request()->routeIs('courier.*')">
                        {{ __('Missions') }}
                    </x-responsive-nav-link>
                @elseif (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('courier.missions.index')" :active="request()->routeIs('courier.*')">
                        {{ __('Missions') }}
                    </x-responsive-nav-link>
                @endif
                @unless (Auth::user()->isAdmin())
                    <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                        {{ __('Commandes') }}
                    </x-responsive-nav-link>
                @endunless
            @endauth
        </div>

        <div class="pt-4 pb-1 border-t border-synoria-yellow/30">
            @auth
                @php
                    $authUser = Auth::user();
                    $avatarUrl = \App\Support\MediaUrl::resolve($authUser->avatar_url);
                    $initial = \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim($authUser->name), 0, 1));
                @endphp
                <div class="px-4 flex items-center gap-3">
                    @if ($avatarUrl)
                        <img src="{{ $avatarUrl }}" alt="" class="h-10 w-10 shrink-0 rounded-full object-cover ring-1 ring-synoria-yellow/40">
                    @else
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-synoria-yellow text-sm font-bold text-synoria-ink">
                            {{ $initial }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <div class="font-semibold text-base text-synoria-ink break-words dark:text-white">{{ $authUser->name }}</div>
                        <div class="text-sm text-synoria-ink-soft dark:text-gray-400">{{ $authUser->role->label() }}</div>
                        <div class="text-xs text-synoria-ink-faint truncate dark:text-gray-500">{{ $authUser->email }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1 px-4 pb-3">
                    <form method="POST" action="{{ route('profile.avatar') }}" enctype="multipart/form-data" class="mb-3 space-y-2 rounded-xl border border-synoria-yellow/30 p-3 dark:border-slate-600">
                        @csrf
                        <p class="text-xs font-medium text-synoria-ink-faint">{{ __('Photo de profil') }}</p>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp,image/gif" required class="block w-full text-xs">
                        <button type="submit" class="w-full rounded-lg bg-synoria-yellow px-3 py-1.5 text-xs font-semibold text-synoria-ink">
                            {{ __('Ajouter / changer la photo') }}
                        </button>
                    </form>
                    <x-responsive-nav-link :href="route('profile.edit')">{{ __('Mon profil') }}</x-responsive-nav-link>
                    <div class="pt-2">
                        <x-logout-button class="text-sm font-medium text-red-600 hover:text-red-700" />
                    </div>
                </div>
            @else
                <div class="mt-3 space-y-1 px-4 pb-3">
                    <x-responsive-nav-link :href="route('login')">{{ __('Connexion') }}</x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('register')">{{ __('Inscription') }}</x-responsive-nav-link>
                </div>
            @endauth
        </div>
    </div>
</nav>
