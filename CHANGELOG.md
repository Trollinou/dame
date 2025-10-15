# Changelog

## 3.3.5 - 2025-10-15
### Correction
- **Tâche Cron des Anniversaires :** Correction d'une série de bugs qui empêchaient l'envoi des emails d'anniversaire. La requête de base de données a été fiabilisée pour gérer différents formats de date et noms de champs, résolvant un problème où aucun adhérent n'était trouvé.
- **Bouton d'email de test :** Correction d'un bug où le bouton "Envoyer un email de test" sur la page des réglages des anniversaires ne donnait pas de retour clair. Il utilise maintenant AJAX pour un retour immédiat ("Email envoyé" ou "Échec") sans recharger la page.

## 3.3.4 - 2025-10-15
### Amélioration
- **Qualité du code :** Remaniement majeur de la structure des fichiers dans les répertoires `/admin` et `/includes`. Les fichiers monolithiques volumineux (`metaboxes.php`, `columns.php`, `settings-page.php`, `cpt.php`, `shortcodes.php`) ont été découpés en modules plus petits et logiques, organisés par fonctionnalité dans de nouveaux sous-répertoires. Cela améliore considérablement la lisibilité, l'organisation et la maintenabilité du code.

### Correction
- **Bug de sauvegarde :** Correction d'un bug qui empêchait la sauvegarde du champ "Académie" (`_dame_school_academy`) sur la fiche d'un adhérent. La cause était une coquille dans le nom du champ lors du processus de sauvegarde.
- **Chemins des scripts :** Correction d'une régression introduite par le remaniement où les chemins vers certains fichiers JavaScript et CSS de l'administration étaient incorrects, provoquant des erreurs 404 et cassant des fonctionnalités comme l'autocomplétion des adresses.

## 3.3.3 - 2025-10-13
### Ajout
- **Gestion des événements :** Il est désormais possible de spécifier si un événement est une compétition (individuelle ou par équipe) et son niveau (départementale, régionale, nationale).
- **Suivi des participants :** Une nouvelle metabox permet d'associer des adhérents (avec une adhésion active) à un événement pour suivre leur participation.
- **Onglet Association :** Ajout d'un nouvel onglet "Association" dans `Réglages > Options DAME`. Cet onglet, positionné en premier, permet de centraliser l'adresse postale de l'association.
- **Autocomplétion d'adresse :** Le champ d'adresse de l'association bénéficie de l'autocomplétion via l'API Geo Gouv, qui remplit automatiquement les champs de code postal, ville, latitude et longitude.
- **Champs Latitude et Longitude :** Ajout de champs non modifiables pour stocker la latitude et la longitude de l'association, en vue de leur utilisation dans de futures fonctionnalités.

### Amélioration
- **Calcul de trajet :** Lors de la saisie de l'adresse d'un événement, la distance et le temps de trajet en voiture depuis l'adresse de l'association sont automatiquement calculés et affichés. Un bouton manuel permet de relancer le calcul. Ces informations sont également visibles sur la page publique de l'événement.
- **Affichage du lieu :** Si le champ "Intitulé du lieu" d'un événement est laissé vide, l'adresse de l'événement est utilisée comme titre par défaut sur la page publique, assurant que les informations de localisation sont toujours visibles.
- **Affichage des événements :** La page publique d'un événement affiche maintenant les informations sur la compétition, les coordonnées GPS (latitude/longitude) et la liste des participants.
- **Interface d'édition d'événement :** Les champs de latitude et de longitude sont maintenant visibles mais non modifiables dans la métaboxe des détails de l'événement.
- **Interface de sélection des participants :** La liste des participants dans la métaboxe est maintenant dotée d'un champ de recherche pour filtrer les adhérents par nom. De plus, les participants déjà sélectionnés sont affichés en premier pour une meilleure visibilité.

## 3.3.2 - 2025-10-12
### Ajout
- **Fonctionnalité de Messagerie :** Introduction d'un nouveau type de contenu "Message" pour créer et gérer les communications par email aux membres.
  - Les messages sont rédigés via l'éditeur de blocs Gutenberg et ne sont pas publics.
  - La page "Envoyer un article" a été renommée "Envoyer un message" et adaptée pour envoyer ces nouveaux messages.
  - Ajout d'actions pour "Dupliquer" un message, s'envoyer un "email de test", et "Copier en tant qu'article" (brouillon).
  - La liste des messages affiche désormais des colonnes pour la "Date d'envoi", "Auteur de l'envoi", et les "Destinataires".
  - Les critères de sélection des destinataires sont maintenant sauvegardés avec le message pour un historique complet.
  - Ajout de la possibilité de dupliquer un message en tant qu'article (brouillon).
  - La colonne "Destinataires" dans la liste des messages affiche désormais les critères de sélection (filtres ou liste manuelle).

### Amélioration
- La logique de filtrage des destinataires pour l'envoi de messages a été améliorée. Lorsque des saisons et des groupes sont sélectionnés, le système effectue une intersection (ET) pour un ciblage plus précis.
- Le rôle "Membre du Bureau" a maintenant la permission de publier des articles, leur donnant accès à la fonctionnalité de messagerie.

### Correction
- Le titre de la page "Envoyer un message" s'affiche désormais correctement.
- La sélection par défaut de la saison en cours a été retirée de la page d'envoi de message pour éviter les erreurs.

## 3.3.1 - 2025-10-12
*   **Amélioration :** Remplacement de la saisie de la taxonomie "Groupes" par une liste de cases à cocher pour une sélection plus intuitive et rapide.
*   **Correctif :** La taxonomie "Groupes" est maintenant hiérarchique pour correspondre à la nouvelle interface de sélection, corrigeant un bug où les IDs des termes étaient enregistrés comme de nouveaux termes.
*   **Correctif :** Rétablissement de la taxonomie "Saison d'adhésion" en mode non-hiérarchique, corrigeant une régression introduite lors du précédent correctif.

## 3.3.0 - 2025-10-07
*   **Fonctionnalité :** Remplacement de plusieurs cases à cocher de classification (`École d'échecs`, `Pôle Excellence`, `Bénévole`, `Elu local`) par une nouvelle taxonomie non hiérarchique "Groupes".
*   **Fonctionnalité :** Création d'un script de migration de données qui s'exécute lors de la mise à jour du plugin pour convertir les anciennes métadonnées vers la nouvelle taxonomie `dame_group`.
*   **Amélioration :** La metabox "Groupes" sur l'écran d'édition d'un adhérent est maintenant positionnée juste en dessous de "Classification et Adhésion" et est dépliée par défaut pour une saisie plus rapide.
*   **Amélioration :** L'écran de consultation d'un adhérent affiche désormais les groupes assignés et masque les anciennes classifications.
*   **Amélioration :** Le filtre de la liste des adhérents a été mis à jour pour utiliser la nouvelle taxonomie "Groupes".
*   **Amélioration :** La page "Envoyer un article" a été mise à jour pour filtrer les destinataires par la nouvelle taxonomie "Groupes". L'ordre des filtres a été ajusté pour une meilleure ergonomie (Sexe, Saison, Groupes).
*   **Amélioration :** La fonctionnalité de sauvegarde et de restauration JSON gère désormais correctement la nouvelle taxonomie "Groupes", assurant l'intégrité des données lors des exports et des imports.
*   **Correctif :** Résolution d'un bug critique qui provoquait une erreur fatale lors de la restauration de sauvegardes antérieures à la version 3.3.0. La fonction d'import est maintenant rétrocompatible.
*   **Correctif :** Correction d'un bug où les préinscriptions n'étaient pas correctement supprimées lors d'une restauration, provoquant des doublons.
*   **Correctif :** Rétablissement des permissions d'accès à la page de consultation d'un adhérent pour les rôles "Éditeur" et "Membre du bureau".
*   **Correctif :** Résolution de plusieurs notices de dépréciation PHP 8.2 liées à l'enregistrement des pages de menu cachées.

## 3.2.8 - 2025-10-02
*   **Correctif :** Résolution d'un bug critique sur la page des options où l'enregistrement des paramètres dans un onglet effaçait les réglages des autres onglets. La sauvegarde est maintenant correctement ciblée sur l'onglet actif.
*   **Correctif :** Rétablissement de la mise en surbrillance des différences lors de la comparaison d'une préinscription avec un adhérent existant.

## 3.2.7 - 2025-09-29
*   **Correctif :** Le menu "Envoyer un article" est maintenant accessible au rôle "Éditeur", corrigeant un problème de permissions qui le limitait aux administrateurs.

## 3.2.6 - 2025-09-28
*   **Amélioration :** Refonte de la page des options en une interface à onglets ("Saisons", "Anniversaires", etc.) pour une meilleure organisation et clarté.
*   **Fonctionnalité :** La gestion des saisons d'adhésion a été améliorée avec un menu déroulant pour sélectionner la saison active.
*   **Amélioration :** La tâche cron des emails d'anniversaire filtre désormais les adhérents par saison active (et inclut la saison précédente en septembre).
*   **Fonctionnalité :** Ajout d'un bouton pour envoyer un email de test d'anniversaire afin de prévisualiser le rendu.
*   **Amélioration :** La mise en page des onglets "Saisons" et "Anniversaires" a été ajustée pour une meilleure ergonomie.

## 3.2.5 - 2025-09-28
*   **Correctif :** La navigation dans l'agenda (`[dame_agenda]`) conserve désormais le mois consulté. Lors d'un retour arrière après avoir cliqué sur un événement ou lors d'un rafraîchissement de la page, l'agenda reste sur le mois précédemment affiché au lieu de revenir au mois actuel.
*   **Correctif :** Correction d'un bug majeur où toutes les informations saisies dans le formulaire de création d'un événement d'agenda étaient perdues si une erreur de validation survenait (par exemple, un champ obligatoire manquant). Le formulaire se recharge maintenant avec les données précédemment entrées.

## 3.2.4 - 2025-09-26
*   **Amélioration :** La couleur du texte des événements sur plusieurs jours s'adapte maintenant (noir ou blanc) en fonction de la luminance de la couleur de fond pour une meilleure lisibilité.
*   **Amélioration :** Le fond des événements ponctuels (non privés) est maintenant éclairci de 75% par rapport à la couleur de leur bordure pour un effet visuel plus doux.
*   **Amélioration :** La mise en page de la liste d'événements (`[dame_liste_agenda]`) a été affinée. L'icône de calendrier a été supprimée et l'heure de l'événement s'affiche maintenant en italique à la suite de la date.
*   **Correctif :** Résolution d'un problème de mise en page où les titres d'événements longs sur plusieurs jours pouvaient déborder de leur conteneur. Ils sont maintenant correctement tronqués avec des points de suspension.
*   **Correctif :** Sur la vue mobile de l'agenda, le point indiquant un événement ponctuel utilise maintenant la couleur de base de l'événement au lieu de la couleur de fond éclaircie.

## 3.2.3 - 2025-09-25
*   **Fonctionnalité :** Ajout d'un système d'envoi automatique d'emails d'anniversaire aux adhérents, avec une option pour l'activer ou le désactiver.
*   **Fonctionnalité :** Ajout d'une option dans `Réglages > Options DAME` pour définir le slug d'un article (publié ou privé) à utiliser comme modèle pour l'email d'anniversaire.
*   **Fonctionnalité :** L'email d'anniversaire est personnalisé avec les balises `[NOM]`, `[PRENOM]` et `[AGE]`.
*   **Fonctionnalité :** Une nouvelle tâche cron journalière envoie les emails d'anniversaire. Un email de résumé est envoyé à l'administrateur.
*   **Amélioration :** La planification des tâches cron (sauvegarde et anniversaires) utilise maintenant le fuseau horaire de WordPress pour une meilleure fiabilité, corrigeant un bug potentiel de décalage horaire.

## 3.2.2 - 2025-09-25
*   **Amélioration :** Les événements privés sont maintenant visibles dans les shortcodes `[dame_agenda]` et `[dame_liste_agenda]` pour les utilisateurs connectés avec un rôle autorisé (Membre du Bureau, Administrateur, etc.).
*   **Amélioration :** Les événements privés sont maintenant visuellement distincts. Dans la vue calendrier, ils ont un fond de couleur `#ffbf8b`. Dans la vue liste, l'icône de la date a ce même fond de couleur.
*   **Correctif :** Le préfixe "Privé :" a été retiré du titre des événements dans le shortcode `[dame_liste_agenda]`.

## 3.2.1 - 2025-09-25
*   **Amélioration :** La description des événements dans la liste (`[dame_liste_agenda]`) est maintenant limitée à la première ligne/paragraphe pour une meilleure lisibilité. Un lien "..." est ajouté si la description est plus longue. La mise en forme (gras, italique) est préservée.

## 3.2.0 - 2025-09-20
*   **Fonctionnalité :** Ajout d'un nouveau rôle "Membre du Bureau" (staff) avec les permissions d'un "Contributeur" ainsi que l'accès en lecture aux contenus privés et la visibilité sur le menu des Pages.
*   **Fonctionnalité :** Ajout d'une page de consultation en lecture seule pour les adhérents, accessible depuis la liste des adhérents dans l'administration. La page imite la disposition de l'écran d'édition pour une expérience utilisateur cohérente.
*   **Amélioration :** La fonction "Envoyer un article" liste désormais les articles publiés et privés.
*   **Sécurité/Permissions :** L'accès aux menus "Envoyer un article" et "Assignation des comptes" est maintenant correctement restreint aux rôles Éditeur et Administrateur (`edit_others_posts`).
*   **Modification :** La propriété `has_archive` du CPT "Agenda" est maintenant définie à `false`.
*   **Qualité du code :** La création du rôle "Membre du Bureau" se base maintenant dynamiquement sur les capacités du rôle "Contributeur" de WordPress.

## 3.1.6 - 2025-09-19
*   **Fonctionnalité :** Ajout d'un menu "DAME" à la barre d'administration de WordPress (Toolbar) pour un accès rapide aux fonctions clés du plugin. Le menu est visible sur le front-end et le back-end pour les utilisateurs connectés et inclut des liens pour "Voir les préinscriptions", "Envoyer un article", et une option pour "Faire une sauvegarde" manuelle (accessible aux administrateurs uniquement).
*   **Correctif :** Le filtrage des catégories dans l'agenda (`[dame_agenda]`) a été corrigé. La désélection d'une catégorie enfant masque désormais correctement ses événements, même si la catégorie parente reste sélectionnée.
*   **Correctif :** Le filtre de recherche textuel de l'agenda (`[dame_agenda]`) se réinitialise maintenant correctement lorsque le champ est vidé avec la croix du navigateur.

## 3.1.5 - 2025-09-18
*   **Fonctionnalité :** Ajout d'un bouton "Ajouter à mon agenda" sur la page de détail des événements.
*   **Fonctionnalité :** Le bouton génère et télécharge un fichier `.ics` contenant les détails de l'événement (titre, description, lieu, dates).
*   **Amélioration :** Le bouton est positionné à côté de la date de l'événement pour une meilleure expérience utilisateur.
*   **Correctif :** Résolution d'un bug critique de fuseau horaire qui provoquait un décalage des heures de l'événement lors de l'import dans un agenda. La conversion en UTC est maintenant gérée correctement.
*   **Amélioration :** L'URL de la page de l'événement est maintenant incluse dans le fichier `.ics`.

## 3.1.4 - 2025-09-18
*   **Fonctionnalité :** Ajout de la géolocalisation pour les événements. La latitude et la longitude sont maintenant sauvegardées automatiquement lors de la sélection d'une adresse.
*   **Fonctionnalité :** Ajout d'une carte interactive (Google Maps) sur la page de détail de l'événement.
*   **Fonctionnalité :** Ajout de boutons pour "Calculer l'itinéraire" et "Ouvrir dans le GPS" avec détection de la plateforme (iOS/Autres) pour une meilleure compatibilité.
*   **Amélioration :** L'adresse complète de l'événement (adresse, complément, code postal et ville) est maintenant affichée sur la page de détail.
*   **Amélioration :** La mise en page de la page de détail de l'événement a été ajustée pour afficher la description avant le lieu.
*   **Correctif :** Le style des boutons de navigation de la carte utilise maintenant le style du thème pour une meilleure harmonie visuelle.
*   **Correctif :** Le script pour le bouton GPS est maintenant correctement chargé sur la page de détail de l'événement, corrigeant un bug où le bouton n'était pas fonctionnel.

## 3.1.3 - 2025-09-18
*   **Amélioration :** L'affichage de l'agenda (`[dame_agenda]`) est maintenant entièrement responsive et optimisé pour les appareils mobiles.
*   **Amélioration :** Sur mobile, les événements sont affichés sous forme de points ou de barres colorées pour une meilleure lisibilité, tout en restant cliquables.
*   **Fonctionnalité :** La page de détail d'un événement affiche désormais toutes les informations pertinentes (date, heure, lieu, description).
*   **Correctif :** Correction de plusieurs problèmes de mise en page sur la vue mobile de l'agenda, notamment le débordement du contenu et l'espacement des événements.

## 3.1.2 - 2025-09-18
*   **Fonctionnalité** : Ajout d'un filtre par sexe (Tous / Masculin / Féminin) sur la page "Envoyer un article". Ce filtre s'applique en plus des filtres par saison et par groupe.
*   **Amélioration** : La logique de tri et de rendu des événements a été entièrement refactorisée pour être plus robuste et prévisible.
*   **Fiabilité** : La création des rôles "Entraineur" et "Membre" ne dépend plus des rôles "Éditeur" et "Abonné". Leurs capacités sont maintenant définies directement dans le plugin pour éviter des erreurs si les rôles de base sont absents ou modifiés.
*   **Correctif** : Correction d'un bug majeur dans le calcul de la saison suivante sur la page des réglages. Le calcul se base maintenant sur la saison active en cours au lieu de la date actuelle, ce qui empêche le système de proposer une année erronée.
*   **Correctif** : Résolution d'un bug d'affichage majeur dans l'agenda `[dame_agenda]` où les événements de plusieurs jours commençant à la même date n'étaient pas positionnés correctement et se superposaient.
*   **Correctif** : Les cases du calendrier s'agrandissent désormais dynamiquement en hauteur pour contenir tous les événements d'une journée sans déborder sur la semaine suivante.
*   **Correctif** : Le shortcode `[dame_liste_agenda]` charge désormais correctement la feuille de style de l'agenda.

## 3.1.1 - 2025-09-17
*   **Refactorisation** : Renommage des fichiers `import-export.php` en `backup-restore-adherent.php` pour une meilleure clarté.
*   **Refactorisation** : Déplacement des styles CSS de la liste d'agenda du fichier `dame-public-styles.css` vers `agenda.css` pour une meilleure organisation.
*   **Amélioration** : Le sous-menu "Envoyer un article" est maintenant accessible au rôle "Éditeur" (capability `publish_posts`).

## 3.1.0 - 2025-09-17
*   **Refactorisation Majeure**: Séparation de toute la fonctionnalité LMS (Learning Management System) dans un nouveau plugin `roi`.
*   Le plugin `dame` se concentre maintenant uniquement sur la gestion des adhérents et des événements.
*   Nettoyage du code et suppression des fonctionnalités déplacées.
*   Correction de plusieurs bugs de menus suite à la refactorisation.

### 3.0.8 (16/09/2025)

*   **Amélioration de la recherche de l'agenda :** La barre de recherche du shortcode `[dame_agenda]` a été améliorée pour inclure non seulement le nom de l'événement, mais aussi sa description et le nom de sa catégorie.

### 3.0.8 (16/09/2025)

*   **Amélioration de la recherche de l'agenda :** La barre de recherche du shortcode `[dame_agenda]` a été améliorée pour inclure non seulement le nom de l'événement, mais aussi sa description et le nom de sa catégorie.

### 3.0.7 (14/09/2025)

*   **Fonctionnalité :** Ajout d'une fonction de sauvegarde et de restauration pour la base de données de l'Agenda (événements et catégories). La nouvelle option est disponible dans le sous-menu "Sauvegarde / Restauration" de l'Agenda.
*   **Amélioration :** La sauvegarde automatique journalière inclut désormais également la base de données de l'Agenda.

### 3.0.6 (14/09/2025)

*   **Amélioration majeure de l'administration de l'agenda**
    *   **Refonte des filtres :** Remplacement du filtre par mois par un filtre par plage de dates (mois/année de début à mois/année de fin) pour une sélection plus précise.
    *   **Personnalisation :** La date de début du filtre est sauvegardée par utilisateur pour être ré-appliquée lors des prochaines visites.
    *   **Colonnes de la liste :** Réorganisation des colonnes pour une meilleure lisibilité (`Date de début`, `Date de fin`, `Lieu` avant `Catégorie`). La colonne de date de publication a été supprimée.
    *   **Tri des colonnes :** Les colonnes "Date de début" et "Date de fin" sont maintenant triables.
    *   **Duplication d'événement :** Ajout d'une action "Dupliquer" qui crée une copie d'un événement en tant que brouillon et redirige directement vers sa page d'édition.
    *   **Logique de duplication :** La duplication copie toutes les données (description, lieu, heures, etc.) à l'exception des dates de début et de fin pour faciliter la reprogrammation.

### 3.0.5 (14/09/2025)

*   **Refonte de l'en-tête de l'agenda `[dame_agenda]`**
    *   **Amélioration UI :** Le nom du mois est maintenant positionné à gauche, tandis que les contrôles de navigation, de recherche et de filtre sont regroupés à droite.
    *   **Fonctionnalité :** La navigation des mois a été repensée en un groupe de boutons `< | Ce mois-ci | >`.
    *   **Fonctionnalité :** Ajout d'un bouton "Ce mois-ci" pour revenir rapidement au mois en cours.
    *   **Correctif :** Rétablissement de l'icône de flèche déroulante à côté du nom du mois et de la fonctionnalité de clic pour ouvrir le sélecteur de mois/année.

### 3.0.4 (14/09/2025)

*   **Amélioration de l'agenda `[dame_agenda]`**
    *   **Correctif :** L'infobulle des événements affiche désormais correctement la description de l'événement.
    *   **Amélioration UI :** Ajout d'une icône de flèche à côté du nom du mois pour indiquer qu'il est cliquable.
    *   **Amélioration UI :** La taille du panneau de sélection du mois/année a été réduite pour une apparence plus compacte.
    *   **Correctif de positionnement :** La logique d'affichage du panneau de sélection du mois/année a été entièrement revue pour utiliser une méthode CSS plus robuste, garantissant qu'il s'affiche toujours correctement sous les contrôles de navigation du calendrier.

### 3.0.3 (13/09/2025)

*   **Amélioration de l'interface d'administration de l'Agenda**
    *   **Liste des événements :** Ajout de colonnes pour "Date de début", "Date de fin", "Lieu", et "Catégorie". La catégorie est affichée avec un indicateur de couleur pour une meilleure lisibilité.
    *   **Édition d'événement :** Remplacement de l'éditeur de blocs Gutenberg par un champ de description simple (avec gras/italique) pour plus de simplicité.
    *   **Édition d'événement :** La case à cocher "Journée entière" est maintenant positionnée avant la date de début.
    *   **Édition d'événement :** La date de fin est automatiquement remplie à partir de la date de début si elle est vide.
    *   **Édition d'événement :** Les champs "Date de début", "Date de fin" et "Catégorie" sont désormais obligatoires, avec validation côté client et serveur.
*   **Correctif :** Suppression d'une case à cocher "Journée entière" en double sur l'écran d'édition des événements.

### 3.0.2 (13/09/2025)

*   **Amélioration de l'affichage de l'agenda `[dame_agenda]`**
    *   **Fonctionnalité :** Le filtre des catégories est maintenant hiérarchique. Les catégories enfants sont indentées sous leur parent pour une meilleure lisibilité.
    *   **Fonctionnalité :** Les événements sont maintenant affichés pour tous les jours visibles dans la grille du calendrier, y compris ceux des mois précédent et suivant.
    *   **Amélioration UI :** Le marqueur du jour courant est maintenant un cercle rouge et est aligné en haut à droite de la cellule, de la même manière que les autres numéros de jour.
    *   **Correctif :** Correction d'un bug de mise en page où le style du jour courant interférait avec l'affichage des événements dans la même cellule.

### 3.0.1 (12/09/2025)

*   **Amélioration majeure de l'agenda `[dame_agenda]`**
    *   **Fonctionnalité :** Le premier jour de la semaine de l'agenda est maintenant synchronisé avec le réglage de WordPress ("La semaine débute le"), assurant que le calendrier commence le lundi pour les sites configurés pour la France.
    *   **Fonctionnalité :** Les événements s'étalant sur plusieurs jours sont maintenant affichés comme une barre continue qui s'étend visuellement sur la bonne durée, améliorant considérablement la lisibilité.
    *   **Fonctionnalité :** La mise en page des événements a été revue pour gérer les superpositions. Les événements longs et les événements ponctuels peuvent maintenant coexister sur la même journée sans se cacher mutuellement.
    *   **Correctif :** Résolution d'un bug critique lié aux fuseaux horaires qui pouvait causer un décalage d'un jour dans l'affichage des événements.
    *   **Technique :** La logique d'affichage des événements en JavaScript a été entièrement refactorisée pour être plus robuste et pour gérer des scénarios de mise en page complexes.

### 3.0.0 (11/09/2025)

*   **Fonctionnalité majeure : Ajout d'un système de gestion d'événements (Agenda)**
    *   **CPT Agenda :** Création d'un nouveau type de contenu `Agenda` pour gérer les événements.
    *   **Taxonomie Catégories d'agenda :** Ajout d'une taxonomie hiérarchique pour les événements, avec un sélecteur de couleur pour chaque catégorie.
    *   **Champs personnalisés :** Ajout de champs pour la date/heure de début et de fin, une option "journée entière", et un groupe de champs pour le lieu (avec auto-complétion de l'adresse).
    *   **Shortcode `[dame_liste_agenda]` :** Affiche une liste des événements à venir. Accepte un attribut `nombre` pour limiter le nombre d'événements affichés (ex: `[dame_liste_agenda nombre="5"]`).
    *   **Shortcode `[dame_agenda]` :** Affiche un calendrier mensuel complet.
*   **Documentation :** Mise à jour des fichiers `README.md` et `USING.md` pour inclure les nouvelles fonctionnalités de l'agenda.

### 2.6.1 (10/09/2025)

*   **Correctif Technique :** Remplacement de la fonction `utf8_decode()`, dépréciée en PHP 8.2, par `mb_convert_encoding()` pour assurer la compatibilité et la pérennité du plugin.

### 2.6.0 (10/09/2025)

*   **Fonctionnalité :** Ajout d'un shortcode `[dame_contact]` pour afficher un formulaire de contact simple.
*   **Fonctionnalité :** Le formulaire de contact inclut les champs Nom, Courriel, Sujet et Message, et envoie les soumissions par email à l'administrateur du site en utilisant les paramètres SMTP configurés.
*   **Amélioration :** La soumission du formulaire de contact est gérée en AJAX, fournissant un retour à l'utilisateur sans rechargement de la page.
*   **Correctif :** Correction d'un bug où les caractères spéciaux (comme les apostrophes) dans le formulaire de contact étaient échappés dans l'email reçu.

### 2.5.1 (10/09/2025)

*   **Correctif :** La tâche planifiée (cron) pour la sauvegarde journalière est maintenant correctement mise à jour lorsque l'heure est modifiée dans les réglages. Ajout d'une vérification pour s'assurer que la tâche est toujours planifiée.

### 2.5.0 (09/09/2025)

*   **Fonctionnalité :** Ajout d'un système de sauvegarde journalière automatique. Les bases de données "Adhérents" et "Apprentissage" sont sauvegardées dans des fichiers séparés et envoyées par email.
*   **Fonctionnalité :** Ajout d'une option dans les réglages pour permettre à l'administrateur de configurer l'heure de la sauvegarde journalière (format HH:MM).
*   **Amélioration / Technique :** Refactorisation de la logique d'exportation des données pour éliminer la duplication de code entre la sauvegarde manuelle et la nouvelle sauvegarde automatique, améliorant ainsi la maintenabilité.

### 2.4.8 (03/09/2025)

*   **Fonctionnalité :** Le champ "Commune de naissance" est maintenant obligatoire pour les adhérents majeurs afin de permettre le contrôle d'honorabilité.
*   **Amélioration UI :** Ajout d'un indicateur visuel `(*)` pour les champs qui deviennent obligatoires (Commune de naissance pour les majeurs, et champs du représentant légal pour les mineurs).
*   **Amélioration UX :** Le formulaire de préinscription affiche maintenant un message d'erreur clair si des champs obligatoires sont manquants lors de la soumission, au lieu de se fier au comportement par défaut du navigateur.

### 2.4.7 (02/09/2025)

*   **Amélioration :** Mise en forme automatique des noms et prénoms. Les noms de famille sont automatiquement mis en majuscules et les prénoms en casse mixte (ex: Jean-Michel) lors de la saisie dans le formulaire de préinscription, la fiche de préinscription et la fiche adhérent, pour les adhérents et les représentants légaux.

### 2.4.6 (02/09/2025)

*   **Correctif :** Correction d'un bug où la metabox "Saisons d'adhésion" sur la fiche adhérent n'était pas fonctionnelle. Elle permet désormais d'ajouter et de retirer des saisons de manière interactive, comme attendu.

### 2.4.5 (02/09/2025)

*   **Fonctionnalité :** Ajout d'une action spéciale sur la fiche adhérent pour annuler une adhésion et renvoyer le membre en pré-inscription. Cette action n'est disponible que si l'adhérent possède uniquement le tag de la saison en cours.

### 2.4.4 (01/09/2025)

*   **Amélioration :** Le remplissage automatique des données du représentant légal pour les adhérents mineurs est remplacé par des boutons manuels. Cela donne à l'utilisateur plus de contrôle sur le remplissage du formulaire.
*   **Amélioration UI :** Les boutons de copie sont placés sur la même ligne que le titre de la section pour un design plus compact.
*   **Amélioration UI :** La sélection du sexe dans le formulaire de préinscription s'affiche désormais sur une seule ligne.

### 2.4.3 (01/09/2025)

*   **Amélioration :** Dans la fonction "Envoyer un article", le filtre par statut ("Actif", "Expiré", "Ancien") est remplacé par une liste déroulante des saisons d'adhésion, permettant une sélection multiple.
*   **Amélioration :** La logique de sélection des destinataires est maintenant une UNION entre les saisons et les classifications (ex: École d'échecs), au lieu d'une intersection.
*   **Fonctionnalité :** La saison en cours est maintenant sélectionnée par défaut dans la nouvelle liste déroulante des saisons.

### 2.4.2 (01/09/2025)

*   **Fonctionnalité :** Ajout d'une nouvelle section dans les réglages pour configurer une URL de paiement (ex: PayAsso).
*   **Fonctionnalité :** Après la soumission du formulaire de préinscription, le formulaire est masqué et de nouvelles options s'affichent.
*   **Fonctionnalité :** Ajout d'un bouton pour rediriger vers l'URL de paiement configurée.
*   **Fonctionnalité :** Ajout d'un bouton "Saisir une nouvelle adhésion" qui réinitialise les champs personnels (Nom, Prénom, etc.) tout en conservant les coordonnées pour faciliter les inscriptions multiples (ex: familles).
*   **Amélioration :** Ajout d'un message d'instruction avant les liens de téléchargement de documents, incluant dynamiquement l'email de contact du club.
*   **Amélioration UI :** Les boutons d'action post-inscription sont maintenant stylisés et réorganisés pour une meilleure clarté (icônes, couleurs, disposition verticale).
*   **Correctif :** Résolution d'un bug majeur où les messages et boutons de succès ne s'affichaient pas après la soumission du formulaire en déplaçant le conteneur de message hors du formulaire.
*   **Correctif :** Résolution d'un bug où le lien de téléchargement de l'autorisation parentale ne s'affichait pas pour les mineurs si le questionnaire de santé nécessitait un certificat médical.

### 2.4.1 (31/08/2025)

*   **Fonctionnalité :** Ajout de la génération d'un PDF d'autorisation parentale pour les adhérents mineurs lors de la préinscription.
*   **Fonctionnalité :** Un bouton de téléchargement pour l'autorisation parentale apparaît à côté de l'attestation de santé si l'adhérent est mineur.
*   **Amélioration :** Ajout d'un texte informatif sur le règlement intérieur juste avant le bouton de validation du formulaire de préinscription.
*   **Correctif :** Résolution d'un problème de compatibilité avec le format de compression du template PDF `el_autorisation_parentale.pdf`.
*   **Correctif :** Correction d'un bug où le contenu au bas de la page du PDF d'autorisation parentale était poussé sur une nouvelle page.

### 2.4.0 (31/08/2025)

*   **Fonctionnalité :** Ajout des champs optionnels "Date de naissance" et "Commune de naissance" pour les représentants légaux 1 et 2 dans les formulaires de préinscription et d'adhérent.
*   **Fonctionnalité :** Le mécanisme d'auto-complétion pour les communes de naissance est maintenant actif pour les nouveaux champs des représentants légaux.
*   **Fonctionnalité :** Ajout d'un champ "Contrôle d'honorabilité" pour l'adhérent (dans la section Classification) et pour chaque représentant légal (après la profession). La valeur par défaut est "Non requis".
*   **Amélioration :** Les nouveaux champs sont intégrés au processus de validation des préinscriptions, incluant la table de comparaison des différences.

### 2.3.4 (31/08/2025)

*   **Fonctionnalité :** Ajout d'un lien dynamique vers le questionnaire de santé sur le formulaire de préinscription. Le lien s'adapte automatiquement pour les membres majeurs ou mineurs en fonction de la date de naissance saisie.
*   **Amélioration UI :** Le lien du questionnaire de santé dispose désormais d'un effet de survol avec un fond bleu (`#3ec0f0`) pour une meilleure visibilité et expérience utilisateur.

### 2.3.3 (31/08/2025)

*   **Fonctionnalité :** Ajout du champ "Type de licence" (Licence A ou B) au formulaire de préinscription. Ce champ est maintenant obligatoire.
*   **Amélioration :** Le type de licence est désormais visible et modifiable dans la fiche de préinscription de l'interface d'administration.
*   **Amélioration :** Les libellés pour le type de licence dans la fiche adhérent sont plus descriptifs ("Licence A (Cours + Compétition)").

### 2.3.2 (29/08/2025)

*   **Correctif :** Correction d'un bug où les données d'un adhérent majeur étaient incorrectement copiées sur le représentant légal lors de la préinscription. La vérification est désormais effectuée côté serveur pour garantir qu'aucune donnée de représentant légal n'est sauvegardée pour les membres adultes.

### 2.3.1 (29/08/2025)

*   **Fonctionnalité majeure : Intègre la gestion du questionnaire de santé**
    *   **Formulaire de Préinscription :** Intègre la réponse au questionnaire de santé par un statut ('Attestation signée' ou 'Certificat médical')
    *   **Génération de PDF :** Le nom du fichier PDF généré est maintenant au format `attestation_sante_NOM_Prenom.pdf`
    *   **Administration (Préinscriptions) :** Le champ "Document de santé" est visible et éditable dans la fiche de préinscription. Il est également affiché dans la vue de rapprochement pour comparer avec un adhérent existant.
    *   **Administration (Adhérents) :** Le champ "Document de santé" est ajouté à la fiche adhérent dans la section "Classification et Adhésion".

### 2.3.0 (29/08/2025)

*   **Fonctionnalité majeure : Système de préinscription en ligne**
    *   **Shortcode `[dame_fiche_inscription]` :** Ajout d'un formulaire de préinscription public. Le formulaire affiche dynamiquement les champs requis en fonction de l'âge du futur membre (majeur ou mineur).
    *   **Gestion des préinscriptions :** Les soumissions sont enregistrées en tant que "Préinscriptions" avec un statut "En attente". Un nouveau menu "Préinscriptions" est disponible pour les administrateurs.
    *   **Interface de validation :** L'écran de gestion d'une préinscription permet de visualiser toutes les données soumises. Le formulaire est éditable par l'administrateur.
    *   **Rapprochement automatique :** Le système recherche automatiquement si un adhérent avec le même nom, prénom et date de naissance existe déjà.
    *   **Tableau de comparaison :** Si un adhérent existant est trouvé, un tableau comparatif est affiché, mettant en évidence les différences entre les données soumises et les données existantes.
    *   **Actions de validation :** L'administrateur peut "Valider et Créer Adhérent" (pour une nouvelle inscription) ou "Mettre à jour l'adhérent" (si une correspondance a été trouvée). L'adhérent est automatiquement marqué comme "Actif" pour la saison en cours.
    *   **Intégration :** Les données de préinscription sont incluses dans les sauvegardes et le script de désinstallation.

### 2.2.1 (28/08/2025)

*   **Amélioration :** Le champ "Lieu de naissance" est désormais un champ unique qui stocke la commune et son code (ex: "Paris (75000)"). Le champ "Code postal de naissance" a été supprimé de la fiche adhérent et des exports/imports.
*   **Amélioration UI :** Les libellés "Adresse (Ligne 1)" et "Adresse (Ligne 2)" ont été renommés en "Adresse" et "Complément" sur la fiche adhérent et dans les exports pour plus de clarté.
*   **Amélioration :** Le champ "Taille vêtements" est maintenant une liste déroulante avec des valeurs prédéfinies (`Non renseigné`, `8/10`, `10/12`, `12/14`, `XS`, `S`, `M`, `L`, `XL`, `XXL`, `XXXL`) pour garantir la cohérence des données. Les valeurs existantes non conformes sont automatiquement converties en "Non renseigné".
*   **Amélioration UI :** La metabox "Saisons d'adhésion" est maintenant déplacée sous "Classification et Adhésion" et est repliée par défaut pour une interface plus épurée.

### 2.2.0 (20/08/2025)

*   **Refonte majeure du système d'adhésion**
    *   **Fonctionnalité :** Le statut d'adhésion est désormais géré par des "tags" de saison (ex: "Saison 2025/2026") au lieu d'un statut fixe (Actif, Expiré, Ancien).
    *   **Fonctionnalité :** Une nouvelle taxonomie "Saison d'adhésion" est ajoutée et associée aux adhérents.
    *   **Amélioration :** La page des adhérents affiche désormais le statut "Actif" (si l'adhérent possède le tag de la saison en cours) ou "Non adhérent". Une nouvelle colonne "Saisons d'adhésion" affiche toutes les saisons passées et présentes d'un membre sous forme de pastilles.
    *   **Amélioration :** Les filtres de la liste des adhérents ont été mis à jour pour permettre de filtrer par "Adhésion active" (Oui/Non).
    *   **Amélioration :** La fonction de "Réinitialisation Annuelle" a été complètement réécrite. Elle permet désormais de créer le tag pour la nouvelle saison et de le définir comme saison "active". Elle ne modifie plus le statut des anciens membres.
    *   **Obsolète :** Le champ "Date d'adhésion" et les statuts "Expiré" et "Ancien" ont été supprimés et migrés vers le nouveau système de tags.

### 2.1.4 (20/08/2025)

*   **Fonctionnalité :** Ajout d'un champ 'Profession' pour l'adhérent ainsi que pour chaque représentant légal.
*   **Amélioration :** Le champ 'Profession' est maintenant inclus dans les exports CSV et les sauvegardes JSON.
*   **Amélioration :** Dans le formulaire de l'adhérent, le champ "Numéro de téléphone" est maintenant positionné après l'email pour une meilleure cohérence.

### 2.1.3 (20/08/2025)

*   **Fonctionnalité :** Ajout d'une case à cocher "Refus mailing" pour chaque email (adhérent et représentants légaux) afin de gérer les préférences de communication.
*   **Amélioration :** La fonctionnalité "Envoyer un article" exclut désormais les adresses email ayant activé le refus de mailing.

### 2.1.2 (19/08/2025)

*   **Fonctionnalité :** Ajout d'une option dans les réglages SMTP pour rendre configurable la taille des lots d'envoi d'emails.
*   **Amélioration :** Si la taille des lots est mise à 0, tous les emails sont envoyés en une seule fois. Le comportement par défaut reste à 20 si l'option n'est pas modifiée.

### 2.1.1 (18/08/2025)

*   **Amélioration :** Le plugin requiert désormais WordPress 6.8 et PHP 8.2 au minimum.

### 2.1.0 (17/08/2025)

*   **Fonctionnalité :** Ajout d'une fonctionnalité de sauvegarde et de restauration pour les contenus d'apprentissage (Leçons, Exercices, Cours) dans un format compressé.
*   **Amélioration :** Remplacement des menus "Leçons", "Exercices" et "Cours" par un menu unique "Apprentissage" pour une meilleure organisation. Les CPTs et la taxonomie "Catégories" sont maintenant accessibles depuis ce menu.

### 2.0.4 (16/08/2025)

*   **Fonctionnalité :** Les réponses aux QCM sont maintenant surlignées en vert (correct) ou en rouge (incorrect) après la soumission.
*   **Fonctionnalité :** Les champs de réponse des QCM sont désactivés après la soumission pour empêcher la modification.
*   **Amélioration :** La taille des pièces d'échecs affichées via les shortcodes a été augmentée pour une meilleure lisibilité.
*   **Correctif :** Correction d'un bug majeur qui empêchait les shortcodes de s'afficher correctement dans les réponses des QCM.

### 2.0.3 (16/08/2025)

*   **Fonctionnalité :** Ajout de shortcodes pour afficher les pièces d'échecs. Il suffit d'écrire `[RB]` pour le Roi Blanc, `[RN]` for le Roi Noir, etc. Les shortcodes sont disponibles pour toutes les pièces et les deux couleurs.

### 2.0.2 (15/08/2025)

*   **Fonctionnalité :** Ajout d'un champ "Difficulté" obligatoire (de 1 à 6) pour les Leçons, Exercices et Cours pour une classification cohérente.
*   **Fonctionnalité :** Ajout d'une colonne "Difficulté" dans les listes d'administration des Leçons, Exercices et Cours, avec une icône étoile colorée pour visualiser rapidement le niveau.
*   **Amélioration :** Le constructeur de cours filtre désormais les contenus disponibles (leçons, exercices) pour ne proposer que ceux correspondant à la difficulté du cours.
*   **Amélioration :** Lors d'un changement de difficulté sur un cours, la liste des contenus sélectionnés est maintenant vidée (après confirmation) pour éviter les incohérences.
*   **Correctif :** Le type de contenu s'affiche désormais correctement ("Leçon" au lieu de "lecon") dans la liste des éléments du constructeur de cours.
*   **Correctif :** Lors du retrait d'un élément d'un cours, celui-ci retourne maintenant dans la bonne catégorie (Leçons ou Exercices) dans la liste des contenus disponibles.

### 2.0.1 (15/08/2025)

*   **Changement :** Le nom du plugin devient "Dossier et Apprentissage des Membres Échiquéens" pour mieux refléter l'ajout des fonctionnalités pédagogiques.

### 2.0.0 (15/08/2025)

*   **Fonctionnalité majeure : Module de contenu échiquéen**
    *   **CPT Leçons :** Ajout d'un type de contenu "Leçon" réservé aux membres, avec suivi de la complétion.
    *   **CPT Exercices :** Ajout d'un type de contenu "Exercice" avec gestion de la difficulté, types de questions, et solution.
    *   **CPT Cours :** Ajout d'un type de contenu "Cours" pour créer des parcours pédagogiques.
    *   **Interface d'exercices publique :** Ajout d'un shortcode `[dame_exercices]` pour un entraînement interactif avec filtres et score.
    *   **Constructeur de cours :** Remplacement de l'interface de création de cours par un système de double liste robuste et fiable pour l'ajout, la suppression et le réordonnancement des leçons/exercices.
    *   **Taxonomie et Permissions :** Création d'une taxonomie partagée et de permissions granulaires pour la gestion du nouveau contenu.
*   **Améliorations et Correctifs de la v2.0.0**
    *   **Correctif :** Le champ "Solution" de l'éditeur d'exercices est maintenant toujours accessible, corrigeant un bug de rendu CSS spécifique à Safari.
    *   **Correctif :** Les permaliens pour les nouveaux types de contenu fonctionnent désormais correctement après l'activation/mise à jour du plugin grâce à un rafraîchissement programmé des règles de réécriture.
    *   **Correctif :** Le formulaire de réponse (QCM) s'affiche maintenant correctement sur les pages d'exercices individuelles, et pas seulement dans le shortcode.
    *   **Amélioration :** Le retour de réponse pour les exercices incorrects affiche maintenant la ou les bonnes réponses pour une meilleure valeur pédagogique.

### 1.16.3 (13/08/2025)

*   **Fonctionnalité :** Ajout d'un filtre par catégorie sur la page "Envoyer un article". Ce filtre permet de sélectionner une ou plusieurs catégories pour affiner dynamiquement la liste des articles. Le filtre est mémorisé entre les sessions.
*   **Correctif :** Correction d'un problème d'affichage des caractères spéciaux (comme les apostrophes) dans la liste déroulante des articles filtrés dynamiquement.

### 1.16.2 (13/08/2025)

*   **Fonctionnalité :** Ajout d'une section de configuration SMTP (`Réglages > Options DAME`) pour permettre l'envoi d'emails via un serveur externe. Cela améliore considérablement la fiabilité de l'envoi d'emails.
*   **Amélioration :** La fonctionnalité "Envoyer un article" envoie désormais les emails par lots de 20 destinataires. Cette modification évite les erreurs et les échecs d'envoi lors de communications à des groupes importants, en contournant les limitations des serveurs d'hébergement.
*   **Correctif :** Correction du problème où l'envoi d'un article à un grand nombre de destinataires échouait sans message d'erreur clair.

### 1.16.1 (12/08/2025)

*   **Correctif :** Correction d'un bug critique dans la fonctionnalité "Envoyer un article" où les emails n'étaient pas envoyés aux groupes filtrés (par exemple, les membres "Actif"). La logique de filtrage a été revue pour s'assurer que la combinaison des filtres (statut et groupe) fonctionne correctement avec une relation `ET` au lieu de `OU`.

### 1.16.0 (11/08/2025)

*   **Fonctionnalité :** Ajout de filtres sur la page de liste des adhérents pour permettre de trier par Groupe (École d'échecs, Pôle Excellence, Bénévole, Elu local) et par État de l'adhésion.

### 1.15.5 (11/08/2025)

*   **Amélioration UI :** Ajout d'une scrollbox sur le popup de complétion des adresses pour visualiser toutes les suggestions.
*   **Fonctionnalité :** L'envoi d'email se fait maintenant par un système de filtres combinables (OU) pour les statuts d'adhésion et les groupes.
*   **Fonctionnalité :** Ajout des groupes "Bénévole" et "Elu local" dans les options d'envoi d'email.
*   **Amélioration :** La logique d'envoi d'email collecte maintenant l'email de l'adhérent ainsi que ceux de ses représentants légaux, en s'assurant de l'unicité des adresses.
*   **Amélioration UI :** Dans l'écran d'envoi d'email, aucun groupe n'est coché par défaut, et les filtres par statut sont toujours visibles et présentés sur une seule ligne.
*   **Sécurité :** Ajout d'une validation du format des adresses email lors de la saisie d'un adhérent.

### 1.15.0 (11/08/2025)

*   **Fonctionnalité :** La date de naissance est maintenant un champ obligatoire.
*   **Fonctionnalité :** Ajout des champs "Code postal de naissance" et "Commune de naissance".
*   **Fonctionnalité :** Implémentation de l'auto-complétion pour les champs de naissance en utilisant l'API géo.api.gouv.fr.
*   **Fonctionnalité :** Ajout d'une case à cocher "Elu local" pour suivre ce statut, incluse dans les imports/exports.
*   **Amélioration :** Le champ "Numéro de licence" a été déplacé dans la section "Classification et Adhésion" pour une meilleure organisation.
*   **Amélioration :** Lors de l'import CSV, si la date de naissance est manquante, la date "19/09/1950" est utilisée par défaut.
*   **Amélioration :** Les nouveaux champs de naissance et le statut "Elu local" sont ajoutés aux exports CSV et JSON.
*   **Amélioration UI :** Les champs de code postal et de ville sont maintenant affichés sur la même ligne avec des tailles ajustées pour un meilleur alignement visuel sur l'ensemble des formulaires.
*   **Amélioration UI :** La liste de suggestions de l'auto-complétion dispose désormais d'une barre de défilement pour les longues listes.
*   **Correctif :** La date d'adhésion n'est plus obligatoire pour les membres actifs.
*   **Correctif :** Les valeurs saisies dans les formulaires ne sont plus effacées en cas d'erreur de validation lors de la sauvegarde.

### 1.14.5 (10/08/2025)

*   **Correctif :** Le menu "Assignations des comptes" est maintenant correctement positionné sous "Ajouter un nouvel adhérent".
*   **Correctif :** L'accès au menu "Assignations des comptes" est maintenant strictement réservé aux administrateurs.
*   **Amélioration :** Le rôle "Membre" est maintenant sélectionné par défaut dans la page "Assignations des comptes".
*   **Correctif Technique :** Correction d'une erreur fatale sur les versions récentes de PHP liée à la réorganisation du menu d'administration.

### 1.14.0 (10/08/2025)

*   **Fonctionnalité :** Ajout d'un écran "Assignation des comptes" pour lier facilement un adhérent à un compte utilisateur WordPress et lui assigner un rôle.
*   **Amélioration :** Le menu "Assignation des comptes" est positionné juste après "Ajouter un nouvel adhérent" pour une meilleure ergonomie.
*   **Correctif :** Correction d'un bug dans la requête de récupération des utilisateurs déjà assignés qui pouvait générer une erreur PHP.

### 1.13.0 (10/08/2025)

*   **Fonctionnalité :** Ajout d'un système d'import d'adhérents par fichier CSV (séparateur `;`, encodage UTF-8).
*   **Fonctionnalité :** Ajout des champs "Autre téléphone" et "Taille vêtements". Ces champs sont intégrés aux imports/exports.
*   **Amélioration :** La date de naissance n'est plus un champ obligatoire.
*   **Amélioration :** Ajout de l'option "Non précisé" pour le Sexe (valeur par défaut) et le Type de licence.
*   **Amélioration :** Le type de licence est positionné à "Non précisé" par défaut lors d'un import CSV si non fourni.
*   **Amélioration :** Mise à jour automatique du département et de la région à la saisie du code postal.
*   **Amélioration :** Nettoyage automatique des numéros de téléphone à l'import (gestion des préfixes 33/+33, suppression des espaces et points).
*   **UI :** Création d'une page de sous-menu "Import / Export" dédiée sous "Adhérents" pour regrouper les fonctionnalités.
*   **Correctif :** L'import CSV est maintenant plus robuste et ignore les BOM (Byte Order Mark) potentiellement présents dans l'en-tête du fichier.

### 1.12.0 (10/08/2025)

*   **Correctif :** Le champ titre ne contient plus de texte par défaut.
*   **Correctif :** Les champs "Type de licence" et "Date d'adhésion" sont toujours visibles.
*   **Correctif :** Le renommage de "Junior" en "École d’échecs" est maintenant appliqué sur tous les écrans.
*   **Amélioration :** Le champ "Département" est positionné au-dessus de "Région" et la sélection de la région est maintenant automatique.

### 1.11.0 (10/08/2025)

*   **Amélioration :** La date d'adhésion est maintenant située sous l'état de l'adhésion pour une meilleure logique.
*   **Amélioration :** Ajout du champ "Type de licence" (A ou B) obligatoire lorsque l'adhérent est actif.
*   **Amélioration :** La classification "Junior" est renommée en "École d’échecs".
*   **Amélioration :** Ajout de la classification "Bénévole".
*   **Amélioration :** Nouvelle section "Informations diverses" (allergies, régime, transport).
*   **Amélioration :** Remplissage automatique des coordonnées du représentant légal 1 pour les nouveaux adhérents mineurs.
*   **Amélioration :** Le focus est mis sur le champ "Prénom" lors de la création d'un nouvel adhérent.
*   **Amélioration :** Le champ titre affiche "Ne pas remplir" par défaut.
*   **Amélioration :** Les options pour le sexe sont affichées sur la même ligne.

### 1.9.1 (09/08/2025)

*   **Correction de bug :** Un compte WordPress déjà assigné à un adhérent n'apparaît plus dans la liste déroulante des autres adhérents, empêchant les attributions multiples.
