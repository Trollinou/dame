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

## 2. Comment utiliser l'Agenda

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
