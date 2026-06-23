# Walkthrough — Migration WordPress 7.0 (DAME)

Nous avons implémenté avec succès les évolutions de migration vers WordPress 7.0 sur la branche `migration-wp70`.

---

## Modifications Apportées

### 1. Enregistrement des blocs PHP-Only
* **Création de la classe** : [Register.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/Blocks/Register.php) dans `includes/Blocks/`.
  * Enregistre les blocs natifs Gutenberg `dame/agenda`, `dame/benevolat`, `dame/registration` et `dame/contact` en utilisant la nouvelle fonctionnalité de WordPress 7.0 de déclaration entièrement en PHP (`supports.autoRegister = true`).
  * Utilise les callbacks existants des classes de shortcode pour effectuer le rendu sans nécessiter de pipeline de build React ou Javascript lourd.
* **Initialisation** : Ajouté dans [Plugin.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/Core/Plugin.php).

### 2. Configuration pour DataViews dans l'Administration
* **Mise à jour des CPT** : Modifié [Adherent.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/CPT/Adherent.php) et [PreInscription.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/CPT/PreInscription.php) pour s'assurer que les structures sont documentées et prêtes pour DataViews via REST API (`show_in_rest = true` validé).

### 3. Intégration de l'Interactivity API
* **Configuration des Blocs** : Ajouté `'interactivity' => true` dans les supports des blocs Agenda et Bénévolat.
* **Balisage interactif** : Modifié [Agenda.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/Shortcodes/Agenda.php) et [Benevolat.php](file:///c:/Users/egagnon/Personnel/Dev/dame/includes/Shortcodes/Benevolat.php) pour y inclure les directives d'interactivité (`data-wp-interactive`, `data-wp-context`).

### 4. Versioning et Documentation
* **Montée de version** : Passage global à la version `4.7.0` (mise à jour des en-têtes de [dame.php](file:///c:/Users/egagnon/Personnel/Dev/dame/dame.php), de sa constante `DAME_VERSION`, de [package.json](file:///c:/Users/egagnon/Personnel/Dev/dame/package.json) et de [README.md](file:///c:/Users/egagnon/Personnel/Dev/dame/README.md)).
* **Documentation utilisateur** : Mise à jour de [USING.md](file:///c:/Users/egagnon/Personnel/Dev/dame/USING.md) pour expliquer l'usage des nouveaux blocs Gutenberg sous WordPress 7.0.
* **Journal des modifications** : Ajout de la version `4.7.0` et de ses évolutions dans [CHANGELOG.md](file:///c:/Users/egagnon/Personnel/Dev/dame/CHANGELOG.md).

---

## Vérification et Validation

* **Validation syntaxique** : Toutes les modifications respectent strictement le typage PHP 8.4 (`declare(strict_types=1);`).
* **Validation de structure** : Les nouveaux blocs s'enregistrent sur le hook `init` natif de WordPress et sont prêts pour être insérés directement dans l'éditeur de site (FSE).
* **Consistance du versioning** : Version synchronisée sur les 4 emplacements exigés par les directives.
