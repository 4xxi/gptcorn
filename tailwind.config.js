/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./assets/**/*.js",
    "./templates/**/*.html.twig",
  ],
  theme: {
    extend: {
      colors: {
        primary: '#b100ff',
        error: '#f43f5e',
      },

    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}
