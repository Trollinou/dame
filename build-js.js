const fs = require('fs');
const path = require('path');
const esbuild = require('esbuild');

const srcDir = path.join(__dirname, 'src/js');
const distDir = path.join(__dirname, 'assets/js');
const srcModulesDir = path.join(srcDir, 'modules');
const distModulesDir = path.join(distDir, 'modules');

if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
}

// 1. Build standard scripts (IIFE/Browser)
const classicEntries = fs.existsSync(srcDir)
    ? fs.readdirSync(srcDir, { withFileTypes: true })
        .filter(dirent => dirent.isFile() && (dirent.name.endsWith('.ts') || dirent.name.endsWith('.js')) && !dirent.name.endsWith('.d.ts'))
        .map(dirent => path.join(srcDir, dirent.name))
    : [];

console.log(`Building ${classicEntries.length} classic script(s) with esbuild...`);

classicEntries.forEach(entry => {
    const filename = path.basename(entry).replace(/\.ts$/, '.js');
    const outfile = path.join(distDir, filename);

    try {
        esbuild.buildSync({
            entryPoints: [entry],
            outfile,
            bundle: true,
            minify: true,
            target: 'es2021',
            sourcemap: false,
            platform: 'browser',
        });
        console.log(`✓ Built classic ${filename}`);
    } catch (error) {
        console.error(`✗ Error building classic ${filename}:`, error.message);
        process.exit(1);
    }
});

// 2. Build WordPress Script Modules (ESM)
if (fs.existsSync(srcModulesDir)) {
    if (!fs.existsSync(distModulesDir)) {
        fs.mkdirSync(distModulesDir, { recursive: true });
    }

    const moduleEntries = fs.readdirSync(srcModulesDir, { withFileTypes: true })
        .filter(dirent => dirent.isFile() && (dirent.name.endsWith('.ts') || dirent.name.endsWith('.js')) && !dirent.name.endsWith('.d.ts'))
        .map(dirent => path.join(srcModulesDir, dirent.name));

    console.log(`Building ${moduleEntries.length} script module(s) (ESM) with esbuild...`);

    moduleEntries.forEach(entry => {
        const filename = path.basename(entry).replace(/\.ts$/, '.js');
        const outfile = path.join(distModulesDir, filename);

        try {
            esbuild.buildSync({
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
        } catch (error) {
            console.error(`✗ Error building module ${filename}:`, error.message);
            process.exit(1);
        }
    });
}

console.log('Build completed successfully.');
