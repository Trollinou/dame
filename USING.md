# Guide d'utilisation des fonctionnalités Échiquéens

Ce document explique comment utiliser les fonctionnalités avancées du plugin DAME.

## 1. Comment utiliser le formulaire de préinscription

Pour permettre aux nouveaux membres de se préinscrire en ligne, vous pouvez utiliser le shortcode `[dame_fiche_inscription]`.

1.  Allez dans `Pages > Ajouter` dans votre administration WordPress.
2.  Dans l'éditeur de contenu, insérez le shortcode : `[dame_fiche_inscription]`
3.  Publiez la page.

Le formulaire est dynamique : il s'adapte selon l'âge calculé à partir de la date de naissance (champs spécifiques pour majeurs vs mineurs). Les soumissions arrivent dans `DAME > Toutes les préinscriptions`.

## 2. Comment utiliser l'Agenda

### a. Afficher le calendrier complet
Utilisez le shortcode `[dame_agenda]` pour afficher un calendrier mensuel interactif.

### b. Afficher une liste des prochains événements
Utilisez le shortcode `[dame_liste_agenda nombre="5"]` (le paramètre `nombre` est optionnel, défaut à 4).

## 3. Appels à Bénévoles (Bénévolat)

Cette fonctionnalité permet de solliciter l'aide des membres pour vos événements (tournois, buvettes, installation).

### a. Créer un appel
1.  Allez dans `DAME > Appels à bénévoles`.
2.  Cliquez sur `Nouvel appel à bénévoles`.
3.  Donnez un titre et une description (ex: "Bénévoles Tournoi de Printemps").
4.  Dans la metabox **Configuration**, ajoutez les dates.
5.  Pour chaque date, ajoutez un ou plusieurs créneaux horaires (ex: 08:00 - 12:00, 12:00 - 14:00).
6.  Publiez l'appel.

### b. Diffuser l'appel
Vous pouvez diffuser l'appel de deux manières :
-   **Lien direct :** Envoyez l'URL de l'appel.
-   **Shortcode :** Copiez le shortcode affiché dans la liste des appels (ex: `[dame_benevolat slug="tournoi-printemps"]`) et collez-le dans une page ou un article.

### c. Suivi des réponses
-   Les membres peuvent s'inscrire sur les créneaux en cochant les cases.
-   Dans l'administration WordPress, ouvrez l'appel pour voir le **Tableau récapitulatif** montrant qui est inscrit sur quel créneau.
-   Les inscrits reçoivent une confirmation visuelle ("Voté") sur l'application PWA.

### d. Sécurité et Verrouillage
-   Dès qu'une journée est passée, les inscriptions pour cette journée sont **automatiquement verrouillées** pour préserver l'historique des présences.
-   Un appel dont toutes les dates sont passées s'affiche comme "Terminé".

## 4. Gestion des données FFE (Fédération Française des Échecs)

Le plugin DAME permet de synchroniser automatiquement les classements ELO et les numéros de licence de vos membres avec le site fédéral.

### a. Configuration du club
Avant toute chose, vous devez renseigner l'identifiant de votre club :
1.  Allez dans `DAME > Réglages > Association`.
2.  Renseignez le champ **Id de référence du club (FFE)** (ex: 571).
3.  Enregistrez les modifications.

### b. Synchronisation automatique (Daily Sync)
Une fois le club configuré, le plugin lance automatiquement chaque jour à **12:00 (Midi)** une tâche de synchronisation qui :
-   Récupère les derniers ELOs (Standard, Rapide, Blitz).
-   Met à jour les numéros de licence officiels.
-   Récupère les IDs FIDE manquants.
-   Met à jour l'ID FFE technique pour chaque membre.

### c. Import manuel (Fichier CSV)
Si vous souhaitez forcer une mise à jour massive à partir d'un export CSV téléchargé sur le site de la FFE :
1.  Allez dans `DAME > Import FFE`.
2.  Sélectionnez votre fichier CSV (séparateur `;`).
3.  Lancez l'importation.
Le système utilise un algorithme de correspondance intelligent (Licence puis Nom) pour mettre à jour vos fiches adhérents sans doublons.

## 5. Application Mobile (PWA)

L'application est accessible à l'adresse : `https://votre-site.com/pwa`.

-   **Public :** Consultation des actualités, de l'agenda et des appels à bénévoles.
-   **Membres :** Inscription, connexion, choix du profil (si famille) et participation aux appels à bénévoles.
-   **Espace de Jeu & Suivi :** Jouez contre l'IA (Stockfish) directement dans l'application avec système d'aide, analyse de partie et blocage de l'annulation en fin de partie. Vos parties terminées sont sauvegardées automatiquement sur votre profil adhérent (avec synchronisation hors-ligne intégrée si la connexion internet est coupée).
-   **Mode Hors-Ligne :** L'application est installable (PWA) et fonctionne même sans connexion internet pour consulter l'agenda, jouer aux échecs ou mettre en attente la sauvegarde de vos parties.
-   **Staff :** Mode administration permettant de consulter les fiches membres et les rapports de messages en mobilité.

## 6. Blocs natifs WordPress 7.0 (Éditeur Gutenberg / FSE)

Si votre site utilise **WordPress 7.0** ou supérieur, vous pouvez utiliser les nouveaux blocs natifs directement dans l'éditeur d'articles, de pages ou dans l'Éditeur de Site (FSE), à la place des shortcodes historiques.

Ces blocs s'insèrent en cliquant sur le bouton **`+`** (Ajouter un bloc) et en recherchant la catégorie **Widgets** ou en tapant leur nom :

*   **DAME Agenda** : Affiche le calendrier mensuel interactif (remplace le shortcode `[dame_agenda]`).
*   **DAME Bénévolat** : Affiche l'appel à bénévoles sélectionné. Une option dans la barre latérale des réglages du bloc (Inspector) vous permet de renseigner le **slug** de l'appel désiré (remplace le shortcode `[dame_benevolat slug="..."]`).
*   **DAME Inscription** : Affiche le formulaire de préinscription en ligne (remplace le shortcode `[dame_fiche_inscription]`).
*   **DAME Contact** : Affiche le formulaire de contact public (remplace le shortcode `[dame_contact]`).

