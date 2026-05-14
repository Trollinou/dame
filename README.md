# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 4.4.4
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

### Agenda & Flux iCalendar
*   **Calendrier Interactif :** Gestion des événements, compétitions et entraînements.
*   **Flux ICS Magiques :** Abonnement direct sur iPhone, Android ou Mac avec gestion intelligente des fuseaux horaires (plus de décalage été/hiver).

### Préinscriptions en Ligne
*   **Zéro Saisie Manuelle :** Formulaire public générant des fiches de préinscription.
*   **Rapprochement :** Système intelligent pour fusionner une préinscription avec un membre déjà existant en base.

## Portabilité & Sauvegarde
*   **Exports CSV :** Formats optimisés pour les imports fédéraux (FFE) ou le secrétariat.
*   **Sauvegardes JSON compressées :** Sauvegardes journalières automatiques par email couvrant les Adhérents, l'Agenda et le Contenu du site (Articles, Pages, Menus). Le système force la restauration des IDs originaux pour préserver parfaitement les relations, les liens de menus et la hiérarchie des pages.

## Dépendances
Pour la fonctionnalité pédagogique (LMS), ce plugin nécessite le plugin **ROI**.

---

## API REST pour PWA Mobile

Le plugin expose plusieurs points de terminaison (endpoints) personnalisés pour permettre l'administration via une application mobile (PWA).

### Authentification
Toutes les requêtes personnalisées nécessitent que l'utilisateur soit authentifié (`is_user_logged_in()`).

### Accès à l'Application (PWA)
L'application mobile est accessible via une URL simplifiée : `https://votre-site.com/pwa`. Une redirection automatique est en place pour pointer vers le dossier de distribution du plugin.

### Support des Métadonnées (Post Meta)
Le support des `'custom-fields'` a été activé pour les types **Adhérents**, **Agenda**, **Contacts**, **Sondages** et **Réponses**. Cela permet de lire et modifier toutes les métadonnées (dates, emails, téléphones, etc.) directement via les endpoints REST standard de WordPress en utilisant le champ `meta`.

**Note spécifique aux Sondages :**
Les données de configuration des sondages (dates et horaires) sont exposées à la racine de l'objet JSON sous la clé `dame_sondage_data`. 

Pour les **Réponses aux Sondages**, l'ID du sondage parent est disponible à la racine sous la clé `sondage_id`.

### 1. Données de Référence (Lookups)
Utilisées pour alimenter les formulaires de l'application.

*   **URL :** `/wp-json/dame/v1/data/{type}`
*   **Méthode :** `GET`
*   **Types disponibles :**
    *   `countries` : Liste des pays.
    *   `regions` : Régions françaises.
    *   `departments` : Départements français.
    *   `department-region-mapping` : Correspondance départements -> régions.
    *   `academies` : Académies scolaires.
    *   `health-document-options` : Statuts des documents de santé.
    *   `clothing-sizes` : Tailles de vêtements.

**Exemple JS :**
```javascript
fetch('/wp-json/dame/v1/data/regions')
  .then(response => response.json())
  .then(data => console.log(data));
```

### 2. Anniversaires du Jour
Récupère la liste des adhérents dont c'est l'anniversaire aujourd'hui pour la saison en cours.

*   **URL :** `/wp-json/dame/v1/birthdays/today`
*   **Méthode :** `GET`

**Réponse type :**
```json
[
  {
    "id": 123,
    "name": "DUPONT Jean",
    "age": 25
  }
]
```

### 3. Prochains Anniversaires
Récupère les `x` prochains anniversaires à venir à partir d'aujourd'hui. Gère automatiquement le passage à l'année suivante.

*   **URL :** `/wp-json/dame/v1/birthdays/upcoming`
*   **Méthode :** `GET`
*   **Paramètres :**
    *   `limit` (optionnel, défaut: 10) : Nombre de résultats souhaités.

**Réponse type :**
```json
[
  {
    "id": 124,
    "name": "MARTIN Sophie",
    "date": "2026-05-15",
    "days_until": 7,
    "next_age": 12
  }
]
```

### 4. Menu PWA
Récupère les éléments du menu de navigation nommé "Menu_PWA".

*   **URL :** `/wp-json/dame/v1/pwa-menu`
*   **Méthode :** `GET`
*   **Accès :** Public
**Réponse type :**
```json
[
  {
    "id": 120,
    "object_id": 12,
    "parent": 0,
    "title": "Accueil",
    "menu_order": 1
  },
  {
    "id": 121,
    "object_id": 45,
    "parent": 0,
    "title": "Agenda",
    "menu_order": 2
  },
  {
    "id": 122,
    "object_id": 46,
    "parent": 121,
    "title": "Sous-menu Agenda",
    "menu_order": 3
  }
]
```


### 5. Ressources Natives WordPress
Le plugin expose également les Custom Post Types et Taxonomies via l'API REST native sous les points de terminaison suivants :

#### Custom Post Types
| Type de contenu | Endpoint REST |
| :--- | :--- |
| **Adhérents** | `/wp-json/wp/v2/adherents` |
| **Agenda** | `/wp-json/wp/v2/agenda` |
| **Contacts** | `/wp-json/wp/v2/contacts` |
| **Flux iCal** | `/wp-json/wp/v2/ical-feeds` |
| **Messages** | `/wp-json/wp/v2/messages` |
| **Préinscriptions** | `/wp-json/wp/v2/pre-inscriptions` |
| **Sondages** | `/wp-json/wp/v2/sondages` |
| **Réponses Sondages** | `/wp-json/wp/v2/sondage-reponses` |

#### Taxonomies
| Taxonomie | Endpoint REST |
| :--- | :--- |
| **Catégories Agenda** | `/wp-json/wp/v2/agenda-categories` |
| **Types de Contact** | `/wp-json/wp/v2/contact-types` |
| **Groupes** | `/wp-json/wp/v2/groups` |
| **Saisons** | `/wp-json/wp/v2/seasons` |
