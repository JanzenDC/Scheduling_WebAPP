/** @type {import('tailwindcss').Config} */
module.exports = {
  darkMode: 'selector',
  content: [
    "./src/**/*.{html,js,php}",
    "./public/**/*.{html,js}",
    "./resources/views/**/*.{html,js,php}",
    "./resources/js/**/*.{html,js}",
    "!./node_modules/**/*",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}