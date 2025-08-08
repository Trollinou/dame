# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.7.0
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.7.0)

*   **Page d'Options :** Une page de réglages (`Réglages > Options DAME`) permet de gérer le comportement du plugin.
*   **Gestion du Cycle de Vie des Adhésions :**
    *   Un champ **État d'adhésion** (Non Adhérent, Actif, Expiré, Ancien) est disponible dans la section de classification.
    *   La saisie d'une **Date d'adhésion** passe automatiquement le statut à "Actif".
    *   Une fonction de **réinitialisation annuelle** sur la page d'options permet de mettre à jour en masse les statuts (`Actif` -> `Expiré`, `Expiré` -> `Ancien`) et de vider la date d'adhésion. Cette action est limitée à une fois par an.
*   **Champs de Données Complets :**
    *   Nom, Prénom, Date de naissance (obligatoires).
    *   Sexe (Masculin / Féminin).
    *   Numéro de licence (format validé), Numéro de téléphone.
    *   Adresse complète (2 lignes, code postal, ville).
    *   Localisation (Pays, Région, Département) avec menus déroulants.
*   **Sélection Auto. du Département :** Le département est automatiquement suggéré à partir du code postal.
*   **Informations Scolaires :** Section dédiée pour l'établissement et l'académie (menu déroulant).
*   **Gestion des Mineurs :** Champs pour deux représentants légaux, chacun avec nom, prénom, email, téléphone, et adresse sur deux lignes.
*   **Classification :**
    *   Cases à cocher pour "Junior" et "Pôle Excellence".
    *   Menu déroulant pour le niveau d'**Arbitre** (Non, Jeune, Club, etc.).
*   **Liaison Utilisateur :** Permet de lier un adhérent à un compte utilisateur WordPress.
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".

## Désinstallation

La suppression des données lors de la désinstallation du plugin peut être activée depuis la page d'options (`Réglages > Options DAME`). Par défaut, les données sont conservées par sécurité.
