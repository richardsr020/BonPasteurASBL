/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './templates/**/*.{html,twig,php}',
        './src/**/*.php',
        './assets/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                pasteur: {
                    green: '#1B7A4B',
                    dark: '#0F4C30',
                    light: '#E8F5EE',
                    gold: '#D4A017',
                },
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
        },
    },
    plugins: [],
};
