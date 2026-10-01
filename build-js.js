const fs = require('fs');
const path = require('path');
const esbuild = require('esbuild');

const srcDir = path.join(__dirname, 'src/js');
const distDir = path.join(__dirname, 'assets/js');

if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
}

const entries = fs.readdirSync(srcDir)
    .filter(file => (file.endsWith('.ts') || file.endsWith('.js')) && !file.endsWith('.d.ts'))
    .map(file => path.join(srcDir, file));

console.log(`Building ${entries.length} script(s) with esbuild...`);

entries.forEach(entry => {
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
        console.log(`✓ Built ${filename}`);
    } catch (error) {
        console.error(`✗ Error building ${filename}:`, error.message);
        process.exit(1);
    }
});
console.log('Build completed successfully.');
