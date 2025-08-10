# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.13.0
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.13.0)

*   **Import CSV des Adhérents :** Une nouvelle fonctionnalité majeure permet d'importer des adhérents à partir d'un fichier CSV. L'import est flexible et ignore les colonnes non reconnues.
*   **Page d'Import/Export dédiée :** Les fonctionnalités d'import et d'export ont été déplacées dans un sous-menu "Import / Export" dédié sous "Adhérents" pour une meilleure clarté.
*   **Champs de données étendus :**
    *   Ajout des champs "Autre téléphone" et "Taille vêtements".
    *   La date de naissance n'est plus obligatoire.
    *   Le champ "Sexe" inclut désormais une option "Non précisé" par défaut.
    *   Le champ "Type de licence" inclut une option "Non précisé".
*   **Amélioration de la saisie :**
    *   La saisie d'un code postal met automatiquement à jour le département et la région.
    *   Le changement du département met à jour la région.
*   **Amélioration de l'import :**
    *   Le type de licence est défini sur "Non précisé" par défaut lors d'un import CSV.
    *   Les numéros de téléphone sont automatiquement formatés (gestion des préfixes +33/33, suppression des espaces et points).
*   **Export CSV et JSON complets :** Les exports incluent maintenant tous les champs, y compris les nouveaux.
*   **Envoi d'articles par email :** Une page "Envoyer un article" permet d'envoyer un article du site à des groupes d'adhérents.
*   **Page d'Options :** Une page de réglages (`Réglages > Options DAME`) permet de gérer le comportement du plugin (désinstallation, email d'envoi).
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".

## Désinstallation

La suppression des données lors de la désinstallation du plugin peut être activée depuis la page d'options (`Réglages > Options DAME`). Par défaut, les données sont conservées par sécurité.

## Changelog

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
