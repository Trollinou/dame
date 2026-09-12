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

### c. Assignation des participants à un événement (Administration)
Dans l'administration WordPress, lors de la création ou de l'édition d'un événement dans l'Agenda :
- **Filtre de recherche instantané :** Un champ de texte permet de filtrer rapidement les adhérents par prénom ou nom. La recherche est insensible à la casse et aux accents (ex: taper "maelle" trouvera immédiatement "Maëlle").
- **Organisation ergonomique :** Les adhérents déjà sélectionnés et inscrits sont automatiquement positionnés en tête de liste pour une lisibilité optimale.

### d. Planification d'événements récurrents (Séries)
Lors de la création d'un nouvel événement (`DAME > Agenda > Ajouter un événement`) :
1. Renseignez les informations de base (Titre, Catégories, Date et Heure de la première séance, Lieu, Participants).
2. Dans la métabox **Récurrence & Répétition**, cochez la case **« Activer la répétition »**.
3. Choisissez le type de fréquence :
   - **Hebdomadaire** : Choisissez l'intervalle (ex: toutes les 1 ou 2 semaines) et cochez les jours souhaités (ex: Mercredi).
   - **Mensuelle** : Choisissez soit une position ordinale (ex: *1er Vendredi du mois* pour les tournois de blitz), soit un jour fixe du mois (ex: *le 15 de chaque mois*).
4. Choisissez la condition de fin :
   - **Jusqu'au [Date]** : Vous pouvez fixer une date de fin (ex: fin des cours le 25 juin). Le système plafonnera automatiquement la date au **31 août** de la saison en cours (permettant la planification en août $X$ pour la saison $X/X+1$).
   - **Après [N] séances**.
5. **Enregistrement en brouillon vs Publication :**
   - Si vous enregistrez l'événement en **Brouillon**, seul l'événement modèle est sauvegardé et vos réglages de récurrence restent mémorisés pour modification ultérieure.
   - Dès que vous cliquez sur **Publier**, **chaque séance est créée comme un événement indépendant** dans le calendrier. Vous pouvez ainsi librement supprimer une séance tombant sur les vacances scolaires ou déplacer une séance isolée.
6. **Suppression de série :**
   - En éditant un événement appartenant à une série, vous disposez d'un bouton **« Supprimer cet événement et les suivants »** (si déclenché sur le 1er événement, supprime toute la série ; si déclenché en cours d'année, supprime les séances restantes en conservant l'historique des séances passées).


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
-   **Espace de Jeu & Suivi :** Jouez contre l'IA directement dans l'application avec système d'aide, analyse de partie et blocage de l'annulation en fin de partie. Vos parties terminées sont sauvegardées automatiquement sur votre profil adhérent (avec synchronisation hors-ligne intégrée si la connexion internet est coupée).
-   **Mode Hors-Ligne :** L'application est installable (PWA) et fonctionne même sans connexion internet pour consulter l'agenda, jouer aux échecs ou mettre en attente la sauvegarde de vos parties.
-   **Staff :** Mode administration permettant de consulter les fiches membres et les rapports de messages en mobilité.

## 6. Espace Apprentissage & Tactique (Module ROI)

Lorsque le module complémentaire d'apprentissage (ROI) est actif, l'application PWA propose un catalogue d'exercices d'entraînement et de cours structurés :
-   **Méthode EEF (École d'Échecs à la Française) :** Cursus fédéral officiel accessible à tous les apprenants (Pions, Cavaliers, Fous, etc.) avec progression linéaire.
-   **Cours assignés :** Cours spécifiques ciblés et prescrits par les entraîneurs du club pour un groupe ou des adhérents précis (débloqués immédiatement pour les élèves ciblés).
-   **100 Commandements (Type 1) :** Questions à choix multiples portant sur les principes théoriques et stratégiques fondamentaux.
-   **Pop'Echecs (Type 2) :** Exercices de repérage et de placement où vous devez positionner une pièce sur la case demandée.
-   **ABCDaire Tactique (Type 3) :** Résolution de tactiques en jouant la bonne séquence de coups contre l'échiquier.
-   **Partie Héros (Type 4) :** Lecture interactive de parties de maîtres sous forme de scénario composé d'étapes :
    -   *Étapes PGN* : Lecture et navigation pas à pas (Début, Précédent, Suivant) dans le déroulement d'une partie.
    -   *Étapes QCM* : Questions à choix multiples basées sur des positions clés pour tester vos choix tactiques ou stratégiques.

## 7. Inscription à la Newsletter (Visiteurs)

DAME intègre un système d'inscription à la newsletter sécurisé avec validation par email (**Double Opt-In** conforme RGPD), protection anti-spam (Honeypot invisible) et détection intelligente des doublons.

### a. Utilisation du Shortcode

Pour afficher le formulaire d'inscription sur vos pages, articles ou widgets :

*   **Bouton avec fenêtre modale (Comportement par défaut) :**
    ```text
    [dame_newsletter]
    ```
*   **Personnaliser le texte du bouton :**
    ```text
    [dame_newsletter button_text="Recevoir nos actualités"]
    ```
*   **Adopter le style des boutons de votre thème :**
    Passez les classes CSS de votre thème avec l'attribut `class` ou `button_class`, et désactivez l'icône email avec `show_icon="false"` :
    ```text
    [dame_newsletter class="entry-button wp-element-button ct-button" show_icon="false"]
    ```
*   **Formulaire direct en ligne (sans bouton ni modale) :**
    ```text
    [dame_newsletter layout="inline"]
    ```

### b. Paramètres & Attributs du Shortcode

| Attribut | Description | Valeur par défaut |
| :--- | :--- | :--- |
| `button_text` | Texte affiché sur le bouton déclencheur | `"S'inscrire à la newsletter"` |
| `class` / `button_class` | Classes CSS personnalisées pour le bouton | `""` |
| `show_icon` | Affiche ou masque l'icône enveloppe Dashicons (`true` / `false`) | `"true"` |
| `layout` | Mode d'affichage (`"button"` pour modale, `"inline"` pour direct) | `"button"` |
| `title` | Titre du formulaire d'inscription | `"Inscription à la newsletter"` |
| `subtitle` | Sous-titre explicatif sous le titre | `"Recevez régulièrement nos actualités et informations."` |

### c. Configuration dans les Réglages

1.  Rendez-vous dans `DAME > Réglages > Onglet Emails`.
2.  Dans la section **Inscription à la Newsletter** :
    *   **Groupe de contact assigné :** Sélectionnez le groupe de contact (taxonomie `dame_contact_type`) automatiquement attribué aux inscrits (ex: *Newsletter*).
    *   **Validation par email (Double Opt-In) :** Case cochée par défaut. Envoie un lien de confirmation temporaire (valable 48h).
    *   **Sujet & Corps de l'email :** Personnalisez le modèle d'email avec les balises dynamiques `{prenom}`, `{nom}`, `{lien_confirmation}`, `{nom_association}`.
    *   **Message de succès :** Message affiché sur le site après confirmation du lien.

### d. Détection Intelligente des Doublons

*   **Adhérents & Responsables légaux :** Si l'email saisi appartient déjà à un membre ou un représentant légal, un message prévient le visiteur qu'il reçoit déjà automatiquement les communications du club.
*   **Contacts existants :** Si l'email correspond à une fiche contact déjà existante, celle-ci n'est pas dupliquée : le groupe Newsletter lui est simplement ajouté lors de la confirmation.

## 8. Formulaire de Préinscription & Signature Électronique

Le shortcode `[dame_fiche_inscription]` permet aux futurs adhérents de saisir leur préinscription directement depuis le site WordPress :
```text
[dame_fiche_inscription]
```

### a. Fonctionnement et signature tactile/souris
1. **Questionnaire de santé :** Le formulaire propose le lien vers le questionnaire officiel FFE adapté selon l'âge (Majeur ou Mineur).
2. **Réponse « NON » partout :** 
   - Déploie immédiatement la zone de **Signature électronique manuscrite** (canvas HTML5 tactile et souris).
   - Affiche les engagements obligatoires : attestation sur l'honneur (majeur) et consentement du représentant légal (mineur).
   - L'apposition de la signature est obligatoire pour pouvoir soumettre le formulaire.
   - Les documents PDF (`ffe_attestation_sante.pdf` et `el_autorisation_parentale.pdf`) sont pré-remplis, signés avec l'image PNG et l'empreinte d'audit (date, heure et adresse IP), puis enregistrés dans le répertoire sécurisé du plugin.
3. **Réponse « OUI » à au moins une question :**
   - Aucune signature n'est demandée sur le formulaire.
   - Un message prévient l'adhérent qu'un certificat médical de moins de 6 mois est obligatoire pour finaliser sa licence.
4. **Validation et cycle de vie documentaire :**
   - **En préinscription :** L'administrateur peut visualiser directement les documents signés depuis la boîte « Détails de la Préinscription ».
   - **Lors de la validation :** Les documents signés de la préinscription sont physiquement dupliqués sous un nom dédié à l'adhérent et à la saison en cours (`_dame_doc_health_attestation_path_{season_id}`).
   - **Nettoyage automatique de la préinscription :** La préinscription d'origine est ensuite supprimée avec ses fichiers temporaires initiaux, laissant l'adhérent avec ses propres documents autonomes et pérennes.
   - Si une préinscription sans suite est supprimée manuellement par l'administrateur, ses documents associés sont automatiquement détruits du serveur (conformité RGPD).
