# Changelog

## 4.4.2 - 2026-05-09
### Ajout
- **Application Mobile (PWA) :** Création des vues détaillées pour l'Agenda (carte GPS intelligente), les Contacts (actions directes), les Sondages (système d'accordéons) et les Messages (rapports statistiques).
- **Application Mobile (PWA) :** Implémentation d'un système de pré-chargement global et mise en cache via Pinia (`DashboardStore`) pour une navigation fluide.
- **Application Mobile (PWA) :** Intégration de l'identité visuelle (Logo Queen SVG) et harmonisation des en-têtes fixes.
- **Optimisation de l'Agenda (REST) :** Ajout du champ `_dame_agenda_description_html` pour le type `dame_agenda`, fournissant une version auto-formatée (via `wpautop`) de la description pour un affichage direct dans la PWA.

### Modifié
- **Application Mobile (PWA) :** Migration complète de l'architecture vers Pinia avec gestion du "Silent Refresh" et mémorisation de la position de défilement.

### Sécurité
- **Protection des données (Sauvegardes) :** Exclusion automatique du mot de passe SMTP (`smtp_password`) des fichiers de sauvegarde JSON afin de renforcer la sécurité des identifiants serveurs.

### Correction
- **Application Mobile (PWA) :** Résolution d'un bug de blocage du chargement (spinner infini) et support multi-ligne pour les titres longs sur iOS.

## 4.4.1 - 2026-05-09
### Modifié
- **Application Mobile (PWA) :** Harmonisation visuelle des icônes de contact et réorganisation des cartes (adhérents et représentants légaux) pour donner la priorité aux informations de communication.
- **Application Mobile (PWA) :** Fiabilisation du déploiement en sous-dossier WordPress via l'utilisation de chemins relatifs et du mode `HashHistory`.

### Correction
- **Application Mobile (PWA) :** Correction de l'affichage des icônes de contact pour les représentants légaux.

## 4.4.0 - 2026-05-09
### Ajout
- **API REST Native (Support PWA) :** Activation du support de l'API REST WordPress pour tous les Custom Post Types (`adherents`, `agenda`, `contacts`, `ical-feeds`, `messages`, `pre-inscriptions`, `sondages`) et Taxonomies (`saisons`, `groupes`, `catégories agenda`, `types contact`).
- **Application Mobile (PWA) :** Sortie de la première version de l'interface d'administration mobile ( consultation des fiches adhérents, gestion des contacts et représentants légaux).
- **Support des Custom Fields :** Ajout du support `'custom-fields'` pour les Adhérents, l'Agenda, les Contacts, les Sondages et les Réponses, permettant l'exposition des métadonnées via l'API REST.
- **Endpoints de Données de Référence :** Création de nouveaux points de terminaison personnalisés sous `/wp-json/dame/v1/data/` pour exposer les données statiques (pays, régions, départements, académies, etc.).
- **Gestion des Anniversaires via REST :** Ajout d'endpoints dédiés pour récupérer les anniversaires du jour (`/birthdays/today`) et les prochains anniversaires à venir (`/birthdays/upcoming`).
- **Synchronisation des Titres :** Mise en place de hooks REST (`rest_after_insert`) pour la régénération automatique des titres normalisés ("NOM Prénom") lors des créations ou modifications via l'API.
- **Redirection PWA simplifiée :** Mise en place d'une redirection automatique de `/pwa` vers le point d'entrée physique de l'application mobile (`pwa/dist/index.html`).

### Sécurité
- **Contrôle d'Accès REST :** Sécurisation de tous les nouveaux endpoints personnalisés via la capacité `edit_posts`.
- **Protection des Métadonnées :** Enregistrement sécurisé de plus de 80 clés de métadonnées avec `register_meta` et `auth_callback`.

### Optimisation
- **Mise en cache intelligente :** Implémentation d'une stratégie de cache via l'API Transients de WordPress pour les données d'anniversaires.

### Nettoyage & Refactoring
- **Découplage Architecturel :** Migration de la logique métier et de caching des contrôleurs REST vers un service `Birthday` spécialisé (SRP).
- **Normalisation :** Centralisation de la génération des titres dans la classe `Utils`.
- **Nettoyage Administration (Sondages) :** Suppression du support `'custom-fields'` pour le type `sondage` afin d'éviter les conflits visuels, tout en conservant l'accès API via `register_rest_field`.
- **Restauration de l'Éditeur Classique (Sondages) :** Désactivation de Gutenberg pour préserver la disposition des metaboxes personnalisées.

## 4.3.7 - 2026-05-07
### Correction
- **Intégrité des Sondages (Doublons) :** Correction d'un bug dans la migration v4.3.2 qui pouvait entraîner la duplication des votes en base de données dans certaines conditions de mise à jour ou de restauration.
- **Robustesse de l'affichage :** Sécurisation des requêtes SQL (DISTINCT et COUNT DISTINCT) pour garantir des résultats corrects même en présence de données redondantes.
- **Nettoyage automatique :** Ajout d'un script de migration pour purger les doublons existants dans la table `dame_poll_votes`.
