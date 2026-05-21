#!/bin/bash

# Configuration
PLUGIN_SLUG="dame"
MAIN_FILE="dame.php"
DIST_IGNORE=".distignore"

# Récupération de la version depuis le fichier PHP principal
VERSION=$(grep -m 1 "Version:" $MAIN_FILE | awk '{print $NF}' | tr -d '\r')

if [ -z "$VERSION" ]; then
    echo "Erreur : Impossible de trouver la version dans $MAIN_FILE"
    exit 1
fi

ZIP_NAME="${PLUGIN_SLUG}-v${VERSION}.zip"
BUILD_DIR="dist-temp"

echo "🏗️  Compilation des assets locaux (Production)..."
if ! npm run build; then
    echo "❌ Erreur : Le build a échoué. Packaging annulé."
    exit 1
fi

# ---> NOUVELLE ÉTAPE : Compilation de la PWA <---
echo "📱 Compilation de la PWA..."
cd pwa || exit 1
# On installe les dépendances PWA (au cas où) et on compile
npm install
if ! npm run build; then
    echo "❌ Erreur : Le build de la PWA a échoué. Packaging annulé."
    exit 1
fi
# On remonte à la racine du plugin
cd ..
# ------------------------------------------------

echo "📦 Préparation du répertoire temporaire..."
rm -f "$ZIP_NAME"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

# Construction de la commande rsync avec exclusion
RSYNC_EXCLUDES=""
if [ -f "$DIST_IGNORE" ]; then
    RSYNC_EXCLUDES="--exclude-from=$DIST_IGNORE"
fi

# Copie des fichiers vers le dossier temporaire (SANS le vendor local)
# On force l'inclusion de composer.json/lock pour l'install propre dans dist-temp
rsync -av . "$BUILD_DIR/$PLUGIN_SLUG/" $RSYNC_EXCLUDES --exclude="$BUILD_DIR" --exclude="*.sh" --exclude="*.zip" --exclude="/vendor"
cp composer.json composer.lock "$BUILD_DIR/$PLUGIN_SLUG/"

echo "📦 Installation isolée des dépendances Composer (Production)..."
cd "$BUILD_DIR/$PLUGIN_SLUG" || exit 1

if ! composer install --no-dev --optimize-autoloader --no-interaction --quiet; then
    echo "❌ Erreur : L'installation Composer a échoué dans le dossier temporaire."
    exit 1
fi

echo "🧹 Nettoyage des fichiers inutiles dans vendor (FPDF)..."
rm -rf vendor/setasign/fpdf/doc
rm -rf vendor/setasign/fpdf/tutorial
rm -f vendor/setasign/fpdf/FAQ.htm vendor/setasign/fpdf/changelog.htm vendor/setasign/fpdf/install.txt

# Suppression des fichiers composer devenus inutiles pour le ZIP
rm composer.json composer.lock

echo "🤐 Création du ZIP..."
cd ..
zip -r "../$ZIP_NAME" "$PLUGIN_SLUG" > /dev/null
cd ..

# Nettoyage final
rm -rf "$BUILD_DIR"

echo "✅ Package créé avec succès : ${ZIP_NAME} (Environnement local préservé)"
echo "ℹ️  La PWA a été compilée avec succès et incluse dans le plugin."
