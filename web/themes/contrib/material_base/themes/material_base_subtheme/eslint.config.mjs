import globals from 'globals';
import eslint from '@eslint/js';
import pluginPrettierRecommended from 'eslint-plugin-prettier/recommended';

export default [
  { files: ['**/*.{js,mjs,cjs,jsx}'] },
  {
    ignores: ['dist/', 'src/', 'scss/', '**/node_modules/', 'webpack.config.cjs'],
  },
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        require: 'readonly',
        Drupal: true,
        drupalSettings: true,
        once: true,
      },
    },
  },
  eslint.configs.recommended,
  pluginPrettierRecommended,
];
