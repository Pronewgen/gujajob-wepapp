import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import { readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

function collectFiles(directory, extension) {
    const entries = readdirSync(directory);
    const files = [];

    entries.forEach((entry) => {
        const absolutePath = join(directory, entry);
        const fileStat = statSync(absolutePath);

        if (fileStat.isDirectory()) {
            files.push(...collectFiles(absolutePath, extension));
            return;
        }

        if (absolutePath.endsWith(extension)) {
            files.push(absolutePath);
        }
    });

    return files;
}

const viteInputs = [
    ...collectFiles('resources/css', '.css'),
    ...collectFiles('resources/js', '.js'),
].sort();

export default defineConfig({
    plugins: [
        laravel({
            input: viteInputs,
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
