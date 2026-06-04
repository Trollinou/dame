const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');
const AdmZip = require('adm-zip');
const { Minimatch } = require('minimatch');

const rootDir = path.resolve(__dirname, '..');
const mainFile = path.join(rootDir, 'dame.php');
const distIgnorePath = path.join(rootDir, '.distignore');
const buildDir = path.join(rootDir, 'dist-temp');
const pluginSlug = 'dame';

console.log('🔍 Extraction de la version du plugin...');
if (!fs.existsSync(mainFile)) {
    console.error(`❌ Erreur : Fichier principal ${mainFile} introuvable.`);
    process.exit(1);
}

const mainContent = fs.readFileSync(mainFile, 'utf8');
const versionMatch = mainContent.match(/Version:\s*([^\s\r\n]+)/);
if (!versionMatch) {
    console.error(`❌ Erreur : Impossible de trouver la version dans ${mainFile}`);
    process.exit(1);
}
const version = versionMatch[1].trim();
console.log(`ℹ️  Version détectée : ${version}`);

const zipName = `${pluginSlug}-v${version}.zip`;
const tempDestDir = path.join(buildDir, pluginSlug);

console.log('🏗️  Compilation des assets locaux (Production)...');
try {
    execSync('npm run build', { cwd: rootDir, stdio: 'inherit' });
} catch (error) {
    console.error('❌ Erreur : Le build a échoué. Packaging annulé.');
    process.exit(1);
}

const pwaDir = path.join(rootDir, 'pwa');
if (fs.existsSync(pwaDir)) {
    console.log('📱 Compilation de la PWA...');
    try {
        execSync('npm install', { cwd: pwaDir, stdio: 'inherit' });
        execSync('npm run build', { cwd: pwaDir, stdio: 'inherit' });
    } catch (error) {
        console.error('❌ Erreur : Le build de la PWA a échoué. Packaging annulé.');
        process.exit(1);
    }
}

// Nettoyage préalable
if (fs.existsSync(path.join(rootDir, zipName))) {
    fs.unlinkSync(path.join(rootDir, zipName));
}
if (fs.existsSync(buildDir)) {
    fs.rmSync(buildDir, { recursive: true, force: true });
}
fs.mkdirSync(tempDestDir, { recursive: true });

// Chargement des règles .distignore
let ignoreRules = [];
if (fs.existsSync(distIgnorePath)) {
    fs.readFileSync(distIgnorePath, 'utf8')
        .split(/\r?\n/)
        .map(line => line.trim())
        .filter(line => line && !line.startsWith('#'))
        .forEach(pattern => {
            let matchPath = pattern;
            let isRootRelative = false;
            if (pattern.startsWith('/')) {
                matchPath = pattern.slice(1);
                isRootRelative = true;
            }

            // Si le motif se termine par '/', c'est un dossier.
            // On exclut le dossier lui-même et tout son contenu (comportement identique à gitignore / rsync)
            if (matchPath.endsWith('/')) {
                const dirPath = matchPath.slice(0, -1);
                ignoreRules.push({
                    mm: new Minimatch(dirPath, { dot: true, matchBase: !isRootRelative, nocomment: true })
                });
                ignoreRules.push({
                    mm: new Minimatch(dirPath + '/**', { dot: true, matchBase: !isRootRelative, nocomment: true })
                });
            } else {
                ignoreRules.push({
                    mm: new Minimatch(matchPath, { dot: true, matchBase: !isRootRelative, nocomment: true })
                });
                ignoreRules.push({
                    mm: new Minimatch(matchPath + '/**', { dot: true, matchBase: !isRootRelative, nocomment: true })
                });
            }
        });
}

function isIgnored(relPath) {
    const normalizedPath = relPath.replace(/\\/g, '/');
    if (!normalizedPath) return false;

    // Règles d'exclusion strictes de package.sh + le dossier script/ lui-même
    if (normalizedPath === 'dist-temp' || normalizedPath.startsWith('dist-temp/')) return true;
    if (normalizedPath.endsWith('.sh')) return true;
    if (normalizedPath.endsWith('.zip')) return true;
    if (normalizedPath === 'vendor' || normalizedPath.startsWith('vendor/')) return true;
    if (normalizedPath === 'script' || normalizedPath.startsWith('script/')) return true;

    for (const rule of ignoreRules) {
        if (rule.mm.match(normalizedPath)) {
            return true;
        }
    }
    return false;
}

function copyFiltered(src, dest) {
    if (!fs.existsSync(src)) {
        return;
    }
    const stats = fs.statSync(src);
    const relPath = path.relative(rootDir, src);

    if (isIgnored(relPath)) {
        return;
    }

    if (stats.isDirectory()) {
        fs.mkdirSync(dest, { recursive: true });
        const entries = fs.readdirSync(src);
        for (const entry of entries) {
            copyFiltered(path.join(src, entry), path.join(dest, entry));
        }
    } else {
        const destDir = path.dirname(dest);
        if (!fs.existsSync(destDir)) {
            fs.mkdirSync(destDir, { recursive: true });
        }
        fs.copyFileSync(src, dest);
    }
}

console.log('📦 Préparation du répertoire temporaire et copie des fichiers...');
const rootEntries = fs.readdirSync(rootDir);
for (const entry of rootEntries) {
    copyFiltered(path.join(rootDir, entry), path.join(tempDestDir, entry));
}

// On s'assure d'avoir composer.json et composer.lock pour l'installation isolée
const compJson = path.join(rootDir, 'composer.json');
const compLock = path.join(rootDir, 'composer.lock');
if (fs.existsSync(compJson)) {
    fs.copyFileSync(compJson, path.join(tempDestDir, 'composer.json'));
}
if (fs.existsSync(compLock)) {
    fs.copyFileSync(compLock, path.join(tempDestDir, 'composer.lock'));
}

// Fonction de résolution intelligente de PHP/Composer (notamment pour l'environnement LocalWP)
function resolveComposerCommand() {
    // 1. Essai de Composer global
    try {
        execSync('composer --version', { stdio: 'ignore' });
        return { cmd: 'composer', options: { shell: true } };
    } catch (e) {
        // Non trouvé, on cherche LocalWP
    }

    // 2. Recherche spécifique pour l'application "Local" sur Windows
    if (process.platform === 'win32') {
        const userProfile = process.env.USERPROFILE || process.env.HOMEPATH || '';
        const appData = process.env.APPDATA || path.join(userProfile, 'AppData/Roaming');
        const localAppData = process.env.LOCALAPPDATA || path.join(userProfile, 'AppData/Local');

        const localComposerPhar = path.join(localAppData, 'Programs/Local/resources/extraResources/bin/composer/composer.phar');
        const lightningServicesDir = path.join(appData, 'Local/lightning-services');
        let phpExePath = null;
        let phpExtPath = null;

        if (fs.existsSync(lightningServicesDir)) {
            const dirs = fs.readdirSync(lightningServicesDir);
            const phpDirs = dirs.filter(d => d.startsWith('php-')).sort().reverse();
            const php84Dir = phpDirs.find(d => d.startsWith('php-8.4'));
            const chosenPhpDir = php84Dir || phpDirs[0];

            if (chosenPhpDir) {
                const testPath = path.join(lightningServicesDir, chosenPhpDir, 'bin/win64/php.exe');
                const extPath = path.join(lightningServicesDir, chosenPhpDir, 'bin/win64/ext');
                if (fs.existsSync(testPath)) {
                    phpExePath = testPath;
                    if (fs.existsSync(extPath)) {
                        phpExtPath = extPath;
                    }
                }
            }
        }

        if (fs.existsSync(localComposerPhar) && phpExePath) {
            console.log(`💡 Environnement LocalWP détecté !`);
            console.log(`   - PHP : ${phpExePath}`);
            console.log(`   - Composer : ${localComposerPhar}`);
            
            // Si le dossier d'extensions PHP de LocalWP existe, on active openssl/curl/mbstring via la ligne de commande CLI
            let cliArgs = '';
            if (phpExtPath) {
                cliArgs = ` -d extension_dir="${phpExtPath}" -d extension=openssl -d extension=curl -d extension=mbstring`;
            }
            
            return {
                cmd: `"${phpExePath}"${cliArgs} "${localComposerPhar}"`,
                options: { shell: true }
            };
        }
    }

    // 3. Recherche spécifique pour l'application "Local" sur macOS
    if (process.platform === 'darwin') {
        const homeDir = process.env.HOME || '';
        const localComposerPhar = '/Applications/Local.app/Contents/Resources/extraResources/bin/composer/composer.phar';
        const lightningServicesDir = path.join(homeDir, 'Library/Application Support/Local/lightning-services');
        let phpBinPath = null;

        if (fs.existsSync(lightningServicesDir)) {
            const dirs = fs.readdirSync(lightningServicesDir);
            const phpDirs = dirs.filter(d => d.startsWith('php-')).sort().reverse();
            const php84Dir = phpDirs.find(d => d.startsWith('php-8.4'));
            const chosenPhpDir = php84Dir || phpDirs[0];

            if (chosenPhpDir) {
                const possiblePaths = [
                    path.join(lightningServicesDir, chosenPhpDir, 'bin/sbin/php'),
                    path.join(lightningServicesDir, chosenPhpDir, 'bin/bin/php'),
                    path.join(lightningServicesDir, chosenPhpDir, 'bin/php')
                ];
                for (const p of possiblePaths) {
                    if (fs.existsSync(p)) {
                        phpBinPath = p;
                        break;
                    }
                }
            }
        }

        if (fs.existsSync(localComposerPhar) && phpBinPath) {
            console.log(`💡 Environnement LocalWP détecté !`);
            console.log(`   - PHP : ${phpBinPath}`);
            console.log(`   - Composer : ${localComposerPhar}`);
            return {
                cmd: `"${phpBinPath}" "${localComposerPhar}"`,
                options: { shell: true }
            };
        }
    }

    return null;
}

console.log('📦 Installation isolée des dépendances Composer (Production)...');
const resolver = resolveComposerCommand();

if (!resolver) {
    console.error("\n❌ Erreur : Composer est introuvable sur le système.");
    console.error("1. Assurez-vous que PHP et Composer sont installés localement et accessibles dans votre PATH.");
    console.error("2. Si vous utilisez l'application 'Local', lancez ce script de packaging directement depuis le 'Site Shell' de l'application (bouton 'Open Site Shell' dans Local).\n");
    fs.rmSync(buildDir, { recursive: true, force: true });
    process.exit(1);
}

try {
    // --ignore-platform-reqs permet d'éviter les erreurs de compatibilité si la version locale de PHP
    // diffère légèrement de celle attendue par le fichier composer.lock lors du packaging
    execSync(`${resolver.cmd} install --no-dev --optimize-autoloader --no-interaction --quiet --ignore-platform-reqs`, {
        cwd: tempDestDir,
        stdio: 'inherit',
        shell: resolver.options.shell
    });
} catch (error) {
    console.error("\n❌ Erreur : L'installation Composer a échoué dans le dossier temporaire.");
    fs.rmSync(buildDir, { recursive: true, force: true });
    process.exit(1);
}

console.log('🧹 Nettoyage des fichiers inutiles dans vendor (FPDF)...');
const fpdfDir = path.join(tempDestDir, 'vendor/setasign/fpdf');
if (fs.existsSync(fpdfDir)) {
    fs.rmSync(path.join(fpdfDir, 'doc'), { recursive: true, force: true });
    fs.rmSync(path.join(fpdfDir, 'tutorial'), { recursive: true, force: true });
    ['FAQ.htm', 'changelog.htm', 'install.txt'].forEach(file => {
        const filePath = path.join(fpdfDir, file);
        if (fs.existsSync(filePath)) {
            fs.unlinkSync(filePath);
        }
    });
}

// Nettoyage de composer.json/lock dans la destination avant de zipper
const destCompJson = path.join(tempDestDir, 'composer.json');
const destCompLock = path.join(tempDestDir, 'composer.lock');
if (fs.existsSync(destCompJson)) fs.unlinkSync(destCompJson);
if (fs.existsSync(destCompLock)) fs.unlinkSync(destCompLock);

console.log('🤐 Création du ZIP...');
try {
    const zip = new AdmZip();
    zip.addLocalFolder(tempDestDir, pluginSlug);
    zip.writeZip(path.join(rootDir, zipName));
} catch (error) {
    console.error('❌ Erreur lors de la création du ZIP :', error);
    fs.rmSync(buildDir, { recursive: true, force: true });
    process.exit(1);
}

// Nettoyage final
fs.rmSync(buildDir, { recursive: true, force: true });

console.log(`✅ Package créé avec succès : ${zipName} (Environnement local préservé)`);
console.log('ℹ️  La PWA a été compilée avec succès et incluse dans le plugin.');
