import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  server: {
    historyApiFallback: true,
  },


server: {
  proxy: {
    '/api': 'http://localhost:8000',
    '/login': 'http://localhost:8000',
    '/logout': 'http://localhost:8000',
  },
}
});