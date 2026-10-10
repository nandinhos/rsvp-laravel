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
                // Design system institucional (PRD §10) — tons de azul.
                institutional: {
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
