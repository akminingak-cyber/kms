import { defineConfig, type Plugin } from 'vite';
import react from '@vitejs/plugin-react';
import { constants, brotliCompressSync, gzipSync } from 'node:zlib';
import { readFileSync, writeFileSync, statSync, readdirSync } from 'node:fs';
import path from 'node:path';

/**
 * Emits a .br and .gz next to every JS/CSS asset so Apache can serve a
 * pre-compressed file straight from disk instead of compressing on every
 * request. The matching rewrite rules live in public/.htaccess; if this plugin
 * is ever removed, those rules simply stop firing and the originals are served.
 */
function precompress(): Plugin {
  return {
    name: 'kms-precompress',
    apply: 'build',
    enforce: 'post',
    closeBundle() {
      const dir = path.resolve('dist/assets');
      let files: string[] = [];
      try {
        files = readdirSync(dir);
      } catch {
        return;
      }
      for (const file of files) {
        if (!/\.(js|css)$/.test(file)) continue;
        const full = path.join(dir, file);
        const source = readFileSync(full);
        // Below ~1.5 KB the compressed file plus its response headers is rarely
        // a win, and every extra file is another thing to upload.
        if (statSync(full).size < 1500) continue;
        writeFileSync(
          `${full}.br`,
          brotliCompressSync(source, {
            params: {
              [constants.BROTLI_PARAM_QUALITY]: 11,
              [constants.BROTLI_PARAM_SIZE_HINT]: source.length,
            },
          }),
        );
        writeFileSync(`${full}.gz`, gzipSync(source, { level: 9 }));
      }
    },
  };
}

export default defineConfig({
  plugins: [react(), precompress()],
  build: {
    target: 'es2020',
    cssCodeSplit: false,
    reportCompressedSize: false,
    rollupOptions: {
      output: {
        /**
         * Three groups, chosen so a visitor downloads as little as possible:
         *  - `react`   changes only on a framework upgrade, so it stays cached
         *              across every deploy of the site itself;
         *  - `content-ka` / `content-en` are ~120 KB of copy each and are
         *              dynamically imported, so a Georgian visitor never pays
         *              for the English text;
         *  - everything else is the app.
         */
        manualChunks(id) {
          if (id.includes('node_modules')) {
            if (/[\\/]node_modules[\\/](react|react-dom|scheduler|react-router)/.test(id)) {
              return 'react';
            }
            return undefined;
          }
          if (id.includes('/src/content/ka.ts')) return 'content-ka';
          if (id.includes('/src/content/en.ts')) return 'content-en';
          return undefined;
        },
      },
    },
  },
  ssr: {
    noExternal: true,
  },
});
