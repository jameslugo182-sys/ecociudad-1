import react from '@vitejs/plugin-react';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [react()],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./tests/js/setup.js'],
        include: ['tests/js/**/*.test.{js,jsx}'],
        coverage: {
            provider: 'v8',
            reporter: ['lcovonly', 'text-summary'],
            reportsDirectory: 'coverage',
            // Solo se reportan los archivos que las pruebas cargan.
            exclude: ['tests/**', 'vitest.config.js', 'vite.config.js'],
        },
    },
});
