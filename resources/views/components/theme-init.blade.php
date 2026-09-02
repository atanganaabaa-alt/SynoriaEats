{{-- Anti-flash + CSS dark immédiat (même si Vite n’a pas rebuild) --}}
<script>
    (function () {
        try {
            var stored = localStorage.getItem('theme') || localStorage.getItem('synoria-theme');
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (stored === 'dark' || (!stored && prefersDark)) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        } catch (e) {}
    })();
</script>
<style id="synoria-dark-fallback">
    html.dark body {
        color: #f3f4f6 !important;
        background-color: #0f1419 !important;
    }
    html.dark .synoria-shell {
        color: #f3f4f6 !important;
        background-color: #0f1419 !important;
        background-image: none !important;
    }
    html.dark .synoria-shell::before { opacity: 0.02 !important; }
    html.dark .synoria-nav,
    html.dark .bg-white,
    html.dark .bg-white\/90,
    html.dark .bg-white\/70,
    html.dark .bg-white\/95 {
        background-color: #0f172a !important;
        color: #f3f4f6 !important;
    }
    html.dark .synoria-panel {
        background-color: rgba(15, 23, 42, 0.92) !important;
        border-color: #334155 !important;
        color: #f3f4f6 !important;
    }
    html.dark .text-synoria-ink,
    html.dark .text-gray-800,
    html.dark .text-gray-900 {
        color: #f3f4f6 !important;
    }
    html.dark .text-synoria-ink-soft,
    html.dark .text-gray-500,
    html.dark .text-gray-600,
    html.dark .text-gray-700 {
        color: #9ca3af !important;
    }
    html.dark .text-synoria-ink-faint { color: #6b7280 !important; }
    html.dark .border-gray-100,
    html.dark .border-gray-200,
    html.dark .border-synoria-yellow\/25,
    html.dark .border-synoria-yellow\/40,
    html.dark .border-synoria-yellow\/50 {
        border-color: #334155 !important;
    }
    html.dark input,
    html.dark select,
    html.dark textarea {
        background-color: #1e293b !important;
        border-color: #475569 !important;
        color: #f3f4f6 !important;
    }
    html.dark [data-theme-toggle] {
        background-color: #1e293b !important;
        border-color: #475569 !important;
        color: #f3f4f6 !important;
    }
    html.dark header.bg-white\/70,
    html.dark .dark\:bg-slate-900\/80 {
        background-color: rgba(15, 23, 42, 0.85) !important;
    }
</style>
