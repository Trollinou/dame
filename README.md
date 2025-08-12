# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.16.1
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.16.1)

*   **Filtrage des Adhérents :** Des filtres ont été ajoutés à la liste des adhérents pour permettre un tri par groupe (École d'échecs, Pôle Excellence, etc.) et par statut d'adhésion.
*   ****Fonctionnalité :** Ajout d'un écran "Assignation des comptes" pour lier facilement un adhérent à un compte utilisateur WordPress et lui assigner un rôle.
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
