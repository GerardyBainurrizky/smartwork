import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            spacing: {
                '68': '17rem',
                '4.5': '1.125rem',
            },
            animation: {
                'skeleton': 'skeleton 1.5s ease-in-out infinite',
            },
            keyframes: {
                skeleton: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.4' },
                },
            },
        },
    },

    plugins: [forms],
};
