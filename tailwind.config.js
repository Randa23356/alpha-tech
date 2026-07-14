/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./**/*.php",
    "./src/**/*.{js,php}",
    "./public/**/*.{js,php}",
    "./public/index.php", // Explicitly include the index.php file
    "./includes/**/*.{js,php}",
    "./admin/**/*.{js,php}",
    "./korti/**/*.{js,php}",
    "./views/**/*.{js,php}",
  ],
  theme: {
    extend: {
      colors: {
        // Dynamic theme colors - these will be overridden by dynamic-theme.php
        primary: {
          50: "rgba(30, 58, 138, 0.05)",
          100: "rgba(30, 58, 138, 0.1)",
          200: "rgba(30, 58, 138, 0.2)",
          300: "rgba(30, 58, 138, 0.3)",
          400: "rgba(30, 58, 138, 0.4)",
          500: "rgba(30, 58, 138, 0.5)",
          600: "rgba(30, 58, 138, 0.6)",
          700: "rgba(30, 58, 138, 0.7)",
          800: "rgba(30, 58, 138, 0.8)",
          900: "rgba(30, 58, 138, 0.9)",
          DEFAULT: "#1e3a8a",
        },
        secondary: {
          50: "rgba(30, 64, 175, 0.05)",
          100: "rgba(30, 64, 175, 0.1)",
          200: "rgba(30, 64, 175, 0.2)",
          300: "rgba(30, 64, 175, 0.3)",
          400: "rgba(30, 64, 175, 0.4)",
          500: "rgba(30, 64, 175, 0.5)",
          600: "rgba(30, 64, 175, 0.6)",
          700: "rgba(30, 64, 175, 0.7)",
          800: "rgba(30, 64, 175, 0.8)",
          900: "rgba(30, 64, 175, 0.9)",
          DEFAULT: "#1e40af",
        },
        // Keep existing colors for backward compatibility
        navy: {
          900: "#1e3a8a",
          800: "#1e40af",
        },
      },
    },
  },
  plugins: [],
};
