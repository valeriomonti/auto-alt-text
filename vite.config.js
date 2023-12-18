import { defineConfig } from 'vite';
import path from 'path';
import postcssNested from 'postcss-nested';


export default defineConfig({
    // Configurazione del percorso base e della directory di output
    base: '/dist/',
    build: {
        manifest: true,
        outDir: path.resolve(__dirname, 'dist'),
        rollupOptions: {
            // Configurazione degli input file
            input: {
                admin: path.resolve(__dirname, 'resources/js/admin.js'),
            },
            output: {
                // Configura qui se necessario ulteriori opzioni di output
            }
        }
    },
    css: {
        postcss: {
            plugins: [
                postcssNested,
                // Aggiungi qui altri plugin PostCSS se necessari
            ],
        },
    },
    // Aggiungi qui ulteriori configurazioni specifiche se necessarie
});
