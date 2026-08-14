# Directives Agent & Règles projet — DAME

## 1. Outillage & MCP
- Interdiction d'utiliser `get_repository_content` sur la racine. Utiliser uniquement `search_code` ou `get_file_content` ciblés.
- Ne JAMAIS réécrire un fichier complet pour une modification. Fournir des diffs ou des fonctions isolées. Pas de disclaimers ni commentaires verbeux.

## 2. Stack Technique
- **Plugin** : `DAME` | Slug: `dame` | Prefix: `dame_` | Namespace: `DAME\` | Table: `{$wpdb->prefix}dame_`
- **WordPress** : 7.0.1 (Interactivity API, Transients).
- **PHP** : 8.4 avec `declare(strict_types=1);`. Composer AUTORISÉ en prod (`composer install --no-dev --optimize-autoloader`). Inclure `vendor/autoload.php` + Autoloader SPL natif fallback dans `dame.php`.
- **JS / CSS** : ES2021 Vanilla (pas de jQuery), SCSS avec BEM. Sources dans `src/`, compilés dans `build/` et `assets/`.

## 3. Architecture & Structure
- **PSR-4 / Namespaces** : Sous-dossiers dans `includes/` en PascalCase (`includes/Admin/`, `includes/CPT/`, `includes/DTO/`). Fichiers/classes en PascalCase.
- **Assets centralisés** : `assets/css/` et `assets/js/`. Naming: `{contexte}-{composant}.{ext}` (ex: `admin-members.js`). Enqueue handles préfixés par `dame-`.
- **Complexité = Sous-dossier** : Si > 300-400 lignes, découper la classe/module dans un sous-dossier thématique avec le pattern Manager/Components. Une classe = Un fichier.

## 4. Règles Code & Sécurité
- **PHP 8.4** : Promoted properties, Enums typés, DTO `readonly`, strict return types. `$wpdb->prepare` obligatoire.
- **Sécurité WP** : Nonce + Capability checks (`manage_options`) systématiques. Input sanitization + Output escaping (`esc_html`, `esc_attr`).
- **Post Meta** : Attribut `name` HTML sans `_`, mais enregistrement meta BDD avec `_` (ex: `_dame_identity_name`).
- **Shortcodes** : Capturer `wp_editor()` via `ob_start()` / `ob_get_clean()`.

## 5. QA & Conformité
- Config PHPStan Level 6 (`phpstan.neon`), PHPCS (détection des écarts de standards) et PHPCBF (correction automatique du style) + ESLint WP (`eslint.config.js`).
- Versionning sémantique synchronisé : `dame.php`, constante `DAME_VERSION`, `package.json`, `CHANGELOG.md`, `RELEASE.md`.