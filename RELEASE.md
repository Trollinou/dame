# Release Notes — DAME v5.8.1

**Date :** 29 octobre 2026

## 🚀 Changements Majeurs

### 1. Ergonomie de l'Écran d'Événement HelloAsso & Émargement Jour J
- **Interface Réactive et Épurée (`DAME\Admin\Pages\Ticketing`, `src/scss/admin/_ticketing.scss`)** :
  - Remplacement des 3 grandes boîtes encombrantes par un bandeau de KPIs compacts intégré en ligne sous le titre.
  - Déplacement des actions techniques (resynchronisation HelloAsso, synchronisation contacts) dans un menu compact `⚙️ Outils ▾`.
  - Inscription manuelle sur place intégrée dans une modale dédiée accessible et fluide (optimisée Safari/WebKit).
- **Sélecteur Déroulant Multi-Tarifs (`src/js/admin-ticketing.ts`)** :
  - Remplacement de la ligne de boutons débordante par un menu déroulant escamotable avec recherche rapide, cases à cocher, compteurs d'inscrits et actions « Tout sélectionner / Tout effacer ».
  - Permet de combiner instantanément plusieurs tarifs (ex: *Open A Adulte* + *Open A U20*).
- **Export Sharly Chess Tri-Options Synchronisé** :
  - Menu d'export dynamique lié à la sélection active : *Exporter la sélection filtrée*, *Exporter les pointés filtrés*, ou *Exporter l'intégralité (Tous)*.
  - Support de requêtes multi-tarifs dans le repository (`TicketingRepository::get_campaign_items`).
- **Pointage Instantané (AJAX) & Recherche en Direct** :
  - Bascule de présence immédiate en 1 clic sans rechargement de page (`wp_ajax_dame_toggle_checkin_ajax`), avec mise à jour temps réel des compteurs et préservation des filtres.
  - Recherche instantanée multi-champs (Nom, Licence, Club) et tri interactif des colonnes en direct.
- **Création de Catégorie de Contact à la Volée** :
  - Création directe d'une nouvelle catégorie de contact lors de l'export des joueurs externes sans avoir à quitter l'écran.

### 2. Attestations d'Adhésion & Paiement (PDF & Envoi par E-mail)
- **Génération Dématérialisée PDF (`DAME\Services\PDF_Generator`)** :
  - Reçus et attestations d'adhésion au format PDF conformes aux exigences administratives (CE, CSE, mutuelles santé, employeurs, Pass'Sport).
  - Conversion automatique et normalisée des montants en toutes lettres en français (`DAME\Core\Utils::number_to_french_words`).
  - Incrustation dynamique du logo du club, du cachet officiel, de la signature du représentant légal et des mentions légales obligatoires (RNA, SIREN, siège social).
  - Encodage Windows-1252 (CP1252) pour FPDF avec support parfait du symbole euro `€` et des caractères accentués français.
  - Prise en charge des téléchargements unitaires ou groupés multipages via les actions groupées de la liste des adhérents.
- **Routage de l'Envoi par E-mail via la File d'Attente Globale (`DAME\Services\BatchSender`)** :
  - Intégration directe dans la file d'attente FIFO asynchrone de `BatchSender` pour respecter rigoureusement les quotas SMTP (`smtp_batch_size`).
  - Stockage persistant et sécurisé des PDFs dans `wp-content/uploads/dame-documents/` avec normalisation multi-plateforme des chemins (`wp_normalize_path`).
  - Modèle d'e-mail personnalisable avec balises dynamiques dans les réglages.
- **Modale Interactive & Actions d'Administration (`src/js/admin-attestation.ts`)** :
  - Action de ligne et bouton « Attestation » ouvrant une modale interactive pré-remplie pour ajuster les modalités de paiement avant téléchargement ou envoi.

### 2. Tarification Simplifiée par Saison
- **Configuration des Tarifs (`DAME\Services\Data_Provider`, `DAME\Taxonomies\Season`)** :
  - Paramétrage direct des montants de base (Licence A, Licence B, réduction féminine, majoration première adhésion) dans chaque saison et dans l'onglet Saisons.
  - Métaboxe de paiement par saison sur la fiche adhérent pour suivre le statut de règlement, la date, le montant effectif et le mode de paiement.
  - Sauvegarde et restauration automatiques intégrées dans les archives JSON / GZ (`DAME\Services\Backup\AdherentBackup`).

### 3. Refonte de l'Onglet Réglages « Association »
- **Agencement en 4 Cartes Thématiques Délimitées (`DAME\Admin\Settings\Tabs\Association`)** :
  - 1. *Identité légale & Contact* (Nom, ID FFE, RNA, SIRET, Email, Site web).
  - 2. *Siège social* (Adresse officielle déclarée pour attestations et documents légaux).
  - 3. *Salle de jeu* (Adresse pour calcul automatique des temps de trajet et distances).
  - 4. *Représentant(e) & Cachet officiel* (Signataire, logo et cachet/signature).
  - Ajout d'une case à cocher « La salle de jeu est située à la même adresse que le siège social » avec recopie dynamique en temps réel.

### 4. Billetterie, Pointage & Intégration HelloAsso (Web & PWA)
- **Synchronisation Sécurisée HelloAsso (`DAME\Services\HelloAsso\*`, `DAME\Repositories\TicketingRepository`)** :
  - Client API v5 avec jetons OAuth 2.0 (rafraîchissement automatique et support de la Sandbox).
  - Contournement du blocage Cloudflare WAF (erreur HTTP 404) via l'en-tête personnalisé `DAME-HelloAsso-Client/5.8.0`.
  - Normalisation intelligente du slug d'organisation et mécanisme d'auto-découverte via `/v5/users/me/organizations`.
  - Ingestion des dates de réalisation des campagnes (`startDate`, `endDate`) au format MySQL.
- **Interface d'Administration & Émargement (`DAME\Admin\Pages\Ticketing`)** :
  - Filtres d'onglets rapides par type (`Tous`, `Événements`, `Adhésions`, `Dons`, `Boutique`, `Paiement direct`).
  - Colonne des dates de réalisation et tri chronologique par défaut (`start_date DESC`) avec colonnes triables.
  - Émargement des participants en un clic, recherche dynamique instantanée, inscription manuelle sur place et export Sharly Chess (CSV).
- **API REST & Prêt pour la PWA (`DAME\API\REST\Ticketing_REST`)** :
  - Authentification transparente Simple JWT Login pour arbitres et encadrants.
  - Endpoints complets de consultation, filtrage et pointage temps réel.
  - Guide complet d'intégration PWA dans `docs/PWA_TICKETING_INTEGRATION.md`.
