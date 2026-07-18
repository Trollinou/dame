import pluginVue from 'eslint-plugin-vue';
import vueTsConfigs from '@vue/eslint-config-typescript';
import js from '@eslint/js';
import globals from 'globals';

export default [
  {
    ignores: [
      '**/node_modules/**',
      '**/dist/**',
      '**/coverage/**',
      '**/ios/**',
      '**/android/**',
      'dist/**',
      'node_modules/**',
      '.eslintrc.cjs',
      'tests/e2e/**'
    ]
  },
  js.configs.recommended,
  ...pluginVue.configs['flat/essential'],
  ...vueTsConfigs(),
  {
    languageOptions: {
      globals: {
        ...globals.browser,
        ...globals.node,
        ...globals.es2021
      }
    },
    rules: {
      'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
      'vue/no-deprecated-slot-attribute': 'off',
      '@typescript-eslint/no-explicit-any': 'off'
    }
  }
];
