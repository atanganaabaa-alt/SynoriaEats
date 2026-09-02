import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Outfit', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                synoria: {
                    yellow: {
                        DEFAULT: '#FCD530',
                        soft: '#FFF4B8',
                        mist: '#FFFCEB',
                        deep: '#E8C012',
                    },
                    green: {
                        DEFAULT: '#63A03A',
                        soft: '#E8F5DC',
                        dark: '#4F832E',
                        deeper: '#3D6624',
                    },
                    ink: {
                        DEFAULT: '#1D242B',
                        soft: '#5A6570',
                        faint: '#8A939C',
                    },
                },
                // Remap emerald → vert Synoria pour l’UI existante.
                emerald: {
                    50: '#F3FAEC',
                    100: '#E8F5DC',
                    200: '#D1EBB9',
                    300: '#A9D87E',
                    400: '#84C455',
                    500: '#63A03A',
                    600: '#568B32',
                    700: '#4F832E',
                    800: '#3D6624',
                    900: '#2F4F1C',
                    950: '#1A2C0F',
                },
            },
            boxShadow: {
                synoria: '0 12px 40px -16px rgba(252, 213, 48, 0.55)',
            },
            backgroundImage: {
                'synoria-glow':
                    'radial-gradient(ellipse 90% 55% at 15% -5%, rgba(252, 213, 48, 0.42), transparent 58%), radial-gradient(ellipse 55% 40% at 95% 5%, rgba(99, 160, 58, 0.14), transparent 52%), linear-gradient(180deg, #FFFDF3 0%, #FFFFFF 42%, #FFFCEB 100%)',
            },
        },
    },

    plugins: [forms],
};
