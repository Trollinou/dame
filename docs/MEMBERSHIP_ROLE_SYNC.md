# Synchronisation Saisonnière des Rôles PWA (`membre` / `subscriber`)

Ce document décrit le mécanisme de gestion automatique du rôle WordPress des utilisateurs (`membre` ↔ `subscriber`) en fonction de la validité de leur adhésion saisonnière dans **DAME**.

---

## 1. Règle métier & Calendrier FFE

Un compte WordPress (`WP_User`) se voit attribuer le rôle `membre` **si et seulement si** :
1. Son adresse e-mail est vérifiée (`_dame_is_verified === '1'`).
2. Son `user_email` correspond (`_dame_email`, `_dame_legal_rep_1_email` ou `_dame_legal_rep_2_email`) à **au moins une** fiche `adherent` rattachée à une saison valide retournée par `Season_Service::get_valid_membership_season_ids()`.

### Fenêtre de chevauchement (1er juillet – 30 septembre)
Conformément aux règles FFE :
- **Du 1er juillet au 30 septembre** (`Season_Service::is_in_membership_overlap()`) : les saisons `N-1/N` et `N/N+1` sont toutes deux considérées comme valides. Les adhérents de la saison précédente conservent leur accès PWA jusqu'au **30 septembre à 23:59:59**.
- **À partir du 1er octobre** : seule la saison active (`N/N+1`) est valide. Tout utilisateur `membre` n'ayant pas renouvelé son adhésion est rétrogradé en `subscriber`.

> **Note REST API** : Les endpoints `GET /dame/v1/birthdays/today` et `GET /dame/v1/birthdays/upcoming` (`Data_Endpoints` → `Birthday`) s'appuient également sur `Season_Service` et appliquent automatiquement cette échéance du 30 septembre.

---

## 2. Garde-fous

- **Rôles protégés** : Les rôles `administrator`, `editor`, `staff` et `entraineur` (`Membership_Role_Sync::PROTECTED_ROLES`) ne sont **jamais** modifiés ou rétrogradés par la synchronisation.
- **Multi-adhérents (parents / responsables légaux)** : Si un même e-mail est présent sur plusieurs fiches `adherent` (ex. plusieurs enfants), le compte reste `membre` tant qu'au moins une fiche dispose d'une adhésion valide.
- **Comptes non vérifiés** : Un utilisateur `subscriber` n'ayant pas validé son lien e-mail (`_dame_is_verified !== '1'`) n'est jamais promu en `membre`.

---

## 3. Points d'exécution

| Déclencheur | Fichier | Comportement |
| :--- | :--- | :--- |
| **Validation d'inscription** | `includes/API/REST/Registration.php` | Passe en `membre` si une adhésion valide existe, sinon reste `subscriber` vérifié. |
| **Changement de saison sur un adhérent** | `includes/Services/Membership_Role_Sync.php` (`set_object_terms`) | Réévalue immédiatement les comptes liés aux e-mails de l'adhérent. |
| **Modification d'e-mail sur un adhérent** | `includes/Services/Membership_Role_Sync.php` (`added_post_meta` / `updated_post_meta`) | Réévalue immédiatement le compte correspondant à la nouvelle adresse. |
| **Cron quotidien (`03:00`)** | `includes/Core/Cron.php` (`dame_membership_role_sync_event`) | Exécute `sync_all_users()` chaque nuit (assure la bascule au 1er octobre). |
| **Action manuelle Admin** | `includes/Admin/Settings/Tabs/Saisons.php` | Bouton *« Synchroniser les rôles Membre / Abonné »* avec bilan promus/rétrogradés. |
