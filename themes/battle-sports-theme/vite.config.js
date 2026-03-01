import { defineConfig } from 'vite';
import { resolve, dirname } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname( fileURLToPath( import.meta.url ) );

export default defineConfig({
  root: resolve( __dirname, 'assets/src' ),
  base: '/wp-content/themes/battle-sports-theme/assets/dist/',
  build: {
    outDir: resolve( __dirname, 'assets/dist' ),
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: resolve( __dirname, 'assets/src/js/main.js' ),
      output: {
        entryFileNames: 'js/[name].js',
        chunkFileNames: 'js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          if (assetInfo.name?.endsWith('.css')) {
            return 'css/style[extname]';
          }
          return 'assets/[name]-[hash][extname]';
        },
      },
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@use "sass:math";`,
      },
    },
  },
});
