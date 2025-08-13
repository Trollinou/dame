# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 1.16.2
**Auteur:** Etienne
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité. Il inclut un mécanisme de mise à jour qui permettra de gérer les migrations de données pour les futures versions.

## Fonctionnalités (v1.16.2)

*   **Configuration SMTP :** Il est désormais possible de configurer un serveur SMTP externe pour l'envoi d'emails, améliorant ainsi la fiabilité et la délivrabilité.
*   **Envoi d'emails par lots :** La fonctionnalité "Envoyer un article" envoie désormais les emails par lots de 20 pour éviter les limitations des serveurs d'hébergement lors de l'envoi à un grand nombre de destinataires.
*   **Filtrage des Adhérents :** Des filtres ont été ajoutés à la liste des adhérents pour permettre un tri par groupe (École d'échecs, Pôle Excellence, etc.) et par statut d'adhésion.
*   **Assignation des comptes :** Ajout d'un écran pour lier facilement un adhérent à un compte utilisateur WordPress et lui assigner un rôle.
*   **Import CSV des Adhérents :** Importez des adhérents à partir d'un fichier CSV.
*   **Page d'Import/Export dédiée :** Les fonctionnalités d'import et d'export ont été centralisées.
*   **Champs de données étendus** et **Améliorations de la saisie et de l'import**.
*   **Export CSV et JSON complets**.
*   **Rôles Utilisateurs Personnalisés :** Ajoute les rôles "Membre" et "Entraineur".

## Configuration

### Envoi d'emails (SMTP)

Pour garantir une bonne délivrabilité des emails envoyés via le plugin (par exemple, via la fonction "Envoyer un article"), il est fortement recommandé de configurer un serveur SMTP. Sans cette configuration, le plugin utilisera la fonction d'envoi par défaut de WordPress, qui peut être peu fiable sur certains hébergements.

Pour configurer le SMTP, allez dans `Réglages > Options DAME` et remplissez les champs suivants dans la section "Paramètres d'envoi d'email" :

*   **Hôte SMTP :** L'adresse de votre serveur SMTP (ex: `smtp.votreserveur.com`).
*   **Port SMTP :** Le port utilisé par votre serveur SMTP (ex: `465` pour SSL, `587` pour TLS).
*   **Chiffrement :** Le type de chiffrement à utiliser (SSL ou TLS). Sélectionnez "Aucun" si votre serveur ne l'utilise pas.
*   **Nom d'utilisateur SMTP :** Votre nom d'utilisateur pour le serveur SMTP (souvent votre adresse email complète).
*   **Mot de passe SMTP :** Le mot de passe associé à votre nom d'utilisateur SMTP. Le mot de passe n'est pas affiché après avoir été enregistré. Si vous laissez ce champ vide lors d'une modification, l'ancien mot de passe sera conservé.

**Note :** L'adresse email renseignée dans le champ "Email de l'expéditeur" doit correspondre à l'adresse email utilisée pour l'authentification SMTP.

## Désinstallation

La suppression des données lors de la désinstallation du plugin peut être activée depuis la page d'options (`Réglages > Options DAME`). Par défaut, les données sont conservées par sécurité.
