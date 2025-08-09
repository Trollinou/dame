# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.10.0
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.10.0)

*   **Envoi d'articles par email :** Une nouvelle page "Envoyer un article" permet d'envoyer un article du site à des groupes d'adhérents. La sélection des destinataires peut se faire par groupes exclusifs (Junior, Pôle Excellence, état d'adhésion) ou par sélection manuelle. La fonctionnalité gère les emails des représentants légaux pour les adhérents mineurs.
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
    *   Localisation (Pays, Région, Département).
*   **Sélection Auto. du Département :** Le département est automatiquement suggéré à partir du code postal.
*   **Informations Scolaires :** Section dédiée pour l'établissement et l'académie.
*   **Gestion des Mineurs :** Champs pour deux représentants légaux (avec adresse complète et téléphone).
*   **Classification :**
    *   Cases à cocher pour "Junior" et "Pôle Excellence".
    *   Menu déroulant pour le niveau d'**Arbitre**.
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".

## Désinstallation

La suppression des données lors de la désinstallation du plugin peut être activée depuis la page d'options (`Réglages > Options DAME`). Par défaut, les données sont conservées par sécurité.

## Changelog

### 1.9.1 (09/08/2025)

*   **Correction de bug :** Un compte WordPress déjà assigné à un adhérent n'apparaît plus dans la liste déroulante des autres adhérents, empêchant les attributions multiples.
