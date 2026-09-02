<div class="inline-flex shrink-0 items-center gap-2" data-ui-preferences>
    <div
        role="group"
        aria-label="{{ __('Langue') }}"
        class="inline-flex rounded-lg border border-synoria-yellow/40 bg-white p-0.5 text-xs font-semibold shadow-sm dark:border-slate-600 dark:bg-slate-800"
    >
        <a href="{{ route('locale.switch', 'fr') }}"
           class="rounded-md px-2.5 py-1 transition {{ app()->getLocale() === 'fr' ? 'bg-synoria-green text-white' : 'text-synoria-ink-soft hover:text-synoria-ink dark:text-gray-300 dark:hover:text-white' }}"
           aria-current="{{ app()->getLocale() === 'fr' ? 'true' : 'false' }}">
            FR
        </a>
        <a href="{{ route('locale.switch', 'en') }}"
           class="rounded-md px-2.5 py-1 transition {{ app()->getLocale() === 'en' ? 'bg-synoria-green text-white' : 'text-synoria-ink-soft hover:text-synoria-ink dark:text-gray-300 dark:hover:text-white' }}"
           aria-current="{{ app()->getLocale() === 'en' ? 'true' : 'false' }}">
            EN
        </a>
    </div>

    <button
        type="button"
        data-theme-toggle
        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-synoria-yellow/40 bg-white text-synoria-ink shadow-sm transition hover:bg-synoria-yellow-soft focus:outline-none focus:ring-2 focus:ring-synoria-yellow/50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-100 dark:hover:bg-slate-700"
        aria-label="{{ __('Mode nuit') }}"
        title="{{ __('Mode nuit') }}"
    >
        {{-- Soleil : visible en mode sombre --}}
        <svg data-theme-icon-sun class="h-4 w-4 hidden" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z" />
        </svg>
        {{-- Lune : visible en mode clair --}}
        <svg data-theme-icon-moon class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z" />
        </svg>
    </button>
</div>

@once
<script>
(function () {
    var KEY = 'theme';

    function isDark() {
        return document.documentElement.classList.contains('dark');
    }

    function syncIcons() {
        var dark = isDark();
        document.querySelectorAll('[data-theme-icon-sun]').forEach(function (el) {
            el.classList.toggle('hidden', !dark);
        });
        document.querySelectorAll('[data-theme-icon-moon]').forEach(function (el) {
            el.classList.toggle('hidden', dark);
        });
        // Compat anciens attributs
        document.querySelectorAll('[data-theme-icon-light]').forEach(function (el) {
            el.classList.toggle('hidden', !dark);
        });
        document.querySelectorAll('[data-theme-icon-dark]').forEach(function (el) {
            el.classList.toggle('hidden', dark);
        });
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            btn.setAttribute('aria-pressed', dark ? 'true' : 'false');
        });
    }

    function apply(theme) {
        if (theme === 'dark') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
        try {
            localStorage.setItem(KEY, theme);
            localStorage.setItem('synoria-theme', theme);
        } catch (e) {}
        syncIcons();
    }

    function bind() {
        document.querySelectorAll('[data-theme-toggle]').forEach(function (btn) {
            if (btn.getAttribute('data-theme-bound') === '1') return;
            btn.setAttribute('data-theme-bound', '1');
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                apply(isDark() ? 'light' : 'dark');
            });
        });
        syncIcons();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bind);
    } else {
        bind();
    }
})();
</script>
@endonce
