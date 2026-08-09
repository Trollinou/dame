/**
 * Utility functions for string manipulation, HTML entity decoding, search filtering, and exercise type labeling.
 */

/**
 * Strips accents and diacritics from a string using Unicode NFD decomposition.
 * Safe against null, undefined, or non-string inputs.
 *
 * @param str Input string
 * @returns String without accents
 */
export function removeAccents(str: string | null | undefined): string {
  if (!str) return '';
  return String(str).normalize('NFD').replace(/[\u0300-\u036f]/g, '');
}

/**
 * Checks if target string contains query string, case-insensitively and accent-insensitively.
 *
 * @param target Target string to search within
 * @param query Query string to search for
 * @returns True if target contains query, false otherwise
 */
export function includesNormalized(
  target: string | null | undefined,
  query: string | null | undefined
): boolean {
  if (!query) return true;
  if (!target) return false;

  const normalizedTarget = removeAccents(target).toLowerCase();
  const normalizedQuery = removeAccents(query).toLowerCase().trim();

  return normalizedTarget.includes(normalizedQuery);
}

/**
 * Decodes HTML entities (e.g. &#8211;, &rsquo;, &amp;, &quot;, &#039;) into plain text characters.
 * Safe against null, undefined, or empty inputs.
 *
 * @param str Input string containing HTML entities
 * @returns Decoded plain text string
 */
export function decodeHtmlEntities(str: string | null | undefined): string {
  if (!str) return '';

  let text = String(str);

  // Fast decoding via DOMParser in browser environment if entities exist
  if (typeof DOMParser !== 'undefined' && text.includes('&')) {
    try {
      const doc = new DOMParser().parseFromString(text, 'text/html');
      return doc.documentElement.textContent || text;
    } catch {
      // Fallback if DOMParser is unavailable or fails
    }
  }

  // Fallback regex replacement for common HTML entities
  if (text.includes('&')) {
    text = text
      .replace(/&#(\d+);/g, (_, dec) => String.fromCharCode(Number(dec)))
      .replace(/&#x([0-9a-f]+);/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
      .replace(/&rsquo;/g, '’')
      .replace(/&lsquo;/g, '‘')
      .replace(/&rdquo;/g, '”')
      .replace(/&ldquo;/g, '“')
      .replace(/&ndash;/g, '–')
      .replace(/&mdash;/g, '—')
      .replace(/&amp;/g, '&')
      .replace(/&lt;/g, '<')
      .replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"')
      .replace(/&#039;/g, "'");
  }

  return text;
}

/**
 * Map of exercise type IDs to their human-readable type labels.
 */
export const EXERCICE_TYPES_MAP: Record<number, string> = {
  1: '100 Commandements',
  2: 'Pop-échecs',
  3: 'ABCDaire',
  4: 'Partie des Héros',
  5: 'Posi-Plan',
  6: 'Associ-Plan',
  7: 'La Marche du Héros',
  8: "Vision'checs",
  9: 'Parcours',
  10: 'Échec & Éval',
  11: "Class'échecs",
  12: 'Qui suis-je ?',
  13: 'Ouvre-Boîte',
  14: 'Cap ou pas Cap ?',
  15: 'Jugement final',
  16: 'Destination finale'
};

/**
 * Returns the human-readable type label for a playlist item or content (e.g. "Leçon", "100 Commandements", "Cap ou pas Cap ?").
 *
 * @param item Playlist item or content object
 * @returns Human-readable type label string
 */
export function getContenuTypeLabel(item: {
  type?: string;
  exercice_type?: number;
  type_exercice?: number;
  titre?: string;
} | null | undefined): string {
  if (!item) return '';

  // 1. Check if it's a lesson
  if (item.type === 'roi_lecon') {
    return 'Leçon';
  }

  // 2. Check if explicit exercise type number is available
  const typeNum = item.exercice_type ?? item.type_exercice;
  if (typeNum && EXERCICE_TYPES_MAP[typeNum]) {
    return EXERCICE_TYPES_MAP[typeNum];
  }

  // 3. Extract type number or name from title (e.g., "T1 – 100 Commandements", "T8 – Vision'checs", "T14 – Cap ou pas Cap ?")
  const decodedTitle = decodeHtmlEntities(item.titre);
  if (decodedTitle) {
    const match = decodedTitle.match(/^T(\d+)\s*[\u2013\u2014\-–]\s*(.+)$/i);
    if (match) {
      const num = parseInt(match[1], 10);
      if (EXERCICE_TYPES_MAP[num]) {
        return EXERCICE_TYPES_MAP[num];
      }
      return match[2].trim();
    }
  }

  return 'Exercice';
}
