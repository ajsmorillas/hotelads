import { defineConfig } from 'astro/config';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
  // AÑADE ESTA LÍNEA (sustituye por tu dominio real)
  site: 'https://hotelads.es',
  
  vite: {
    plugins: [tailwindcss()],
    build: {
      assetsInlineLimit: 10240 
    }
  }
});