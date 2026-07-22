# AGENTS — Directives de Développement PWA (Vue 3 / Ionic / eg-chessboard / TanStack Table)

Ce document définit les directives d'architecture, de charte graphique et de conception pour l'application PWA. Tout agent travaillant sur ce projet doit se conformer à ces règles.

## 1. STACK TECHNIQUE & ARCHITECTURE

- **Framework** : Vue 3 (`<script setup lang="ts">`), TypeScript strict.
- **UI Framework** : Ionic Vue (`@ionic/vue`).
- **Store & État** : Pinia (`src/stores/`).
- **Gestion des Requêtes & Caching API** : TanStack Query (`@tanstack/vue-query` + `persistQueryClient`).
- **Gestion des Grilles & Tableaux de Données** : TanStack Table (`@tanstack/vue-table` + `DataTable.vue`).
- **Composants d'Échiquier** : Module `eg-chessboard` (`TheChessboard` / `BoardCore`).

---

## 2. GESTION DES LISTES & GRILLES DE DONNÉES (TANSTACK TABLE & EXPORT CSV)

- **Composant Maître Unifié (`src/components/shared/DataTable/`)** :
  - Toute liste d'administration ou grille de données tabulaires (membres, contacts, messages, bénévolat, etc.) doit **impérativement s'appuyer sur TanStack Table** via le wrapper réutilisable `DataTable.vue`.
- **Rendu Responsive Dual-Mode** :
  - **Desktop / Tablette (`>768px`)** : Rendu HTML Data Grid réactif avec en-têtes fixes (sticky), tri interactif sur les colonnes (`enableSorting: true`) et redimensionnement dynamique.
  - **Mobile (`<=768px`)** : Rendu automatique sous forme de liste Ionic (`ion-list`) via le slot `#mobile-item` pour une lisibilité optimale sur petits écrans.
- **Recherche & Filtres Facettés** :
  - La recherche globale doit être insensible aux accents (via la fonction utilitaire `removeAccents`).
  - Les filtres de vue doivent s'appuyer sur la configuration `DataTableFilterConfig` et utiliser des composants de choix Ionic ergonomiques (`action-sheet`).
- **Gestion de la Visibilité des Colonnes (`columnVisibility`)** :
  - Lorsqu'une colonne sert de critère de filtre (ex: filtre par Saison) mais n'a pas besoin d'être affichée sous forme de colonne dans le tableau HTML desktop, la colonne doit être déclarée dans `columns` puis masquée à l'affichage via la prop `:column-visibility="{ columnId: false }"`.
  - Cela permet d'assurer le filtrage dynamique tout en gardant un affichage épuré.
- **Tri & Accesseurs Typés (`CustomColumnDef`)** :
  - Les définitions de colonnes doivent s'appuyer sur le type `CustomColumnDef<TData>`.
  - Le tri (`enableSorting: true`) doit utiliser un `accessorFn` explicite ou une fonction de tri personnalisée (`sortingFn`) pour les critères non alphabétiques (ex: ordre logique des catégories d'âge).
- **Exportation Universelle CSV / Excel** :
  - Toute grille de données gérée par `DataTable` doit intégrer une configuration d'exportation `DataTableExportConfig`.
  - L'exportation doit s'appuyer sur la fonction utilitaire `exportToCsv` (`src/utils/csvExport.ts`) avec encodage **UTF-8 BOM (`\uFEFF`)** pour garantir l'ouverture sans altération des caractères accentués sous Microsoft Excel.

---

## 3. RÈGLES DE DESIGN & ENCAPSULATION DE L'ÉCHIQUIER (`eg-chessboard`)

### A. Échiquiers Standard (Jeu, Analyse, Visualiseurs d'exercices)
- **Règle absolue** : Les échiquiers de lecture et de jeu (`PuzzleViewer`, `QcmViewer`, `InteractiveQcmViewer`, `PgnViewer`, `PlacementViewer`, `PlayPage`, `AnalysisPage`) doivent **strictement adopter un aspect plat et net** :
  - **`border-radius: 0;`** (angles droits).
  - **`box-shadow: none;`** (aucune ombre portée sur l'échiquier).

### B. Objets Manipulables & Cartes à Déplacer / Trier (Exceptions)
- Dans les contextes où l'échiquier représente un élément d'inventaire ou une carte à déplacer/ordonner (`OrderViewer`, `TypeMarcheHeros.vue` Phase 1a, `MatchingViewer`) :
  - L'échiquier **intérieur** conserve ses angles droits (`border-radius: 0;`).
  - Le **conteneur / enveloppe externe** (ex: `.board-wrapper-card`, `.item-wrapper`, `.bank-item-wrapper`) conserve son visuel de carte d'objet manipulable (bords arrondis de la carte, bordure de sélection, légère ombre d'élévation pour faire comprendre à l'utilisateur qu'il s'agit d'un objet interactif déplaçable).

---

## 4. ADAPTATIVE & RESPONSIVE DESIGN (TABLETTE, MOBILE & DESKTOP)

### Non-Tronquage de l'Échiquier
- **Règle d'or** : Un échiquier ne doit **JAMAIS** être tronqué ni rogné (pièces coupées ou coordonnées masquées).
- **Ratio d'aspect** : Conserver systématiquement `aspect-ratio: 1 / 1` sur les conteneurs d'échiquier.
- **Adaptatif Tablette & Mobile** :
  - **Portrait (Mobile / Tablette)** : Limiter la largeur maximale de l'échiquier en fonction de la hauteur d'écran disponible (ex: `max-width: min(720px, 60vh)`), pour réserver la hauteur nécessaire aux commandes et descriptions sans provoquer de décalage ou de tronquage vertical.
  - **Paysage (Mobile / Tablette / Desktop)** : Ajuster dynamiquement l'échiquier selon le viewport (ex: `width: min(65vh, 48vw); aspect-ratio: 1 / 1;`), afin que le plateau et la colonne latérale d'actions/historique tiennent parfaitement dans la vue sans défillement indésirable.

---

## 5. COMPOSANTS PARTAGÉS & MODULARITÉ (`src/components/shared/`)

- Toute logique d'affichage d'exercice, de tableau de données ou de motif d'interaction réutilisable doit être extraite dans un composant dédié dans `src/components/shared/`.
- **Catalogue des composants partagés** :
  - `DataTable/` : Ensemble des composants génériques de grille de données TanStack Table (`DataTable.vue`, `DataTableToolbar.vue`, `DataTablePagination.vue`).
  - `DiagramViewer.vue` : Affichage d'un diagramme statique (lecture seule, FEN + flèches).
  - `PuzzleViewer.vue` : Résolution de tactique / puzzle interactif.
  - `QcmViewer.vue` : QCM simple avec FEN d'accompagnement.
  - `InteractiveQcmViewer.vue` : QCM interactif étape par étape avec coups joués sur le plateau.
  - `PgnViewer.vue` : Replay pas à pas d'une partie PGN avec commentaires.
  - `PlacementViewer.vue` : Exercice de repérage et de placement de pièces par clic sur les cases.
  - `MatchingViewer.vue` : Association de positions d'échiquiers avec leurs descriptions.
  - `OrderViewer.vue` : Tri / ordonnancement de positions (de la banque vers les slots).
  - `ParcoursViewer.vue` : Déplacement guidé/solo d'une pièce sur un parcours.
  - `VisionViewer.vue` : Exercice de calcul et de visualisation de coups sans déplacer les pièces physiques.

---

## 6. GESTION DE L'ÉTAT & REQUÊTES SERVEUR (TANSTACK QUERY & PINIA)

- **Récupération des données API** : Toute interrogation ou récupération de données serveur (membres, actualités, tournois, cours, etc.) doit impérativement passer par **TanStack Query** (`useQuery`, `fetchQuery`) au sein des stores Pinia (`src/stores/`).
- **Appels HTTP & Rafraîchissement Transparent JWT (`safeFetch`)** :
  - Tous les appels HTTP vers les APIs du serveur (WordPress REST API, DAME, ROI) doivent **impérativement s'appuyer sur la fonction utilitaire `safeFetch`** (`src/utils/safeFetch.ts`) au lieu du `fetch` natif.
  - **Gestion automatique du HTTP 401** : `safeFetch` intercepte automatiquement le statut HTTP 401, sollicite le rafraîchissement transparent du jeton JWT via `authStore.tryRefreshToken()`, et réessaie la requête initiale avec le nouveau jeton avant d'envisager toute déconnexion.
  - **Interdiction de déconnexion directe** : Il est strictement interdit de faire un `authStore.logout()` immédiat sur un statut 401 sans être passé par la tentative de rafraîchissement de `safeFetch`.
- **Caching & Persistance** : Le cache global géré par `queryClient` (instance définie dans `src/queryClient.ts`) assure le suivi des états de chargement (`isLoading`), la fraîcheur des données et la persistance hors-ligne via `persistQueryClient`.
- **Invalidation & Mises à jour du Cache** : Après toute mutation ou modification de données côté serveur, utiliser systématiquement `queryClient.invalidateQueries({ queryKey: [...] })` ou `queryClient.setQueryData(...)` pour garder le cache synchronisé.
- **Déconnexion** : Lors du logout de l'utilisateur, vider l'ensemble du cache mémoire et du stockage persistant avec `queryClient.clear()`.

---

## 7. CONVENTIONS DE CODE & QUALITÉ

- **Typage TypeScript** : Tout composant Vue doit déclarer ses `defineProps` et `defineEmits` typés.
- **Gestion des Événements** : Utiliser les événements réactifs d'échiquier (`@board-created`, `@move`, `@check`, etc.).
- **Linter & Contrôle Qualité Stricte (ESLint & TypeScript)** :
  - **Exécution obligatoire** : La validation via le linter et la vérification de types TypeScript (`npm run lint` / `vue-tsc` / `npm run build`) est **obligatoire** avant toute livraison de fonctionnalité.
  - **Résolution à la source** : Toutes les erreurs et avertissements (warnings) doivent être **explicitement résolus dans le code**.
  - **Interdiction de contournement** : Il est **strictement interdit d'assouplir ou de désactiver les directives** de configuration du linter, ou de masquer les avertissements/erreurs avec des commentaires de suppression (`// @ts-ignore`, `// eslint-disable`, `@ts-nocheck`, etc.) pour ignorer un problème au lieu de corriger l'implémentation.
- **Build & Tests** : Valider impérativement que le build de production s'exécute sans erreur (`npm run build`).
