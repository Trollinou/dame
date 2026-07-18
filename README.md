# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 4.7.5
**Auteur:** Etienne Gagnon
**Licence:** GPL v2 or later

## Description
DAME est une solution complète de gestion de club conçue pour centraliser et automatiser le suivi administratif des membres. Contrairement à une simple liste, DAME gère le **cycle de vie complet de l'adhérent** : de la préinscription en ligne jusqu'à l'historique pluriannuel des adhésions, en passant par le suivi des obligations légales (honorabilité, santé).

Le plugin est structuré autour d'une architecture moderne (POO) garantissant performance et sécurité pour les données sensibles de votre association.

## Prérequis

*   **WordPress :** 6.9 ou supérieur
*   **PHP :** 8.4 ou supérieur

## Développement & Architecture
DAME utilise un workflow de développement professionnel pour garantir la performance et la maintenabilité :
*   **Sources :** Tous les fichiers sources (Javascript ES2021, SCSS) se trouvent dans le répertoire `src/`.
*   **Build Pipeline :** Les fichiers de production (minifiés et optimisés) sont générés dans `assets/` via `npm run build`.
*   **Dépendances :** Gestion rigoureuse via Composer (librairies tierces) et npm (outils de développement).
*   **Qualité :** Analyse statique PHP via PHPStan (Level 6) et linting JS via WP-Scripts.

## Fonctionnalités Cœurs : Gestion des Adhésions

C'est le moteur principal du plugin, conçu pour offrir une vision claire de vos effectifs à tout instant.

### 1. Système de "Saisons Dynamiques"
Oubliez les statuts figés (ancien/nouveau). DAME utilise une taxonomie de saisons :
*   **Historique Total :** Chaque membre conserve la trace de toutes ses saisons d'adhésion (ex: "Saison 2023/2024", "Saison 2024/2025").
*   **Statut Automatique :** Le plugin calcule en temps réel si un membre est "Actif" en vérifiant s'il possède le tag de la saison définie comme "Active" dans vos réglages.
*   **Basculement Simplifié :** Un outil de réinitialisation annuelle permet de préparer la nouvelle saison en un clic, sans perdre les données passées.

### 2. Dossier Administratif Complet
Chaque fiche membre centralise :
*   **Identité & Coordonnées :** État civil complet, adresses, emails et téléphones.
*   **Représentants Légaux :** Gestion double pour les mineurs avec fonctions de recopie rapide des coordonnées.
*   **Santé & Légal :** Suivi des attestations de santé, certificats médicaux et autorisations parentales (avec génération de PDF).
*   **Honorabilité :** Champs spécifiques pour le contrôle d'honorabilité (obligatoire pour les encadrants et bénévoles en contact avec des mineurs).

### 3. Classification et Segmentation
*   **Groupes de Membres :** Classez vos adhérents par niveaux ou fonctions (École d'échecs, Compétition, Bénévoles, Élus) pour des filtres précis.
*   **Filtres Avancés :** Retrouvez instantanément vos membres par saison, par groupe, par genre, par catégorie d'âge (calculée automatiquement en UTC) ou par type de licence.

---

## Fonctionnalités Additionnelles

### Messagerie & Communication
*   **Mailing Ciblé :** Envoyez des messages à des segments précis (ex: "Toutes les femmes de l'école d'échecs").
*   **Traçabilité Totale :** Suivi individuel des envois (date/heure précise pour chaque destinataire) et statistiques d'ouverture (Pixel Tracking).
*   **Gestion des Refus :** Case "Refus mailing" pour respecter les préférences de vos membres et partenaires.
*   **Duplication flexible :** Transformez n'importe quel Message envoyé en Article WordPress (brouillon) pour alimenter votre blog sans double saisie.

### Gestion des Partenaires (Contacts)
*   **Module Dédié :** Un annuaire distinct pour les partenaires externes (Presse, Mairies, Sponsors), intégré au système de messagerie.

### Appels à Bénévoles (Participation)
*   **Planification :** Créez des événements (tournois, fêtes) et définissez des créneaux horaires.
*   **Recrutement Simple :** Les membres s'inscrivent sur les créneaux via le site ou l'application mobile.
*   **Suivi en Temps Réel :** Tableau récapitulatif des inscrits par créneau dans l'administration.
*   **Protection des Données :** Les votes sur des dates passées sont verrouillés pour préserver l'historique.

### Agenda & Flux iCalendar
*   **Calendrier Interactif :** Gestion des événements, compétitions et entraînements.
*   **Flux ICS Magiques :** Abonnement direct sur iPhone, Android ou Mac avec gestion intelligente des fuseaux horaires (plus de décalage été/hiver).

### Préinscriptions en Ligne
*   **Zéro Saisie Manuelle :** Formulaire public générant des fiches de préinscription.
*   **Rapprochement :** Système intelligent pour fusionner une préinscription avec un membre déjà existant en base.

### Synchronisation FFE (Fédération Française des Échecs)
*   **Daily Sync :** Synchronisation automatisée chaque jour à midi pour récupérer les classements ELO (Standard, Rapide, Blitz) et les numéros de licence officiels.
*   **Import CSV :** Outil d'importation manuelle optimisé avec algorithme de correspondance à double niveau (Licence, puis Nom) pour maintenir la base de données à jour sans doublons.
*   **FIDE ID :** Récupération automatique des identifiants FIDE manquants via scraping sécurisé.

## Portabilité & Sauvegarde
*   **Exports CSV :** Formats optimisés pour les imports fédéraux (FFE) ou le secrétariat.
*   **Sauvegardes JSON compressées :** Sauvegardes journalières automatiques par email couvrant les Adhérents, l'Agenda et le Contenu du site (Articles, Pages, Menus). Le système force la restauration des IDs originaux pour préserver parfaitement les relations, les liens de menus et la hiérarchie des pages.

## Dépendances
Pour la fonctionnalité pédagogique (LMS), ce plugin nécessite le plugin **ROI**.

---

## API REST pour PWA Mobile

Le plugin expose plusieurs points de terminaison (endpoints) personnalisés pour permettre l'administration via une application mobile (PWA).

### Authentification
Toutes les requêtes personnalisées nécessitent que l'utilisateur soit authentifié (`is_user_logged_in()`), sauf l'endpoint d'inscription.

### Accès à l'Application (PWA)
L'application mobile est accessible via une URL simplifiée : `https://votre-site.com/pwa`. Une redirection automatique est en place pour pointer vers le dossier de distribution du plugin.

### Support des Métadonnées (Post Meta)
Le support des `'custom-fields'` a été activé pour les types **Adhérents**, **Agenda**, **Contacts**, **Benevolat** et **Benevolat_Reponse**. Cela permet de lire et modifier toutes les métadonnées directement via les endpoints REST standard.

**Note spécifique au Bénévolat :**
Les données de configuration (dates et horaires) sont exposées sous la clé `dame_benevolat_data`. 
Pour les **Réponses**, l'ID du bénévolat parent est disponible sous la clé `benevolat_id` et les choix sous `choices`.

### 1. Données de Référence (Lookups)
Utilisées pour alimenter les formulaires de l'application.
*   **URL :** `/wp-json/dame/v1/data/{type}`
*   **Méthode :** `GET`

### 2. Anniversaires du Jour
*   **URL :** `/wp-json/dame/v1/birthdays/today`
*   **Méthode :** `GET`

### 3. Prochains Anniversaires
*   **URL :** `/wp-json/dame/v1/birthdays/upcoming`
*   **Méthode :** `GET`

### 4. Menu PWA
*   **URL :** `/wp-json/dame/v1/pwa-menu`
*   **Méthode :** `GET`

### 5. Configuration PWA
Retourne la configuration globale de l'application (comme l'activation et les chemins d'assets du module ROI).
*   **URL :** `/wp-json/dame/v1/pwa-config`
*   **Méthode :** `GET`

### 6. Inscription (Membres uniquement)
Permet à un membre du club de créer son compte utilisateur WordPress.
*   **URL :** `/wp-json/dame/v1/register`
*   **Méthode :** `POST`

### 6. Mes Identités (Profiles)
Récupère les identités liées à l'email de l'utilisateur connecté.
*   **URL :** `/wp-json/dame/v1/my-identities`
*   **Méthode :** `GET`
*   **Logique :** Si l'utilisateur est un adulte majeur unique lié à cet email, seul son profil de membre est renvoyé. Pour les familles, tous les profils (adhérents + représentants) sont listés.

### 7. Gestion du Bénévolat
*   **Récupérer mon vote :** `GET /wp-json/dame/v1/benevolats/{id}/my-vote`
*   **Voter / Modifier :** `POST /wp-json/dame/v1/benevolats/{id}/vote`
    *   Corps : `{ "choices": ["0_1", "1_0"] }`

### 8. Ressources Natives WordPress
Le plugin expose les Custom Post Types via `/wp-json/wp/v2/` :
*   **Adhérents :** `adherents`
*   **Agenda :** `agenda` (supporte `after_date` et `before_date`)
*   **Contacts :** `contacts`
*   **Bénévolat :** `benevolats`
*   **Réponses :** `benevolat-reponses`
