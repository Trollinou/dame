# DAME - Dossier et Apprentissage des Membres Échiquéens

**Version:** 2.4.1
**Auteur:** Etienne Gagnon
**Licence:** GPL v2 or later

## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications, leurs adhésions par saison, et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité.

## Prérequis

*   **WordPress :** 6.8 ou supérieur
*   **PHP :** 8.2 ou supérieur

## Fonctionnalités Principales

### Gestion des Adhésions par Saison

Le système de gestion des adhésions a été entièrement repensé pour offrir plus de flexibilité et un meilleur suivi historique.

*   **Adhésion par Tags de Saison :** L'ancien système de statut (Actif, Ancien, etc.) est remplacé par une taxonomie "Saison d'adhésion". Chaque membre se voit attribuer un "tag" pour chaque saison à laquelle il adhère (ex: "Saison 2024/2025").
*   **Statut Dynamique :** Un membre est considéré comme "Actif" s'il possède le tag de la saison en cours. Sinon, il est "Non adhérent".
*   **Historique des Adhésions :** Toutes les saisons d'adhésion d'un membre sont conservées et visibles sous forme de "pastilles" sur sa fiche et dans la liste des adhérents.
*   **Gestion Simplifiée :** Sur la fiche d'un adhérent, un simple menu déroulant "Adhésion pour la saison actuelle" permet de le marquer comme "Actif" ou "Non adhérent", ce qui ajoute ou retire automatiquement le tag de la saison en cours.
*   **Filtres Avancés :** La liste des adhérents peut être filtrée pour n'afficher que les membres "Actifs", "Inactifs", ou tous les membres ayant adhéré à une saison spécifique (ex: tous les adhérents de la "Saison 2023/2024").
*   **Réinitialisation Annuelle Intelligente :** La fonction de "Réinitialisation Annuelle" (`Réglages > Options DAME`) ne modifie plus les anciens membres. Son rôle est désormais de créer le tag pour la nouvelle saison qui commence et de le définir comme saison "active".
*   **Système de suivi de l'honorabilité :** Les champs de date de naissance et de commune de naissance sont saisissable pour les adherents et/ou représetnant legaux afin de suivre le processus de contrôle d'honorabilité s'il sont amené à accompagner des mineurs.

### Préinscription en Ligne

*   **Formulaire de Préinscription :** Un shortcode `[dame_fiche_inscription]` permet d'afficher un formulaire public où les futurs membres peuvent s'inscrire. Le formulaire s'adapte dynamiquement pour les adhérents majeurs et mineurs.
*   **Génération de PDF :** Génération de l'attestation de réponse négative au questionnaire de santé.
*   **Interface de Validation :** Les administrateurs disposent d'une interface dédiée pour examiner, modifier et valider les préinscriptions.
*   **Rapprochement Automatique :** Le système détecte les doublons potentiels en comparant les nouvelles inscriptions avec la base de données existante (nom, prénom, date de naissance).
*   **Mise à Jour Facilitée :** Si un doublon est trouvé, un tableau de comparaison met en évidence les différences et permet de mettre à jour la fiche de l'adhérent existant en un clic.

### Gestion des Données des Membres

*   **Gestion des Données Personnelles :** Fiche détaillée pour chaque membre (coordonnées, date de naissance, etc.).
*   **Représentants Légaux :** Gestion des informations pour les représentants légaux des membres mineurs.
*   **Classification :** Catégorisation des membres (École d'échecs, Pôle Excellence, Bénévole, etc.).
*   **Assignation de Compte Utilisateur :** Outil pour lier un dossier d'adhérent à un compte utilisateur WordPress.
*   **Import / Export :** Outils complets pour importer des membres depuis un fichier CSV et exporter toutes les données en CSV ou JSON.

### Module Pédagogique (Échecs)

*   **Contenus Pédagogiques :** Gestion de Leçons, Exercices et Cours avec un système de difficulté unifié.
*   **Constructeur de Cours :** Interface visuelle pour assembler des leçons et des exercices en un parcours pédagogique.
*   **Suivi de Progression :** Les entraîneurs peuvent suivre les leçons complétées par les membres.
*   **Exercices Interactifs :** Interface publique pour s'entraîner sur les exercices avec feedback immédiat.
*   **Sauvegarde et Restauration :** Outil pour sauvegarder et restaurer l'ensemble du contenu pédagogique.

### Administration et Configuration

*   **Préférences de Communication :** Gestion du consentement au mailing pour chaque adresse email.
*   **Configuration SMTP :** Permet de configurer un serveur SMTP externe pour fiabiliser l'envoi d'emails.
*   **Envoi d'emails par Lots :** La taille des lots d'envoi est configurable pour s'adapter aux contraintes des hébergeurs.
*   **Désinstallation Sécurisée :** Les données sont conservées par défaut lors de la désinstallation, mais peuvent être supprimées via une option.

## Configuration SMTP

Pour garantir une bonne délivrabilité des emails envoyés via le plugin, il est fortement recommandé de configurer un serveur SMTP. Allez dans `Réglages > Options DAME` et remplissez les champs de la section "Paramètres d'envoi d'email".
