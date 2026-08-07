import js from '@eslint/js';
import globals from 'globals';
import reactHooks from 'eslint-plugin-react-hooks';
import reactRefresh from 'eslint-plugin-react-refresh';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  { ignores: ['dist', 'dist-ssr', 'node_modules'] },
  {
    extends: [js.configs.recommended, ...tseslint.configs.recommended],
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2022,
      globals: globals.browser,
    },
    plugins: {
      'react-hooks': reactHooks,
      'react-refresh': reactRefresh,
    },
    rules: {
      ...reactHooks.configs.recommended.rules,
      'react-refresh/only-export-components': ['warn', { allowConstantExport: true }],
    },
  },
  {
    /**
     * These modules deliberately export hooks and helpers alongside their
     * components — `useContent` belongs with the context it reads, and the
     * prerenderer needs `render` and the config from the SSR entry point.
     * Splitting them apart to satisfy the rule would trade real cohesion for
     * slightly finer hot-reload granularity in dev.
     */
    files: [
      'src/i18n/LocaleContext.tsx',
      'src/components/seo/Seo.tsx',
      'src/components/ui/primitives.tsx',
      'src/entry-server.tsx',
    ],
    rules: { 'react-refresh/only-export-components': 'off' },
  },
  {
    // Build scripts run in Node and are plain JS.
    files: ['scripts/**/*.mjs', 'vite.config.ts', '*.config.js'],
    languageOptions: { globals: globals.node },
    rules: { 'no-console': 'off' },
  },
);
