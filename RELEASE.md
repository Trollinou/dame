# Release Notes — DAME v5.6.2

**Date :** 9 octobre 2026

## 🚀 Changements Majeurs

### 1. Attestations d'Adhésion & Paiement (PDF & Envoi par E-mail)
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
