# Changelog
Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [4.8.6] - 2026-08-09
### Ajouté
- **Composant d'En-tête Unifié d'Exercice (`ExerciseHeader.vue`)** :
  - Création et déploiement du composant réutilisable `ExerciseHeader.vue` pour harmoniser l'affichage supérieur de l'ensemble des types d'exercices (Types 1 à 16).
  - **Panel 1 (Métadonnées Compactes)** : Affiche le titre de l'exercice sur la première ligne, puis le Type humain de l'exercice en bas à gauche et la chaîne `Chapitre // Niveau X` en bas à droite (ex: `Matérialité // Niveau 1`), où le chapitre correspond à l'un des 5 thèmes officiels (*Matérialité*, *Activité*, *Sécurité*, *Structure*, *Combinaison*).
  - **Panel 2 (Consigne / Question + Badge)** : Panneau ultra-compact combinant la consigne ou question courante et le badge de progression de série `x / N` (ex: `Diagramme 1 / 4`, `Question 1 / 3` ou `1 / 1`).
  - Ajout du helper `formatChapitreNiveauLabel()` dans `stringUtils.ts` et de la suite de tests unitaires Vitest dans `tests/unit/ExerciseHeader.spec.ts`.
- **Exercice Type 8 (Vision'checs) — Refonte Multi-Diagrammes & Panneau Responsive** :
  - Refonte complète de `VisionViewer.vue` et `TypeVisionChecs.vue` pour gérer la nouvelle structure API `config.diagrammes` (série de 4 diagrammes par exercice) avec rétrocompatibilité automatique pour l'ancien format monodiagramme (`config.fen_depart`).
  - Déduction dynamique du coup attendu depuis la flèche bleue (`brush: "blue"`) dans les `shapes` de chaque diagramme et orientation de l'échiquier selon le trait FEN (`w` ➔ blanc, `b` ➔ noir).
  - Création de la fonction utilitaire `fenUtils.ts` pour extraire automatiquement les pièces et leurs coordonnées depuis la FEN.
  - Rendu responsive dual-panel avec grille compacte à **4 colonnes sur mobile** (`<=768px`) sous forme de badges de pièces horizontaux avec fond neutre uniforme et icônes SVG natives (`<cg-board>`) devant leurs coordonnées (`c6`, `d7`, `b8`, etc.).
  - Dimensionnement dynamique de l'échiquier contraint par la hauteur de la vue (`max-width: min(100%, calc(100vh - 230px))`) garantissant l'affichage à 100% de l'échiquier (rangées 1 à 8 et colonnes a à h) sans aucun défilement vertical sur écran de téléphone.
  - Révélation de la position complète et animation du déplacement de la pièce (`boardApi.move(...)`) en maintenant les annotations visuelles (`:preserve-shapes-on-position-change="true"`).
  - Ajout de la suite de tests unitaires Vitest dans `tests/unit/TypeVisionChecs.spec.ts`.
- **Exercice Type 1 (100 Commandements) — Support des séries de QCM** :
  - Mise à jour du composant `Type100Commandements.vue` pour gérer la nouvelle structure API `config.qcms` (série de QCMs successifs) avec rétrocompatibilité automatique pour l'ancien format de QCM unique (`config.question`).
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
