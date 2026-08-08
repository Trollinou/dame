/**
 * Services d'autocomplétion géographique (Communes & Adresses)
 */

export interface GeoCity {
  nom: string;
  codesPostaux: string[];
}

export interface GeoAddressResult {
  fulltext: string;
  zipcode: string;
  city: string;
}

/**
 * Recherche de communes via geo.api.gouv.fr
 */
export async function fetchCitySuggestions(query: string): Promise<string[]> {
  if (query.trim().length < 3) return [];
  try {
    const res = await fetch(`https://geo.api.gouv.fr/communes?fields=nom,codesPostaux&nom=${encodeURIComponent(query)}`);
    if (!res.ok) return [];
    const data: GeoCity[] = await res.json();
    return data.slice(0, 5).map((c) => `${c.nom} (${c.codesPostaux[0] || ''})`);
  } catch (err) {
    console.error('Erreur API Geo Communes:', err);
    return [];
  }
}

/**
 * Autocomplétion d'adresses via data.geopf.fr/geocodage
 */
export async function fetchAddressSuggestions(query: string): Promise<GeoAddressResult[]> {
  if (query.trim().length < 5) return [];
  try {
    const res = await fetch(`https://data.geopf.fr/geocodage/completion?text=${encodeURIComponent(query)}&type=StreetAddress`);
    if (!res.ok) return [];
    const data = await res.json();
    return data.results || [];
  } catch (err) {
    console.error('Erreur API Geo Adresse:', err);
    return [];
  }
}
