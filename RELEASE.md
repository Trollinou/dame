# Release Notes — DAME v5.1.0

**Date :** 18 août 2026

## 🚀 Changements Majeurs

### Contacts & Sauvegardes / Imports
- **Import HelloAsso des tournois (CSV)** :
  - Support de l'import CSV HelloAsso avec attribution de catégorie.
  - Détection multicritère (Email, Nom+Prénom normalisé, Licence FFE/FIDE) excluant automatiquement les adhérents et leurs représentants légaux.
  - Conservation des catégories existantes pour les contacts multi-groupes.
- **Outil de Détection & Nettoyage sélectif des Doublons** :
  - Tableau interactif listant les fiches contacts correspondant à des adhérents enregistrés.
  - Affichage précis de la source de correspondance (Adhérent / Représentant légal) avec lien direct vers la fiche.
  - Suppression sélective avec confirmation.

## 📄 Fichiers Modifiés / Déploiement
- `dame.php` (version 5.1.0 & constante `DAME_VERSION`)
- `package.json` (version 5.1.0)
- `includes/Admin/Pages/Backups.php`
- `includes/Core/Utils.php`
- `includes/Services/Backup.php`
- `CHANGELOG.md`
- `RELEASE.md`
