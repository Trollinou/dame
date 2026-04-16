# État du Projet : DAME

## Placeholders Actifs
- Nom : Dossier Administratif des Membres Échiquéens
- Slug : dame
- Prefix : dame_
- Namespace : DAME\

## Tâches Prioritaires (Instructions Agent)
1. Vérifier la conformité de l'autoloader SPL dans le fichier racine `dame.php`.
2. S'assurer que le fichier `.distignore` contient bien toutes les exclusions de la section 7 de AGENTS.md.
3. Documenter chaque nouveau Hook ou Endpoint REST dans `README.md` et `USING.md`.

## Vérifications de conformité avant validation
- [ ] PHPStan Level 6 passé ?
- [ ] ESLint sans erreur ?
- [ ] Pas de `require vendor/autoload` ?
- [ ] Mapping des metas respecté (HTML vs DB) ?
