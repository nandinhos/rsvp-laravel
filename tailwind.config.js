import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // ── Design system institucional (PRD §10) ──
                // Escala completa 50–950 derivada dos tons de azul institucional.
                // Fonte única de verdade: nenhum hex hardcoded nas views.
                institutional: {
                    50: '#f1f6fb',
                    100: '#e2edf7',
                    200: '#c0d8ec',
                    300: '#8fb8db',
                    400: '#5e97c4',
                    500: '#276ca3', // interaction
                    600: '#1c4e80', // base
                    700: '#174067',
                    800: '#123152',
                    900: '#0b2a4a', // deep
                    950: '#071d33',
                    // aliases semânticos (compatibilidade + legibilidade)
                    deep: '#0B2A4A',
                    base: '#1C4E80',
                    interaction: '#276CA3',
                    surface: '#FFFFFF',
                    neutral: '#F6F9FC',
                    text: '#182738',
                },
            },
        },
    },

    plugins: [forms],
};
