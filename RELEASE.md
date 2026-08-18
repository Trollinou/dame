# Release Notes — DAME v5.1.0

**Date :** 18 août 2026

## 🚀 Changements Majeurs

### Imports & Contacts
- **Menu unifié « Import Manuel »** :
  - Renommage et réorganisation du menu *Import FFE* en *Import Manuel*.
  - Centralisation de tous les outils d'importation CSV :
    - Mise à jour FFE (Licences & ELO).
    - Import CSV Adhérents.
    - Import CSV Contacts standard.
    - Import CSV HelloAsso avec matching multicritère.
    - Outil de détection & nettoyage sélectif des doublons Contacts / Adhérents.
- **Import HelloAsso des tournois (CSV)** :
  - Support de l'import CSV HelloAsso avec attribution de catégorie.
  - Détection multicritère (Email, Nom+Prénom normalisé, Licence FFE/FIDE) excluant automatiquement les adhérents et leurs représentants légaux.
  - Conservation des catégories existantes pour les contacts multi-groupes.
- **Outil de Détection & Nettoyage sélectif des Doublons** :
  - Tableau interactif listant les fiches contacts correspondant à des adhérents enregistrés.
  - Affichage précis de la source de correspondance (Adhérent / Représentant légal) avec lien direct vers la fiche.
  - Suppression sélective avec confirmation.
- **Simplification du menu « Sauvegardes et Restaurations »** :
  - Recentrage de l'écran sur les sauvegardes complètes (`.json.gz`), restaurations globales, exports CSV et sauvegardes de modules.

### Événements & Géolocalisation
- **Calcul d'itinéraire et de distance dans l'Agenda** :
  - Rétablissement de l'action du bouton « Calculer » dans la métaboxe Agenda.
  - Prise en charge des itinéraires locaux à distance / durée nulles (`0.00 km`, `0 min`).
  - Géocodage automatique à la volée en cas d'absence des coordonnées GPS lors du clic.

## 📄 Fichiers Modifiés / Déploiement
- `dame.php` (version 5.1.0 & constante `DAME_VERSION`)
- `package.json` (version 5.1.0)
- `includes/Admin/Menu.php`
- `includes/Admin/Pages/ImportFFE.php`
- `includes/Admin/Pages/Backups.php`
- `includes/Core/Utils.php`
- `includes/Metaboxes/Agenda/Manager.php`
- `includes/Metaboxes/Adherent/Identity.php`
- `src/js/admin-common.js`
- `assets/js/admin-common.js`
- `includes/Services/Backup.php`
- `CHANGELOG.md`
- `RELEASE.md`
