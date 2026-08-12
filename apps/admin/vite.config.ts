import { fileURLToPath } from 'node:url';

import react from '@vitejs/plugin-react';
// `vitest/config` rather than `vite`: it is the same defineConfig widened to
// understand the `test` block, so the app and its tests share one configuration
// instead of drifting apart in two files.
import { defineConfig } from 'vitest/config';

/**
 * A static single-page application. There is no server here to deploy, and no
 * secret reaches the bundle — the panel holds a staff token in memory only,
 * obtained by signing in (ADR-0013).
 */
export default defineConfig({
  plugins: [react()],
  build: {
    // Committed and served by the existing edge, so the output must be
    // reproducible rather than convenient.
    outDir: 'dist',
    sourcemap: true,
  },
  server: {
    port: 5173,
    /*
     * The API is same-origin in every deployed environment, so the panel calls
     * relative paths. In development this proxy reproduces that, which keeps
     * the browser's credential and CORS behaviour identical to production —
     * a development setup that needs CORS relaxations tests a configuration
     * nobody ships.
     */
    proxy: {
      '/api': {
        target: process.env.KMS_API_ORIGIN ?? 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
  test: {
    /*
     * Pinned to this package rather than inferred. Inside a pnpm workspace
     * Vitest otherwise walks up to the workspace root and resolves every
     * relative import against the wrong directory.
     */
    root: fileURLToPath(new URL('.', import.meta.url)),
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./src/test/setup.ts'],
  },
});
