# Guide d'Intégration PWA — Billetterie & Pointage HelloAsso (DAME)

Ce document fournit l'ensemble des spécifications techniques, contrats d'API, interfaces TypeScript et recommandations d'implémentation pour intégrer le module de **Billetterie & Émargement HelloAsso** dans l'application mobile / PWA du club.

---

## 1. Architecture & Principes Directeurs

```mermaid
flowchart TD
    subgraph PWA ["PWA Club (Mobile / Tablette / Desktop)"]
        UI_Camp["Écran Campagnes\n(Tournois, Stages, Adhésions)"]
        UI_Pointage["Écran Pointage / Émargement\n(Temps Réel, Filtres Elo, Club)"]
        UI_OnSite["Modale Inscription sur Place\n(Espèces / Chèque)"]
        CacheStore["Store Local (IndexedDB / Cache API)\nMode Hors-ligne / Optimistic UI"]
    end

    subgraph DAME_REST ["Plugin WordPress DAME (REST API v1)"]
        AuthCheck{"Vérification Rôles & Capabilités\nmanage_dame_ticketing | edit_posts"}
        EndpointCamp["GET /dame/v1/ticketing/campaigns"]
        EndpointItems["GET /dame/v1/ticketing/campaigns/{id}/items"]
        EndpointCheckin["POST /dame/v1/ticketing/items/{id}/checkin"]
        EndpointOnsite["POST /dame/v1/ticketing/campaigns/{id}/items"]
        MatcherService["Service Matcher & Enrichissement\n(Adhérents, Pre-inscriptions, Contacts)"]
    end

    subgraph External ["Sources Externes"]
        HA_Webhook["HelloAsso Webhook\n(Inscriptions en ligne)"]
        Sharly["Export Sharly Chess\n(Appariements Arbitre)"]
    end

    UI_Camp -->|fetch| EndpointCamp
    UI_Pointage -->|fetch & bascule| EndpointCheckin
    UI_Pointage -->|listing| EndpointItems
    UI_OnSite -->|création| EndpointOnsite

    EndpointCamp & EndpointItems & EndpointCheckin & EndpointOnsite --> AuthCheck
    EndpointOnsite --> MatcherService
    HA_Webhook -->|ingestion temps réel| DAME_REST
    DAME_REST -.->|génération CSV| Sharly
```

### Principes Clés :
1. **Zéro friction sur le lieu de l'événement** : L'organisateur / arbitre / entraîneur peut pointer les joueurs en 1 clic sur smartphone sans avoir de compte HelloAsso.
2. **Support des joueurs externes** : Les stages et tournois accueillent des adhérents mais aussi des joueurs d'autres clubs de la Ligue ou extérieurs. La PWA distingue clairement les adhérents du club des joueurs externes.
3. **Mise à jour optimiste (Optimistic UI)** : Pour une fluidité maximale lors des pointages à l'entrée de la salle, l'UI bascule l'état visuel immédiatement avant confirmation réseau.
4. **Contrat PWA Strict** : Les DTO et types TypeScript sont totalement isolés de WordPress et du DOM (`src/types/api/ticketing.ts`).

---

## 2. Sécurité, Rôles & Authentification (Simple JWT Login)

### Rôles Autorisés
L'accès aux endpoints `/dame/v1/ticketing/*` est restreint aux utilisateurs disposant de la capabilité `manage_dame_ticketing` ou `edit_posts` :
- `administrator`
- `editor`
- `staff`
- `entraineur`

### Intégration Native avec **Simple JWT Login**
Le site et la PWA utilisant l'extension **Simple JWT Login**, le flux d'authentification s'intègre parfaitement et de façon transparente avec le noyau WordPress :

1. **Obtention du JWT** : La PWA s'authentifie sur la route Simple JWT Login (ex: `POST /wp-json/simple-jwt-login/v1/auth`) avec les identifiants de l'arbitre ou de l'entraîneur et récupère le `jwt`.
2. **Propagation du Token** : Chaque requête envoyée à `/dame/v1/ticketing/*` doit simplement inclure l'en-tête standard :
   ```http
   Authorization: Bearer <votre_jwt_token>
   ```
3. **Résolution Automatique du Current User** : Simple JWT Login intercepte le filtre natif WordPress `determine_current_user`, valide la signature/expiration du token, et initialise l'utilisateur WordPress connecté (`wp_set_current_user`).
4. **Vérification des Droits DAME** : Le contrôleur `Ticketing_REST` vérifie automatiquement `current_user_can('manage_dame_ticketing') || current_user_can('edit_posts')`. Tout arbitre, entraîneur, staff ou administrateur authentifié par son JWT est immédiatement autorisé.

> [!TIP]
> **Format des En-têtes HTTP Requis pour la PWA** :
> ```http
> Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
> Accept: application/json
> Content-Type: application/json
> ```

> [!IMPORTANT]
> En cas de token expiré ou de droits insuffisants :
> - Token absent / invalide : `401 Unauthorized` ou `403 Forbidden` (`{"code": "jwt_auth_invalid_token"}` ou `{"code": "rest_forbidden"}`).
> - Rôle non autorisé (ex: simple abonné sans droit de billetterie) : `403 Forbidden` (`{"code": "rest_forbidden", "message": "Désolé, vous n’avez pas l’autorisation de faire cela."}`).
> La PWA peut alors automatiquement rediriger l'utilisateur vers son écran de connexion.

---

## 3. Contrat de Données TypeScript

Les types sont disponibles dans le codebase DAME sous `src/types/api/ticketing.ts` et exportés via `src/types/index.ts` :

```typescript
export type CampaignCategory = 'tournament' | 'membership' | 'stage' | 'other';

/**
 * Campagne HelloAsso synchronisée dans DAME
 */
export interface TicketingCampaignDTO {
  id: number;
  form_slug: string;
  form_type: string;                  // 'Event', 'Membership', 'Form'
  title: string;
  campaign_category: CampaignCategory; // 'tournament' | 'membership' | 'stage' | 'other'
  linked_agenda_id?: number | null;    // ID de l'événement dame_agenda associé
  linked_season_id?: number | null;
  start_date?: string | null;          // ISO Date
  end_date?: string | null;
  last_sync_at?: string | null;
  total_items: number;                 // Nombre total d'inscrits
  checked_in_count: number;            // Nombre d'inscrits pointés présents
  total_amount_cents: number;          // Montant total collecté (en centimes)
  tiers: Record<string, number>;       // Répartition par formule/tarif : {"Open A": 25, "Open B": 18}
}

/**
 * Billet / Participant à une campagne
 */
export interface TicketingItemDTO {
  id: number;                          // ID unique dame_ha_items
  campaign_id: number;
  ha_item_id?: number | null;          // ID HelloAsso d'origine (null si sur place)
  source: 'helloasso' | 'onsite';      // Origine : HelloAsso en ligne ou sur place
  status: string;                      // 'Processed', 'Registered'
  participant_first_name: string;
  participant_last_name: string;
  participant_email: string;
  participant_birth_date?: string | null;
  payer_first_name: string;
  payer_last_name: string;
  payer_email: string;
  tier_name: string;                   // Nom du tarif (ex: 'Tournoi Principal', 'Moins de 20 ans')
  amount_cents: number;                // Montant payé (en centimes)
  payment_method: string;              // 'HelloAsso', 'Espèces', 'Chèque'
  order_date?: string | null;
  license_number: string;              // N° Licence FFE (ex: 'N12345')
  fide_id: string;                     // Code FIDE international
  elo?: number | null;                 // Classement Elo
  club_name: string;                   // Nom du club d'échecs
  phone: string;
  matched_adherent_id: number;         // > 0 si reconnu comme adhérent du club
  matched_pre_inscription_id: number;  // > 0 si rattaché à une pré-inscription
  matched_contact_id: number;          // > 0 si présent dans l'annuaire contact
  checked_in: boolean;                 // true = Présent / pointé
  checked_in_at?: string | null;       // Date et heure de pointage (ISO ou MySQL)
}

/**
 * Requête d'ajout d'un participant sur place
 */
export interface AddOnSiteParticipantRequest {
  participant_first_name: string;      // Requis
  participant_last_name: string;       // Requis
  participant_email?: string;
  license_number?: string;
  club_name?: string;
  elo?: number;
  tier_name?: string;
  amount?: number;                     // En euros décimaux (ex: 20 ou 15.50)
  payment_method?: 'Espèces' | 'Chèque' | 'Autre';
}
```

---

## 4. Spécification Complète des Endpoints REST

### 4.1. Lister les Campagnes de Billetterie
Récupère toutes les campagnes synchronisées avec leurs compteurs de pointage et ventilation par tarif.

- **Méthode** : `GET`
- **URL** : `/wp-json/dame/v1/ticketing/campaigns`
- **Exemple de Réponse (`200 OK`)** :
```json
[
  {
    "id": 1,
    "form_slug": "open-echecs-printemps-2026",
    "form_type": "Event",
    "title": "Open d'Échecs de Printemps 2026",
    "campaign_category": "tournament",
    "linked_agenda_id": 412,
    "linked_season_id": 2025,
    "start_date": "2026-05-16 09:00:00",
    "end_date": "2026-05-17 18:00:00",
    "last_sync_at": "2026-10-27 12:45:00",
    "total_items": 45,
    "checked_in_count": 38,
    "total_amount_cents": 90000,
    "tiers": {
      "Open A (+1600)": 28,
      "Open B (-1700)": 17
    }
  }
]
```

---

### 4.2. Lister les Inscrits d'une Campagne
Retourne la liste détaillée des participants avec filtrage possible.

- **Méthode** : `GET`
- **URL** : `/wp-json/dame/v1/ticketing/campaigns/{id}/items`
- **Paramètres Query (optionnels)** :
  - `checked_in` : `1` (uniquement les présents) ou `0` (uniquement les absents).
  - `tier` : filtrer par nom exact du tarif (ex: `Open A (+1600)`).

- **Exemple de Réponse (`200 OK`)** :
```json
[
  {
    "id": 102,
    "campaign_id": 1,
    "ha_item_id": 8847123,
    "source": "helloasso",
    "status": "Processed",
    "participant_first_name": "Maxime",
    "participant_last_name": "Vachier",
    "participant_email": "mv@example.com",
    "participant_birth_date": "1990-10-21",
    "payer_first_name": "Maxime",
    "payer_last_name": "Vachier",
    "payer_email": "mv@example.com",
    "tier_name": "Open A (+1600)",
    "amount_cents": 2000,
    "payment_method": "HelloAsso",
    "order_date": "2026-04-10 14:22:10",
    "license_number": "A00001",
    "fide_id": "623539",
    "elo": 2760,
    "club_name": "Clichy Échecs 92",
    "phone": "0601020304",
    "matched_adherent_id": 0,
    "matched_pre_inscription_id": 0,
    "matched_contact_id": 14,
    "checked_in": true,
    "checked_in_at": "2026-05-16 08:45:12"
  },
  {
    "id": 103,
    "campaign_id": 1,
    "ha_item_id": null,
    "source": "onsite",
    "status": "Processed",
    "participant_first_name": "Julien",
    "participant_last_name": "Dupont",
    "participant_email": "julien@club.fr",
    "participant_birth_date": null,
    "payer_first_name": "Julien",
    "payer_last_name": "Dupont",
    "payer_email": "julien@club.fr",
    "tier_name": "Open B (-1700)",
    "amount_cents": 1500,
    "payment_method": "Espèces",
    "order_date": "2026-05-16 08:50:00",
    "license_number": "T12345",
    "fide_id": "",
    "elo": 1420,
    "club_name": "Échiquier du Club",
    "phone": "",
    "matched_adherent_id": 42,
    "matched_pre_inscription_id": 0,
    "matched_contact_id": 0,
    "checked_in": true,
    "checked_in_at": "2026-05-16 08:50:00"
  }
]
```

---

### 4.3. Basculer le Statut de Pointage (Check-in 1-clic)
Pointe un participant comme présent ou réinitialise son pointage.

- **Méthode** : `POST`
- **URL** : `/wp-json/dame/v1/ticketing/items/{id}/checkin`
- **Body JSON** (optionnel) :
```json
{
  "checked_in": true
}
```
*(Si `checked_in` est omis dans le body, le statut s'inverse automatiquement).*

- **Exemple de Réponse (`200 OK`)** :
```json
{
  "success": true,
  "item_id": 102
}
```

---

### 4.4. Ajouter un Participant sur Place
Enregistre une inscription de dernière minute (ex: espèces ou chèque à la table d'accueil).
Le joueur est **automatiquement marqué comme pointé présent** et le service effectue un rapprochement immédiat avec la base d'adhérents / contacts pour enrichir son club, son Elo et son numéro de licence.

- **Méthode** : `POST`
- **URL** : `/wp-json/dame/v1/ticketing/campaigns/{id}/items`
- **Body JSON** :
```json
{
  "participant_first_name": "Sophie",
  "participant_last_name": "Bernard",
  "participant_email": "sophie.bernard@example.org",
  "license_number": "M67890",
  "club_name": "Échecs Club",
  "elo": 1580,
  "tier_name": "Open B (-1700)",
  "amount": 20.00,
  "payment_method": "Espèces"
}
```

- **Exemple de Réponse (`200 OK`)** :
```json
{
  "success": true,
  "item_id": 104
}
```

---

## 5. Recommandations UX & Composants PWA

### 5.1. Écran de Pointage en Temps Réel (Vue Arbitre / Entraîneur)

```
┌────────────────────────────────────────────────────────┐
│ ← Open d'Échecs de Printemps 2026                     │
│ [ 38 / 45 Pointés ]  ████████████████░░░░ 84%         │
├────────────────────────────────────────────────────────┤
│ 🔍 Rechercher joueur, licence, club...                 │
│ [Tous (45)]  [Présents (38)]  [En Attente (7)]         │
│ [Tous les Tournois ▼]                                  │
├────────────────────────────────────────────────────────┤
│ ♟ VACHIER Maxime                          [ ✓ PRÉSENT ] │
│   Licence: A00001 | Elo: 2760                          │
│   Club: Clichy Échecs 92  •  Tarif: Open A             │
│   Badge: [ EXTERNE / LIGUE ]                           │
├────────────────────────────────────────────────────────┤
│ ♟ DUPONT Julien                           [ ✓ PRÉSENT ] │
│   Licence: T12345 | Elo: 1420                          │
│   Club: Notre Club  •  Tarif: Open B                   │
│   Badge: [ ★ ADHÉRENT ]                                │
├────────────────────────────────────────────────────────┤
│ ♟ MARTIN Lucas                            [ POINTER ]  │
│   Licence: B45678 | Elo: 1610                          │
│   Club: Échiquier Voisin  •  Tarif: Open A             │
│   Badge: [ EXTERNE / LIGUE ]                           │
├────────────────────────────────────────────────────────┤
│ [ + Inscription sur Place ]    [ 📥 Export Sharly ]    │
└────────────────────────────────────────────────────────┘
```

#### Éléments recommandés :
- **Badge d'appartenance** :
  - `matched_adherent_id > 0` $\rightarrow$ Vert / Étoile **« ★ Adhérent Club »**.
  - `matched_adherent_id === 0` $\rightarrow$ Bleu neutre **« Externe / Ligue »**.
- **Indicateur de source** :
  - `source === 'helloasso'` $\rightarrow$ Icône carte de crédit / paiement en ligne vérifié.
  - `source === 'onsite'` $\rightarrow$ Icône billet / paiement comptant (espèces ou chèque).
- **Barre de recherche rapide** : Filtrer localement dans le tableau en mémoire sur `participant_last_name`, `participant_first_name`, `license_number` et `club_name` pour un résultat instantané lors de l'annonce du nom par le joueur.

---

## 6. Service API Prêt à l'Emploi (TypeScript avec `js-sdk`)

La PWA disposant déjà du SDK TypeScript dédié dans `js-sdk` (`SimpleJwtLogin`), l'intégration est immédiate et profite directement :
- du stockage persistant sécurisé via `LocalStorageTokenStorage`,
- du rafraîchissement proactif et silencieux du token avant expiration via `getValidJwt()`,
- de la méthode native `fetchWithAuth(url, init)` qui injecte automatiquement le `Authorization: Bearer <token>` et réessaye automatiquement en cas de `401 Unauthorized`.

### Implémentation du Service Billetterie utilisant `SimpleJwtLogin`

```typescript
import { SimpleJwtLogin } from 'simple-jwt-login'; // ou chemin relatif vers ../js-sdk
import type {
  TicketingCampaignDTO,
  TicketingItemDTO,
  AddOnSiteParticipantRequest
} from './types/api/ticketing';

export class TicketingApiService {
  private sdk: SimpleJwtLogin;
  private endpointPrefix: string;

  /**
   * @param sdk Instance configurée de SimpleJwtLogin (avec LocalStorageTokenStorage)
   * @param useWpJson Utiliser '/wp-json' (défaut) ou la query string '/?rest_route='
   */
  constructor(sdk: SimpleJwtLogin, useWpJson = true) {
    this.sdk = sdk;
    this.endpointPrefix = useWpJson ? '/wp-json/dame/v1/ticketing' : '/?rest_route=/dame/v1/ticketing';
  }

  /**
   * Récupère la liste de toutes les campagnes de billetterie
   */
  async getCampaigns(): Promise<TicketingCampaignDTO[]> {
    const url = `${this.endpointPrefix}/campaigns`;
    const res = await this.sdk.fetchWithAuth(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`Erreur récupération campagnes (${res.status})`);
    }

    return res.json();
  }

  /**
   * Récupère les participants d'une campagne avec filtres optionnels
   */
  async getCampaignItems(
    campaignId: number,
    filters?: { checked_in?: boolean; tier?: string }
  ): Promise<TicketingItemDTO[]> {
    const params = new URLSearchParams();
    if (filters?.checked_in !== undefined) {
      params.set('checked_in', filters.checked_in ? '1' : '0');
    }
    if (filters?.tier) {
      params.set('tier', filters.tier);
    }

    const separator = this.endpointPrefix.includes('?') ? '&' : '?';
    const qs = params.toString() ? `${separator}${params.toString()}` : '';
    const url = `${this.endpointPrefix}/campaigns/${campaignId}/items${qs}`;

    const res = await this.sdk.fetchWithAuth(url, {
      method: 'GET',
      headers: { 'Accept': 'application/json' },
    });

    if (!res.ok) {
      throw new Error(`Erreur récupération inscrits (${res.status})`);
    }

    return res.json();
  }

  /**
   * Bascule le statut de pointage (Check-in 1-clic)
   */
  async toggleCheckin(
    itemId: number,
    checkedIn?: boolean
  ): Promise<{ success: boolean; item_id: number }> {
    const url = `${this.endpointPrefix}/items/${itemId}/checkin`;
    const body = checkedIn !== undefined ? JSON.stringify({ checked_in: checkedIn }) : undefined;

    const res = await this.sdk.fetchWithAuth(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body,
    });

    if (!res.ok) {
      throw new Error(`Erreur pointage billet #${itemId} (${res.status})`);
    }

    return res.json();
  }

  /**
   * Enregistre un participant sur place et le pointe automatiquement
   */
  async addOnSiteParticipant(
    campaignId: number,
    data: AddOnSiteParticipantRequest
  ): Promise<{ success: boolean; item_id: number }> {
    const url = `${this.endpointPrefix}/campaigns/${campaignId}/items`;

    const res = await this.sdk.fetchWithAuth(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(data),
    });

    if (!res.ok) {
      throw new Error(`Erreur inscription sur place (${res.status})`);
    }

    return res.json();
  }
}

// ─────────────────────────────────────────────────────────────────────────────
// Exemple d'initialisation dans la PWA :
// ─────────────────────────────────────────────────────────────────────────────
// import { SimpleJwtLogin, LocalStorageTokenStorage } from '../js-sdk';
//
// const authClient = new SimpleJwtLogin('https://monclub.fr', {
//   useWpJson: true,
//   tokenStorage: new LocalStorageTokenStorage('monclub_pwa'),
//   onSessionExpired: (err) => {
//     console.warn('Session expirée, redirection vers le login...', err);
//   },
// });
//
// export const ticketingApi = new TicketingApiService(authClient);
```
```

---

## 7. Gestion Hors-ligne & Recommandations Tournoi

Dans les gymnases ou salles polyvalentes à faible connectivité :
1. **Pré-chargement (Cache)** : À l'ouverture de la campagne, charger la totalité des inscrits dans le store local (Pinia / Redux / Zustand / TanStack Query).
2. **Recherche 100% Locale** : Effectuer les filtres et recherches texte directement dans le tableau JavaScript sans requête serveur.
3. **Queue de Pointage Hors-ligne (Sync Queue)** :
   - Si `navigator.onLine === false`, marquer le joueur localement comme pointé et empiler l'appel `{ itemId, checkedIn: true, timestamp }` dans `IndexedDB`.
   - Écouter l'événement `window.addEventListener('online', ...)` pour dépiler les requêtes de pointage dès que la connexion 4G/Wi-Fi est rétablie.
4. **Export Sharly Chess de Secours** : Rappeler aux arbitres que l'export Sharly Chess reste toujours téléchargeable directement depuis l'administration WordPress (`DAME > Billetterie`) ou intégrable dans la PWA pour générer le fichier `boule_inscrits.csv` d'un simple clic.

