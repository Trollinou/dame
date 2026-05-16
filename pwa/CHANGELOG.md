# Changelog
Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.4.5] - 2026-05-15
### Ajouté
- **Pagination Réelle (Agenda)** : Implémentation d'une stratégie de chargement à la demande (Infinite Scroll) bi-directionnelle pour l'Agenda.
- **Scroll Infini Top/Bottom** : Capacité de charger l'historique (scrolling vers le haut) et les événements futurs (scrolling vers le bas) de manière fluide.
- **Optimisation Réseau** : Migration vers une architecture de requêtes ciblées (`after_date`, `before_date`) exploitant les nouveaux filtres du backend WordPress pour minimiser le transfert de données.

### Modifié
- **Performance DOM** : Limitation de l'affichage initial à 20 événements pour garantir une fluidité maximale sur mobile.
- **UX Agenda** : Repositionnement automatique et instantané sur "Aujourd'hui" lors du premier chargement de la session.
- **UX Navigation** : Mémorisation intelligente de la position de défilement lors du retour depuis un détail d'événement (évite le scroll forcé sur "Aujourd'hui").

### Corrigé
- **Imports & Nettoyage** : Revue complète de toutes les vues pour supprimer les imports de composants, d'icônes et de hooks inutilisés.
- **Résolution de Composants** : Correction d'un warning Vue critique lié à l'absence d'import de `IonButtons` dans plusieurs fichiers (`PublicHomePage.vue`, etc.).
- **Stabilité** : Correction d'une erreur `ReferenceError` sur la variable `isFirstLoad` dans la vue Agenda.

## [4.4.4] - 2026-05-14
### Ajouté
- **Navigation Hybride** : Refonte complète de la navigation pour séparer les accès Public (Actualités, Agenda, Tournois) et Privé (Staff uniquement).
- **Mode Administration** : Implémentation d'un interrupteur (Toggle) dans la barre d'onglets permettant au staff de basculer vers les outils de gestion (Adhérents, Contacts, Sondages, Messages).
- **Dashboard Public** : Création de `PublicHomePage.vue` offrant une vue synthétique des dernières nouvelles, des prochains événements et des sondages en cours.
- **Actualités** : Création des vues `NewsPage.vue` (liste avec recherche textuelle et filtrage par catégorie) et `NewsDetailPage.vue` (lecture complète).
- **Pages Dynamiques** : Création de `GenericPage.vue` pour afficher n'importe quelle page WordPress (utilisé pour les règlements de tournois et pages d'information).
- **Menu Tournoi** : Intégration de l'endpoint `dame/v1/pwa-menu` pour générer dynamiquement la liste des tournois sous forme de cartes interactives.
- **Intégration HelloAsso** : Système d'injection dynamique transformant les shortcodes `[helloasso]` en boutons d'inscription Ionic natifs, respectant le placement dans l'éditeur WordPress.
- **Composable Navigation** : Création de `useInternalLinks.ts` pour intercepter les liens WordPress et rediriger vers `GenericPage` sans quitter la PWA.

### Modifié
- **Authentification** : Déplacement des actions de profil (Connexion/Déconnexion) vers le header global.
- **UX/UI** : Harmonisation des formats de date entre l'accueil et l'agenda.
- **Store d'Authentification** : Gestion des rôles utilisateur (`administrator`, `editor`, `staff`) pour sécuriser l'accès au mode Admin.
- **PWA Assets** : Mise à jour complète des icônes (192px, 512px, apple-touch, favicon) avec support du mode "maskable" pour Android.

### Corrigé
- **Stabilité** : Résolution d'une erreur critique `TypeError` dans la barre d'onglets liée aux actions de déconnexion.
- **Réactivité** : Passage de `isAuthenticated` en propriété calculée pour une mise à jour instantanée de l'interface lors du login/logout.
- **Navigation** : Correction de tous les liens de retour (Back buttons) dans les vues de détail administratives.
- **Nettoyage** : Suppression des boutons d'ajout ("+") non fonctionnels dans les vues Agenda, Adhérents et Contacts.

## [4.4.3] - 2026-05-10
### Corrigé
- **Authentification** : Correction du bug de "Silent Fetching" qui ne se déclenchait plus après une nouvelle connexion (migration du pré-chargement vers le hook `onIonViewWillEnter` d'Ionic).
- **Sécurité & Cache** : Implémentation d'un nettoyage complet de tous les stores Pinia (`clearData`) lors de la déconnexion pour éviter la persistance de données entre deux sessions.
- **Gestion des Sessions** : Centralisation de la logique de déconnexion (`authStore.logout()`) lors de la détection d'une session expirée (erreur 401) dans tous les stores de données.

## [4.4.2] - 2026-05-09
### Ajouté
- **Dashboard** : Création du `DashboardStore` Pinia pour la mise en cache des anniversaires et données de l'accueil.
- **Fiche Événement** : Création de `AgendaDetailPage.vue` avec affichage complet (Lieu, Carte GPS intelligente, Description HTML).
- **Fiche Contact** : Création de `ContactDetailPage.vue` avec identité professionnelle et actions de communication.
- **Fiche Sondage** : Création de `SondageDetailPage.vue` avec système d'accordéons par date et liste des inscrits par créneau.
- **Fiche Message** : Création de `MessageDetailPage.vue` avec visualisation du contenu et rapport statistique d'envoi.
- **UI/UX** : Intégration du logo "Dame" (Queen SVG) et harmonisation des headers fixes sur toute l'application.
- **Performance** : Système de pré-chargement global des données au démarrage pour une navigation instantanée.

### Modifié
- **Architecture** : Migration complète vers Pinia avec gestion du "Silent Refresh" et verrouillage des requêtes simultanées.
- **Navigation** : Mémorisation de la position de défilement lors du retour depuis une vue détaillée.

### Corrigé
- **Stabilité** : Correction d'un bug de chargement infini (stuck spinner) lié à la gestion du verrou réseau dans les stores.
- **UI** : Support du multi-ligne pour les titres longs dans les headers condensés sous iOS.

## [4.4.1] - 2026-05-09
### Corrigé
- **Fiche Adhérent** : Correction de l'affichage des icônes de contact pour les représentants légaux.
- **Fiche Adhérent** : Harmonisation de la structure visuelle (icônes start/end) entre les contacts de l'adhérent et ceux des représentants.
- **Fiche Adhérent** : Ajustement de l'ordre d'affichage dans la carte de contact (adresse déplacée en bas).

## [4.4.0] - 2026-05-09
### Ajouté
- **Fiche Adhérent** : Création de la vue `MemberDetailPage.vue` pour la consultation détaillée.
- **Fiche Adhérent** : Affichage des informations d'identité, de licence sportive et de catégorie.
- **Fiche Adhérent** : Ajout d'une carte de Contact (Email, Téléphone, Adresse postale).
- **Fiche Adhérent** : Ajout d'une carte dynamique pour les Représentants Légaux (pour les mineurs) avec boutons d'appels et d'emails directs.
- **Routing** : Ajout de la route dynamique `/tabs/members/:id`.

### Modifié
- **Configuration** : Alignement du numéro de version de la PWA sur la version globale du plugin WordPress (`dame`).
- **Store** : Mise à jour de l'interface TypeScript `Member` pour mapper les clés de l'API REST personnalisée (ex: `_dame_license_number`, `_dame_address_1`).

### Corrigé
- **Déploiement** : Passage du router Vue en `createWebHashHistory` pour supporter l'exécution dans un sous-dossier WordPress.
- **Déploiement** : Configuration de Vite (`base: './'`) pour générer des chemins d'assets relatifs.
- **API** : Remplacement des URLs codées en dur par la variable d'environnement `VITE_API_BASE_URL` pour tous les appels `fetch` (Auth, WP REST, et endpoints spécifiques DAME).
