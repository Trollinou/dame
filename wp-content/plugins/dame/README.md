# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.2.0
**Auteur:** Jules
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités

*   **Gestion des Adhérents :** Crée une section "Adhérents" dédiée dans le menu d'administration de WordPress pour une gestion centralisée.
*   **Génération Automatique du Titre :** Le titre de la fiche adhérent est automatiquement généré sous la forme `NOM Prénom`, simplifiant la saisie.
*   **Champs de Données Complets :** Permet de sauvegarder des informations détaillées pour chaque membre :
    *   Nom, Prénom, Date de naissance (tous obligatoires).
    *   Numéro de licence (format validé : A12345).
    *   Email, Numéro de téléphone.
    *   Adresse complète (2 lignes, code postal, ville).
    *   Date d'adhésion.
*   **Gestion des Mineurs :** Inclut des champs spécifiques pour les informations de **deux représentants légaux**.
*   **Classification :** Permet de classifier les membres avec les étiquettes "Junior" et "Pôle Excellence".
*   **Liaison Utilisateur :** Permet de lier un adhérent à un compte utilisateur WordPress existant.
*   **Rôles Utilisateurs Personnalisés :** Ajoute deux nouveaux rôles :
    *   **Membre** : Basé sur le rôle "Abonné", mais avec la permission de poster des commentaires.
    *   **Entraineur** : Basé sur le rôle "Éditeur".
*   **Interface d'Administration Optimisée :** La liste des adhérents affiche les informations les plus pertinentes (Licence, Email, etc.) pour une meilleure lisibilité.
*   **Prêt pour la Traduction :** Le plugin est entièrement internationalisé. Un fichier modèle `dame.pot` est fourni dans le dossier `languages` pour faciliter la création de traductions.

## Installation

1.  Compressez le dossier `dame` pour créer un fichier `dame.zip`.
2.  Depuis votre tableau de bord WordPress, allez dans `Extensions` > `Ajouter`.
3.  Cliquez sur le bouton `Téléverser une extension` en haut de la page.
4.  Choisissez le fichier `dame.zip` que vous venez de créer et cliquez sur `Installer maintenant`.
5.  Une fois l'installation terminée, cliquez sur `Activer l'extension`.

Une fois activé, un nouveau menu "Adhérents" apparaîtra dans votre tableau de bord d'administration.

## Comment l'utiliser

1.  Allez dans le menu `Adhérents`.
2.  Cliquez sur `Ajouter` pour créer une nouvelle fiche adhérent.
3.  Remplissez les informations dans les différentes boîtes de dialogue. Le champ "Titre" sera automatiquement rempli lors de la sauvegarde à partir du nom et du prénom.
4.  Cliquez sur `Publier` pour sauvegarder l'adhérent.

## Désinstallation

Par défaut, la désactivation et la suppression de ce plugin ne suppriment aucune des données que vous avez créées (les fiches adhérents, etc.). C'est une mesure de sécurité pour éviter toute perte de données accidentelle.

Si vous souhaitez supprimer **toutes** les données associées au plugin DAME lors de sa suppression, vous devez ajouter la ligne suivante à votre fichier `wp-config.php` **avant** de supprimer le plugin :

```php
define( 'DAME_DELETE_ON_UNINSTALL', true );
```
