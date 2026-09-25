import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";

import fs from "fs";
import path from "path";

// Buscar automáticamente todos los archivos CSS de los temas
const themeFiles = fs.existsSync(path.resolve(__dirname, "resources/css/themes"))
    ? fs.readdirSync(path.resolve(__dirname, "resources/css/themes"))
        .filter(file => file.endsWith(".css"))
        .map(file => `resources/css/themes/${file}`)
    : [];

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel({
            input: [
                "resources/css/app.css",
                ...themeFiles,
                "resources/js/app.js"
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1600,
    },
});
