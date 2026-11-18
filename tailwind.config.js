/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.blade.php',
    './resources/**/*.js',
    './resources/**/*.vue',
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ['var(--font-ui)'],
        serif: ['var(--font-reading)'],
        mono: ['var(--font-mono)'],
      },
    },
  },
  plugins: [],
}
