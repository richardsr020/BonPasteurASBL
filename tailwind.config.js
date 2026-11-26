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
                amini: {
                    ink: '#121212',
                    coal: '#0E0E0E',
                    wine: '#861D2C',
                    wineDark: '#4C171F',
                    wineDeep: '#271417',
                    gold: '#FFC83C',
                    paper: '#FFFFFF',
                    mist: '#ECECEC',
                    line: '#E1E1E1',
                    muted: '#202020',
                    steel: '#5D6C7B',
                },
                pasteur: {
                    green: '#861D2C',
                    dark: '#121212',
                    light: '#ECECEC',
                    gold: '#FFC83C',
                },
            },
            fontFamily: {
            sans: ['Montserrat', 'Arial', 'sans-serif'],
            display: ['Montserrat', 'Arial', 'sans-serif'],
                mono: ['"Geist Mono"', 'ui-monospace', 'monospace'],
            },
            borderRadius: {
                card: '1.5rem',
            },
            boxShadow: {
                soft: '0 18px 50px -40px rgba(18, 18, 18, 0.5)',
            },
            keyframes: {
                'fade-up': {
                    '0%': { opacity: '0', transform: 'translateY(14px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
            animation: {
                'fade-up': 'fade-up 0.6s ease-out both',
            },
        },
    },
    plugins: [],
};
