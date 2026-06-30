/** @type {import('tailwindcss').Config} */
export default {
  content: [
    './resources/**/*.{html,js,vue,php}',
    './resources/views/**/*.blade.php',
  ],
  theme: {
    extend: {
      colors: {
        padel: {
          50: '#f0fdf4',
          100: '#dcfce7',
          500: '#22c55e',
          600: '#16a34a',
          700: '#15803d',
          900: '#14532d',
        },
      },
    },
  },
  plugins: [require('@tailwindcss/forms')],
}
