# Release Notes — DAME v5.0.0

**Date :** 14 août 2026

## 🚀 Changements Majeurs

### Assurance Qualité & Conformité PHPCS / PHPStan
- **Conformité PHPCS (WordPress Coding Standards)** :
  - 100% de conformité sur l'ensemble des modules d'administration, taxonomies, colonnes et composants du plugin (`WordPress-Core`, `WordPress-Extra`, `WordPress-Docs`).
  - Sécurisation accrue des redirections (`wp_safe_redirect`) et sanitisation des entrées HTTP avec `wp_unslash()`.
  - Normalisation de l'échappement à l'affichage (`esc_html_e()`) et ajout des annotations `phpcs:ignore` ciblées pour les requêtes SQL préparées et filtres GET natifs WP.
- **Analyse Statique PHPStan (Niveau 6)** :
  - Codebase validée avec 0 erreur (`[OK] No errors`) sur l'ensemble des 80 fichiers PHP.

## 📄 Fichiers Modifiés / Déploiement
- `dame.php` (version 5.0.0 & constante `DAME_VERSION`)
- `package.json` (version 5.0.0)
- `CHANGELOG.md`
- `RELEASE.md`
