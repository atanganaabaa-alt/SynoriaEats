<nav x-data="{ open: false }" class="synoria-nav relative z-[100] border-b border-synoria-yellow/50 bg-white" style="backdrop-filter: none; -webkit-backdrop-filter: none; background-color: #ffffff;">
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
                        {{ __('Amina') }}
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

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <x-dropdown align="right" width="56">
                        <x-slot name="trigger">
                            <button
                                type="button"
                                class="inline-flex max-w-xs items-center gap-3 rounded-full border border-synoria-yellow/40 bg-white px-2.5 py-1.5 shadow-sm transition hover:bg-synoria-yellow-soft focus:outline-none focus:ring-2 focus:ring-synoria-yellow/50"
                            >
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-synoria-yellow text-sm font-bold text-synoria-ink">
                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim(Auth::user()->name), 0, 1)) }}
                                </span>
                                <span class="min-w-0 text-left leading-tight pe-1">
                                    <span class="block truncate text-sm font-semibold text-synoria-ink" title="{{ Auth::user()->name }}">
                                        {{ Auth::user()->name }}
                                    </span>
                                    <span class="block text-xs text-synoria-ink-faint">
                                        {{ Auth::user()->role->label() }}
                                    </span>
                                </span>
                                <svg class="h-4 w-4 shrink-0 text-synoria-ink-faint" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="border-b border-synoria-yellow/20 px-4 py-3">
                                <p class="text-sm font-semibold text-synoria-ink break-words">{{ Auth::user()->name }}</p>
                                <p class="mt-0.5 text-xs text-synoria-ink-soft">{{ Auth::user()->email }}</p>
                            </div>
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profil') }}
                            </x-dropdown-link>
                            <div class="px-4 py-2">
                                <x-logout-button
                                    redirect="login"
                                    class="w-full text-left text-sm font-medium text-synoria-ink hover:text-synoria-green"
                                >
                                    Changer de compte
                                </x-logout-button>
                            </div>
                            <div class="border-t border-synoria-yellow/20 px-4 py-2">
                                <x-logout-button class="w-full text-left text-sm font-medium text-red-600 hover:text-red-700" />
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}" class="text-sm text-synoria-ink-soft hover:text-synoria-ink">Connexion</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-3 py-1.5 rounded-md bg-synoria-green text-white text-sm font-medium hover:bg-synoria-green-dark">
                            Inscription
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

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white/95">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('restaurants.index')" :active="request()->routeIs('restaurants.*')">
                {{ __('Restaurants') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('companion.show')" :active="request()->routeIs('companion.*')">
                {{ __('Amina') }}
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
                <div class="px-4 flex items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-synoria-yellow text-sm font-bold text-synoria-ink">
                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(trim(Auth::user()->name), 0, 1)) }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-semibold text-base text-synoria-ink break-words">{{ Auth::user()->name }}</div>
                        <div class="text-sm text-synoria-ink-soft">{{ Auth::user()->role->label() }}</div>
                        <div class="text-xs text-synoria-ink-faint truncate">{{ Auth::user()->email }}</div>
                    </div>
                </div>
                <div class="mt-3 space-y-1 px-4 pb-3">
                    <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profil') }}</x-responsive-nav-link>
                    <div class="pt-2">
                        <x-logout-button redirect="login" class="text-sm font-medium text-synoria-ink hover:text-synoria-green">
                            Changer de compte
                        </x-logout-button>
                    </div>
                    <div class="pt-1">
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
