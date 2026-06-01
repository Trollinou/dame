# Changelog
Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.5.7] - 2026-06-01
### Ajouté
- **Fiche Contact** : Affichage dynamique du sexe (genre) au-dessus du prénom et du nom du contact si renseigné.

## [4.5.6] - 2026-05-27
### Ajouté
- **Progressive Web App (PWA)** : Implémentation complète via `vite-plugin-pwa` avec Service Worker, permettant l'installation de l'application sur l'écran d'accueil et la mise en cache de l'interface et du moteur de jeu (Stockfish WASM).
- **Mode Hors-Ligne (Jeu)** : L'espace de jeu et l'analyse fonctionnent désormais à 100% hors connexion, le moteur d'échecs s'exécutant localement sur l'appareil.
- **Persistance Globale des Données** : Ajout du plugin `pinia-plugin-persistedstate` sur la quasi-totalité des stores (`auth`, `chess`, `members`, `contacts`, `agenda`, `messages`, `benevolat`).
- **Persistance des Statistiques de Jeu** : Les compteurs d'aide ("Aide") et d'annulation ("Oups !") sont désormais sauvegardés en temps réel et restaurés lors de la reprise d'une partie.
- **Cache de Page Intelligent** : Le store des tournois télécharge silencieusement et de manière proactive le contenu HTML complet de tous les tournois dès le chargement de la liste pour garantir leur disponibilité hors-ligne "zéro délai".
- **Synchronisation Optimisée (Fraîcheur)** : Implémentation d'une stratégie de "Freshness" basée sur le nouveau champ WordPress `modified`. L'application bloque les appels réseau silencieux de mise à jour si les données locales sont suffisamment récentes, évitant ainsi la saturation serveur et les erreurs console.

### Modifié
- **Layout Espace de Jeu (Responsive)** : Refonte totale du layout de la page de jeu. En mode paysage, l'échiquier bascule automatiquement sur un affichage en deux colonnes (jeu à gauche, boutons d'action sur un panneau latéral droit).
- **Adaptation iPad (Portrait/Paysage)** : Agrandissement significatif de la surface de l'échiquier sur les écrans de tablette pour un meilleur confort de jeu et d'analyse (jusqu'à 760px en portrait).
- **Optimisation iPhone Paysage** : Création d'une "Mini-Interface" en mode paysage pour les téléphones (masquage du header, barres de pièces ultra-fines, boutons slim) maximisant l'espace alloué au plateau.
- **Layout d'Analyse (Responsive)** : Application du même modèle en deux colonnes à la page d'analyse (Paysage), avec un tableau d'historique compacté et un défilement optimisé pour ne pas tronquer l'affichage.
- **Mécanique de Centrage de l'Échiquier** : Remplacement des conteneurs Flexbox par un affichage standard en mode portrait afin de stabiliser définitivement les coordonnées internes de clic sur les pièces.
- **Ergonomie des Boutons** : Ajout d'un espacement de sécurité (4-5px) entre les boutons d'action et les flèches de navigation pour éviter les clics accidentels et améliorer le confort visuel.

### Corrigé
- **Sécurité et Stabilité Réseau** : Implémentation d'un utilitaire `safeFetch` avec timeout (4s) pour toutes les requêtes d'arrière-plan. Bloque les erreurs de type "CORS" ou "502 Bad Gateway" en cas de coupure du serveur.
- **Erreurs Console (Mode Hors-Ligne)** : Toutes les requêtes API vérifient maintenant `navigator.onLine` avant d'être lancées. Si l'appareil est déconnecté et possède déjà du cache, l'application est totalement silencieuse au lieu de générer des avertissements techniques.
- **Interactivité après Rotation** : Correction du bug rendant les pièces in-cliquables après avoir pivoté l'appareil. Le composant d'échiquier est maintenant automatiquement détruit puis recréé (via l'attribut `key`) après un debounce de 200ms lors de la rotation de l'écran.
- **Alignement de l'Historique** : Correction du rendu des lignes incomplètes dans le tableau des coups (dernière ligne) par l'injection de cellules fantômes invisibles, garantissant un alignement vertical parfait.
- **Affichage des Numéros de Coups** : Correction d'un bug de retour à la ligne sur les petits écrans (wrap) empêchant le point de séparation de se retrouver sous le numéro du coup.
- **Défaut de Scroll de l'Historique (iPad Portrait)** : Application d'un `min-height: 0` sur la section parente pour débloquer le défilement vertical (`overflow-y`) de la liste des coups qui restait précédemment bloquée ou tronquée.
- **Saut brutal du scroll dans l'Historique** : Correction du calcul de la position active (`watch currentPly`) en utilisant `getBoundingClientRect` au lieu de `offsetTop` pour garantir un recentrage fluide sur le bon coup, sans sauter à la fin de la liste.
- **Erreur au Démarrage (Home)** : Correction d'une `ReferenceError: newsStore` liée à un import manquant sur la page d'accueil.
- **Store (Actualités)** : Exposition de la méthode `savePost` permettant la mise à jour manuelle du cache pour les articles consultés individuellement.

## [4.5.5] - 2026-05-26
### Ajouté
- **Tri des Adhérents** : Nouveau sélecteur de tri par Nom (A-Z/Z-A) et par Catégorie d'âge (respectant l'ordre sportif U8 -> Vétéran) sur la page des adhérents.
- **Filtrage des Contacts** : Ajout de filtres par Région et par Département sur la page des contacts.
- **Données de Référence** : Intégration d'un nouveau store `referenceData` pour récupérer dynamiquement les régions, départements et leur mapping depuis WordPress.
- **Mapping Intelligent** : Filtrage dynamique de la liste des départements en fonction de la région sélectionnée et vice-versa.

### Modifié
- **Interface des Filtres** : Passage des sélecteurs (Saison, Tri, Région, etc.) en mode `action-sheet` pour une meilleure lisibilité sur mobile des intitulés longs.
- **Uniformisation UI** : Utilisation d'une grille Ionic (`ion-grid`) pour aligner tous les filtres sur une seule ligne dans le header, optimisant l'espace vertical.

### Corrigé
- **Sécurité (XSS)** : Migration massive de `v-html` vers une nouvelle directive personnalisée `v-safe-html` utilisant `DOMPurify` pour prévenir l'injection de scripts malveillants tout en préservant les composants Ionic autorisés.
- **Sécurité (Accès)** : Renforcement du contrôle d'accès sur les routes administratives via le router Vue (`requiresAdmin`), empêchant les utilisateurs non autorisés d'accéder aux données sensibles.
- **Fuite d'État** : Réinitialisation forcée du mode administration (`adminMode`) lors de la déconnexion.
- **Composants Vue** : Correction d'un avertissement lié à l'absence d'import de `IonBadge` dans la vue des adhérents.

### Optimisé
- **Performance de Rendu** : Amélioration de la directive `v-safe-html` avec une détection de changement de valeur, supprimant les recalculs et manipulations du DOM inutiles lors des mises à jour d'état (ex: saisie recherche).

## [4.5.4] - 2026-05-26
### Ajouté
- **Section Licence** : Création d'une nouvelle section dédiée dans la fiche adhérent regroupant les informations sportives.
- **Classements ELO** : Affichage stylisé des indices Standard, Rapide et Blitz sur une seule ligne via une grille de badges.
- **Données FFE & FIDE** : Intégration du numéro de licence FFE (avec type) et de l'identifiant FIDE côte à côte.
- **Catégorie d'Âge** : Affichage de la catégorie (ex: U12, Sénior) dans la section Identité.
- **Badges de Liste** : Ajout de badges visuels pour la catégorie d'âge directement dans la liste globale des adhérents pour un repérage rapide.

### Modifié
- **Organisation de la Fiche** : Extraction des données de licence de la section Identité pour alléger la lecture et clarifier le profil sportif.
- **Uniformisation des Dates** : Migration vers l'utilisation systématique de la date locale (YYYY-MM-DD) au lieu de la date UTC (toISOString) pour les calculs de l'agenda, évitant les disparités d'affichage au passage de minuit.

### Corrigé
- **Gestion des Sessions (Agenda)** : Ajout d'une détection automatique des erreurs 401 (Unauthorized) dans le store de l'agenda. Les sessions expirées ou corrompues déclenchent désormais une déconnexion immédiate, évitant l'affichage persistant d'une liste vide pour les utilisateurs déconnectés du serveur.

## [4.5.3] - 2026-05-22
### Ajouté
- **Espace de Jeu (Échecs)** : Intégration complète d'un échiquier interactif contre l'IA.
- **Moteur Stockfish 18** : Support de Stockfish via Web Worker (version single-thread) avec force ajustable de 1320 à 2800 ELO.
- **Système de Suggestion (Aide)** : Nouveau moteur d'IA à la demande proposant le meilleur coup via une flèche visuelle verte.
- **Compteurs de Performance** : Suivi des annulations ("Oups !") et des coups parfaits (correspondant à l'aide) directement sur les boutons d'action.
- **Suivi Matériel** : Affichage dynamique des pièces capturées et du score d'avantage (+X) au-dessus et en-dessous de l'échiquier.
- **Timer de Partie** : Chronomètre automatique démarrant au premier coup et s'arrêtant en fin de match.
- **Vue d'Analyse Dédiée** : Page séparée pour consulter l'historique complet des coups dans un tableau structuré (3 tours par ligne) avec navigation interactive et surbrillance du coup courant.

### Modifié
- **Ergonomie Mobile** : Mise en page figée (scroll-y="false") pour supprimer les décalages de coordonnées de clic sur l'échiquier.
- **Robustesse Fin de Partie** : Utilisation de `chess.js` pour expliciter les raisons des matchs nuls (Pat, matériel insuffisant, répétition, 50 coups).
- **Formatage PGN** : Utilisation de symboles de pièces Unicode agrandis et pleins pour une meilleure lisibilité.
- **Optimisation Build** : Passage à la cible `es2022` et retrait du plugin legacy pour supporter nativement les BigInt et WebAssembly.

### Corrigé
- **Décalage Tactile** : Stabilisation complète de l'échiquier via un layout Flexbox empêchant tout mouvement parasite lors de la sélection des pièces.

## [4.5.2] - 2026-05-22
### Corrigé
- **Agenda (Infinite Scroll)** : Correction d'une boucle infinie d'appels API lors du défilement vers le bas. L'application détecte désormais correctement la fin de la liste (erreur 400 de WordPress) et désactive le chargement automatique pour éviter les erreurs en cascade dans la console.

## [4.5.1] - 2026-05-22
### Ajouté
- **Classements ELO Dynamiques** : Affichage des indices Standard, Rapide et Blitz sur la page d'accueil pour les adhérents connectés.
- **Gestion des Familles (ELO)** : Support de l'imbrication des membres rattachés (`associated_members`) permettant aux responsables légaux de voir les classements de tous leurs enfants.
- **Interface Adaptive** : Double mode d'affichage (Grille horizontale pour les joueurs seuls, Liste alignée avec en-têtes pour les familles).

### Modifié
- **Sécurité des Sessions** : Purge systématique de toutes les données privées (Identités, ELOs, Inscriptions Bénévolat) lors de la déconnexion pour éviter toute fuite de données entre deux utilisateurs sur le même appareil.
- **Robustesse Bénévolat** : Refonte de la détection de participation (`hasUserVoted`) avec vérification stricte des IDs (> 0) et exclusion des profils génériques pour éliminer les "faux positifs" d'inscription.
- **Optimisation Cycle de Vie** : Migration de la page de sélection vers les hooks Ionic (`onIonViewWillEnter`) pour garantir la fraîcheur des données malgré le cache du navigateur.
- **UI/UX** : Condensation de l'affichage famille (marges réduites) et alignement parfait des cartes ELO sur la largeur des listes d'actualités.

### Corrigé
- **Cache API** : Ajout de l'en-tête `cache: 'no-store'` sur les requêtes sensibles (Identités, Bénévolat) pour forcer la synchronisation avec le serveur WordPress lors du changement de compte.
- **Identification Mineurs** : Correction de l'étiquetage des mineurs utilisant leur propre email (bascule automatique de 'Gestion' vers 'Adhérent').

## [4.5.0] - 2026-05-18
### Ajouté
- **Module Bénévolat** : Implémentation complète de la gestion des participations bénévoles (remplace le module Sondages).
- **Vote Public** : Création de `BenevolatVotePage.vue` permettant aux adhérents de proposer leur aide sur des créneaux spécifiques.
- **Indicateurs de Participation** : Ajout de badges "Inscrit" (participation enregistrée) et "Terminé" (événement passé) pour une meilleure visibilité.
- **Affluence en Temps Réel** : Affichage du nombre d'inscrits par créneau dans le formulaire de participation.

### Modifié
- **Refactoring Sémantique** : Migration globale du terme "Sondage" vers "Bénévolat" dans tout le code source (Vues, Stores, Router, API).
- **Architecture des Stores** : Renommage de `useSondageStore` en `useBenevolatStore` avec support du cache local pour le suivi des participations.
- **Intégration Articles** : Mise à jour du système d'interception transformant les formulaires de bénévolat en boutons d'action natifs "Proposer mon aide".
- **Tri des Bénévolats** : Implémentation d'un double tri (chronologique pour les appels en cours, antéchronologique pour les terminés).
- **Optimisation Mobile** : Ajustement de la barre d'onglets pour supporter l'affichage de 6 boutons sur les écrans Android étroits.

### Corrigé
- **Affichage Paysage** : Correction chirurgicale des contenus coupés par la Dynamic Island/Notch via l'intégration des zones de sécurité (Safe Areas) sur toutes les pages de détails.
- **Navigation Admin** : Résolution du problème de surlignage de l'onglet Bénévolat lors de la sélection en mode gestionnaire.
- **Synchro WordPress** : Correction de l'envoi de l'identité du membre (`member_id`) lors de la participation pour une liaison correcte avec le plugin WP.

## [4.4.6a] - 2026-05-17
### Ajouté
- **Gestion des Événements "En cours"** : Support complet pour les événements ayant commencé dans le passé mais se terminant dans le futur.
- **Dédoublonnage Automatique** : Implémentation d'un système de filtrage par ID dans l'Agenda pour gérer les chevauchements de requêtes API entre le passé et le futur.

### Modifié
- **Stabilité du Cache Agenda** : Refonte de la fusion des données dans le store pour empêcher la suppression accidentelle de l'historique lors d'un rafraîchissement depuis la Homepage.
- **Persistence de la Pagination** : Déplacement de l'état de pagination (`upcomingPage`, `pastPage`) dans le store global pour maintenir la cohérence de l'historique lors de la navigation.

## [4.4.6] - 2026-05-16
### Ajouté
- **Gestion Multi-Identités** : Implémentation d'un système de sélection de profil pour les comptes familiaux (un email pour plusieurs adhérents/responsables).
- **Page de Sélection** : Création de `SelectPersonPage.vue` permettant de choisir l'identité active après la connexion.
- **Pull-to-Refresh** : Ajout de la fonctionnalité de rafraîchissement par tirage vers le bas sur la page d'accueil pour mettre à jour News, Agenda et Sondages instantanément.
- **Inscription Publique** : Création de la page `RegisterPage.vue` liée à un nouvel endpoint WordPress pour permettre aux membres de créer leur compte eux-mêmes.
- **Synchronisation Auto** : Mise en place d'un système de surveillance (Watcher) rafraîchissant automatiquement les données lors des changements de session (Login/Logout).

### Modifié
- **Sécurité des Rôles** : Refonte complète de la détection des droits. Ajout du rôle `Entraineur` aux accès privilégiés et exclusion stricte du rôle `Membre` des fonctions de gestion.
- **Stabilité API REST** : Forçage du contexte `view` pour l'Agenda et les Sondages afin d'éliminer les erreurs 403 Forbidden rencontrées par certains profils.
- **Header Dynamique** : Optimisation de l'affichage de l'identité dans le header avec un conteneur flexible supportant les noms longs sans troncature et suppression de la dépendance restrictive `ion-button`.
- **UX Inscription** : Clarification du formulaire de connexion et d'inscription (utilisation de l'Identifiant WordPress au lieu de l'Email).

### Corrigé
- **Titres Sondages** : Résolution du bug masquant les titres des sondages pour les utilisateurs non connectés sur la Homepage.
- **Identité Virtuelle** : Correction de l'absence de nom pour les comptes administrateurs purs via une récupération forcée du profil WordPress complet (`/me`).
- **Accès Admin** : Suppression d'un fallback de sécurité qui accordait par erreur des droits d'administration au rôle "Membre".
- **État Persistant** : Correction du bug dans la page d'inscription qui affichait toujours l'écran de succès lors d'une nouvelle tentative après déconnexion.

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
