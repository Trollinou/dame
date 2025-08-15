## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

## Changelog

### 2.0.0 (15/08/2025)

*   **Fonctionnalité majeure : Module de contenu échiquéen**
    *   **CPT Leçons :** Ajout d'un type de contenu "Leçon" réservé aux membres. Les entraîneurs peuvent suivre la complétion.
    *   **CPT Exercices :** Ajout d'un type de contenu "Exercice" avec gestion de la difficulté, types de questions (QCM, Vrai/Faux), et solution.
    *   **CPT Cours :** Ajout d'un type de contenu "Cours" permettant aux entraîneurs de créer des parcours pédagogiques en assemblant leçons et exercices.
    *   **Interface d'exercices publique :** Une nouvelle interface accessible via shortcode `[dame_exercices]` permet aux utilisateurs de s'entraîner sur des exercices aléatoires, filtrés par catégorie et difficulté, avec un compteur de score.
    *   **Constructeur de cours :** Une interface en glisser-déposer permet de construire les cours de manière intuitive.
    *   **Taxonomie partagée :** Une nouvelle taxonomie "Catégories d'échecs" a été créée pour organiser tout le contenu pédagogique.
    *   **Permissions granulaires :** La création de contenu est réservée aux rôles Entraîneur et Administrateur.
    *   **Compatibilité :** Le contenu (leçons, exercices, solutions) est compatible avec les shortcodes, notamment ceux du plugin RPB Chessboard.

### 1.16.3 (13/08/2025)

*   **Fonctionnalité :** Ajout d'un filtre par catégorie sur la page "Envoyer un article". Ce filtre permet de sélectionner une ou plusieurs catégories pour affiner dynamiquement la liste des articles. Le filtre est mémorisé entre les sessions.
*   **Correctif :** Correction d'un problème d'affichage des caractères spéciaux (comme les apostrophes) dans la liste déroulante des articles filtrés dynamiquement.

### 1.16.2 (13/08/2025)

*   **Fonctionnalité :** Ajout d'une section de configuration SMTP (`Réglages > Options DAME`) pour permettre l'envoi d'emails via un serveur externe. Cela améliore considérablement la fiabilité de l'envoi d'emails.
*   **Amélioration :** La fonctionnalité "Envoyer un article" envoie désormais les emails par lots de 20 destinataires. Cette modification évite les erreurs et les échecs d'envoi lors de communications à des groupes importants, en contournant les limitations des serveurs d'hébergement.
*   **Correctif :** Correction du problème où l'envoi d'un article à un grand nombre de destinataires échouait sans message d'erreur clair.

### 1.16.1 (12/08/2025)

*   **Correctif :** Correction d'un bug critique dans la fonctionnalité "Envoyer un article" où les emails n'étaient pas envoyés aux groupes filtrés (par exemple, les membres "Actif"). La logique de filtrage a été revue pour s'assurer que la combinaison des filtres (statut et groupe) fonctionne correctement avec une relation `ET` au lieu de `OU`.

### 1.16.0 (11/08/2025)

*   **Fonctionnalité :** Ajout de filtres sur la page de liste des adhérents pour permettre de trier par Groupe (École d'échecs, Pôle Excellence, Bénévole, Elu local) et par État de l'adhésion.

### 1.15.5 (11/08/2025)

*   **Amélioration UI :** Ajout d'une scrollbox sur le popup de complétion des adresses pour visualiser toutes les suggestions.
*   **Fonctionnalité :** L'envoi d'email se fait maintenant par un système de filtres combinables (OU) pour les statuts d'adhésion et les groupes.
*   **Fonctionnalité :** Ajout des groupes "Bénévole" et "Elu local" dans les options d'envoi d'email.
*   **Amélioration :** La logique d'envoi d'email collecte maintenant l'email de l'adhérent ainsi que ceux de ses représentants légaux, en s'assurant de l'unicité des adresses.
*   **Amélioration UI :** Dans l'écran d'envoi d'email, aucun groupe n'est coché par défaut, et les filtres par statut sont toujours visibles et présentés sur une seule ligne.
*   **Sécurité :** Ajout d'une validation du format des adresses email lors de la saisie d'un adhérent.

### 1.15.0 (11/08/2025)

*   **Fonctionnalité :** La date de naissance est maintenant un champ obligatoire.
*   **Fonctionnalité :** Ajout des champs "Code postal de naissance" et "Commune de naissance".
*   **Fonctionnalité :** Implémentation de l'auto-complétion pour les champs de naissance en utilisant l'API géo.api.gouv.fr.
*   **Fonctionnalité :** Ajout d'une case à cocher "Elu local" pour suivre ce statut, incluse dans les imports/exports.
*   **Amélioration :** Le champ "Numéro de licence" a été déplacé dans la section "Classification et Adhésion" pour une meilleure organisation.
*   **Amélioration :** Lors de l'import CSV, si la date de naissance est manquante, la date "19/09/1950" est utilisée par défaut.
*   **Amélioration :** Les nouveaux champs de naissance et le statut "Elu local" sont ajoutés aux exports CSV et JSON.
*   **Amélioration UI :** Les champs de code postal et de ville sont maintenant affichés sur la même ligne avec des tailles ajustées pour un meilleur alignement visuel sur l'ensemble des formulaires.
*   **Amélioration UI :** La liste de suggestions de l'auto-complétion dispose désormais d'une barre de défilement pour les longues listes.
*   **Correctif :** La date d'adhésion n'est plus obligatoire pour les membres actifs.
*   **Correctif :** Les valeurs saisies dans les formulaires ne sont plus effacées en cas d'erreur de validation lors de la sauvegarde.

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
