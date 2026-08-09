# Changelog
Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Ajouté
- **Exercice Type 8 (Vision'checs) — Refonte Multi-Diagrammes & Panneau Responsive** :
  - Refonte complète de `VisionViewer.vue` et `TypeVisionChecs.vue` pour gérer la nouvelle structure API `config.diagrammes` (série de 4 diagrammes par exercice) avec rétrocompatibilité automatique pour l'ancien format monodiagramme (`config.fen_depart`).
  - Déduction dynamique du coup attendu depuis la flèche bleue (`brush: "blue"`) dans les `shapes` de chaque diagramme et orientation de l'échiquier selon le trait FEN (`w` ➔ blanc, `b` ➔ noir).
  - Création de la fonction utilitaire `fenUtils.ts` pour extraire automatiquement les pièces et leurs coordonnées depuis la FEN (layout 1 colonne si ≤ 4 pièces, 2 colonnes Blancs/Noirs si > 4 pièces).
  - Rendu responsive dual-panel : panneau de description au-dessus de l'échiquier sur mobile (`<=768px`) et à gauche sur ordinateur/tablette (`>768px`).
  - Rendu visuel des icônes de pièces SVG via le conteneur natif `<cg-board>` devant leurs coordonnées (`c6`, `d7`, `b8`, etc.).
  - Révélation de la position complète et animation du déplacement de la pièce (`boardApi.move(...)`) en maintenant les annotations visuelles (`:preserve-shapes-on-position-change="true"`).
  - Ajout de la suite de tests unitaires Vitest dans `tests/unit/TypeVisionChecs.spec.ts`.
- **Exercice Type 1 (100 Commandements) — Support des séries de QCM** :
  - Mise à jour du composant `Type100Commandements.vue` pour gérer la nouvelle structure API `config.qcms` (série de QCMs successifs) avec rétrocompatibilité automatique pour l'ancien format de QCM unique (`config.question`).
  - Ajout d'un indicateur visuel de progression (`ion-badge`) indiquant la question courante (`Question X / N`).
  - Enregistrement de la progression via `store.validerElement(id)` à la fin de la série complète de QCMs.
  - Ajout des tests unitaires Vitest correspondants dans `tests/unit/Type100Commandements.spec.ts`.
- **Affichage du type d'exercice dans la playlist des cours (`CoursPage.vue` & `stringUtils.ts`)** :
  - Remplacement des labels de statut textuels redondants ("Complété", "Disponible", "Verrouillé") sous chaque élément de la liste par le nom humain du type de contenu (ex: "100 Commandements", "Vision'checs", "Cap ou pas Cap ?", "Leçon"). L'état de progression de chaque élément reste clairement indiqué par l'icône de droite (coche verte pour complété, cadenas pour verrouillé, flèche pour disponible).
  - Ajout de la fonction utilitaire `getContenuTypeLabel()` et de la table `EXERCICE_TYPES_MAP` dans `stringUtils.ts` pour mapper dynamiquement le type de chaque exercice.

### Corrigé
- **Décodage des entités HTML dans les titres (`stringUtils.ts`, `CoursPage.vue`, `ContenuPage.vue`, `ApprentissageHubPage.vue`)** :
  - Ajout de la fonction utilitaire `decodeHtmlEntities()` (s'appuyant sur `DOMParser` avec repli par expressions régulières) pour éliminer les entités HTML affichées en brut (ex: `&#8211;`, `&rsquo;`, `&amp;`, `&#039;`) dans les titres de cours, de chapitres, d'exercices et l'en-tête navigateur.
- **Réinitialisation de l'état de réussite lors de la relecture d'un exercice (`ContenuPage.vue`)** :
  - Correction de l'initialisation de `estReussi` lors du chargement d'un exercice (`post_type === 'roi_exercice'`). Désormais, rejouer un exercice déjà validé antérieurement réinitialise l'affichage au premier QCM/étape et exige d'aller au bout de l'exercice pour afficher la carte "Exercice réussi !".

## [4.8.5] - 2026-08-08
### Corrigé
- **Centrage automatique et défilement de l'Agenda (`LeClubPage.vue`)** :
  - Ajout d'un observateur réactif `watch(selectedSegment)` pour exécuter le défilement automatique vers l'événement du jour ou le plus proche à venir dès le basculement sur le segment `'agenda'`.
  - Implémentation d'une fonction `scrollToCurrentEvent()` résiliente avec tentatives répétées (`attemptScroll`) adaptées aux cycles de rendu et transitions du DOM Ionic.
- **Résolution des spinners de chargement infini résiduels (`AgendaSegmentView.vue` & `LeClubPage.vue`)** :
  - Importation explicite des composants `IonInfiniteScroll` et `IonInfiniteScrollContent` depuis `@ionic/vue` pour assurer la liaison réactive du composant Web.
  - Ajout de la vérification conditionnelle `v-if="!searchQuery && hasMorePast"` (scroll haut) et `v-if="!searchQuery && hasMoreUpcoming"` (scroll bas) afin de détruire complètement les éléments du DOM lorsque tous les événements passés ou futurs sont chargés.
  - Encapsulation des gestionnaires `loadMoreUpcoming` et `loadMorePast` dans un bloc `try ... finally` avec complétion asynchrone et désactivation explicite (`target.disabled = true`).
- **Nettoyage configuration Vite (`vite.config.ts`)** :
  - Remplacement de `__dirname` par `fileURLToPath(new URL('./src', import.meta.url))` pour supprimer l'avertissement de déprécation `configLoader: 'native'`.

### Qualité & Code Health
- **Découpage des composants Vue.js monolithiques (`PreInscriptionPage`, `PlayPage`, `AgendaPage` ➔ `LeClubPage`)** :
  - **`PreInscriptionPage.vue` (1261 ➔ ~150 lignes)** :
    - Extraction des requêtes d'autocomplétion géographique (Communes & Adresses) dans l'utilitaire `src/utils/geoApi.ts`.
    - Création des composables métier `usePreInscriptionForm.ts`, `useAddressAutocomplete.ts` et `usePreInscriptionApi.ts`.
    - Scission du template en 5 sous-composants UI dédiés (`PreInscriptionSuccessCard.vue`, `PreInscriptionIdentitySelector.vue`, `PreInscriptionMemberSection.vue`, `PreInscriptionLegalRepSection.vue`, `PreInscriptionHealthSection.vue`).
  - **`PlayPage.vue` (1013 ➔ ~200 lignes)** :
    - Extraction des composables réutilisables `useBoardOrientation.ts` (responsive portrait/paysage), `usePlayClock.ts` (pendule d'échecs) et `usePlayGame.ts` (moteur, annulations, statuts).
    - Scission en 4 sous-composants UI (`PlayInfoBar.vue`, `CapturedPiecesBar.vue`, `PlayActionsPanel.vue`, `PlaySettingsModal.vue`).
  - **`AgendaPage.vue` ➔ Renommage `LeClubPage.vue` (715 ➔ ~100 lignes)** :
    - Renommage de la vue en `LeClubPage.vue` et mise à jour des routes (`src/router/index.ts`).
    - Extraction du composable `useAgendaSearch.ts` pour le filtrage et la recherche.
    - Scission des 4 sous-onglets du hub en composants dédiés (`ActualitesSegmentView.vue`, `AgendaSegmentView.vue`, `TournoisSegmentView.vue`, `BenevolatSegmentView.vue`).
- **Standardisation des requêtes HTTP & Rafraîchissement JWT (`safeFetch`) dans les stores Pinia** :
  - Remplacement des appels `fetch` natifs restants par `safeFetch` dans `referenceData.ts` et `chess.ts` pour garantir la gestion des timeouts, la tentative de rafraîchissement transparent des jetons JWT et la résilience hors-ligne.
  - Élimination des vérifications manuelles `response.status === 401` et des déconnexions directes (`authStore.logout()`) dans `dashboard.ts`, `benevolat.ts`, `agenda.ts` et `apprentissage.ts` (5 emplacements), afin d'autoriser le rafraîchissement transparent des jetons conformément à la Règle 6 de `AGENTS.md`.
  - Nettoyage des imports inutilisés (`useAuthStore`) dans `agenda.ts`.
- **Centralisation de la Récupération et Pagination WP REST (`wpApi.ts`)** :
  - Création du module utilitaire `src/utils/wpApi.ts` fournissant la fonction generic `fetchWpCollection<T>(path)`.
  - Gestion automatique de l'injection du token JWT (`Authorization: Bearer`), détection et parcours multi-pages via `X-WP-TotalPages`, validation stricte du statut HTTP (`res.ok`) sur toutes les pages, et fusion transparentes des résultats.
  - Refactorisation des stores Pinia `members.ts`, `contacts.ts` et `messages.ts` pour éliminer ~120 lignes de code dupliqué et sécuriser la gestion des erreurs HTTP.
  - Ajout d'une suite complète de tests unitaires Vitest dans `tests/unit/wpApi.spec.ts`.
- **Centralisation utilitaire `stringUtils` & Tests Unitaires Vitest** :
  - Extraction de la fonction d'élimination des accents `removeAccents` et création du helper `includesNormalized` dans `src/utils/stringUtils.ts`.
  - Refactorisation de `DataTable.vue` et `AgendaPage.vue` pour utiliser la fonction utilitaire centralisée `removeAccents`.
  - Restauration et mise à jour complète de la suite de tests unitaires `vitest` dans `tests/unit/example.spec.ts` pour valider la suppression des diacritiques et le filtrage normalisé.
