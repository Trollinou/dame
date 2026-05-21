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

## 4. Application Mobile (PWA)

L'application est accessible à l'adresse : `https://votre-site.com/pwa`.

-   **Public :** Consultation des actualités, de l'agenda et des appels à bénévoles.
-   **Membres :** Inscription, connexion, choix du profil (si famille) et participation aux appels à bénévoles.
-   **Staff :** Mode administration permettant de consulter les fiches membres et les rapports de messages en mobilité.
