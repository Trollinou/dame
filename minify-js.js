const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const srcDir = path.join(__dirname, 'src/js');
const distDir = path.join(__dirname, 'assets/js');

if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
}

fs.readdirSync(srcDir).forEach(file => {
    if (path.extname(file) === '.js') {
        const input = path.join(srcDir, file);
        const output = path.join(distDir, file);
        console.log(`Minifying ${file}...`);
        try {
            execSync(`npx terser "${input}" -o "${output}" --compress --mangle`);
        } catch (error) {
            console.error(`Error minifying ${file}:`, error.message);
        }
    }
});
