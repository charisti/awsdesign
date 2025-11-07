const defaultTheme = require('tailwindcss/defaultTheme');

module.exports = {
  content: [
    './templates/**/*.{twig,html}',
    './components/**/*.{twig,html}',
    './src/js/**/*.js',
    '../../custom/**/*.twig',
    '../../../modules/custom/**/*.twig',
  ],
  theme: {
    extend: {
      colors: {
        brand: {
          DEFAULT: '#111827',
          accent: '#F97316',
          muted: '#6B7280',
        },
      },
      fontFamily: {
        sans: ['Inter', ...defaultTheme.fontFamily.sans],
      },
    },
  },
  plugins: [],
};
