import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5173,
    historyApiFallback: true,
    proxy: {
      '/api': 'http://backend:80',
      '/login': 'http://backend:80',
      '/logout': 'http://backend:80',
    },
  },
});
