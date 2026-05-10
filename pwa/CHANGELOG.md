# Changelog
Tous les changements notables apportés à ce projet seront documentés dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.0.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
