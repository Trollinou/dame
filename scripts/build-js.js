const fs = require('fs');
const path = require('path');
const esbuild = require('esbuild');

const rootDir = path.resolve(__dirname, '..');
const srcDir = path.join(rootDir, 'src/js');
const distDir = path.join(rootDir, 'assets/js');
const srcModulesDir = path.join(srcDir, 'modules');
const distModulesDir = path.join(distDir, 'modules');

if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
}
if (!fs.existsSync(distModulesDir)) {
    fs.mkdirSync(distModulesDir, { recursive: true });
}

async function buildAll() {
    const startTime = Date.now();

    // 1. Build standard scripts (IIFE/Browser)
    const classicEntries = fs.existsSync(srcDir)
        ? fs.readdirSync(srcDir, { withFileTypes: true })
            .filter(dirent => dirent.isFile() && (dirent.name.endsWith('.ts') || dirent.name.endsWith('.js')) && !dirent.name.endsWith('.d.ts'))
            .map(dirent => path.join(srcDir, dirent.name))
        : [];

    console.log(`Building ${classicEntries.length} classic script(s) in parallel with esbuild...`);

    const classicPromises = classicEntries.map(async entry => {
        const filename = path.basename(entry).replace(/\.ts$/, '.js');
        const outfile = path.join(distDir, filename);

        await esbuild.build({
            entryPoints: [entry],
            outfile,
            bundle: true,
            minify: true,
            target: 'es2021',
            sourcemap: false,
            platform: 'browser',
        });
        console.log(`✓ Built classic ${filename}`);
    });

    // 2. Build WordPress Script Modules (ESM)
    const moduleEntries = fs.existsSync(srcModulesDir)
        ? fs.readdirSync(srcModulesDir, { withFileTypes: true })
            .filter(dirent => dirent.isFile() && (dirent.name.endsWith('.ts') || dirent.name.endsWith('.js')) && !dirent.name.endsWith('.d.ts'))
            .map(dirent => path.join(srcModulesDir, dirent.name))
        : [];

    console.log(`Building ${moduleEntries.length} script module(s) (ESM) in parallel with esbuild...`);

    const modulePromises = moduleEntries.map(async entry => {
        const filename = path.basename(entry).replace(/\.ts$/, '.js');
        const outfile = path.join(distModulesDir, filename);

        await esbuild.build({
            entryPoints: [entry],
            outfile,
            bundle: true,
            minify: true,
            format: 'esm',
            target: 'es2021',
            sourcemap: false,
            platform: 'browser',
            external: ['@wordpress/interactivity', '@wordpress/*'],
        });
        console.log(`✓ Built module ${filename}`);
    });

    try {
        await Promise.all([...classicPromises, ...modulePromises]);
        const elapsed = Date.now() - startTime;
        console.log(`✨ Build completed successfully in ${elapsed}ms.`);
    } catch (error) {
        console.error('✗ Build failed:', error.message);
        process.exit(1);
    }
}

buildAll();
