import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import ReactivityTransform from '@vue-macros/reactivity-transform/vite';

export default defineConfig({
  plugins: [
    ReactivityTransform(),
    laravel({
      input: [
        'resources/vendor/Witchcraft/js/vue.js',
        'resources/vendor/Witchcraft/sass/app.scss',
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
      reactivityTransform: true
    }),
  ],
  build: {
    outDir: 'public/vendor/Witchcraft',
    emptyOutDir: true,
    rollupOptions: {
      input: {
        vue: 'resources/vendor/Witchcraft/js/vue.js',
        css: 'resources/vendor/Witchcraft/sass/app.scss',
      },
    },
  },
  server: {
    host: '0.0.0.0',
    hmr: {
      host: 'localhost'
    }
  },
});
