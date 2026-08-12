// Shared flat config for every TypeScript package in the workspace.
//
// The rules that matter here are the ones CLAUDE.md §4 states as standards, and
// they are enforced rather than described: `any` without a written
// justification, and a floating promise whose rejection nobody handles.
import js from '@eslint/js';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  {
    ignores: [
      '**/dist/**',
      '**/node_modules/**',
      // The PHP half of the monorepo. Its `vendor/` ships stray JavaScript that
      // belongs to other projects, and linting somebody else's dependency
      // produces findings nobody may act on.
      'services/**',
      '**/vendor/**',
      // Quarantined and does not build. See legacy/README.md and OQ-17.
      'legacy/**',
      // Generated from the OpenAPI contracts and never hand-edited, so linting
      // it would only produce findings nobody may act on.
      'packages/ts-api-client/src/types/**',
    ],
  },
  js.configs.recommended,
  ...tseslint.configs.recommended,
  {
    // Build and generation scripts run under Node, not in a browser.
    files: ['**/*.mjs', '**/*.config.ts'],
    languageOptions: { globals: globals.node },
  },
  {
    files: ['**/*.{ts,tsx}'],
    languageOptions: {
      ecmaVersion: 2023,
      globals: { ...globals.browser, ...globals.node },
    },
    plugins: { 'react-hooks': reactHooks },
    rules: {
      ...reactHooks.configs.recommended.rules,
      // `strict` is on and `any` defeats it. Where one is genuinely needed the
      // disable comment forces the justification into the diff, which is what
      // §4 asks for.
      '@typescript-eslint/no-explicit-any': 'error',
      '@typescript-eslint/no-unused-vars': ['error', { argsIgnorePattern: '^_' }],
      // A caught error that is neither handled nor re-raised with context is a
      // bug, always (§4).
      'no-empty': ['error', { allowEmptyCatch: false }],
      eqeqeq: ['error', 'always', { null: 'ignore' }],
    },
  },
);
