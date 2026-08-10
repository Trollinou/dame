# Release Notes — DAME v5.0.0

**Date :** 10 août 2026

## 🚀 Changements Majeurs

### Packaging & Nettoyage d'Architecture
- **Retrait complet du module PWA** :
  - Mise à jour du script de packaging ([`script/package.cjs`](file:///Users/etienne/Developments/dame/script/package.cjs)) pour supprimer les sous-builds `npm run build` liés à la PWA.
  - Nettoyage des exclusions et règles PWA dans [`.distignore`](file:///Users/etienne/Developments/dame/.distignore) et [`.gitignore`](file:///Users/etienne/Developments/dame/.gitignore).

## 📄 Fichiers Modifiés / Déploiement
- `dame.php` (version 5.0.0 & constante `DAME_VERSION`)
- `package.json` (version 5.0.0)
- `README.md` (version 5.0.0)
- `CHANGELOG.md`
- `RELEASE.md`
