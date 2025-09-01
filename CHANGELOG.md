## Description

DAME est un plugin WordPress conçu pour gérer une base de données d'adhérents pour un club, une association ou toute autre organisation. Il fournit une interface d'administration simple et intégrée pour gérer les informations des membres, leurs classifications et leurs liens avec les comptes utilisateurs WordPress.

## Changelog

### 2.4.4 (01/09/2025)

*   **Amélioration :** Le remplissage automatique des données du représentant légal pour les adhérents mineurs est remplacé par des boutons manuels. Cela donne à l'utilisateur plus de contrôle sur le remplissage du formulaire.
*   **Amélioration UI :** Les boutons de copie sont placés sur la même ligne que le titre de la section pour un design plus compact et une taille de police fixe de 13px.
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
