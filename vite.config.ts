import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

/**
 * Assets are NOT pre-compressed to .br/.gz on disk.
 *
 * Serving a pre-compressed twin requires a rewrite from app.css to app.css.br
 * plus a header block to attach Content-Encoding. On cPanel's LiteSpeed that
 * header is matched against the requested path rather than the rewritten one,
 * so it is never attached — the browser gets brotli bytes labelled text/css and
 * quietly drops the stylesheet, leaving the site rendering as unstyled HTML.
 *
 * The output filters in the generated .htaccess compress the same responses,
 * with the server setting Content-Encoding itself. Hashed filenames are cached
 * for a year, so that is one gzip per asset per visitor.
 */
export default defineConfig({
  plugins: [react()],
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
