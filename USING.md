# Guide d'utilisation des fonctionnalités Échiquéennes

Ce document explique comment utiliser les nouvelles fonctionnalités de Leçons, Exercices et Cours dans le plugin DAME.

## 1. Comment utiliser les Exercices interactifs

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

C'est le seul shortcode qui a été ajouté.

## 2. Comment ajouter une Leçon, un Exercice ou un Cours à un Menu

Vous pouvez ajouter des liens directs vers n'importe quelle Leçon, Exercice ou Cours publié directement dans vos menus de navigation.

1.  Allez dans `Apparence > Menus` dans votre administration WordPress.
2.  Sur la gauche, dans la section "Ajouter des éléments de menu", vous devriez voir des sections pour "Leçons", "Exercices", et "Cours". Si vous ne les voyez pas, cliquez sur "Options de l'écran" en haut à droite et cochez les cases correspondantes.
3.  Dépliez la section que vous souhaitez (par exemple, "Leçons"). Vous verrez une liste de toutes les leçons que vous avez publiées.
4.  Cochez la case à côté de la ou les leçons que vous voulez ajouter au menu.
5.  Cliquez sur le bouton "Ajouter au menu".
6.  Les éléments apparaîtront dans la structure de votre menu sur la droite. Vous pouvez les glisser-déposer pour les réorganiser.
7.  N'oubliez pas de cliquer sur "Enregistrer le menu".

Vous pouvez également ajouter des liens vers les **archives** de ces contenus (la liste de toutes les leçons, par exemple) ou vers les **catégories** que vous avez créées.

## 3. Gestion des Catégories

Les Leçons, Exercices et Cours partagent les mêmes catégories. Vous pouvez les gérer depuis le menu de chaque type de contenu :
-   `Leçons > Catégories d'échecs`
-   `Exercices > Catégories d'échecs`
-   `Cours > Catégories d'échecs`

Depuis cet écran, vous pouvez ajouter, modifier, supprimer des catégories et les organiser de manière hiérarchique (en définissant des catégories parentes).

## 4. Rôles et Permissions

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
