# DAME - Dossier Administratif des Membres Échiquéens

**Version:** 3.3.10
**Auteur:** Etienne Gagnon
**Licence:** GPL v2 or later

## Description
DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications, leurs adhésions par saison, et leurs liens avec les comptes utilisateurs WordPress.

Ce plugin a été développé en suivant les meilleures pratiques de WordPress en matière de sécurité, de performance, de maintenabilité et d'évolutivité.

## Prérequis

*   **WordPress :** 6.8 ou supérieur
*   **PHP :** 8.2 ou supérieur

## Fonctionnalités Principales

### Flux iCalendar

*   **Abonnement aux Événements :** Le plugin propose désormais des flux iCalendar (.ics) pour permettre aux utilisateurs de s'abonner aux événements de l'Agenda directement depuis leur application de calendrier (Google Calendar, Outlook, Apple Calendar, etc.).
*   **Flux Public et Privé :** Deux flux globaux sont disponibles par défaut :
    *   `/feed/agenda/public` : Inclut tous les événements publics.
    *   `/feed/agenda/prive` : Inclut tous les événements privés (accessible uniquement aux utilisateurs connectés ayant les permissions nécessaires).
*   **Flux Personnalisés :** Une nouvelle interface d'administration (`Agenda > Flux iCalendar`) permet de créer un nombre illimité de flux personnalisés.
    *   Chaque flux personnalisé peut être configuré pour inclure uniquement les événements publics appartenant à une ou plusieurs catégories spécifiques.
    *   Les URL de ces flux sont générées automatiquement à partir du titre du flux (ex: `/feed/agenda/competitions-regionales`).
*   **Mise à Jour Automatique :** Les calendriers s'abonnant à ces flux se mettront à jour automatiquement pour refléter les ajouts, modifications ou suppressions d'événements.

### Sondages (type Doodle)

*   **Création de Sondages :** Un nouveau type de contenu "Sondage" permet de créer des sondages de disponibilité. L'interface d'administration permet d'ajouter dynamiquement des dates et des plages horaires.
*   **Affichage par Shortcode :** Le shortcode `[dame_sondage slug="..."]` affiche le formulaire de vote sur n'importe quelle page ou article. Le slug est visible dans une colonne dédiée sur la liste des sondages.
*   **Vote Interactif :** Les utilisateurs (connectés ou non) peuvent soumettre leurs disponibilités. Le formulaire affiche le nombre de participants déjà inscrits pour chaque créneau (ex: `14h00 - 15h00 (5 inscrits)`).
*   **Modification des Votes :** Les utilisateurs connectés peuvent modifier leur vote à tout moment. Les visiteurs non connectés peuvent également modifier leur vote tant qu'ils n'ont pas fermé leur navigateur.
*   **Consultation des Résultats :** L'écran d'édition d'un sondage présente un tableau récapitulatif des résultats (qui est disponible pour quel créneau) et une liste des participants avec la possibilité de supprimer des votes individuellement.
*   **Intégrité des Données :** La configuration d'un sondage est automatiquement verrouillée dès qu'il reçoit sa première réponse pour garantir la cohérence des résultats.

### Messagerie

*   **Gestion des Messages :** Un nouveau type de contenu "Message" permet de créer et de gérer des communications pour les membres. Ces messages sont rédigés avec l'éditeur de blocs de WordPress (Gutenberg) et ne sont pas publics sur le site.
*   **Envoi Ciblé :** Une page "Envoyer un message" permet de sélectionner un message et de l'envoyer à des destinataires filtrés. La logique de filtrage avancée permet de combiner les critères : `("Genre" ET "Saison d'adhésion" ET "Groupe Saisonnier") OU "Groupe Permanent"`.
*   **Actions Rapides :** Depuis la liste des messages, il est possible de dupliquer un message pour le réutiliser, de s'envoyer un email de test pour vérifier le rendu, ou de copier un message en tant qu'article (brouillon).
*   **Suivi des Envois :** La liste des messages affiche la date du dernier envoi, l'auteur de l'envoi, et les destinataires (critères de filtre ou liste manuelle) pour un suivi clair des communications.

### Gestion des Adhésions par Saison

Le système de gestion des adhésions a été entièrement repensé pour offrir plus de flexibilité et un meilleur suivi historique.

*   **Adhésion par Tags de Saison :** L'ancien système de statut (Actif, Ancien, etc.) est remplacé par une taxonomie "Saison d'adhésion". Chaque membre se voit attribuer un "tag" pour chaque saison à laquelle il adhère (ex: "Saison 2024/2025").
*   **Statut Dynamique :** Un membre est considéré comme "Actif" s'il possède le tag de la saison en cours. Sinon, il est "Non adhérent".
*   **Historique des Adhésions :** Toutes les saisons d'adhésion d'un membre sont conservées et visibles sous forme de "pastilles" sur sa fiche et dans la liste des adhérents.
*   **Gestion Simplifiée :** Sur la fiche d'un adhérent, un simple menu déroulant "Adhésion pour la saison actuelle" permet de le marquer comme "Actif" ou "Non adhérent", ce qui ajoute ou retire automatiquement le tag de la saison en cours.
*   **Filtres Avancés :** La liste des adhérents peut être filtrée pour n'afficher que les membres "Actifs", "Inactifs", ou tous les membres ayant adhéré à une saison spécifique (ex: tous les adhérents de la "Saison 2023/2024").
*   **Réinitialisation Annuelle Intelligente :** La fonction de "Réinitialisation Annuelle" (`Réglages > Options DAME`) ne modifie plus les anciens membres. Son rôle est désormais de créer le tag pour la nouvelle saison qui commence et de le définir comme saison "active".
*   **Système de suivi de l'honorabilité :** Les champs de date de naissance et de commune de naissance sont saisissable pour les adherents et/ou représetnant legaux afin de suivre le processus de contrôle d'honorabilité s'il sont amené à accompagner des mineurs.

### Préinscription en Ligne

*   **Formulaire de Préinscription :** Un shortcode `[dame_fiche_inscription]` permet d'afficher un formulaire public où les futurs membres peuvent s'inscrire. Le formulaire s'adapte dynamiquement pour les adhérents majeurs et mineurs.
*   **Formulaire de Contact :** Un shortcode `[dame_contact]` permet d'afficher un formulaire de contact simple (Nom, Email, Sujet, Message) qui envoie un email à l'administrateur.
*   **Génération de PDF :** Génération de l'attestation de réponse négative au questionnaire de santé.
*   **Interface de Validation :** Les administrateurs disposent d'une interface dédiée pour examiner, modifier et valider les préinscriptions.
*   **Rapprochement Automatique :** Le système détecte les doublons potentiels en comparant les nouvelles inscriptions avec la base de données existante (nom, prénom, date de naissance).
*   **Mise à Jour Facilitée :** Si un doublon est trouvé, un tableau de comparaison met en évidence les différences et permet de mettre à jour la fiche de l'adhérent existant en un clic.

### Gestion des Rôles et Permissions

*   **Rôle "Membre du Bureau" :** Ajout d'un rôle `staff` spécialement conçu pour les membres du bureau, avec des droits équivalents à un "Contributeur" mais avec la permission de lire les contenus privés et de voir le menu des Pages.
*   **Permissions Personnalisées pour les Messages :** Le plugin utilise maintenant un jeu de permissions personnalisées pour la gestion des messages (ex: `publish_dame_messages`). Ces permissions sont attribuées aux rôles `Administrator`, `Editor`, et `Staff`, leur permettant de gérer les messages sans avoir de droits sur les autres contenus du site.
*   **Page de Consultation :** Une page de consultation en lecture seule est maintenant disponible pour les fiches adhérents directement depuis l'administration, permettant une visualisation rapide des données sans risque de modification accidentelle.

### Gestion des Données des Membres

*   **Gestion des Données Personnelles :** Fiche détaillée pour chaque membre (coordonnées, date de naissance, etc.).
*   **Représentants Légaux :** Gestion des informations pour les représentants légaux des membres mineurs.
*   **Classification par Groupes :** Catégorisation flexible des membres (École d'échecs, Pôle Excellence, Bénévole, etc.) via une taxonomie "Groupes" dédiée.
*   **Assignation de Compte Utilisateur :** Outil pour lier un dossier d'adhérent à un compte utilisateur WordPress.
*   **Import / Export :** Outils complets pour importer des membres depuis un fichier CSV et exporter toutes les données en CSV ou JSON.

### Gestion d'Événements (Agenda)

*   **Enrichissement des données d'événement :** Il est maintenant possible de spécifier si un événement est une compétition (individuelle ou par équipe) et son niveau (départementale, régionale, nationale).
*   **Suivi des Participants :** Association des adhérents (avec adhésion active) aux événements pour suivre leur participation. L'interface de sélection est optimisée avec un champ de recherche et un tri qui place les participants déjà sélectionnés en tête de liste.
*   **Calcul de Trajet :** Lors de la saisie de l'adresse d'un événement, la distance et le temps de trajet en voiture depuis l'adresse de l'association (configurée dans les réglages) sont automatiquement calculés et affichés. Un bouton manuel "Calculer" permet de rafraîchir ces informations.
*   **Affichage des détails amélioré :** La page publique d'un événement affiche maintenant les informations sur la compétition, les coordonnées GPS, la distance, le temps de trajet et la liste des participants.
*   **Calendrier d'Événements :** Un nouveau type de contenu "Agenda" permet de créer et gérer des événements.
*   **Affichage Calendrier :** Le shortcode `[dame_agenda]` affiche un calendrier mensuel interactif avec navigation, filtres par catégorie et recherche.
*   **Affichage Liste :** Le shortcode `[dame_liste_agenda nombre="X"]` affiche une liste des X prochains événements.
*   **Catégories Colorées :** Chaque catégorie d'événement peut être associée à une couleur pour une identification visuelle rapide sur le calendrier.
*   **Détails Complets :** Les événements peuvent inclure une description, des dates et heures de début/fin, une option "journée entière" et des informations de lieu détaillées.
*   **Ajout à l'agenda personnel :** Un bouton sur la page de détail permet aux visiteurs de télécharger un fichier `.ics` pour ajouter facilement l'événement à leur propre agenda (Google Calendar, Outlook, etc.).
*   **Sauvegarde et Restauration :** Outil pour sauvegarder et restaurer la base de données des événements et de leurs catégories.

### Administration et Configuration

*   **Préférences de Communication :** Gestion du consentement au mailing pour chaque adresse email.
*   **Configuration SMTP :** Permet de configurer un serveur SMTP externe pour fiabiliser l'envoi d'emails.
*   **Envoi d'emails par Lots :** La taille des lots d'envoi est configurable pour s'adapter aux contraintes des hébergeurs.
*   **Sauvegarde Automatique :** Le plugin effectue une sauvegarde journalière automatique des bases de données "Adhérents" et "Apprentissage". Les fichiers de sauvegarde sont envoyés par email à l'adresse de l'expéditeur configurée.
*   **Heure de Sauvegarde Configurable :** L'heure de déclenchement de la sauvegarde journalière peut être personnalisée dans les réglages.
*   **Emails d'Anniversaire Automatiques :** Une tâche journalière peut envoyer automatiquement un email de vœux aux adhérents le jour de leur anniversaire. La fonctionnalité peut être activée ou désactivée dans les options.
    *   **Modèle d'Email Personnalisé :** Le contenu de l'email est basé sur un article WordPress (publié ou privé) dont le slug est à définir dans les options.
    *   **Personnalisation :** Le sujet et le contenu de l'article peuvent inclure les balises `[NOM]`, `[PRENOM]` et `[AGE]` qui seront remplacées par les informations de l'adhérent.
    *   **Rapport d'Envoi :** Un email de résumé est envoyé à l'adresse de l'expéditeur pour lister les adhérents à qui l'email a été envoyé.
*   **Désinstallation Sécurisée :** Les données sont conservées par défaut lors de la désinstallation, mais peuvent être supprimées via une option.

## Configuration

Pour garantir une bonne délivrabilité des emails envoyés via le plugin, il est fortement recommandé de configurer un serveur SMTP. Allez dans `Réglages > Options DAME` et remplissez les champs de la section "Paramètres d'envoi d'email".

### Sauvegarde Automatique

Vous pouvez configurer l'heure de la sauvegarde journalière dans la section "Paramètres de sauvegarde" de la page d'options.

## Dépendances

Pour la fonctionnalité LMS, ce plugin nécessite le plugin **ROI**.
