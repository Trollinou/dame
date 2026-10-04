# Directives Agent & Règles projet — DAME

## 1. Outillage & MCP
- Interdiction d'utiliser `get_repository_content` sur la racine. Utiliser uniquement `search_code` ou `get_file_content` ciblés.
- Ne JAMAIS réécrire un fichier complet pour une modification. Fournir des diffs ou des fonctions isolées. Pas de disclaimers ni commentaires verbeux.
- **Consolidation des scripts** : Tous les scripts d'outillage Node.js / packaging / synchronisation résident obligatoirement dans `scripts/` (aucun dossier `script/`).
- **Pipeline de Build Parallèle** : Les scripts de bundling (`build-js.js`) doivent compiler les points d'entrée en parallèle asynchrone non-bloquant (`Promise.all` avec l'API `esbuild.build`).
- **Contrôle Qualité Pré-Packaging** : Le script de release (`scripts/package.cjs`) doit obligatoirement valider l'intégrité de la suite QA (`typecheck`, `lint:js`, `phpstan`) avant de générer l'archive de production.

## 2. Stack Technique
- **Plugin** : `DAME` | Slug: `dame` | Prefix: `dame_` | Namespace: `DAME\` | Table: `{$wpdb->prefix}dame_`
- **WordPress** : 7.1 (Interactivity API, Script Modules, Block Bindings API, Template Registration API, HTML API, Transients).
- **Options API & Autoload (WP 7.1)** : Spécifier impérativement le paramètre `'autoload' => false` lors de l'enregistrement (`add_option()`, `register_setting()`) de toute option volumineuse ou ponctuelle (backups, logs, mappings étendus) afin de préserver la mémoire du bootstrap WordPress.
- **PHP** : 8.4 avec `declare(strict_types=1);`.
  - Promoted properties, Enums typés, DTO `readonly`, strict return types.
  - Typage strict des paramètres : interdiction formelle des types implicites nullables (utiliser explicitement `?Type $param = null`).
  - Composer AUTORISÉ en prod (`composer install --no-dev --optimize-autoloader`). Inclure `vendor/autoload.php` + Autoloader SPL natif fallback dans `dame.php`.
- **JS / CSS** : TypeScript Strict (`strict: true`, ES2021 Vanilla, pas de jQuery), SCSS avec BEM.
  - Scripts classiques Admin/Front dans `src/js/*.ts` compilés dans `assets/js/*.js`.
  - **Script Modules (Interactivity API)** dans `src/js/modules/*.ts` compilés en ESM dans `assets/js/modules/*.js` avec `@wordpress/interactivity` externalisé.
  - **Contrat PWA** : Types dans `src/types/` rigoureusement isolés sans aucune dépendance WordPress ni DOM.

## 3. Architecture & Structure
- **PSR-4 / Namespaces** : Sous-dossiers dans `includes/` en PascalCase (`includes/Admin/`, `includes/CPT/`, `includes/DTO/`, `includes/Blocks/`). Fichiers/classes en PascalCase. Déclarer `"autoload": { "psr-4": { "DAME\\": "includes/" } }` dans `composer.json`.
- **Cycle de Vie Événementiel & Lazy Loading** :
  - **API REST** : Ne jamais instancier les contrôleurs de routes REST au bootstrap global. Encapsuler leur enregistrement dans le hook `rest_api_init`.
  - **Services d'Administration** : Isoler strictement les services d'import/export/backup et metaboxes sous le bloc conditionnel `if ( is_admin() )`.
- **Blocs Gutenberg, Bindings & Templates FSE (WP 7.1)** :
  - Blocs hybrides déclarés avec `block.json` (catégorie `dame`, `viewScriptModule`) dans `blocks/`.
  - Sources Block Bindings déclarées dans `DAME\Blocks\Manager` avec `show_in_rest => true` pour supporter l'édition directe dans le canevas de l'éditeur.
  - Déclaration formelle des modèles FSE via `register_block_template()`.
- **Exhaustivité des Shortcodes** : Toute évolution transverse doit couvrir l'intégralité des 6 shortcodes (`dame_agenda`, `dame_liste_agenda`, `dame_fiche_inscription`, `dame_contact`, `dame_newsletter`, `dame_benevolat`).
- **Gestion Fine des Assets Frontend** :
  - Enregistrement des feuilles de style via `wp_register_style()`. Ne charger `dame-public-styles` que sur les pages contenant des blocs, shortcodes ou CPT DAME (`is_singular(...)`) pour préserver les Web Vitals globaux.
  - Unicité réactive : Tout composant géré par l'Interactivity API ne doit avoir aucun script JS classique résiduel en doublon.
- **Complexité = Sous-dossier** : Si > 300-400 lignes, découper la classe/module dans un sous-dossier thématique avec le pattern Manager/Components. Une classe = Un fichier.

## 4. Règles Code, Sécurité & Caching
- **Repositories & Object Cache (`wp_cache_*`)** :
  - Toute requête SQL personnalisée `$wpdb` encapsulée dans un Repository doit interroger l'Object Cache WordPress (`wp_cache_get`) avant d'exécuter la requête, puis stocker le résultat (`wp_cache_set`) dans un groupe dédié (`dame_members`, `dame_agenda`).
  - **Invalidation Déterministe** : Tout Repository doit écouter les hooks de mutation (`save_post_{cpt}`, `deleted_post`, `set_object_terms`) pour purger atomiquement les clés de cache concernées (`wp_cache_delete`).
- **Validation Déclarative REST (WP 7.1)** : Déclarer systématiquement les règles de typage, `validate_callback` et `sanitize_callback` dans la structure `args` de `register_rest_route()`.
- **Requêtes BDD (`$wpdb`)** : `$wpdb->prepare` obligatoire. Lors de l'utilisation de `$wpdb->insert()` ou `$wpdb->update()`, veiller à la stricte parité entre le nombre d'éléments du tableau `$data` et les spécificateurs du tableau `$format`.
- **Formulaires, Interactivity API & Idempotence** :
  - **Unicité du gestionnaire** : Un formulaire ne doit avoir qu'un seul point d'entrée de soumission (ne jamais cumuler un écouteur JS classique et une directive `data-wp-on--submit`).
  - **Anti-rebond Client** : Vérifier un verrou synchrone (`if (ctx.isSubmitting) return;`), stopper la propagation (`event.stopPropagation()`) et désactiver immédiatement le bouton submit dans le DOM.
  - **Idempotence Serveur** : Poser un verrou transient court (10 à 15s) sur l'empreinte de la soumission pour garantir l'unicité du traitement et des e-mails en cas de requêtes concurrentes.
- **Files d'attente & WP-Cron** : Respecter l'architecture asynchrone des services (ex: `BatchSender`). Ne pas forcer d'exécution synchrone en contournement sans avoir préalablement vérifié l'intégrité de l'insertion en base de données.
- **HTML API** : Utiliser `WP_HTML_Tag_Processor` / `WP_HTML_Processor` pour toute manipulation ou injection dans le HTML (e-mails, tracking, wrappers) au lieu de regex.
- **Sécurité WP** : Nonce + Capability checks (`manage_options`, `edit_posts`) systématiques. Input sanitization + Output escaping (`esc_html`, `esc_attr`). Ne jamais utiliser de mots-clés réservés du langage (ex: `$default`) comme noms d'arguments (utiliser `$default_value`).
- **Post Meta** : Attribut `name` HTML sans `_`, mais enregistrement meta BDD avec `_` (ex: `_dame_identity_name`). Déclaration systématique avec `register_post_meta()` et `show_in_rest => true` pour Block Bindings & REST API.
- **Shortcodes** : Capturer `wp_editor()` via `ob_start()` / `ob_get_clean()`.

## 5. QA, Tests & Conformité
- **Analyse Statique & Standards** : Config PHPStan Level 7 (`phpstan.neon`), PHPCS (détection des écarts de standards) et PHPCBF (correction automatique du style) + ESLint WP (`eslint.config.js`).
- **Tests Unitaires (PHPUnit 11)** : Couvrir les calculs purs et règles métier critiques (DTO, `Recurrence_Calculator`, `Adherent_Matcher`) par des tests PHPUnit purs et rapides configurés dans `phpunit.xml.dist`.
- **Versionning Sémantique Synchronisé** : `dame.php`, constante `DAME_VERSION`, `package.json`, `CHANGELOG.md`, `RELEASE.md` et `block.json` (synchronisés via `scripts/version-sync.cjs`).

## 6. Performance Frontend, Web Vitals & Compatibilité Safari (WebKit / macOS)
- **Rendu Virtuel & INP** :
  - Appliquer `content-visibility: auto; contain-intrinsic-size: ...;` sur les cartes et listes d'éléments répétées (listes d'agenda, annuaires) pour décharger le moteur de rendu des éléments hors écran.
- **Overlays & Modales** :
  - Interdiction formelle de `backdrop-filter: blur(...)` sur les calques/backdrops contenant des champs de saisie (génère une latence majeure de frappe sous WebKit macOS due au recalcul permanent du flou plein écran).
  - Éviter `overflow: hidden` sur `body` lors de l'ouverture d'une modale (perturbe l'arbre de défilement Safari). Utiliser `overscroll-behavior: contain` sur le conteneur modale.
  - Structure de positionnement : Modale racine en `position: fixed; inset: 0;`, Backdrop en `position: absolute; inset: 0;`, et dialogue avec isolation matérielle (`isolation: isolate;`, `transform: translateZ(0);`, `contain: layout style;`).
- **Champs de formulaire & Autofill** :
  - **Autocorrection (`autocorrect="off"`)** : À désactiver sur les champs de type Nom, Prénom, Email, Identifiant (évite les remplacements automatiques intempestifs du dictionnaire Safari/iOS).
  - **Correcteur orthographique (`spellcheck`)** : À conserver actif (`true` par défaut) sur les zones de texte libre / messages (`textarea`, sujets, descriptions).
  - **Autocomplétion (`autocomplete="off"`)** : À utiliser sur les formulaires simples pour empêcher Safari d'interroger en tâche de fond le carnet d'adresses macOS (XPC) à chaque frappe.
  - **Honeypots anti-spam** : Placer impérativement les honeypots en fin de `<form>` avec `display: none !important;` (ne jamais utiliser de `left: -9999px` en tête de formulaire, ce qui trompe l'heuristique AutoFill de Safari).

## 7. Intégration Thèmes WordPress (Blocksy / FSE)
- **Variables & Palettes** :
  - Mapper les couleurs de texte et de fond sur les tokens de palette WordPress & Blocksy (ex: `--theme-palette-color-3` pour le texte sombre principal, `--theme-palette-color-8` pour le blanc).
- **Cohérence DOM / Sélecteurs** :
  - Toujours vérifier que les règles SCSS ciblent les sélecteurs DOM réels instanciés par les modules TypeScript (`src/js/*.ts`) avant d'introduire des classes BEM.