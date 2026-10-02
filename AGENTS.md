# Directives Agent & Règles projet — DAME

## 1. Outillage & MCP
- Interdiction d'utiliser `get_repository_content` sur la racine. Utiliser uniquement `search_code` ou `get_file_content` ciblés.
- Ne JAMAIS réécrire un fichier complet pour une modification. Fournir des diffs ou des fonctions isolées. Pas de disclaimers ni commentaires verbeux.

## 2. Stack Technique
- **Plugin** : `DAME` | Slug: `dame` | Prefix: `dame_` | Namespace: `DAME\` | Table: `{$wpdb->prefix}dame_`
- **WordPress** : 7.1 (Interactivity API, Script Modules, Block Bindings API, Transients).
- **PHP** : 8.4 avec `declare(strict_types=1);`. Composer AUTORISÉ en prod (`composer install --no-dev --optimize-autoloader`). Inclure `vendor/autoload.php` + Autoloader SPL natif fallback dans `dame.php`.
- **JS / CSS** : TypeScript Strict (`strict: true`, ES2021 Vanilla, pas de jQuery), SCSS avec BEM.
  - Scripts classiques Admin/Front dans `src/js/*.ts` compilés dans `assets/js/*.js`.
  - **Script Modules (Interactivity API)** dans `src/js/modules/*.ts` compilés en ESM dans `assets/js/modules/*.js` avec `@wordpress/interactivity` externalisé.
  - **Contrat PWA** : Types dans `src/types/` rigoureusement isolés sans aucune dépendance WordPress ni DOM.

## 3. Architecture & Structure
- **PSR-4 / Namespaces** : Sous-dossiers dans `includes/` en PascalCase (`includes/Admin/`, `includes/CPT/`, `includes/DTO/`, `includes/Blocks/`). Fichiers/classes en PascalCase.
- **Blocs Gutenberg & Bindings** : Blocs hybrides déclarés avec `block.json` (catégorie `dame`, `viewScriptModule`) dans `blocks/`. Sources Block Bindings déclarées dans `DAME\Blocks\Manager`. Modèles FSE dans `templates/`.
- **Exhaustivité des Shortcodes** : Toute évolution transverse doit couvrir l'intégralité des 6 shortcodes (`dame_agenda`, `dame_liste_agenda`, `dame_fiche_inscription`, `dame_contact`, `dame_newsletter`, `dame_benevolat`).
- **Assets centralisés** : `assets/css/`, `assets/js/` et `assets/js/modules/`. Naming: `{contexte}-{composant}.{ext}`. Enqueue handles préfixés par `dame-`.
- **Complexité = Sous-dossier** : Si > 300-400 lignes, découper la classe/module dans un sous-dossier thématique avec le pattern Manager/Components. Une classe = Un fichier.

## 4. Règles Code & Sécurité
- **PHP 8.4** : Promoted properties, Enums typés, DTO `readonly`, strict return types. `$wpdb->prepare` obligatoire.
- **HTML API** : Utiliser `WP_HTML_Tag_Processor` / `WP_HTML_Processor` pour toute manipulation ou injection dans le HTML (e-mails, tracking, wrappers) au lieu de regex.
- **Sécurité WP** : Nonce + Capability checks (`manage_options`, `edit_posts`) systématiques. Input sanitization + Output escaping (`esc_html`, `esc_attr`).
- **Post Meta** : Attribut `name` HTML sans `_`, mais enregistrement meta BDD avec `_` (ex: `_dame_identity_name`). Déclaration systématique avec `register_post_meta()` et `show_in_rest => true` pour Block Bindings & REST API.
- **Shortcodes** : Capturer `wp_editor()` via `ob_start()` / `ob_get_clean()`.

## 5. QA & Conformité
- Config PHPStan Level 7 (`phpstan.neon`), PHPCS (détection des écarts de standards) et PHPCBF (correction automatique du style) + ESLint WP (`eslint.config.js`).
- Versionning sémantique synchronisé : `dame.php`, constante `DAME_VERSION`, `package.json`, `CHANGELOG.md`, `RELEASE.md`.