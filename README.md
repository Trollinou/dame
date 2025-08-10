# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.12.0
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.11.1)

*   **Envoi d'articles par email :** Une nouvelle page "Envoyer un article" permet d'envoyer un article du site à des groupes d'adhérents. La sélection des destinataires peut se faire par groupes exclusifs (École d'échecs, Pôle Excellence, état d'adhésion) ou par sélection manuelle. La fonctionnalité gère les emails des représentants légaux pour les adhérents mineurs.
*   **Export CSV des Adhérents :** Une nouvelle option sur la page d'options permet d'exporter la liste complète des adhérents au format CSV avec des colonnes prédéfinies.
*   **Import/Export Complet :** Une nouvelle section sur la page d'options permet d'exporter l'intégralité de la base d'adhérents dans un fichier JSON, et de la réimporter pour une restauration complète.
*   **Autocomplétion d'Adresse :** Intégration avec l'API Géoplateforme de l'IGN pour suggérer et remplir automatiquement les champs d'adresse, de code postal et de ville.
*   **Page d'Options :** Une page de réglages (`Réglages > Options DAME`) permet de gérer le comportement du plugin.
*   **Gestion du Cycle de Vie des Adhésions :**
    *   Un champ **État d'adhésion** (Non Adhérent, Actif, Expiré, Ancien).
    *   La saisie d'une **Date d'adhésion** passe automatiquement le statut à "Actif".
    *   Une fonction de **réinitialisation annuelle** sur la page d'options met à jour en masse les statuts.
*   **Champs de Données Complets :**
    *   Nom, Prénom, Date de naissance (obligatoires).
    *   Sexe, Numéro de licence, Numéro de téléphone.
    *   Localisation (Pays, Département, Région).
*   **Sélection Auto. de la Région :** La région est automatiquement suggérée à partir du département.
*   **Informations Scolaires :** Section dédiée pour l'établissement et l'académie.
*   **Gestion des Mineurs :** Champs pour deux représentants légaux (avec adresse complète et téléphone).
*   **Classification :**
    *   Cases à cocher pour "École d'échecs", "Pôle Excellence" et "Bénévole".
    *   Menu déroulant pour le niveau d'**Arbitre**.
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".

## Désinstallation

La suppression des données lors de la désinstallation du plugin peut être activée depuis la page d'options (`Réglages > Options DAME`). Par défaut, les données sont conservées par sécurité.

## Changelog

### 1.12.0 (10/08/2025)

*   **Fonctionnalité :** Ajout d'une fonction d'export au format CSV de la liste des adhérents depuis la page d'options. L'export inclut toutes les données des adhérents et de leurs représentants légaux.

### 1.11.1 (10/08/2025)

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
