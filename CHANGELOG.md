## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

## Changelog

### 1.15.0 (11/08/2025)

*   **Fonctionnalité :** La date de naissance est maintenant un champ obligatoire.
*   **Fonctionnalité :** Ajout des champs "Code postal de naissance" et "Commune de naissance".
*   **Fonctionnalité :** Implémentation de l'auto-complétion pour les champs de naissance en utilisant l'API géo.api.gouv.fr.
*   **Amélioration :** Lors de l'import CSV, si la date de naissance est manquante, la date "19/09/1950" est utilisée par défaut.
*   **Amélioration :** Les nouveaux champs de naissance sont ajoutés à l'export CSV et JSON.

### 1.14.5 (10/08/2025)

*   **Correctif :** Le menu "Assignations des comptes" est maintenant correctement positionné sous "Ajouter un nouvel adhérent".
*   **Correctif :** L'accès au menu "Assignations des comptes" est maintenant strictement réservé aux administrateurs.
*   **Amélioration :** Le rôle "Membre" est maintenant sélectionné par défaut dans la page "Assignations des comptes".
*   **Correctif Technique :** Correction d'une erreur fatale sur les versions récentes de PHP liée à la réorganisation du menu d'administration.

### 1.14.0 (10/08/2025)

*   **Fonctionnalité :** Ajout d'un écran "Assignation des comptes" pour lier facilement un adhérent à un compte utilisateur WordPress et lui assigner un rôle.
*   **Amélioration :** Le menu "Assignation des comptes" est positionné juste après "Ajouter un nouvel adhérent" pour une meilleure ergonomie.
*   **Correctif :** Correction d'un bug dans la requête de récupération des utilisateurs déjà assignés qui pouvait générer une erreur PHP.

### 1.13.0 (10/08/2025)

*   **Fonctionnalité :** Ajout d'un système d'import d'adhérents par fichier CSV (séparateur `;`, encodage UTF-8).
*   **Fonctionnalité :** Ajout des champs "Autre téléphone" et "Taille vêtements". Ces champs sont intégrés aux imports/exports.
*   **Amélioration :** La date de naissance n'est plus un champ obligatoire.
*   **Amélioration :** Ajout de l'option "Non précisé" pour le Sexe (valeur par défaut) et le Type de licence.
*   **Amélioration :** Le type de licence est positionné à "Non précisé" par défaut lors d'un import CSV si non fourni.
*   **Amélioration :** Mise à jour automatique du département et de la région à la saisie du code postal.
*   **Amélioration :** Nettoyage automatique des numéros de téléphone à l'import (gestion des préfixes 33/+33, suppression des espaces et points).
*   **UI :** Création d'une page de sous-menu "Import / Export" dédiée sous "Adhérents" pour regrouper les fonctionnalités.
*   **Correctif :** L'import CSV est maintenant plus robuste et ignore les BOM (Byte Order Mark) potentiellement présents dans l'en-tête du fichier.

### 1.12.0 (10/08/2025)

*   **Correctif :** Le champ titre ne contient plus de texte par défaut.
*   **Correctif :** Les champs "Type de licence" et "Date d'adhésion" sont toujours visibles.
*   **Correctif :** Le renommage de "Junior" en "École d’échecs" est maintenant appliqué sur tous les écrans.
*   **Amélioration :** Le champ "Département" est positionné au-dessus de "Région" et la sélection de la région est maintenant automatique.

### 1.11.0 (10/08/2025)

*   **Amélioration :** La date d'adhésion est maintenant située sous l'état de l'adhésion pour une meilleure logique.
*   **Amélioration :** Ajout du champ "Type de licence" (A ou B) obligatoire lorsque l'adhérent est actif.
*   **Amélioration :** La classification "Junior" est renommée en "École d’échecs".
*   **Amélioration :** Ajout de la classification "Bénévole".
*   **Amélioration :** Nouvelle section "Informations diverses" (allergies, régime, transport).
*   **Amélioration :** Remplissage automatique des coordonnées du représentant légal 1 pour les nouveaux adhérents mineurs.
*   **Amélioration :** Le focus est mis sur le champ "Prénom" lors de la création d'un nouvel adhérent.
*   **Amélioration :** Le champ titre affiche "Ne pas remplir" par défaut.
*   **Amélioration :** Les options pour le sexe sont affichées sur la même ligne.

### 1.9.1 (09/08/2025)

*   **Correction de bug :** Un compte WordPress déjà assigné à un adhérent n'apparaît plus dans la liste déroulante des autres adhérents, empêchant les attributions multiples.
