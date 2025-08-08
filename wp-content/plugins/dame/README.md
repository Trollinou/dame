# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.5.0
**Auteur:** Jules
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.5.0)

*   **Gestion des Adhérents :** Crée une section "Adhérents" dédiée dans le menu d'administration de WordPress.
*   **Génération Automatique du Titre :** Le titre de la fiche est automatiquement généré (`NOM Prénom`).
*   **Champs de Données Complets :**
    *   Nom, Prénom, Date de naissance (obligatoires).
    *   Sexe (Masculin / Féminin).
    *   Numéro de licence (format validé), Numéro de téléphone.
    *   Adresse complète (2 lignes, code postal, ville).
    *   Localisation (Pays, Région, Département) avec menus déroulants.
    *   Date d'adhésion.
*   **Sélection Auto. du Département :** Le département est automatiquement suggéré à partir du code postal.
*   **Informations Scolaires :** Section dédiée pour l'établissement et l'académie (menu déroulant).
*   **Gestion des Mineurs :** Champs pour deux représentants légaux, chacun avec nom, prénom, email, téléphone, et adresse sur deux lignes.
*   **Classification :**
    *   Cases à cocher pour "Junior" et "Pôle Excellence".
    *   Menu déroulant pour le niveau d'**Arbitre** (Non, Jeune, Club, etc.).
*   **Liaison Utilisateur :** Permet de lier un adhérent à un compte utilisateur WordPress.
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".
*   **Interface d'Administration Optimisée :** Colonnes de liste personnalisées pour une meilleure visibilité.
*   **Prêt pour la Traduction :** Entièrement internationalisé.

## Installation

1.  Compressez le dossier `dame` pour créer un fichier `dame.zip`.
2.  Depuis votre tableau de bord WordPress, allez dans `Extensions` > `Ajouter`.
3.  Cliquez sur `Téléverser une extension`.
4.  Choisissez le fichier `dame.zip` et cliquez sur `Installer maintenant`.
5.  Activez l'extension.

## Désinstallation

Par défaut, la désactivation et la suppression de ce plugin ne suppriment aucune donnée. Pour supprimer toutes les données, ajoutez la ligne suivante à votre fichier `wp-config.php` avant de supprimer le plugin :
`define( 'DAME_DELETE_ON_UNINSTALL', true );`
