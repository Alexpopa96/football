const defaultTheme = require('tailwindcss/defaultTheme')

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],
    theme: {
        fontFamily: {
            sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            display: ['Outfit', ...defaultTheme.fontFamily.sans],
        },
        extend: {
            colors: {
                'primaryColor': '#54ACE4',
                'hoverColor': '#349de0', //blue-50
                'layoutDark' : '#1F263C',
                'slotDark' : '#15192A',
                'inputDark' : '#1F263C',
                'tableDark' : '#1F263C',
                'textTableDark' : '#f3f4f6',
                'textInputDark' : '#f3f4f6',
                'borderInputDark' : '#4b5563',
                'pitch': {
                    950: '#05070d',
                    900: '#0a0f1c',
                    800: '#0f1729',
                    700: '#161f38',
                    600: '#1f2b4a',
                },
            },
        }
    },
    darkMode: 'class',
    plugins: [require('@tailwindcss/forms')],
};
