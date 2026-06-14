import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';
import sitemap from '@astrojs/sitemap';

export default defineConfig({
  // AÑADE ESTA LÍNEA (sustituye por tu dominio real)
  site: 'https://hotelads.es',

  integrations: [sitemap()],

  vite: {
    plugins: [tailwindcss()],
    build: {
      assetsInlineLimit: 10240
    }
  }
});