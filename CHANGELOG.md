# Changelog

## [4.8.1] - 2026-07-18

### Application Mobile (PWA)
- **Nouveau Types d'Exercices :** 
  - Implénetation du type 7 : La Marche du Héros.


## [4.8.0] - 2026-07-18

### Application Mobile (PWA)
- **Apprentissage (Restriction & Isolation par Identité) :**
  - Restructuration de l'accès au module Apprentissage basé sur le rôle de l'utilisateur connecté via un appel découplé à `/roi/v1/config` (Option A).
  - Envoi automatique du header HTTP `X-Selected-Identity` dans toutes les requêtes d'Apprentissage (parcours, progression, contenu, validation) pour isoler les progressions de chaque membre ou parent au sein d'un même compte.
  - Masquage dynamique de l'onglet et restriction d'accès aux URLs d'apprentissage dans le routeur via un garde de navigation (`beforeEach`).
  - Correction de l'affichage du Hub d'apprentissage pour les testeurs / administrateurs autorisés (remplacement de la vérification stricte `isAdherent` par `canAccessApprentissage`).
- **Type d'exercice Partie Héros :** Création du composant `TypePartieHeros.vue` (Type 4) permettant de lire et de résoudre un scénario composé d'étapes de visualisation de parties (PGN) avec barre de contrôle de navigation (Début, Précédent, Suivant) et d'étapes de questions à choix multiples (QCM) sur des positions spécifiques avec validation par Toasts.
- **Progression d'apprentissage :** Implémentation de l'envoi de la progression des exercices terminés avec succès au backend WordPress (requête POST vers `/roi/v1/progression`). Ajout d'un tableau réactif `exercicesValides` dans le store `apprentissage` pour suivre et mettre à jour localement les exercices réussis sans rechargement.
- **Authentification & Redirection :** Correction du flux de connexion en restaurant l'écran de sélection de profil (`SelectPersonPage.vue`) en dehors de la structure des onglets (Tabs). L'API redirige maintenant vers `/select-person` si plusieurs identités sont détectées, ou directement vers `/tabs/profil` si un seul profil est rattaché au compte.
- **Profil Utilisateur :** Optimisation de l'affichage de la page de profil pour économiser de l'espace vertical (avatar et marges réduits), permettant aux boutons d'actions d'être visibles sur mobile sans défilement. Intégration d'un bouton de changement de profil dynamique qui s'affiche si le compte dispose de plusieurs identités.
- **Onglet Le Club (Navigation & Actualités) :**
  - Ajout d'un nouvel onglet « Actualités » en première position.
  - Renommage de la sous-section « Général » en « Agenda ».
  - Titre de la page dynamique s'adaptant à l'onglet actif.
  - Correction des redirections de détails et boutons de retour pour les actualités, l'agenda, les tournois et le bénévolat.
  - Liaison automatique des boutons de la page d'accueil vers les onglets correspondants via paramètres de requête (`tab`).
- **Espace Administration :**
  - Intégration d'un bouton de menu latéral (`ion-menu-button`) sur mobile pour toutes les pages d'administration.
  - Correction du surlignement de l'onglet actif pour les sous-pages et fiches de détails.
  - Synchronisation et réinitialisation de la pile de navigation de l'outlet lors des transitions entre l'espace public et l'administration pour éviter les conflits d'affichage.

## [4.7.5] - 2026-07-03

### Plugin WordPress (Backend)
- **E-mails d'anniversaire (Transition de saison) :** Alignement du moteur d'envoi d'e-mails (`send_wishes()`) sur la même logique de filtrage des saisons d'adhésion (`get_filtered_season_ids()`) lors de la période de transition (du 1er juillet au 31 octobre).

## [4.7.4] - 2026-07-03

### Plugin WordPress (Backend)
- **Anniversaires (Transition de saison) :** Prise en compte de la période de transition de saison d'adhésion (du 1er juillet au 30 octobre inclus) afin de remonter et combiner les anniversaires des membres des saisons `Saison XXXX-1/XXXX` et `Saison XXXX/XXXX+1` tout en assurant leur déduplication s'ils sont inscrits dans les deux.

## [4.7.3] - 2026-06-30

### Plugin WordPress (Backend)
- **Agenda (Synchronisation de date) :** Correction du bug de synchronisation entre la date de début et la date de fin lors de la saisie clavier en écoutant uniquement l'événement `blur` au lieu de `change` (évitant ainsi le format tronqué sur les années).
- **Agenda (Saisie des heures) :** Remplacement des listes déroulantes de choix de l'heure par des champs de saisie natifs HTML5 `<input type="time" step="900">` pour une saisie plus moderne et ergonomique par pas de 15 minutes.
- **Appel à bénévoles (Date par défaut) :** Pré-remplissage automatique lors de l'ajout d'une nouvelle date avec le lendemain de la date la plus récente déjà saisie.

## [4.7.2] - 2026-06-30

### Plugin WordPress & PWA
- **Mentions Légales :** Ajout de la mention de protection des données personnelles et de l'exercice des droits sous le bouton de validation du formulaire de préinscription (shortcode WordPress et PWA).

### Plugin WordPress (Backend)
- **Gestion des Consentements (Opt-out) :** Remplacement de l'opt-in de communication par e-mail par des cases d'opposition individuelles décochées par défaut pour l'adhérent (`dame_email_refuses_comms`) et ses représentants légaux (`dame_legal_rep_1_email_refuses_comms` / `dame_legal_rep_2_email_refuses_comms`).
- **Métabox PreInscription :** Ajout et prise en charge des cases à cocher d'opposition e-mail dans la métabox WordPress pour la visualisation et l'édition administrative.

### Application Mobile (PWA)
- **Formulaire de Préinscription :** Intégration de cases à cocher d'opposition (opt-out) pour l'adhérent et les représentants légaux avec support du retour à la ligne automatique (`ion-text-wrap`) pour éviter la troncature du texte.

## [4.7.1] - 2026-06-30

### Plugin WordPress (Backend)
- **Configuration REST PWA :** Ajout de la clé `current_season` (nom de la saison d'adhésion active) dans la réponse de l'API `/pwa-config`.

### Application Mobile (PWA)
- **Affichage Saison Active :** Intégration de la saison active sur les en-têtes de la page de préinscription et dans la carte d'action de la page d'accueil (ex: `Préinscription Saison 2026/2027`).
- **Correction Liens PDF Questionnaire :** Résolution d'un bug d'URL 404 en éliminant le suffixe `/wp-json` pour pointer proprement vers les documents de questionnaire de santé physiques du plugin.

## [4.7.0] - 2026-06-29

### Plugin WordPress (Backend)
- **API REST (Préinscription) :** Création du contrôleur REST `PreInscription.php` gérant la soumission du formulaire, le chargement sécurisé des détails d'un adhérent et les téléchargements des PDF (attestations de santé et autorisations parentales).
- **Vérification d'Adhésion en cours :** Ajout de la clé `already_registered` (basée sur la saison active) dans la réponse de l'API `/my-identities` pour filtrer les membres déjà à jour.

### Application Mobile (PWA)
- **Formulaire de Préinscription :** Recréation du formulaire WordPress au sein de la PWA (`PreInscriptionPage.vue`) avec gestion dynamique Majeurs/Mineurs et autocomplétion des adresses (GéoPortail/IGN) et des lieux de naissance (geo.api.gouv.fr).
- **Gestion Unifiée des Cibles :** Proposition d'une liste unique fusionnant l'utilisateur connecté et l'ensemble de ses enfants associés avec indication d'état (`✅ Rempli`).
- **Nommage des Documents :** Personnalisation automatique des noms des PDF téléchargés intégrant le NOM et le Prénom de l'adhérent.
- **Optimisation Interface & Filtres :** Résolution des erreurs de sessions expirées (refresh automatique en cas de 401), suppression de l'effet de clignotement de la carte d'action sur la page d'accueil, et masquage automatique si tout le foyer est déjà inscrit pour la saison active.

## [4.6.2] - 2026-06-24

### Plugin WordPress (Backend)
- **Barre d'outils d'administration (Toolbar) :** Correction de l'erreur de permissions (« droit insuffisant ») sur le raccourci « Envoyer un message » en rectifiant son URL (`admin.php?page=dame-mailing`). Transition des vérifications de rôles codés en dur vers l'utilisation de la capacité `edit_dame_messages`, et sécurisation du bouton de sauvegarde manuelle en le masquant si l'utilisateur n'a pas la capacité `manage_options`.

### Application Mobile (PWA)
- **Authentification (Renouvellement de session) :** Implémentation du rafraîchissement silencieux du jeton JWT (`/auth/refresh`) lorsque l'application revient au premier plan ou est relancée. Cela permet d'éviter les déconnexions intempestives et répétitives après l'expiration de la session courte de base (ex: 60 minutes) tout en s'appuyant sur la fenêtre de rafraîchissement globale (ex: 2 semaines) configurée côté serveur.

## [4.6.1] - 2026-06-21

### Plugin WordPress (Backend)
- **Manifest Dynamique :** Interception de `/dame-manifest.json` pour générer à la volée le manifest de la PWA à partir du titre et du logo du site WordPress.
- **Invitation à l'installation (Bannière) :** Ajout d'un système d'invitation à l'installation sur tout le site WordPress avec bannière premium (effet glassmorphism, support du mode sombre).
- **Logique d'installation :**
  - **Android/Chromium :** Interception de l'événement `beforeinstallprompt` avec bouton « Installer ».
  - **iOS/Safari :** Infobulle d'aide étape par étape (Partage -> Sur l'écran d'accueil) avec cooldown de 7 jours (sauvegardé en `localStorage`).
  - **Service Worker :** Enregistrement automatique du Service Worker de la PWA sur le frontend WordPress.

### Application Mobile (PWA)
- **TanStack Query (Vue Query) :** Migration complète des requêtes et de la mise en cache réseau (news, agenda, bénévolat, dashboard, adhérents, contacts, messages, reference data) avec persistance locale pour un mode hors-ligne fluide.
- **Sécurité (Connexion) :** Remplacement des requêtes par le SDK `simple-jwt-login`, déconnexion automatique en cas de token expiré ou invalide, et blocage d'accès immédiat (avec révocation du token) pour les comptes n'ayant pas validé leur e-mail (rôle `subscriber` unique).
- **Correctifs :** Correction de l'affichage vide des onglets d'administration (adhérents, contacts, messages) et des actualités/bénévolats en mode déconnecté en supprimant les blocages liés à `initialData`. Résolution de l'erreur d'injection de contexte lors de l'appel du client de requête TanStack hors setup Vue.

## [4.6.0] - 2026-06-04

### Plugin WordPress (Backend)
- **Détection du module ROI :** Ajout de l'endpoint REST `/dame/v1/pwa-config` permettant de savoir si le plugin ROI (apprentissage) est actif et d'exposer l'URL absolue de ses assets.
- **Optimisation de la taille du plugin :** Externalisation de Stockfish vers le plugin ROI. Allègement du livrable DAME de 7,3 Mo par la suppression des fichiers physiques WASM et JS.
- **API REST (Identités) :** Correction de l'absence du champ `member_id` dans la réponse de l'endpoint `/dame/v1/my-identities` pour les membres physiques.

### Application Mobile (PWA)
- **Migration JWT (Simple JWT Login) :** Adaptation du store d'authentification PWA pour supporter la transition vers le plugin *Simple JWT Login*. Mise à jour de l'endpoint d'authentification (`/simple-jwt-login/v1/auth`) et fiabilisation de la récupération du profil utilisateur et des rôles depuis l'API WordPress native.
- **Configuration API Locale :** Remplacement de l'adresse de l'API locale par `dev.local` dans la configuration de développement.
- **Gestion dynamique de l'Échiquier :** Masquage complet du bouton d'accès au jeu "Jouer une partie" si le module ROI n'est pas actif sur le site.
- **Chargement dynamique de Stockfish :** Modification de `PlayPage.vue` pour instancier les Workers Web en utilisant l'URL d'assets fournie dynamiquement par le plugin ROI.
- **Cache dynamique Workbox :** Configuration de la stratégie `CacheFirst` (`runtimeCaching`) dans le Service Worker pour intercepter et stocker localement le binaire de Stockfish issu du plugin ROI lors de la première connexion en ligne.
- **Garde de navigation (Router) :** Sécurisation des routes `/play` et `/analysis` en interdisant leur accès si le module ROI est inactif.
- **Sauvegarde et suivi des parties d'échecs :** Intégration de la sauvegarde automatique des parties d'échecs terminées vers le plugin ROI pour les profils adhérents (type `member`), avec gestion de file d'attente locale (`localStorage`) pour le mode hors ligne et synchronisation automatique.
- **Fin de partie définitive :** Blocage de l'annulation de coups (bouton Oups) dès que la partie est terminée.

### Outillage & Automatisation
- **Script de Packaging Node.js :** Remplacement du script shell obsolète `package.sh` par un script Node.js multiplateforme moderne `script/package.cjs`.
- **Intégration LocalWP :** Auto-détection intelligente et support automatique des environnements de développement PHP et Composer de l'application *Local* (LocalWP) sur Windows et macOS.
- **Chargement dynamique d'extensions :** Résolution des erreurs d'environnement PHP (notamment l'absence d'OpenSSL CLI sur Windows) via le chargement à la volée des extensions PHP de l'instance LocalWP lors de la phase de packaging.
- **Gestion d'exclusions :** Amélioration du mécanisme d'exclusion de dossiers (ex: exclusion récursive propre du dossier `pwa/node_modules/` via l'extension du support des motifs `.distignore` se terminant par `/`).

## [4.5.8] - 2026-06-01

### Plugin WordPress (Backend)
- **Conservation des Filtres :** Implémentation du mécanisme de retour à la liste filtrée pour les Adhérents, Événements, Appels à bénévoles et Messages. Mémorisation de l'URL dans le profil utilisateur (`user_meta`).
- **Bouton de retour :** Rendu visuel d'un bouton moderne et premium avec effet de survol dynamique et micro-animations.
- **Colonnes & Filtres :**
  - Ajout de la colonne "Civ." (Civilité) pour les listes Adhérents et Contacts.
  - Ajout d'un filtre par civilité ("Monsieur", "Madame", "Non précisé") pour les listes Adhérents et Contacts.
  - Suppression du filtre obsolète de statut d'adhésion sur les Adhérents.
  - Suppression du filtre obsolète de date sur les Contacts.

## [4.5.7] - 2026-06-01

### Plugin WordPress (Backend)
- **Personnalisation :** Ajout du tag `[CIVILITE]` qui se résout en "Monsieur" ou "Madame" selon le sexe.
- **Contacts :** Ajout du champ "Sexe" (Masculin, Féminin, Non précisé) dans les fiches contact.
- **Migration :** Initialisation automatique du sexe des contacts existants à "Non précisé" lors de la mise à jour en version 4.5.7.

### Application Mobile (PWA)
- **Fiche Contact :** Affichage dynamique du sexe (genre) au-dessus du prénom et du nom du contact si renseigné.

## [4.5.6] - 2026-05-27

### Plugin WordPress (Backend)
- **Version Bump :** Passage du plugin en version 4.5.6 pour s'aligner sur les évolutions majeures de l'application mobile.

### Application Mobile (PWA)
- **Progressive Web App (PWA) :** Implémentation complète via `vite-plugin-pwa` permettant l'installation sur l'écran d'accueil et la mise en cache de l'interface et du moteur de jeu.
- **Mode Hors-Ligne (Jeu) :** L'espace de jeu et l'analyse fonctionnent désormais à 100% hors connexion grâce au moteur Stockfish WASM local.
- **Persistance des Données :** Intégration de `pinia-plugin-persistedstate` pour sauvegarder localement les sessions, le contenu de l'agenda, les contacts et l'état des parties en cours.
- **Espace de Jeu (Échecs) :** Nouveau layout responsive optimisé pour iPad et iPhone (mode paysage) avec panneau latéral d'action et mini-interface.
- **Suivi de Performance :** Les compteurs d'aide ("Aide") et d'annulation ("Oups !") sont sauvegardés en temps réel.
- **Cache de Page Intelligent :** Téléchargement proactif du contenu HTML des tournois pour garantir une disponibilité "zéro délai" hors-ligne.
- **Sécurité et Stabilité :** Utilitaire `safeFetch` avec timeout, blocage des appels réseau en mode déconnecté et correction des bugs de rotation d'écran sur l'échiquier.

## [4.5.5] - 2026-05-26

### Plugin WordPress (Backend)
- **Sécurité (Inscription) :** [security fix] Correction d'une faille critique permettant de contourner la vérification d'e-mail. Les nouveaux inscrits reçoivent désormais le rôle `subscriber` par défaut et sont promus au rôle `membre` uniquement après validation du jeton envoyé par e-mail.
- **Logique Métier (Catégories) :** Correction de l'oubli du suffixe "F" pour les catégories d'âge adultes (SéniorF, Sénior+F, VétéranF) pour les membres de sexe féminin. Mise à jour automatique des filtres admin et de l'API REST associée.
- **API REST (Données) :** Nouvel endpoint `department-region-mapping` permettant de récupérer la relation complète entre départements et régions pour optimiser les filtres dynamiques côté client.

### Application Mobile (PWA)
- **Tri des Adhérents :** Nouveau sélecteur de tri par Nom (A-Z/Z-A) et par Catégorie d'âge (respectant l'ordre sportif U8 -> Vétéran) sur la page des adhérents.
- **Filtrage des Contacts :** Ajout de filtres par Région et par Département sur la page des contacts.
- **Données de Référence :** Intégration d'un nouveau store `referenceData` pour récupérer dynamiquement les régions, départements et leur mapping depuis WordPress.
- **Mapping Intelligent :** Filtrage dynamique de la liste des départements en fonction de la région sélectionnée et vice-versa.
- **Interface des Filtres :** Passage des sélecteurs (Saison, Tri, Région, etc.) en mode `action-sheet` pour une meilleure lisibilité sur mobile des intitulés longs.
- **Uniformisation UI :** Utilisation d'une grille Ionic (`ion-grid`) pour aligner tous les filtres sur une seule ligne dans le header, optimisant l'espace vertical.
- **Sécurité (XSS) :** Migration massive de `v-html` vers une nouvelle directive personnalisée `v-safe-html` utilisant `DOMPurify` pour prévenir l'injection de scripts malveillants tout en préservant les composants Ionic autorisés.
- **Sécurité (Accès) :** Renforcement du contrôle d'accès sur les routes administratives via le router Vue (`requiresAdmin`), empêchant les utilisateurs non autorisés d'accéder aux données sensibles.
- **Optimisation de Rendu :** Amélioration de la directive `v-safe-html` avec une détection de changement de valeur, supprimant les recalculs et manipulations du DOM inutiles.

## [4.5.4] - 2026-05-25

### Plugin WordPress (Backend)
- **API REST (Adhérent) :** Exposition de nouveaux champs méta (`_dame_fide_id`, `_dame_elo_standard`, `_dame_elo_rapide`, `_dame_elo_blitz`) pour consommation par la PWA.
- **API REST (Catégorie d'Âge) :** Ajout d'un champ calculé `dame_age_category` utilisant la logique métier centralisée pour une cohérence parfaite entre Web et Mobile.
- **Qualité de Code :** Correction de l'intégralité des erreurs PHPStan (Level 6) : typage strict des itérables, suppression de code mort et sécurisation des manipulations DOM.

### Application Mobile (PWA)
- **Section Licence :** Création d'une nouvelle section dédiée dans la fiche adhérent regroupant les informations sportives.
- **Classements ELO :** Affichage stylisé des indices Standard, Rapide et Blitz sur une seule ligne via une grille de badges.
- **Données FFE & FIDE :** Intégration du numéro de licence FFE (avec type) et de l'identifiant FIDE côte à côte.
- **Catégorie d'Âge :** Affichage de la catégorie (ex: U12, Sénior) dans la section Identité et ajout de badges visuels dans la liste globale.
- **Organisation de la Fiche :** Extraction des données de licence de la section Identité pour clarifier le profil sportif.

## [4.5.3] - 2026-05-22

### Plugin WordPress (Backend)
- **Correction ELO :** Fiabilisation de l'extraction des classements sur le site FFE (remplacement des espaces insécables au lieu de la troncature) pour préserver la lettre d'indice (ex: "1299 E").
- **Optimisation Batch :** Ajout d'une limite de sécurité à 10 recherches d'ID FIDE par exécution quotidienne pour prévenir les timeouts PHP.

### Application Mobile (PWA)
- **Espace de Jeu (Échecs) :** Intégration complète d'un échiquier interactif contre l'IA (Stockfish 18).
- **Moteur Stockfish :** Support via Web Worker avec force ajustable de 1320 à 2800 ELO.
- **Système de Suggestion :** Nouveau moteur d'IA proposant le meilleur coup via une flèche visuelle.
- **Suivi de Performance :** Compteurs d'annulations et de coups parfaits ; affichage dynamique du matériel capturé.
- **Vue d'Analyse :** Page dédiée pour consulter l'historique complet des coups (tableau 3 tours par ligne) avec navigation interactive.
- **UI/UX :** Stabilisation de l'échiquier (layout Flexbox) et retrait du plugin legacy (cibles es2022).

## [4.5.2] - 2026-05-22

### Application Mobile (PWA)
- **Agenda (Infinite Scroll) :** Correction d'une boucle infinie d'appels API lors du défilement. Détection automatique de fin de liste pour désactiver le chargement.

## [4.5.1] - 2026-05-22

### Plugin WordPress (Backend)
- **Synchronisation FFE Automatisée :** Création d'un service de batch (`FFESyncBatch`) remplaçant les scripts externes. Synchronisation quotidienne à 12:00 via WP-Cron (ELOs, Licences, ID FIDE).
- **Import CSV FFE :** Nouvel outil d'import manuel avec algorithme de correspondance à double niveau (Licence, puis Nom normalisé).
- **API REST (Identités) :** Refonte complète de la logique `my-identities` appliquant 4 règles métier pour supporter les comptes familiaux (un seul email pour plusieurs profils).
- **API REST (Données Sportives) :** Inclusion systématique des 3 classements ELO et du prénom du joueur.
- **Interface Adhérent :** Mise à jour de la metabox "Classification" intégrant les champs ELO et FIDE en lecture seule.
- **Réglages :** Ajout de l'identifiant de référence du club (ID FFE) dans l'onglet Association.
- **Corrections :** Suppression d'erreurs de syntaxe PHP et mise en conformité PHP 8.4 des appels `fgetcsv`.

### Application Mobile (PWA)
- **Classements ELO Dynamiques :** Affichage des indices Standard, Rapide et Blitz sur la page d'accueil pour les adhérents connectés.
- **Gestion des Familles (ELO) :** Support de l'imbrication des membres rattachés permettant aux responsables légaux de voir les classements de tous leurs enfants.
- **Interface Adaptive :** Double mode d'affichage (Grille horizontale pour les joueurs seuls, Liste alignée pour les familles).
- **Sécurité des Sessions :** Purge systématique de toutes les données privées lors de la déconnexion.
- **Robustesse Bénévolat :** Refonte de la détection de participation avec vérification stricte des identités.
- **UI/UX :** Optimisation du cycle de vie des pages Ionic et condensation de l'affichage famille.

## [4.5.0] - 2026-05-18

## 4.4.6a - 2026-05-17
### Ajout
- **REST API (Agenda) :** Amélioration du filtrage par date pour inclure les événements "en cours" (ceux ayant commencé dans le passé mais finissant aujourd'hui ou plus tard).
- **Application Mobile (PWA) :** Support complet pour les événements "en cours" et système de dédoublonnage automatique dans l'Agenda.

### Modifié
- **Application Mobile (PWA) :** Refonte de la fusion des données (store) pour une meilleure stabilité du cache et persistance globale de l'état de la pagination.

## 4.4.6 - 2026-05-16
### Ajout
- **Système d'Inscription (REST API) :** Implémentation d'un nouvel endpoint `POST /dame/v1/register` permettant aux membres du club de créer leur propre compte utilisateur WordPress.
- **Vérification par Email :** Mise en place d'un système de jetons de vérification envoyés par e-mail avec redirection automatique vers la PWA après validation.
- **Sécurité (Connexion) :** Restriction de l'accès aux comptes non vérifiés via le filtre `wp_authenticate_user`, avec une exception pour les rôles `Membre` ou supérieurs.
- **Gestion Multi-Identités (REST API) :** Création de l'endpoint `GET /dame/v1/my-identities` permettant à un utilisateur de récupérer toutes les fiches adhérents liées à son adresse e-mail.
- **Application Mobile (PWA) :** Implémentation d'un système de sélection de profil pour les comptes familiaux (un email pour plusieurs adhérents/responsables).
- **Application Mobile (PWA) :** Création de `SelectPersonPage.vue` permettant de choisir l'identité active après la connexion.
- **Application Mobile (PWA) :** Ajout de la fonctionnalité de rafraîchissement par tirage vers le bas (Pull-to-refresh) sur la page d'accueil.
- **Application Mobile (PWA) :** Création de la page `RegisterPage.vue` liée au nouvel endpoint d'inscription.
- **Application Mobile (PWA) :** Mise en place d'un système de synchronisation automatique rafraîchissant les données lors des changements de session.

### Modifié
- **Sécurité des Rôles (PWA) :** Refonte de la détection des droits incluant le rôle `Entraineur` et isolant strictement le rôle `Membre` des fonctions de gestion.
- **Stabilité API REST (PWA) :** Forçage du contexte `view` pour l'Agenda et les Sondages afin d'éliminer les erreurs 403 Forbidden.
- **Interface (PWA) :** Optimisation du header pour l'affichage dynamique de l'identité (nom et rôle) avec support des noms longs et suppression des dépendances restrictives.
- **Sécurité (Toolbar) :** Restriction de l'affichage de la barre d'outils DAME aux seuls rôles d'encadrement (Staff, Entraineur, Admin), masquage pour les Abonnés et Membres.

### Correction
- **Qualité Code (Adherent Matcher) :** Fiabilisation de la recherche d'adhérents par email et conformité stricte PHP 8.4.
- **REST API (Validation) :** Correction d'une erreur fatale `ArgumentCountError` dans les callbacks de validation des routes REST.
- **Sauvegardes (Restauration) :** Correction critique de la restauration des rôles utilisateurs (mapping dynamique du préfixe de base de données) garantissant la conservation des droits après import.
- **Redirection PWA :** Refonte de la redirection après vérification d'email pour utiliser l'URL dynamique du site au lieu d'une URL locale.
- **Application Mobile (PWA) :** Résolution du bug de masquage des titres de sondages pour les utilisateurs non connectés.
- **Application Mobile (PWA) :** Correction de l'absence de nom pour les comptes administrateurs purs via une récupération forcée du profil complet.
- **Application Mobile (PWA) :** Suppression d'un fallback de sécurité qui accordait par erreur des droits d'administration au rôle "Membre".
- **Application Mobile (PWA) :** Correction du bug de l'état persistant sur la page d'inscription après deconnexion.

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
