# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 4.1.3
**Auteur:** Etienne Gagnon
**Licence:** GPL v2 or later

## Description
DAME est une solution complète de gestion de club conçue pour centraliser et automatiser le suivi administratif des membres. Contrairement à une simple liste, DAME gère le **cycle de vie complet de l'adhérent** : de la préinscription en ligne jusqu'à l'historique pluriannuel des adhésions, en passant par le suivi des obligations légales (honorabilité, santé).

Le plugin est structuré autour d'une architecture moderne (POO) garantissant performance et sécurité pour les données sensibles de votre association.

## Prérequis

*   **WordPress :** 6.9 ou supérieur
*   **PHP :** 8.4 ou supérieur

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
*   **Sauvegardes JSON compressées :** Sauvegardes journalières automatiques par email. Le script d'import gère le re-mappage automatique des IDs pour une restauration parfaite sur n'importe quel serveur.

## Dépendances
Pour la fonctionnalité pédagogique (LMS), ce plugin nécessite le plugin **ROI**.
