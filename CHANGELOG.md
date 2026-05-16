# Changelog

## 4.4.5 - 2026-05-15
### Ajout
- **Optimisation REST API (Agenda) :** Implémentation du filtrage par date côté serveur via les nouveaux paramètres `after_date` et `before_date` pour l'endpoint `/wp-json/wp/v2/agenda`.
- **Tri Chronologique (REST) :** Autorisation du paramètre `orderby=meta_value` pour l'agenda, permettant un tri précis basé sur la date de début de l'événement (`_dame_start_date`).
- **Application Mobile (PWA) :** Implémentation d'une pagination réelle et bidirectionnelle (Infinite Scroll Top/Bottom) pour l'Agenda.
- **Application Mobile (PWA) :** Optimisation réseau exploitant les nouveaux filtres backend pour minimiser les transferts de données.

### Modifié
- **Application Mobile (PWA) :** Amélioration de l'UX avec repositionnement automatique sur "Aujourd'hui" et mémorisation intelligente de la position de défilement au retour des détails.
- **Application Mobile (PWA) :** Optimisation des performances DOM avec limitation de l'affichage initial à 20 événements.

### Correction
- **Application Mobile (PWA) :** Résolution d'erreurs critiques (`ReferenceError`, warnings Vue `IonButtons`) et nettoyage complet des imports inutilisés.

## 4.4.4 - 2026-05-14
### Ajout
- **API REST (PWA Menu) :** Création d'un nouvel endpoint `dame/v1/pwa-menu` permettant de récupérer les éléments du menu "Menu_PWA" avec support de la hiérarchie (ID, Parent, Object ID, Title, Order).
- **Application Mobile (PWA) :** Refonte complète de la navigation avec séparation des accès Public (Actualités, Agenda, Tournois) et Privé (Staff).
- **Application Mobile (PWA) :** Mode "Administration" permettant au staff de basculer vers les outils de gestion via un interrupteur dans la barre d'onglets.
- **Application Mobile (PWA) :** Nouveau Dashboard Public offrant une vue synthétique des nouvelles, événements et sondages.
- **Application Mobile (PWA) :** Module d'Actualités (`NewsPage` et `NewsDetailPage`) avec recherche et filtrage par catégorie.
- **Application Mobile (PWA) :** Affichage dynamique des pages WordPress (`GenericPage`) avec interception intelligente des liens internes.
- **Application Mobile (PWA) :** Menu des Tournois dynamique basé sur l'endpoint REST `pwa-menu`.
- **Application Mobile (PWA) :** Intégration native des formulaires d'inscription HelloAsso.

### Modifié
- **Application Mobile (PWA) :** Migration des actions de profil vers le header global et mise à jour des actifs (icônes maskables, splash screens).
- **Application Mobile (PWA) :** Gestion fine des rôles (`administrator`, `editor`, `staff`) pour la sécurisation du mode Admin.

### Correction
- **Qualité Code (Data Endpoints) :** Correction de plusieurs erreurs PHPDoc et types de retour dans la classe `Data_Endpoints` pour une conformité totale avec PHPStan Level 6.
- **Application Mobile (PWA) :** Correction de bugs critiques de navigation, de stabilité (TypeError au logout) et de réactivité de l'interface.
- **Application Mobile (PWA) :** Nettoyage de l'interface (suppression des boutons d'ajout non fonctionnels).

## 4.4.3 - 2026-05-10
### Ajout
- **Mailing (Envois cumulés) :** Conservation de l'historique complet des destinataires lors d'envois multiples pour un même message, permettant un suivi précis sur le long terme.

### Modifié
- **Mailing (Personnalisation) :** Amélioration de la gestion des balises `[NOM]`, `[PRENOM]` et `[AGE]` dans la file d'attente globale SMTP avec récupération intelligente des données (distinction entre l'adhérent et son représentant légal selon l'e-mail).
- **Mailing (Formatage) :** Utilisation systématique des utilitaires de formatage `Utils` pour les noms et prénoms dans tous les contextes d'envoi et de génération de documents (PDF).

### Correction
- **Fiabilité de l'identité :** Ajout d'un fallback automatique sur le "Nom de naissance" si le "Nom d'usage" est manquant pour éviter les titres de fiches et les balises de mailing vides.
- **Rapports de Mailing :** Correction de la perte d'historique des destinataires lors de renvois successifs d'un même message.
- **Mailing (Filtrage) :** Fiabilisation du marquage des messages reçus (`_dame_message_received`) pour le bon fonctionnement du filtrage incrémental lors des envois groupés.
- **Authentification** : Correction du bug de "Silent Fetching" qui ne se déclenchait plus après une nouvelle connexion (migration du pré-chargement vers le hook `onIonViewWillEnter` d'Ionic).
- **Sécurité & Cache** : Implémentation d'un nettoyage complet de tous les stores Pinia (`clearData`) lors de la déconnexion pour éviter la persistance de données entre deux sessions.
- **Gestion des Sessions** : Centralisation de la logique de déconnexion (`authStore.logout()`) lors de la détection d'une session expirée (erreur 401) dans tous les stores de données.

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
