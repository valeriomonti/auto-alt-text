import { defineConfig } from 'vite';
import path from 'path';
import postcssNested from 'postcss-nested';


export default defineConfig({

    base: '/dist/',
    build: {
        manifest: true,
        outDir: path.resolve(__dirname, 'dist'),
        rollupOptions: {
            input: {
                admin: path.resolve(__dirname, 'resources/js/admin.js'),
                "media-library": path.resolve(__dirname, 'resources/js/media-library.js'),
            },
            output: {
            }
        }
    },
    css: {
        postcss: {
            plugins: [
                postcssNested,

            ],
        },
    },

});
