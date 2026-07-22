# AGENTS — Directives de Développement PWA (Vue 3 / Ionic / eg-chessboard)

Ce document définit les directives d'architecture, de charte graphique et de conception pour l'application PWA. Tout agent travaillant sur ce projet doit se conformer à ces règles.

## 1. STACK TECHNIQUE & ARCHITECTURE

- **Framework** : Vue 3 (`<script setup lang="ts">`), TypeScript strict.
- **UI Framework** : Ionic Vue (`@ionic/vue`).
- **Store & État** : Pinia (`src/stores/`).
- **Gestion des Requêtes & Caching API** : TanStack Query (`@tanstack/vue-query` + `persistQueryClient`).
- **Composants d'Échiquier** : Module `eg-chessboard` (`TheChessboard` / `BoardCore`).

---

## 2. RÈGLES DE DESIGN & ENCAPSULATION DE L'ÉCHIQUIER (`eg-chessboard`)

### A. Échiquiers Standard (Jeu, Analyse, Visualiseurs d'exercices)
- **Règle absolue** : Les échiquiers de lecture et de jeu ([PuzzleViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/PuzzleViewer.vue), [QcmViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/QcmViewer.vue), [InteractiveQcmViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/InteractiveQcmViewer.vue), [PgnViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/PgnViewer.vue), [PlacementViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/PlacementViewer.vue), [PlayPage](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/views/PlayPage.vue), [AnalysisPage](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/views/AnalysisPage.vue)) doivent **strictement adopter un aspect plat et net** :
  - **`border-radius: 0;`** (angles droits).
  - **`box-shadow: none;`** (aucune ombre portée sur l'échiquier).

### B. Objets Manipulables & Cartes à Déplacer / Trier (Exceptions)
- Dans les contextes où l'échiquier représente un élément d'inventaire ou une carte à déplacer/ordonner ([OrderViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/OrderViewer.vue), [TypeMarcheHeros.vue](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/views/types/TypeMarcheHeros.vue) Phase 1a, [MatchingViewer](file:///c:/Users/egagnon/Personnel/Dev/dame/pwa/src/components/shared/MatchingViewer.vue)) :
  - L'échiquier **intérieur** conserve ses angles droits (`border-radius: 0;`).
  - Le **conteneur / enveloppe externe** (ex: `.board-wrapper-card`, `.item-wrapper`, `.bank-item-wrapper`) conserve son visuel de carte d'objet manipulable (bords arrondis de la carte, bordure de sélection, légère ombre d'élévation pour faire comprendre à l'utilisateur qu'il s'agit d'un objet interactif déplaçable).

---

## 3. ADAPTATIVE & RESPONSIVE DESIGN (TABLETTE, MOBILE & DESKTOP)

### Non-Tronquage de l'Échiquier
- **Règle d'or** : Un échiquier ne doit **JAMAIS** être tronqué ni rogné (pièces coupées ou coordonnées masquées).
- **Ratio d'aspect** : Conserver systématiquement `aspect-ratio: 1 / 1` sur les conteneurs d'échiquier.
- **Adaptatif Tablette & Mobile** :
  - **Portrait (Mobile / Tablette)** : Limiter la largeur maximale de l'échiquier en fonction de la hauteur d'écran disponible (ex: `max-width: min(720px, 60vh)`), pour réserver la hauteur nécessaire aux commandes et descriptions sans provoquer de décalage ou de tronquage vertical.
  - **Paysage (Mobile / Tablette / Desktop)** : Ajuster dynamiquement l'échiquier selon le viewport (ex: `width: min(65vh, 48vw); aspect-ratio: 1 / 1;`), afin que le plateau et la colonne latérale d'actions/historique tiennent parfaitement dans la vue sans défillement indésirable.

---

## 4. COMPOSANTS PARTAGÉS & MODULARITÉ (`src/components/shared/`)

- Toute logique d'affichage d'exercice ou de motif d'interaction réutilisable doit être extraite dans un composant dédié dans `src/components/shared/`.
- **Catalogue des composants partagés** :
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

## 5. GESTION DE L'ÉTAT & REQUÊTES SERVEUR (TANSTACK QUERY & PINIA)

- **Récupération des données API** : Toute interrogation ou récupération de données serveur (membres, actualités, tournois, cours, etc.) doit impérativement passer par **TanStack Query** (`useQuery`, `fetchQuery`) au sein des stores Pinia (`src/stores/`).
- **Caching & Persistance** : Le cache global géré par `queryClient` (instance définie dans `src/queryClient.ts`) assure le suivi des états de chargement (`isLoading`), la fraîcheur des données et la persistance hors-ligne via `persistQueryClient`.
- **Invalidation & Mises à jour du Cache** : Après toute mutation ou modification de données côté serveur, utiliser systématiquement `queryClient.invalidateQueries({ queryKey: [...] })` ou `queryClient.setQueryData(...)` pour garder le cache synchronisé.
- **Déconnexion** : Lors du logout de l'utilisateur, vider l'ensemble du cache mémoire et du stockage persistant avec `queryClient.clear()`.

---

## 6. CONVENTIONS DE CODE & QUALITÉ

- **Typage TypeScript** : Tout composant Vue doit déclarer ses `defineProps` et `defineEmits` typés.
- **Gestion des Événements** : Utiliser les événements réactifs d'échiquier (`@board-created`, `@move`, `@check`, etc.).
- **Linter & Contrôle Qualité Stricte (ESLint & TypeScript)** :
  - **Exécution obligatoire** : La validation via le linter et la vérification de types TypeScript (`npm run lint` / `vue-tsc` / `npm run build`) est **obligatoire** avant toute livraison de fonctionnalité.
  - **Résolution à la source** : Toutes les erreurs et avertissements (warnings) doivent être **explicitement résolus dans le code**.
  - **Interdiction de contournement** : Il est **strictement interdit d'assouplir ou de désactiver les directives** de configuration du linter, ou de masquer les avertissements/erreurs avec des commentaires de suppression (`// @ts-ignore`, `// eslint-disable`, `@ts-nocheck`, etc.) pour ignorer un problème au lieu de corriger l'implémentation.
- **Build & Tests** : Valider impérativement que le build de production s'exécute sans erreur (`npm run build`).
