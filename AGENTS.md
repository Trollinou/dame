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
  - **Nommage Strict des Variables Globales JS (snake_case obligatoire)** :
    - Toute variable globale exposée à JavaScript via `wp_localize_script()` ou `wp_add_inline_script()` doit impérativement respecter la convention `snake_case` préfixée par `dame_` (ex: `dame_mailing_data`, `dame_admin_data`, `dame_attestation_data`, `dame_newsletter_data`, `dame_admin_commands`). L'introduction de noms en `camelCase` (ex: `dameMailingData`, `dameAdminCommands`, `dameAttestationData`) est formellement interdite.
    - Dans `src/types/globals.d.ts`, interdiction de déclarer des interfaces ou variables globales fantômes (non injectées par PHP ou non consommées par les scripts réels). Toute déclaration globale doit avoir une correspondance 1-pour-1 effective.

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
- **Gestion Fine des Assets & Performance (WordPress 7.1)** :
  - **Séparation Stricte Enregistrement vs Mise en file d'attente** : Toujours pré-enregistrer les styles, scripts et script modules via `wp_register_style()`, `wp_register_script()` et `wp_register_script_module()` avant tout appel à `wp_enqueue_*()`. Interdiction formelle d'appeler `wp_enqueue_*()` de manière inconditionnelle ou globale sur les hooks `wp_enqueue_scripts` ou `admin_enqueue_scripts`.
  - **Stratégie de Chargement Différée (`defer`)** : Utiliser impérativement le tableau d'arguments `array( 'strategy' => 'defer', 'in_footer' => true )` en 5ᵉ paramètre de `wp_register_script()` / `wp_enqueue_script()`. L'ancien booléen `$in_footer = true` est formellement interdit.
  - **Script Modules ESM & Unicité Réactive** : Les Script Modules (`dame/*`) sont le standard natif par défaut pour l'Interactivity API. Tout composant interactif ne doit charger aucun script JS classique en doublon.
  - **Chargement Contextuel Front-End** :
    - N'enfiler les feuilles de style (`dame-public-styles`) et scripts spécifiques que sur les vues singulières pertinentes (`is_singular(...)`), lors du rendu effectif d'un shortcode (`render()`) ou via `block.json` (`style`, `viewScriptModule`).
    - N'enfiler les dépendances tierces (ex: police d'icônes `dashicons`) que si le bloc/shortcode l'exige expressément (ex: bouton avec icône activée).
  - **Isolation Back-End / Admin** :
    - Filtrer strictement tout enqueue d'administration via `$hook_suffix` ou `get_current_screen()`.
    - Isoler les scripts lourds (palette de commandes, autocomplétion IGN, coordonnées GPS) et la sérialisation des dictionnaires de données (`wp_localize_script`) aux seuls écrans et formulaires qui les consomment directement.
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
- **Files d'attente d'envoi & EG Mail** : Déléguer la file d'attente FIFO et les quotas d'envoi d'emails à EG Mail via la fonction native `wp_mail()`. Piloter la priorité et les lots via les en-têtes standardisés `X-Queue-Priority` et `X-Queue-Batch`.
- **HTML API** : Utiliser `WP_HTML_Tag_Processor` / `WP_HTML_Processor` pour toute manipulation ou injection dans le HTML (e-mails, tracking, wrappers) au lieu de regex.
- **Sécurité WP** : Nonce + Capability checks (`manage_options`, `edit_posts`) systématiques. Input sanitization + Output escaping (`esc_html`, `esc_attr`). Ne jamais utiliser de mots-clés réservés du langage (ex: `$default`) comme noms d'arguments (utiliser `$default_value`).
- **Post Meta** : Attribut `name` HTML sans `_`, mais enregistrement meta BDD avec `_` (ex: `_dame_identity_name`). Déclaration systématique avec `register_post_meta()` et `show_in_rest => true` pour Block Bindings & REST API.
- **Shortcodes** : Capturer `wp_editor()` via `ob_start()` / `ob_get_clean()`.
- **Mailing, Résolution de Destinataires & Filtres Géographiques** :
  - **Étanchéité Serveur** : Le résolveur de destinataires (`RecipientResolver`) ne doit jamais présumer que le client JavaScript a converti les critères de plus haut niveau (ex: sélection d'une région) en critères fins (ex: départements cochés).
  - **Prise en compte des Régions** : Toute sélection de régions (`dame_contact_regions`) doit être résolue côté serveur en dérivant les départements via `Data_Provider::get_departments_by_region()` et en combinant dans une `meta_query` (relation `OR`) la région (`_dame_contact_region`) et les départements ciblés (`_dame_contact_department`), afin d'empêcher toute fuite vers des contacts non concernés.

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