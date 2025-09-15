# Guide d'utilisation des fonctionnalités Échiquéennes

Ce document explique comment utiliser les nouvelles fonctionnalités de Leçons, Exercices et Cours dans le plugin DAME - Dossier et Apprentissage des Membres Échiquéens.

## 1. Comment utiliser le formulaire de préinscription

Pour permettre aux nouveaux membres de se préinscrire en ligne, vous pouvez utiliser le shortcode `[dame_fiche_inscription]`.

1.  Allez dans `Pages > Ajouter` dans votre administration WordPress.
2.  Donnez un titre à votre page (par exemple, "Préinscription" ou "Rejoignez-nous").
3.  Dans l'éditeur de contenu, insérez le shortcode suivant :
    ```
    [dame_fiche_inscription]
    ```
4.  Publiez la page.

Le shortcode affichera un formulaire complet de préinscription. Ce formulaire est dynamique :
-   Il demande d'abord les informations de base (nom, prénom, date de naissance).
-   Une fois la date de naissance saisie, il calcule l'âge et affiche les champs conditionnels :
    -   Pour un **majeur**, il demande la profession.
    -   Pour un **mineur**, il affiche les sections pour un ou deux représentants légaux.

Après soumission, les données sont envoyées aux administrateateurs pour validation dans le menu `Adhérents > Préinscriptions`.

## 2. Comment utiliser les Exercices interactifs

Pour permettre à vos utilisateurs de faire des exercices, vous devez créer une page dédiée à cela.

1.  Allez dans `Pages > Ajouter` dans votre administration WordPress.
2.  Donnez un titre à votre page (par exemple, "Faire des Exercices").
3.  Dans l'éditeur de contenu, insérez le shortcode suivant :
    ```
    [dame_exercices]
    ```
4.  Publiez la page.

Lorsque les utilisateurs visiteront cette page, ils verront une interface leur permettant de :
-   Choisir une **catégorie** d'exercices.
-   Choisir un **niveau de difficulté**.
-   Lancer une série d'exercices aléatoires basés sur ces critères.
-   Voir leur score (bonnes réponses / exercices tentés).

Ce shortcode est le principal pour les fonctionnalités interactives. D'autres shortcodes sont également disponibles pour l'affichage des pièces d'échecs.

## 3. Shortcodes pour les pièces d'échecs

En plus du shortcode `[dame_exercices]`, le plugin fournit des shortcodes pour afficher facilement les symboles des pièces d'échecs dans n'importe quel contenu (articles, pages, leçons, etc.).

Voici la liste des shortcodes disponibles :

| Pièce | Blanc | Noir |
| :--- | :---: | :--: |
| Roi | `[RB]` | `[RN]` |
| Dame | `[DB]` | `[DN]` |
| Tour | `[TB]` | `[TN]` |
| Fou | `[FB]` | `[FN]` |
| Cavalier | `[CB]` | `[CN]` |
| Pion | `[PB]` | `[PN]` |

Lorsque vous écrivez l'un de ces shortcodes dans votre éditeur, il sera automatiquement remplacé par le symbole correspondant (par exemple, `[RB]` deviendra ♔).

## 4. Comment utiliser l'Agenda

Le plugin inclut un système d'agenda complet pour gérer vos événements.

### a. Afficher le calendrier complet

Pour afficher le calendrier mensuel interactif, utilisez le shortcode `[dame_agenda]`.

1.  Créez une nouvelle page (ex: "Agenda").
2.  Insérez le shortcode `[dame_agenda]` dans le contenu.
3.  Publiez la page.

Le calendrier affichera les événements du mois en cours et permettra aux utilisateurs de :
-   Naviguer entre les mois.
-   Cliquer sur le nom du mois pour ouvrir un sélecteur de mois/année.
-   Filtrer les événements par catégorie.
-   Rechercher des événements par mot-clé.
-   Voir les détails d'un événement au survol.
-   Cliquer sur un événement pour voir sa page de détails.

### b. Afficher une liste des prochains événements

Pour afficher une liste des événements à venir, utilisez le shortcode `[dame_liste_agenda]`.

Ce shortcode est très utile pour une page d'accueil ou une barre latérale.

**Utilisation de base :**
Affiche les 4 prochains événements.
```
[dame_liste_agenda]
```

**Utilisation avec un nombre personnalisé :**
Pour afficher un nombre différent d'événements (par exemple, 10), utilisez l'attribut `nombre`.
```
[dame_liste_agenda nombre="10"]
```

## 5. Comment ajouter une Leçon, un Exercice ou un Cours à un Menu

Vous pouvez ajouter des liens directs vers n'importe quelle Leçon, Exercice ou Cours publié directement dans vos menus de navigation.

1.  Allez dans `Apparence > Menus` dans votre administration WordPress.
2.  Sur la gauche, dans la section "Ajouter des éléments de menu", vous devriez voir des sections pour "Leçons", "Exercices", et "Cours". Si vous ne les voyez pas, cliquez sur "Options de l'écran" en haut à droite et cochez les cases correspondantes.
3.  Dépliez la section que vous souhaitez (par exemple, "Leçons"). Vous verrez une liste de toutes les leçons que vous avez publiées.
4.  Cochez la case à côté de la ou les leçons que vous voulez ajouter au menu.
5.  Cliquez sur le bouton "Ajouter au menu".
6.  Les éléments apparaîtront dans la structure de votre menu sur la droite. Vous pouvez les glisser-déposer pour les réorganiser.
7.  N'oubliez pas de cliquer sur "Enregistrer le menu".

Vous pouvez également ajouter des liens vers les **archives** de ces contenus (la liste de toutes les leçons, par exemple) ou vers les **catégories** que vous avez créées.

## 5. Gestion des Catégories

Les Leçons, Exercices et Cours partagent les mêmes catégories. Vous pouvez les gérer depuis le menu de chaque type de contenu :
-   `Leçons > Catégories d'échecs`
-   `Exercices > Catégories d'échecs`
-   `Cours > Catégories d'échecs`

Depuis cet écran, vous pouvez ajouter, modifier, supprimer des catégories et les organiser de manière hiérarchique (en définissant des catégories parentes).

## 6. Rôles et Permissions

Pour rappel, voici comment les permissions sont gérées :
-   **Leçons :**
    -   Seuls les utilisateurs avec le rôle "Entraineur" ou "Administrateur" peuvent en créer/modifier.
    -   Seuls les utilisateurs avec le rôle "Membre" ou supérieur peuvent les voir.
-   **Exercices :**
    -   Seuls les "Entraineur" ou "Administrateur" peuvent en créer/modifier.
    -   Tout le monde (y compris les visiteurs non connectés) peut les faire via la page avec le shortcode.
-   **Cours :**
    -   Seuls les "Entraineur" ou "Administrateur" peuvent en créer/modifier.
    -   Tout le monde peut les voir (la visibilité des leçons à l'intérieur du cours respectera toujours la restriction aux membres).
