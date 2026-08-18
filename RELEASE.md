# Release Notes — DAME v5.1.1

**Date :** 18 août 2026

## 🚀 Changements Majeurs

### Administration & Ergonomie
- **Unification du menu « Import Manuel »** :
  - Renommage et réorganisation du menu *Import FFE* en *Import Manuel*.
  - Centralisation de tous les outils d'importation CSV :
    - Mise à jour FFE (Licences & ELO).
    - Import CSV Adhérents.
    - Import CSV Contacts standard.
    - Import CSV HelloAsso avec matching multicritère.
    - Outil de détection & nettoyage sélectif des doublons Contacts / Adhérents.
- **Simplification du menu « Sauvegardes et Restaurations »** :
  - Recentrage de l'écran sur les sauvegardes complètes (`.json.gz`), restaurations globales, exports CSV et sauvegardes de modules.

## 📄 Fichiers Modifiés / Déploiement
- `dame.php` (version 5.1.1 & constante `DAME_VERSION`)
- `package.json` (version 5.1.1)
- `includes/Admin/Menu.php`
- `includes/Admin/Pages/ImportFFE.php`
- `includes/Admin/Pages/Backups.php`
- `CHANGELOG.md`
- `RELEASE.md`
