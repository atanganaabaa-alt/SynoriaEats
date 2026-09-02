function currentTheme() {
    return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
}

function applyTheme(theme) {
    if (theme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    localStorage.setItem('theme', theme);
    localStorage.setItem('synoria-theme', theme);
    syncThemeUi();
}

function syncThemeUi() {
    const isDark = currentTheme() === 'dark';

    document.querySelectorAll('[data-theme-icon-sun], [data-theme-icon-light]').forEach((el) => {
        el.classList.toggle('hidden', !isDark);
    });

    document.querySelectorAll('[data-theme-icon-moon], [data-theme-icon-dark]').forEach((el) => {
        el.classList.toggle('hidden', isDark);
    });

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        btn.setAttribute('aria-pressed', isDark ? 'true' : 'false');
    });
}

export function initTheme() {
    const stored = localStorage.getItem('theme') || localStorage.getItem('synoria-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    applyTheme(stored || (prefersDark ? 'dark' : 'light'));

    document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
        if (btn.dataset.themeBound === '1') {
            return;
        }

        btn.dataset.themeBound = '1';
        btn.addEventListener('click', () => {
            applyTheme(currentTheme() === 'dark' ? 'light' : 'dark');
        });
    });
}

(function () {
    const stored = localStorage.getItem('theme') || localStorage.getItem('synoria-theme');
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    const theme = stored || (prefersDark ? 'dark' : 'light');
    document.documentElement.classList.toggle('dark', theme === 'dark');
})();
