import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
        },
    },
    build: {
        rollupOptions: {
            output: {
                // Three.js only loads on pages with a 3D scene.
                manualChunks: {
                    three: ['three', '@react-three/fiber'],
                    motion: ['motion'],
                    // Shared by the seat map page and the lazy 3D hall. Left alone, Rollup
                    // merges it into the page's chunk, the page loses its manifest entry
                    // and Laravel answers the seat map with "Unable to locate file".
                    'hall-layout': [fileURLToPath(new URL('./resources/js/three/hallLayout.ts', import.meta.url))],
                },
            },
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
